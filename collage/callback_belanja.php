<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_log('=== Tripay Callback Belanja Received ===');

// Database Config
$servername = "localhost";
$username = "ypikhair_admin";
$password = "hakim123123123";
$dbname = "ypikhair_datautama";

// Tripay Config
define('TRIPAY_PRIVATE_KEY', 'peyOY-QK9Bw-dTcOF-ISsZV-kHZvx');

// Get callback data
$json = file_get_contents('php://input');
$callback_data = json_decode($json, true);

error_log("Callback data: " . print_r($callback_data, true));

// Validate signature
$callback_signature = $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] ?? '';
$signature = hash_hmac('sha256', $json, TRIPAY_PRIVATE_KEY);

if ($callback_signature !== $signature) {
    http_response_code(400);
    error_log("Invalid signature");
    exit('Invalid signature');
}

// Connect to database
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Find pesanan by order_id
    $order_id = $callback_data['merchant_ref'];
    $status = $callback_data['status'] ?? '';
    
    $stmt = $conn->prepare("SELECT * FROM pesanan_belanja WHERE order_id = ? LIMIT 1");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pesanan = $result->fetch_assoc();
    $stmt->close();
    
    if (!$pesanan) {
        error_log("Pesanan not found for order_id: $order_id");
        http_response_code(404);
        exit('Pesanan not found');
    }
    
    // Map Tripay status
    $new_status = 'pending';
    if ($status === 'PAID') {
        $new_status = 'berhasil';
    } elseif (in_array($status, ['EXPIRED', 'FAILED', 'REFUND'])) {
        $new_status = 'gagal';
    }
    
    error_log("Updating pesanan {$pesanan['id']} from {$pesanan['status']} to $new_status");
    
    // Update pesanan status
    $stmt = $conn->prepare("UPDATE pesanan_belanja SET status = ?, updated_at = NOW() WHERE order_id = ?");
    $stmt->bind_param("ss", $new_status, $order_id);
    
    if ($stmt->execute()) {
        error_log("Pesanan status updated successfully");
        
        // If successful, create voucher (jika belum ada)
        if ($new_status === 'berhasil') {
            // Cek apakah voucher sudah ada
            $stmt_check = $conn->prepare("SELECT id FROM voucher_pembayaran WHERE pesanan_id = ?");
            $stmt_check->bind_param("i", $pesanan['id']);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $existing_voucher = $result_check->fetch_assoc();
            $stmt_check->close();
            
            if (!$existing_voucher) {
                // Generate voucher code
                $voucher_code = 'VCH-' . strtoupper(substr(md5($order_id . time() . rand()), 0, 10));
                
                // Create voucher
                $stmt2 = $conn->prepare("
                    INSERT INTO voucher_pembayaran (student_id, pesanan_id, voucher_code, status) 
                    VALUES (?, ?, ?, 'pending')
                ");
                $stmt2->bind_param("iis", $pesanan['student_id'], $pesanan['id'], $voucher_code);
                $stmt2->execute();
                $stmt2->close();
                
                error_log("Voucher created: $voucher_code for student {$pesanan['student_id']}, order: $order_id");
            } else {
                error_log("Voucher already exists for order: $order_id");
            }
        }
        
        http_response_code(200);
        echo json_encode(['success' => true]);
    } else {
        error_log("Failed to update pesanan: " . $stmt->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log("Callback error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

