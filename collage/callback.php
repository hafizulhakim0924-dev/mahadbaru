<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Matikan di production
ini_set('log_errors', 1);
error_log('=== Tripay Callback Received ===');

// Database Config (sama seperti file utama)
$servername = "localhost";
$username = "ypikhair_admin";
$password = "hakim123123123";
$dbname = "ypikhair_datautama";

// Tripay Config
define('TRIPAY_PRIVATE_KEY', 'RlGRM-dPVm0-4gxYN-AakNR-pI3Li');

// Validasi IP Tripay (whitelist)
$valid_ips = [
    '103.234.254.162', // IP Tripay
    '103.234.255.50',
    '103.234.255.51'
];

$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
error_log("Callback from IP: $client_ip");

// Uncomment ini di production untuk keamanan:
// if (!in_array($client_ip, $valid_ips)) {
//     http_response_code(403);
//     error_log("Unauthorized IP: $client_ip");
//     exit('Unauthorized');
// }

// Get callback data
$json = file_get_contents('php://input');
$callback_data = json_decode($json, true);

error_log("Callback data: " . print_r($callback_data, true));

// Validasi signature
$callback_signature = $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] ?? '';
$merchant_ref = $callback_data['merchant_ref'] ?? '';
$status = $callback_data['status'] ?? '';

$signature = hash_hmac('sha256', $json, TRIPAY_PRIVATE_KEY);

if ($callback_signature !== $signature) {
    http_response_code(400);
    error_log("Invalid signature. Expected: $signature, Got: $callback_signature");
    exit('Invalid signature');
}

// Connect to database
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Find payment by order_id - cek di payments (tagihan/SPP) atau pesanan_belanja (belanja)
    $order_id = $callback_data['merchant_ref'];
    
    // Map Tripay status ke status lokal
    $new_status = 'pending';
    switch ($status) {
        case 'PAID':
            $new_status = 'berhasil';
            break;
        case 'EXPIRED':
        case 'FAILED':
        case 'REFUND':
            $new_status = 'gagal';
            break;
    }
    
    // Cek apakah ini pembayaran tagihan/SPP (order_id dimulai dengan 'ORD-')
    $stmt = $conn->prepare("SELECT * FROM payments WHERE order_id = ? LIMIT 1");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();
    $stmt->close();
    
    if ($payment) {
        // Ini adalah pembayaran tagihan/SPP
        error_log("Processing payment (tagihan/SPP) for order_id: $order_id");
        error_log("Updating payment {$payment['payment_id']} from {$payment['status']} to $new_status");
        
        // Update payment status
        $stmt = $conn->prepare("UPDATE payments SET status = ?, updated_at = NOW() WHERE payment_id = ?");
        $stmt->bind_param("ss", $new_status, $payment['payment_id']);
        
        if ($stmt->execute()) {
            error_log("Payment status updated successfully");
            
            // Jika berhasil, hapus tagihan dari tabel tagihan
            if ($new_status === 'berhasil') {
                $tagihan_detail = json_decode($payment['tagihan_detail'], true);
                
                if (is_array($tagihan_detail)) {
                    foreach ($tagihan_detail as $nama_tagihan => $jumlah) {
                        // Hapus atau set jumlah = 0
                        $stmt2 = $conn->prepare("DELETE FROM tagihan WHERE student_id = ? AND nama_tagihan = ?");
                        $stmt2->bind_param("is", $payment['student_id'], $nama_tagihan);
                        $stmt2->execute();
                        $stmt2->close();
                        
                        error_log("Deleted bill: $nama_tagihan for student {$payment['student_id']}");
                    }
                }
            }
            
            $stmt->close();
            http_response_code(200);
            echo json_encode(['success' => true, 'type' => 'payment']);
        } else {
            error_log("Failed to update payment: " . $stmt->error);
            $stmt->close();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    } else {
        // Cek apakah ini pembayaran belanja (order_id dimulai dengan 'BLJ-')
        $stmt = $conn->prepare("SELECT * FROM pesanan_belanja WHERE order_id = ? LIMIT 1");
        $stmt->bind_param("s", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $pesanan = $result->fetch_assoc();
        $stmt->close();
        
        if ($pesanan) {
            // Ini adalah pembayaran belanja
            error_log("Processing pesanan belanja for order_id: $order_id");
            error_log("Updating pesanan {$pesanan['id']} from {$pesanan['status']} to $new_status");
            
            // Update pesanan status
            $stmt = $conn->prepare("UPDATE pesanan_belanja SET status = ?, updated_at = NOW() WHERE order_id = ?");
            $stmt->bind_param("ss", $new_status, $order_id);
            
            if ($stmt->execute()) {
                error_log("Pesanan status updated successfully");
                
                // Jika berhasil, create voucher (jika belum ada)
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
                
                $stmt->close();
                http_response_code(200);
                echo json_encode(['success' => true, 'type' => 'belanja']);
            } else {
                error_log("Failed to update pesanan: " . $stmt->error);
                $stmt->close();
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        } else {
            // Tidak ditemukan di kedua tabel
            error_log("Payment/Pesanan not found for order_id: $order_id");
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Payment/Pesanan not found']);
        }
    }
    $conn->close();
    
} catch (Exception $e) {
    error_log("Callback error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>