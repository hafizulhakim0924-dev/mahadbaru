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
define('TRIPAY_PRIVATE_KEY', 'HOZre-hSczD-GQ4IX-hUagv-6n2ga');

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
    
    // Find payment by order_id
    $order_id = $callback_data['merchant_ref'];
    $stmt = $conn->prepare("SELECT * FROM payments WHERE order_id = ? LIMIT 1");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();
    $stmt->close();
    
    if (!$payment) {
        error_log("Payment not found for order_id: $order_id");
        http_response_code(404);
        exit('Payment not found');
    }
    
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
        
        http_response_code(200);
        echo json_encode(['success' => true]);
    } else {
        error_log("Failed to update payment: " . $stmt->error);
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