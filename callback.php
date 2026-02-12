<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Matikan di production
ini_set('log_errors', 1);
error_log('=== Tripay Callback Received ===');

// Database Config (sama seperti file utama)
$servername = "localhost";
$username = "ypikhair_adminmahadzubair";
$password = "Hakim123!";
$dbname = "ypikhair_mahadzubair";

// Tripay Config
define('TRIPAY_PRIVATE_KEY', 'peyOY-QK9Bw-dTcOF-ISsZV-kHZvx');

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
                $tagihan_detail = json_decode($payment['tagihan_detail'] ?? '{}', true);
                
                if (is_array($tagihan_detail) && !empty($tagihan_detail)) {
                    foreach ($tagihan_detail as $nama_tagihan => $jumlah) {
                        // Hapus tagihan yang sudah dibayar
                        $stmt2 = $conn->prepare("DELETE FROM tagihan WHERE student_id = ? AND nama_tagihan = ?");
                        if ($stmt2) {
                            $stmt2->bind_param("is", $payment['student_id'], $nama_tagihan);
                            if ($stmt2->execute()) {
                                error_log("Deleted bill: $nama_tagihan for student {$payment['student_id']}");
                            } else {
                                error_log("Failed to delete bill: $nama_tagihan - " . $stmt2->error);
                            }
                            $stmt2->close();
                        } else {
                            error_log("Failed to prepare delete bill query: " . $conn->error);
                        }
                    }
                } else {
                    // Jika tagihan_detail kosong, coba hapus berdasarkan tagihan string
                    $tagihan_str = $payment['tagihan'] ?? '';
                    if (!empty($tagihan_str)) {
                        $tagihan_list = explode(', ', $tagihan_str);
                        foreach ($tagihan_list as $nama_tagihan) {
                            $nama_tagihan = trim($nama_tagihan);
                            if (!empty($nama_tagihan)) {
                                $stmt2 = $conn->prepare("DELETE FROM tagihan WHERE student_id = ? AND nama_tagihan = ?");
                                if ($stmt2) {
                                    $stmt2->bind_param("is", $payment['student_id'], $nama_tagihan);
                                    if ($stmt2->execute()) {
                                        error_log("Deleted bill: $nama_tagihan for student {$payment['student_id']}");
                                    }
                                    $stmt2->close();
                                }
                            }
                        }
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
                    error_log("Payment successful, attempting to create voucher for order: $order_id, pesanan_id: {$pesanan['id']}, student_id: {$pesanan['student_id']}");
                    
                    try {
                        // Cek apakah voucher sudah ada
                        $stmt_check = $conn->prepare("SELECT id FROM voucher_pembayaran WHERE pesanan_id = ?");
                        if (!$stmt_check) {
                            error_log("Failed to prepare voucher check query: " . $conn->error);
                        } else {
                            $stmt_check->bind_param("i", $pesanan['id']);
                            if (!$stmt_check->execute()) {
                                error_log("Failed to execute voucher check query: " . $stmt_check->error);
                            } else {
                                $result_check = $stmt_check->get_result();
                                $existing_voucher = $result_check->fetch_assoc();
                                $stmt_check->close();
                                
                                if (!$existing_voucher) {
                                    // Generate voucher code
                                    $voucher_code = 'VCH-' . strtoupper(substr(md5($order_id . time() . rand()), 0, 10));
                                    
                                    error_log("Creating new voucher: $voucher_code for pesanan_id: {$pesanan['id']}, student_id: {$pesanan['student_id']}");
                                    
                                    // Create voucher
                                    $stmt2 = $conn->prepare("
                                        INSERT INTO voucher_pembayaran (student_id, pesanan_id, voucher_code, status) 
                                        VALUES (?, ?, ?, 'pending')
                                    ");
                                    if (!$stmt2) {
                                        error_log("Failed to prepare voucher insert query: " . $conn->error);
                                    } else {
                                        $stmt2->bind_param("iis", $pesanan['student_id'], $pesanan['id'], $voucher_code);
                                        if ($stmt2->execute()) {
                                            $voucher_id = $stmt2->insert_id;
                                            error_log("✅ Voucher created successfully! ID: $voucher_id, Code: $voucher_code, Student: {$pesanan['student_id']}, Order: $order_id, Pesanan ID: {$pesanan['id']}");
                                        } else {
                                            error_log("❌ Failed to create voucher (execute): " . $stmt2->error);
                                        }
                                        $stmt2->close();
                                    }
                                } else {
                                    error_log("ℹ️ Voucher already exists for order: $order_id, pesanan_id: {$pesanan['id']}, voucher_id: {$existing_voucher['id']}");
                                }
                            }
                        }
                    } catch (Exception $e) {
                        error_log("❌ Exception creating voucher: " . $e->getMessage());
                        error_log("Stack trace: " . $e->getTraceAsString());
                    }
                } else {
                    error_log("Payment status is not 'berhasil' (status: $new_status), skipping voucher creation");
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