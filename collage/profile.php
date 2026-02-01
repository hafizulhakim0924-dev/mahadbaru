<?php
session_start();
// Session security
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Session timeout (30 menit)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: login_siswa.php?timeout=1');
    exit;
}
$_SESSION['last_activity'] = time();

date_default_timezone_set('Asia/Jakarta');
// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database Config
$servername = "localhost";
$username = "ypikhair_admin";
$password = "hakim123123123";
$dbname = "ypikhair_datautama";
// =====================
// LOGIN CHECK UNIVERSAL
// =====================
if (!isset($_SESSION['student']['id']) && !isset($_SESSION['user_id'])) {
    header('Location: login_siswa.php');
    exit;
}

// Gunakan session yang tersedia
$student_id = isset($_SESSION['student']['id'])
    ? intval($_SESSION['student']['id'])
    : intval($_SESSION['user_id']);

// Tripay Config
define('TRIPAY_API_KEY', 'ytprKupP1zxpZg6XeFBkpe6oJjrT7jaae1zROemR');
define('TRIPAY_PRIVATE_KEY', 'RlGRM-dPVm0-4gxYN-AakNR-pI3Li');
define('TRIPAY_MERCHANT_CODE', 'T47806');
define('TRIPAY_API_URL', 'https://tripay.co.id/api');

// Payment Config
define('PAYMENT_EXPIRY_HOURS', 1);

// Create database connection
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log('DB Connection Error [' . date('Y-m-d H:i:s') . ']: ' . $e->getMessage());
    die('<!DOCTYPE html><html><head><title>Error</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h2>Sistem Sedang Dalam Perbaikan</h2><p>Silakan coba beberapa saat lagi atau hubungi administrator.</p></body></html>');
}

// =====================
// FIX LOGIN CHECK
// =====================
if (!isset($_SESSION['student']['id']) && !isset($_SESSION['user_id'])) {
    header('Location: login_siswa.php');
    exit;
}

// Ambil user id
$student_id = isset($_SESSION['student']['id'])
            ? intval($_SESSION['student']['id'])
            : intval($_SESSION['user_id']);


$student_id = isset($_SESSION['student']['id'])
    ? intval($_SESSION['student']['id'])
    : intval($_SESSION['user_id']);


// Helper functions
function httpRequest($url, $headers = [], $post = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        error_log('CURL Error: ' . $curl_error);
        return [
            'code' => 0,
            'data' => ['success' => false, 'message' => 'CURL Error: ' . $curl_error]
        ];
    }
    
    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON Decode Error: ' . json_last_error_msg());
        error_log('Response: ' . substr($response, 0, 500));
    }
    
    return [
        'code' => $httpCode,
        'data' => $decoded
    ];
}

function createTripayPayment($amount, $order_id, $customer_name, $customer_email, $method) {
    $privateKey = TRIPAY_PRIVATE_KEY;
    $merchantCode = TRIPAY_MERCHANT_CODE;
    
    // Signature untuk create transaction: merchant_code + merchant_ref + amount
    $signature = hash_hmac('sha256', $merchantCode . $order_id . $amount, $privateKey);
    
    $data = [
        'method' => $method,
        'merchant_ref' => $order_id,
        'amount' => intval($amount), // Pastikan integer
        'customer_name' => $customer_name,
        'customer_email' => $customer_email,
        'order_items' => [
            [
                'sku' => 'BILL01',
                'name' => 'Pembayaran Tagihan Sekolah',
                'price' => intval($amount),
                'quantity' => 1
            ]
        ],
        'return_url' => 'https://ypi-khairaummah.sch.id/profile.php?tab=pending',
        'expired_time' => (time() + (PAYMENT_EXPIRY_HOURS * 3600)),
        'signature' => $signature,
        'callback_url' => 'https://ypi-khairaummah.sch.id/callback.php'
    ];
    
    $headers = [
        'Authorization: Bearer ' . TRIPAY_API_KEY,
        'Content-Type: application/json'
    ];
    
    error_log('Tripay Request: ' . json_encode($data));
    $result = httpRequest(TRIPAY_API_URL . '/transaction/create', $headers, $data);
    error_log('Tripay Response: ' . json_encode($result));
    
    return $result;
}

// Get student data from database
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    session_destroy();
    header('Location: login_siswa.php?error=invalid');
    exit;
}

// Get student bills (tagihan)
$stmt = $conn->prepare("SELECT * FROM tagihan WHERE student_id = ? ORDER BY id ASC");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$student_bills = [];
while ($row = $result->fetch_assoc()) {
    $student_bills[$row['nama_tagihan']] = floatval($row['jumlah']);
}
$stmt->close();

// Get tagihan history from tagihan_history table
function getTagihanHistory($conn, $student_id) {
    $stmt = $conn->prepare("SELECT * FROM tagihan_history WHERE student_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    $stmt->close();
    
    return $history;
}

$tagihan_history = getTagihanHistory($conn, $student_id);

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login_siswa.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']);
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    try {
        if ($action === 'create_payment') {
            $selected = $_POST['tagihan'] ?? [];
            $method = $_POST['method'] ?? '';
            
            // Validasi input
            if (empty($selected) || !is_array($selected)) {
                throw new Exception('Pilih minimal satu tagihan');
            }
            
            if (empty($method) || !preg_match('/^[A-Z0-9]+$/', $method)) {
                throw new Exception('Metode pembayaran tidak valid');
            }
            
            // START TRANSACTION
            $conn->begin_transaction();
            
            try {
                // Lock student record untuk prevent race condition
                $stmt = $conn->prepare("SELECT id FROM students WHERE id = ? FOR UPDATE");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $stmt->close();
                
                // Auto-cancel semua pending payment yang lama (baik expired maupun belum)
                $stmt = $conn->prepare("UPDATE payments 
                                       SET status = 'cancelled' 
                                       WHERE student_id = ? 
                                       AND status = 'pending'");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $stmt->close();
                
                // Calculate total dengan validasi ketat
                $total = 0;
                $bills = [];
                
                foreach ($selected as $bill) {
                    $bill = filter_var($bill, FILTER_SANITIZE_STRING);
                    
                    if (!isset($student_bills[$bill])) {
                        throw new Exception('Tagihan tidak valid: ' . htmlspecialchars($bill));
                    }
                    
                    $bill_amount = floatval($student_bills[$bill]);
                    
                    if ($bill_amount <= 0) {
                        throw new Exception('Jumlah tagihan tidak valid');
                    }
                    
                    $total += $bill_amount;
                    $bills[$bill] = $bill_amount;
                }
                
                if ($total <= 0 || $total > 100000000) { // Max 100 juta
                    throw new Exception('Total pembayaran tidak valid');
                }
                
                // Generate IDs
                $payment_id = uniqid('pay_');
                $order_id = 'ORD-' . $student_id . '-' . date('YmdHis') . '-' . rand(1000, 9999);
                
                // Create Tripay payment
                $customer_email = !empty($student['phone_no']) ? $student['phone_no'] . '@example.com' : 'student' . $student_id . '@example.com';
                
                $tripay_response = createTripayPayment(
                    $total,
                    $order_id,
                    $student['name'],
                    $customer_email,
                    $method
                );
                
                if ($tripay_response['code'] != 200) {
                    $error_msg = 'HTTP Error: ' . $tripay_response['code'];
                    if (isset($tripay_response['data']['message'])) {
                        $error_msg = $tripay_response['data']['message'];
                    }
                    throw new Exception($error_msg);
                }
                
                if (!isset($tripay_response['data']['success']) || !$tripay_response['data']['success']) {
                    $error_msg = $tripay_response['data']['message'] ?? 'Koneksi ke payment gateway gagal';
                    throw new Exception($error_msg);
                }
                
                $tripay_data = $tripay_response['data']['data'];
                
                // Prepare data dengan JSON validation
                $tagihan_str = implode(', ', array_keys($bills));
                $tagihan_detail_json = json_encode($bills, JSON_UNESCAPED_UNICODE);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Gagal memproses data tagihan');
                }
                
                $method_name = filter_var($tripay_data['payment_name'] ?? $method, FILTER_SANITIZE_STRING);
                $status = 'pending';
                $expired_at = date('Y-m-d H:i:s', intval($tripay_data['expired_time']));
                $tripay_ref = filter_var($tripay_data['reference'], FILTER_SANITIZE_STRING);
                $pay_code = filter_var($tripay_data['pay_code'] ?? '', FILTER_SANITIZE_STRING);
                $pay_url = filter_var($tripay_data['checkout_url'] ?? '', FILTER_SANITIZE_URL);
                
                // QR String handling
                $qr_string = '';
                if (!empty($tripay_data['qr_string'])) {
                    $qr_string = $tripay_data['qr_string'];
                } elseif (!empty($tripay_data['qr_url'])) {
                    $qr_string = $tripay_data['qr_url'];
                } elseif (stripos($method, 'QRIS') !== false && !empty($pay_code)) {
                    $qr_string = $pay_code;
                }
                
                $instructions_json = json_encode($tripay_data['instructions'] ?? [], JSON_UNESCAPED_UNICODE);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Gagal memproses instruksi pembayaran');
                }
                
                // Insert dengan prepared statement
                $stmt = $conn->prepare("INSERT INTO payments 
                    (payment_id, student_id, name, class, tagihan, tagihan_detail, nominal, 
                     method_code, method_name, status, waktu_input, expired_at, order_id, 
                     tripay_ref, pay_code, pay_url, qr_string, instructions) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->bind_param("sissssdssssssssss", 
                    $payment_id,
                    $student_id,
                    $student['name'],
                    $student['class'],
                    $tagihan_str,
                    $tagihan_detail_json,
                    $total,
                    $method,
                    $method_name,
                    $status,
                    $expired_at,
                    $order_id,
                    $tripay_ref,
                    $pay_code,
                    $pay_url,
                    $qr_string,
                    $instructions_json
                );
                
                if (!$stmt->execute()) {
                    throw new Exception('Gagal menyimpan pembayaran');
                }
                
                $stmt->close();
                
                // COMMIT TRANSACTION
                $conn->commit();
                
                // Prepare response
                $payment_record = [
                    'payment_id' => $payment_id,
                    'student_id' => $student_id,
                    'name' => $student['name'],
                    'class' => $student['class'],
                    'tagihan' => $tagihan_str,
                    'tagihan_detail' => $bills,
                    'nominal' => $total,
                    'method_code' => $method,
                    'method_name' => $method_name,
                    'status' => 'pending',
                    'waktu_input' => date('Y-m-d H:i:s'),
                    'expired_at' => $expired_at,
                    'order_id' => $order_id,
                    'tripay_ref' => $tripay_ref,
                    'pay_code' => $pay_code,
                    'pay_url' => $pay_url,
                    'qr_string' => $qr_string,
                    'instructions' => $tripay_data['instructions'] ?? []
                ];
                
                $response = [
                    'success' => true,
                    'message' => 'Pembayaran berhasil dibuat',
                    'payment_data' => $payment_record
                ];
                
            } catch (Exception $e) {
                // ROLLBACK jika error
                $conn->rollback();
                
                error_log('Payment creation failed for student ' . $student_id . ': ' . $e->getMessage());
                throw $e;
            }

        } elseif ($action === 'check_payment') {
            $payment_id = filter_var($_POST['payment_id'] ?? '', FILTER_SANITIZE_STRING);
            
            if (empty($payment_id)) {
                throw new Exception('Payment ID tidak valid');
            }
            
            // Get payment data
            $stmt = $conn->prepare("SELECT tripay_ref, status FROM payments 
                                   WHERE payment_id = ? AND student_id = ?");
            $stmt->bind_param("si", $payment_id, $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $payment = $result->fetch_assoc();
            $stmt->close();
            
            if (!$payment) {
                throw new Exception('Pembayaran tidak ditemukan');
            }
            
            // Jika sudah berhasil, tidak perlu cek lagi
            if ($payment['status'] === 'berhasil') {
                $response = [
                    'success' => true,
                    'status' => 'berhasil',
                    'message' => 'Pembayaran sudah berhasil'
                ];
            } else {
                // Call Tripay API untuk cek status transaksi
                $headers = [
                    'Authorization: Bearer ' . TRIPAY_API_KEY,
                    'Content-Type: application/json'
                ];
                
                $tripay_ref = $payment['tripay_ref'];
                $check_response = httpRequest(
                    TRIPAY_API_URL . '/transaction/detail?reference=' . $tripay_ref, 
                    $headers
                );
                
                if ($check_response['code'] == 200 && 
                    isset($check_response['data']['success']) &&
                    $check_response['data']['success'] &&
                    isset($check_response['data']['data']['status'])) {
                    
                    $tripay_status = $check_response['data']['data']['status'];
                    
                    // Map Tripay status ke status lokal
                    if ($tripay_status === 'PAID') {
                        // Update status di database
                        $stmt = $conn->prepare("UPDATE payments 
                                               SET status = 'berhasil' 
                                               WHERE payment_id = ?");
                        $stmt->bind_param("s", $payment_id);
                        $stmt->execute();
                        $stmt->close();
                        
                        $response = [
                            'success' => true,
                            'status' => 'berhasil',
                            'message' => 'Pembayaran berhasil! Halaman akan dimuat ulang.'
                        ];
                    } elseif ($tripay_status === 'UNPAID') {
                        $response = [
                            'success' => true,
                            'status' => 'pending',
                            'message' => 'Pembayaran masih menunggu'
                        ];
                    } elseif ($tripay_status === 'EXPIRED') {
                        // Update status expired
                        $stmt = $conn->prepare("UPDATE payments 
                                               SET status = 'expired' 
                                               WHERE payment_id = ?");
                        $stmt->bind_param("s", $payment_id);
                        $stmt->execute();
                        $stmt->close();
                        
                        $response = [
                            'success' => true,
                            'status' => 'expired',
                            'message' => 'Pembayaran sudah kadaluarsa'
                        ];
                    } elseif ($tripay_status === 'FAILED') {
                        // Update status failed
                        $stmt = $conn->prepare("UPDATE payments 
                                               SET status = 'gagal' 
                                               WHERE payment_id = ?");
                        $stmt->bind_param("s", $payment_id);
                        $stmt->execute();
                        $stmt->close();
                        
                        $response = [
                            'success' => true,
                            'status' => 'gagal',
                            'message' => 'Pembayaran gagal'
                        ];
                    } else {
                        $response = [
                            'success' => true,
                            'status' => 'pending',
                            'message' => 'Status: ' . $tripay_status
                        ];
                    }
                } else {
                    throw new Exception('Gagal mengecek status dari payment gateway');
                }
            }
            
        } elseif ($action === 'get_receipt') {
            $payment_id = filter_var($_POST['payment_id'] ?? '', FILTER_SANITIZE_STRING);
            
            if (empty($payment_id)) {
                throw new Exception('Payment ID tidak valid');
            }
            
            error_log('Getting receipt for payment_id: ' . $payment_id); // Debug log
            
            $payment = null;
            
            // Check source from payment_id prefix
            if (strpos($payment_id, 'MANUAL-') === 0) {
                // From pembayaran table
                $id = str_replace('MANUAL-', '', $payment_id);
                
                // Validasi ID adalah integer
                if (!filter_var($id, FILTER_VALIDATE_INT)) {
                    throw new Exception('Invalid payment ID format');
                }
                
                $id = intval($id);
                $stmt = $conn->prepare("SELECT p.*, s.name, s.class FROM pembayaran p 
                                        LEFT JOIN students s ON p.student_id = s.id 
                                        WHERE p.id = ? AND p.student_id = ?");
                $stmt->bind_param("ii", $id, $student_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                
                if ($row) {
                    $payment = [
                        'payment_id' => $payment_id,
                        'name' => $row['name'] ?? $student['name'],
                        'class' => $row['class'] ?? $student['class'],
                        'tagihan' => $row['nama_tagihan'],
                        'nominal' => $row['jumlah_bayar'],
                        'method_name' => 'Tunai/Transfer Manual',
                        'waktu_input' => $row['tanggal'],
                        'source' => 'manual'
                    ];
                }
                
            } elseif (strpos($payment_id, 'JSON-') === 0) {
                // From payments.json
                $possible_paths = [
                    $_SERVER['DOCUMENT_ROOT'] . '/payments.json',
                    $_SERVER['DOCUMENT_ROOT'] . '/public_html/payments.json',
                    '/home/ypikhair/public_html/payments.json',
                    dirname(__FILE__) . '/payments.json'
                ];
                
                $json_file = null;
                foreach ($possible_paths as $path) {
                    if (file_exists($path) && is_readable($path)) {
                        $json_file = $path;
                        break;
                    }
                }
                
                if ($json_file) {
                    $json_content = @file_get_contents($json_file);
                    if ($json_content !== false) {
                        $json_data = json_decode($json_content, true);
                        
                        if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
                            foreach ($json_data as $record) {
                                if ('JSON-' . ($record['id'] ?? '') === $payment_id && 
                                    (($record['student_id'] ?? '') == $student_id || 
                                     strcasecmp($record['name'] ?? '', $student['name']) === 0)) {
                                    $payment = [
                                        'payment_id' => $payment_id,
                                        'name' => $record['name'] ?? $student['name'],
                                        'class' => $record['class'] ?? $student['class'],
                                        'tagihan' => $record['tagihan'] ?? 'Pembayaran',
                                        'nominal' => $record['nominal'] ?? 0,
                                        'method_name' => $record['bank_tujuan'] ?? 'Manual Input',
                                        'waktu_input' => $record['waktu_input'] ?? date('Y-m-d H:i:s'),
                                        'source' => 'json'
                                    ];
                                    break;
                                }
                            }
                        }
                    }
                }
                
            } else {
                // From payments table (Tripay)
                $stmt = $conn->prepare("SELECT * FROM payments WHERE payment_id = ? AND student_id = ?");
                $stmt->bind_param("si", $payment_id, $student_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                
                if ($row) {
                    $payment = [
                        'payment_id' => $row['payment_id'],
                        'name' => $row['name'],
                        'class' => $row['class'],
                        'tagihan' => $row['tagihan'],
                        'nominal' => $row['nominal'],
                        'method_name' => $row['method_name'],
                        'waktu_input' => $row['waktu_input'],
                        'source' => 'tripay',
                        'status' => $row['status']
                    ];
                }
            }
            
            error_log('Payment data found: ' . ($payment ? 'YES' : 'NO')); // Debug log
            
            if ($payment) {
                $receipt_html = generateReceiptHTML($payment, $student);
                $response = [
                    'success' => true,
                    'receipt_html' => $receipt_html
                ];
            } else {
                throw new Exception('Bukti pembayaran tidak ditemukan');
            }
        }
    } catch (Exception $e) {
        error_log('Payment processing error for student ' . $student_id . ': ' . $e->getMessage());
        $response['message'] = $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

function generateReceiptHTML($payment, $student) {
    // Pastikan data valid
    if (!isset($payment['waktu_input']) || empty($payment['waktu_input'])) {
        $payment['waktu_input'] = date('Y-m-d H:i:s');
    }
    
    // Format tanggal pembayaran
    $timestamp = strtotime($payment['waktu_input']);
    if ($timestamp === false) {
        $timestamp = time();
    }
    
    $tanggal_bayar = date('d F Y, H:i', $timestamp);
    $bulan_indo = [
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
        'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
        'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
        'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    ];
    $tanggal_bayar = str_replace(array_keys($bulan_indo), array_values($bulan_indo), $tanggal_bayar);
    $tanggal_cetak = date('d F Y, H:i:s');
    $tanggal_cetak = str_replace(array_keys($bulan_indo), array_values($bulan_indo), $tanggal_cetak);
    
    // Determine payment source label
    $source_label = 'Online Payment';
    if (isset($payment['source'])) {
        if ($payment['source'] == 'manual') {
            $source_label = 'Pembayaran Tunai/Manual';
        } elseif ($payment['source'] == 'json') {
            $source_label = 'Pembayaran Manual (Arsip)';
        }
    }
    
    // Sanitize data
    $payment_id_safe = htmlspecialchars($payment['payment_id'] ?? 'N/A');
    $name_safe = htmlspecialchars($payment['name'] ?? $student['name'] ?? 'N/A');
    $class_safe = htmlspecialchars($payment['class'] ?? $student['class'] ?? 'N/A');
    $tagihan_safe = htmlspecialchars($payment['tagihan'] ?? 'Pembayaran');
    $nominal_safe = number_format(floatval($payment['nominal'] ?? 0), 0, ',', '.');
    $method_safe = htmlspecialchars($payment['method_name'] ?? 'N/A');
    
    $receipt_html = '
    <div class="receipt-paper">
        <div class="receipt-header">
            <img src="https://ibnuzubair.ypi-khairaummah.sch.id/logo.jpeg" alt="Logo YPI" class="receipt-logo" onerror="this.style.display=\'none\'">
            <h1>Mahad Ibnu Zubair</h1>
            <p class="school-address">Jl. Ulak Karang Mahad Ibnu Zubair</p>
            <p class="school-address">Telp: (0778) 123456</p>
            <div class="divider"></div>
            <h2 class="receipt-title">BUKTI PEMBAYARAN</h2>
        </div>
        
        <div class="receipt-body">
            <table class="receipt-table">
                <tr>
                    <td class="label">No. Transaksi</td>
                    <td class="colon">:</td>
                    <td class="value">' . $payment_id_safe . '</td>
                </tr>
                <tr>
                    <td class="label">Tanggal Bayar</td>
                    <td class="colon">:</td>
                    <td class="value">' . $tanggal_bayar . ' WIB</td>
                </tr>
                <tr>
                    <td colspan="3" class="section-divider"></td>
                </tr>
                <tr>
                    <td class="label">Nama Siswa</td>
                    <td class="colon">:</td>
                    <td class="value">' . $name_safe . '</td>
                </tr>
                <tr>
                    <td class="label">Kelas</td>
                    <td class="colon">:</td>
                    <td class="value">' . $class_safe . '</td>
                </tr>
                <tr>
                    <td colspan="3" class="section-divider"></td>
                </tr>
                <tr>
                    <td class="label">Jenis Tagihan</td>
                    <td class="colon">:</td>
                    <td class="value">' . $tagihan_safe . '</td>
                </tr>
                <tr>
                    <td class="label">Jumlah Bayar</td>
                    <td class="colon">:</td>
                    <td class="value amount">Rp ' . $nominal_safe . '</td>
                </tr>
                <tr>
                    <td class="label">Metode Bayar</td>
                    <td class="colon">:</td>
                    <td class="value">' . $method_safe . '</td>
                </tr>
                <tr>
                    <td class="label">Dibayar Melalui</td>
                    <td class="colon">:</td>
                    <td class="value">' . $source_label . '</td>
                </tr>
                <tr>
                    <td colspan="3" class="section-divider"></td>
                </tr>
                <tr>
                    <td class="label">Status</td>
                    <td class="colon">:</td>
                    <td class="value"><span class="status-paid">LUNAS</span></td>
                </tr>
            </table>
        </div>
        
        <div class="receipt-footer">
            <div class="divider"></div>
            <p class="footer-note">Bukti pembayaran ini sah dan dikeluarkan oleh sistem</p>
            <p class="footer-note">Mahad Ibnu Zubair secara otomatis.</p>
            <p class="print-time">Dicetak pada: ' . $tanggal_cetak . ' WIB</p>
            <p class="thank-you">*** Terima Kasih ***</p>
        </div>
        
        <div class="watermark">LUNAS</div>
    </div>';
    
    return $receipt_html;
}

// Get payment methods from Tripay
function getTripayPaymentMethods() {
    $headers = [
        'Authorization: Bearer ' . TRIPAY_API_KEY,
        'Content-Type: application/json'
    ];
    
    $response = httpRequest(TRIPAY_API_URL . '/merchant/payment-channel', $headers);
    
    if ($response['code'] == 200 && isset($response['data']['success']) && $response['data']['success']) {
        return $response['data']['data'];
    }
    
    error_log('Failed to fetch payment methods from Tripay: HTTP ' . $response['code']);
    
    // Fallback methods
    return [
        ['code' => 'BRIVA', 'name' => 'BRI Virtual Account', 'type' => 'virtual_account'],
        ['code' => 'BNIVA', 'name' => 'BNI Virtual Account', 'type' => 'virtual_account'],
        ['code' => 'MANDIRIVA', 'name' => 'Mandiri Virtual Account', 'type' => 'virtual_account'],
        ['code' => 'BCAVA', 'name' => 'BCA Virtual Account', 'type' => 'virtual_account'],
        ['code' => 'QRIS', 'name' => 'QRIS', 'type' => 'qr_code'],
        ['code' => 'GOPAY', 'name' => 'GoPay', 'type' => 'ewallet'],
        ['code' => 'OVO', 'name' => 'OVO', 'type' => 'ewallet'],
        ['code' => 'DANA', 'name' => 'DANA', 'type' => 'ewallet']
    ];
}

$available_methods = getTripayPaymentMethods();

// Function to get combined payment history
function getCombinedPaymentHistory($conn, $student_id, $student_name) {
    $all_payments = [];
    
    // 1. Get from payments table (Tripay payments)
    $stmt = $conn->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY waktu_input DESC");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $row['tagihan_detail'] = json_decode($row['tagihan_detail'], true);
        $row['instructions'] = json_decode($row['instructions'], true);
        $row['source'] = 'tripay';
        $all_payments[] = $row;
    }
    $stmt->close();
    
    // 2. Get from pembayaran table (manual/cash payments)
    $stmt = $conn->prepare("SELECT p.*, s.name, s.class FROM pembayaran p 
                            LEFT JOIN students s ON p.student_id = s.id 
                            WHERE p.student_id = ? 
                            ORDER BY p.tanggal DESC");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $normalized = [
            'payment_id' => 'MANUAL-' . $row['id'],
            'student_id' => $student_id,
            'name' => $row['name'] ?? $student_name,
            'class' => $row['class'] ?? '',
            'tagihan' => $row['nama_tagihan'] ?? 'Pembayaran Manual',
            'tagihan_detail' => null,
            'nominal' => floatval($row['jumlah_bayar']),
            'method_code' => 'CASH',
            'method_name' => 'Tunai/Transfer Manual',
            'status' => 'berhasil',
            'waktu_input' => $row['tanggal'],
            'expired_at' => null,
            'order_id' => null,
            'tripay_ref' => null,
            'pay_code' => null,
            'pay_url' => null,
            'qr_string' => null,
            'instructions' => null,
            'source' => 'manual'
        ];
        $all_payments[] = $normalized;
    }
    $stmt->close();
    
    // 3. Get from payments.json
    $possible_paths = [
        $_SERVER['DOCUMENT_ROOT'] . '/payments.json',
        $_SERVER['DOCUMENT_ROOT'] . '/public_html/payments.json',
        '/home/ypikhair/public_html/payments.json',
        dirname(__FILE__) . '/payments.json'
    ];
    
    $json_file = null;
    foreach ($possible_paths as $path) {
        if (file_exists($path) && is_readable($path)) {
            $json_file = $path;
            break;
        }
    }
    
    if ($json_file) {
        $json_content = @file_get_contents($json_file);
        if ($json_content !== false) {
            $json_data = json_decode($json_content, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
                foreach ($json_data as $payment_record) {
                    $match = false;
                    
                    if (isset($payment_record['student_id']) && $payment_record['student_id'] == $student_id) {
                        $match = true;
                    } elseif (isset($payment_record['name']) && strcasecmp($payment_record['name'], $student_name) === 0) {
                        $match = true;
                    }
                    
                    if ($match && isset($payment_record['status']) && $payment_record['status'] === 'berhasil') {
                        $normalized = [
                            'payment_id' => 'JSON-' . ($payment_record['id'] ?? uniqid()),
                            'student_id' => $student_id,
                            'name' => $payment_record['name'] ?? $student_name,
                            'class' => $payment_record['class'] ?? '',
                            'tagihan' => $payment_record['tagihan'] ?? 'Pembayaran',
                            'tagihan_detail' => null,
                            'nominal' => floatval($payment_record['nominal'] ?? 0),
                            'method_code' => strtoupper($payment_record['bank_tujuan'] ?? 'MANUAL'),
                            'method_name' => $payment_record['bank_tujuan'] ?? 'Manual Input',
                            'status' => 'berhasil',
                            'waktu_input' => $payment_record['waktu_input'] ?? date('Y-m-d H:i:s'),
                            'expired_at' => null,
                            'order_id' => null,
                            'tripay_ref' => null,
                            'pay_code' => null,
                            'pay_url' => null,
                            'qr_string' => null,
                            'instructions' => null,
                            'source' => 'json'
                        ];
                        $all_payments[] = $normalized;
                    }
                }
            }
        }
    }
    
    // Sort by date (newest first)
    usort($all_payments, function($a, $b) {
        return strtotime($b['waktu_input']) - strtotime($a['waktu_input']);
    });
    
    return $all_payments;
}

// Get combined payment history
$my_payments = getCombinedPaymentHistory($conn, $student_id, $student['name']);

$current_tab = $_GET['tab'] ?? null;
$show_menu = empty($current_tab);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Pembayaran Mahad Ibnu Zubair Padang</title>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    
    <style>
       * { 
    margin: 0; 
    padding: 0; 
    box-sizing: border-box; 
    -webkit-tap-highlight-color: transparent;
    -webkit-touch-callout: none;
}

input, textarea, select {
    -webkit-user-select: text;
}
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8f9fa; color: #333; line-height: 1.6; }
        .container { max-width: 420px; margin: 0 auto; background: white; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; text-align: center; position: relative; }
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .logout { position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); color: white; padding: 8px 12px; text-decoration: none; border-radius: 20px; font-size: 12px; backdrop-filter: blur(10px); }
        .tabs { display: flex; background: white; border-bottom: 1px solid #e9ecef; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: thin; }
        .tabs::-webkit-scrollbar { height: 4px; }
        .tabs::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 2px; }
        .tabs a { flex: 0 0 auto; min-width: 70px; padding: 12px 10px; text-decoration: none; color: #6c757d; text-align: center; border-bottom: 3px solid transparent; transition: all 0.3s; font-size: 12px; white-space: nowrap; }
        .tabs a.active { color: #10b981; border-bottom-color: #10b981; font-weight: 600; background: #f0fdf4; }
        .tabs a:hover { background: #f8f9fa; }
        .tabs a .tab-text { display: inline; }
        @media (max-width: 600px) {
            .tabs a { min-width: 55px; padding: 10px 6px; font-size: 11px; }
            .tabs a .tab-text { display: none; }
            .tabs a { min-width: 50px; }
        }
        @media (max-width: 480px) {
            .tabs a { min-width: 45px; padding: 10px 5px; font-size: 10px; }
        }
        .content { padding: 20px; }
        .content-with-bottom-nav { padding-bottom: 90px !important; }
        .product-list-item {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
            cursor: pointer;
        }
        .product-list-item:hover {
            border-color: #10b981;
            background: #f0fdf4;
        }
        .product-list-item:active {
            transform: scale(0.98);
        }
        .product-name {
            font-size: 14px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 4px;
        }
        .product-info {
            font-size: 13px;
            color: #6b7280;
        }
        .product-add-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            background: #10b981;
            color: white;
            border-radius: 6px;
            font-size: 18px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .product-list-item:hover .product-add-btn {
            background: #059669;
            transform: scale(1.1);
        }
        .card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .bill-item { border: 2px solid #e9ecef; border-radius: 8px; padding: 15px; margin-bottom: 10px; cursor: pointer; transition: all 0.3s; }
        .bill-item:hover { border-color: #10b981; }
        .bill-item.selected { border-color: #10b981; background: #f0fdf4; }
        .bill-item input[type="checkbox"] { margin-right: 10px; transform: scale(1.2); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group select { width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 16px; background: white; }
        .btn { width: auto; min-width: 44px; height: 44px; padding: 10px 16px; border: none; border-radius: 12px; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; transition: all 0.3s; gap: 6px; }
        .btn-primary { background: linear-gradient(135deg, #10b981 0%, #34d399 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-small { min-width: 36px; height: 36px; padding: 6px 12px; font-size: 12px; margin: 4px; display: inline-flex; }
        .btn-full { width: 100%; }
        .btn-danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4); }
        .total-box { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; border-radius: 12px; margin: 20px 0; text-align: center; }
        .total-box h3 { font-size: 24px; }
        .empty-state { text-align: center; padding: 60px 20px; color: #6c757d; }
        .empty-state h3 { margin-bottom: 10px; color: #333; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); overflow-y: auto; }
        .modal-content { position: relative; background: white; width: 90%; max-width: 500px; border-radius: 12px; margin: 30px auto; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .modal-header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; text-align: center; position: relative; }
        .modal-body { padding: 20px; max-height: 70vh; overflow-y: auto; }
        .payment-info { text-align: center; margin-bottom: 20px; }
        .va-number { font-size: 20px; font-weight: bold; font-family: monospace; background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0; border: 2px dashed #10b981; user-select: all; }
        .qr-container { text-align: center; margin: 20px 0; min-height: 270px; display: flex; flex-direction: column; justify-content: center; align-items: center; }
        .qr-container canvas { border: 1px solid #dee2e6; border-radius: 8px; }
        .instruction-list { background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .instruction-list ol { padding-left: 20px; }
        .instruction-list li { margin-bottom: 5px; font-size: 14px; }
        .close-modal { position: absolute; top: 15px; right: 20px; color: white; font-size: 24px; cursor: pointer; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: rgba(255,255,255,0.2); }
        .close-modal:hover { background: rgba(255,255,255,0.3); }
        .status-pending { background: #fff3cd; color: #856404; padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: 600; }
        .status-berhasil { background: #d4edda; color: #155724; padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: 600; }
        .payment-item { background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .payment-item h4 { margin-bottom: 15px; color: #333; }
        .payment-item p { margin-bottom: 10px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        table th, table td { padding: 12px 8px; border: 1px solid #e9ecef; text-align: left; }
        table th { background: #f8f9fa; font-weight: 600; }
        .copy-success { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background: #28a745; color: white; padding: 10px 20px; border-radius: 8px; z-index: 10000; display: none; }
        .source-badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: 600; margin-left: 5px; }
        .source-tripay { background: #e3f2fd; color: #1976d2; }
        .source-manual { background: #fff3e0; color: #f57c00; }
        .source-json { background: #f3e5f5; color: #7b1fa2; }
        .polling-indicator { display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: #28a745; margin-left: 10px; animation: pulse 2s infinite; }
        
        /* Status Tagihan Styles */
        .tagihan-history-item { 
            background: white; 
            border-radius: 12px; 
            padding: 20px; 
            margin-bottom: 15px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            border-left: 4px solid #10b981;
        }
        .tagihan-history-item h4 { 
            margin-bottom: 10px; 
            color: #333; 
            font-size: 16px;
        }
        .tagihan-history-item .history-detail { 
            font-size: 14px; 
            color: #666; 
            margin-bottom: 8px;
        }
        .tagihan-history-item .history-detail strong { 
            color: #333; 
        }
        .history-action-badge { 
            display: inline-block; 
            padding: 4px 10px; 
            border-radius: 12px; 
            font-size: 11px; 
            font-weight: 600; 
            margin-left: 5px;
        }
        .action-added { background: #d4edda; color: #155724; }
        .action-updated { background: #fff3cd; color: #856404; }
        .action-reduced { background: #f8d7da; color: #721c24; }
        .action-removed { background: #e2e3e5; color: #383d41; }
        .history-timestamp { 
            font-size: 12px; 
            color: #999; 
            margin-top: 10px;
            font-style: italic;
        }
        
        /* Receipt Styles */
        .receipt-paper {
            background: white;
            max-width: 400px;
            margin: 0 auto;
            padding: 30px 25px;
            font-family: 'Courier New', Courier, monospace;
            color: #000;
            position: relative;
        }
        .receipt-logo {
            width: 80px;
            height: 80px;
            display: block;
            margin: 0 auto 15px;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .receipt-header h1 {
            font-size: 20px;
            font-weight: bold;
            margin: 10px 0 5px;
            letter-spacing: 1px;
        }
        .school-address {
            font-size: 11px;
            color: #555;
            margin: 2px 0;
        }
        .divider {
            border-top: 2px solid #000;
            margin: 15px 0;
        }
        .receipt-title {
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0;
            letter-spacing: 2px;
        }
        .receipt-body {
            margin: 20px 0;
        }
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .receipt-table td {
            padding: 6px 0;
            vertical-align: top;
            border: none;
        }
        .receipt-table .label {
            width: 40%;
            color: #555;
        }
        .receipt-table .colon {
            width: 5%;
            text-align: center;
        }
        .receipt-table .value {
            width: 55%;
            font-weight: bold;
        }
        .receipt-table .amount {
            font-size: 16px;
            color: #000;
        }
        .section-divider {
            border-top: 1px dashed #ccc;
            padding-top: 8px !important;
        }
        .status-paid {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .receipt-footer {
            margin-top: 25px;
            text-align: center;
        }
        .footer-note {
            font-size: 10px;
            color: #666;
            margin: 3px 0;
        }
        .print-time {
            font-size: 10px;
            color: #999;
            margin: 10px 0;
        }
        .thank-you {
            font-size: 13px;
            font-weight: bold;
            margin-top: 15px;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 72px;
            font-weight: bold;
            color: rgba(40, 167, 69, 0.08);
            pointer-events: none;
            z-index: 0;
            letter-spacing: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        @media print {
            body { background: white; }
            .modal { display: block !important; position: static; background: none; }
            .modal-content { width: 100%; max-width: none; margin: 0; box-shadow: none; border-radius: 0; }
            .modal-header { display: none !important; }
            .modal-body { padding: 0; max-height: none; }
            .receipt-paper { padding: 20px; }
            .btn, button { display: none !important; }
            .watermark { color: rgba(40, 167, 69, 0.05); }
        }
        
        @media (max-width: 480px) {
            .container { max-width: 100%; }
            .content { padding: 15px; }
            .modal-content { width: 95%; margin: 20px auto; }
            table { font-size: 12px; }
            table th, table td { padding: 8px 4px; }
            .receipt-paper { padding: 20px 15px; }
            .receipt-table { font-size: 11px; }
        }
        
        /* Menu Grid Styles */
        /* Bottom Navigation Bar */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 8px 0 max(8px, env(safe-area-inset-bottom));
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            z-index: 1000;
            max-width: 420px;
            margin: 0 auto;
        }
        .bottom-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 8px 4px;
            text-decoration: none;
            color: #6b7280;
            transition: all 0.2s;
            cursor: pointer;
            min-width: 0;
        }
        .bottom-nav-item.active {
            color: #10b981;
        }
        .bottom-nav-icon {
            width: 24px;
            height: 24px;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .bottom-nav-item.active .bottom-nav-icon {
            transform: scale(1.1);
        }
        .bottom-nav-label {
            font-size: 11px;
            font-weight: 500;
            text-align: center;
            line-height: 1.2;
            margin-top: 2px;
        }
        .bottom-nav-item:active {
            opacity: 0.7;
        }
        /* Padding untuk konten agar tidak tertutup bottom nav */
        .content-with-bottom-nav {
            padding-bottom: 80px;
        }
    </style>
</head>
<body>
    <div id="copy-notification" class="copy-success">
        Berhasil disalin!
    </div>

    <div class="container">
        <div class="header">
            <a href="?logout=1" class="logout">Logout</a>
            <h1>Portal Pembayaran</h1>
            <p><?= htmlspecialchars($student['name']) ?> - <?= htmlspecialchars($student['class']) ?></p>
        </div>

        <?php if ($show_menu): ?>
            <!-- Halaman Pilihan Menu -->
            <div class="content content-with-bottom-nav" style="padding: 24px 20px;">
                <div style="text-align: center; margin-bottom: 32px;">
                    <h2 style="font-size: 22px; font-weight: 700; color: #1a1a1a; margin-bottom: 8px;">Selamat Datang</h2>
                    <p style="font-size: 15px; color: #6b7280; margin: 0;">Pilih menu di bawah untuk memulai</p>
                </div>
                
                <div style="text-align: center; padding: 40px 20px;">
                    <div style="font-size: 64px; margin-bottom: 16px;">👋</div>
                    <p style="font-size: 16px; color: #6b7280; line-height: 1.6;">
                        Gunakan menu navigasi di bawah untuk mengakses fitur-fitur yang tersedia
                    </p>
                </div>
            </div>
            
            <!-- Bottom Navigation Bar -->
            <nav class="bottom-nav">
                <a href="?tab=bayar" class="bottom-nav-item <?= $current_tab == 'bayar' ? 'active' : '' ?>">
                    <div class="bottom-nav-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                    </div>
                    <div class="bottom-nav-label">Bayar</div>
                </a>
                <a href="?tab=belanja" class="bottom-nav-item <?= $current_tab == 'belanja' ? 'active' : '' ?>">
                    <div class="bottom-nav-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                    </div>
                    <div class="bottom-nav-label">Belanja</div>
                </a>
                <a href="?tab=voucher" class="bottom-nav-item <?= $current_tab == 'voucher' ? 'active' : '' ?>">
                    <div class="bottom-nav-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12c0 1.66-1.34 3-3 3H6c-1.66 0-3-1.34-3-3s1.34-3 3-3h12c1.66 0 3 1.34 3 3z"></path>
                            <path d="M9 12h6"></path>
                        </svg>
                    </div>
                    <div class="bottom-nav-label">Voucher</div>
                </a>
                <a href="?tab=absensi" class="bottom-nav-item <?= $current_tab == 'absensi' ? 'active' : '' ?>">
                    <div class="bottom-nav-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <div class="bottom-nav-label">Absensi</div>
                </a>
            </nav>
        <?php else: ?>
            <div class="content content-with-bottom-nav">
            <div class="tabs">
                <a href="?" title="Menu Utama">🏠 <span class="tab-text">Menu</span></a>
                <a href="?tab=bayar" class="<?= $current_tab == 'bayar' ? 'active' : '' ?>" title="Bayar Tagihan">💳 <span class="tab-text">Bayar</span></a>
                <a href="?tab=pending" class="<?= $current_tab == 'pending' ? 'active' : '' ?>" title="Status Pembayaran">⏳ <span class="tab-text">Status</span></a>
                <a href="?tab=tagihan" class="<?= $current_tab == 'tagihan' ? 'active' : '' ?>" title="Status Tagihan">📋 <span class="tab-text">Tagihan</span></a>
                <a href="?tab=history" class="<?= $current_tab == 'history' ? 'active' : '' ?>" title="Riwayat Pembayaran">📜 <span class="tab-text">Riwayat</span></a>
                <a href="?tab=absensi" class="<?= $current_tab == 'absensi' ? 'active' : '' ?>" title="Rekap Absensi">✅ <span class="tab-text">Absensi</span></a>
                <a href="?tab=belanja" class="<?= $current_tab == 'belanja' ? 'active' : '' ?>" title="Belanja Barang">🛒 <span class="tab-text">Belanja</span></a>
                <a href="?tab=voucher" class="<?= $current_tab == 'voucher' ? 'active' : '' ?>" title="Voucher Pembayaran">🎫 <span class="tab-text">Voucher</span></a>
            </div>

            <div class="content">
                <?php if ($current_tab == 'bayar'): ?>
                <?php 
                $unpaid_bills = array_filter($student_bills, function($amount) { return $amount > 0; });
                ?>
                
                <?php if (empty($unpaid_bills)): ?>
                    <div class="empty-state">
                        <h3>Semua Tagihan Lunas!</h3>
                        <p>Tidak ada tagihan yang perlu dibayar saat ini.</p>
                    </div>
                <?php else: ?>
                    <form id="payment-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <h3 style="margin-bottom: 20px;">Pilih Tagihan:</h3>
                        
                        <?php foreach ($unpaid_bills as $bill => $amount): 
                            // Get tanggal tagihan ditambahkan dari tagihan_history
                            $stmt = $conn->prepare("SELECT created_at FROM tagihan_history WHERE student_id = ? AND nama_tagihan = ? ORDER BY created_at ASC LIMIT 1");
                            $stmt->bind_param("is", $student_id, $bill);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $history_row = $result->fetch_assoc();
                            $stmt->close();
                            
                            $tanggal_ditambahkan = $history_row ? date('d/m/Y H:i', strtotime($history_row['created_at'])) : null;
                        ?>
                            <div class="bill-item" onclick="toggleBill('<?= htmlspecialchars($bill) ?>')">
                                <label style="cursor: pointer; display: block;">
                                    <input type="checkbox" name="tagihan[]" value="<?= htmlspecialchars($bill) ?>" 
                                           onchange="updateTotal()">
                                    <strong><?= htmlspecialchars($bill) ?></strong>
                                    <br><small>Jumlah: Rp <?= number_format($amount, 0, ',', '.') ?></small>
                                    <br><small style="color: #999; font-size: 11px;">
                                        <?php if ($tanggal_ditambahkan): ?>
                                            📅 Ditambahkan: <?= $tanggal_ditambahkan ?>
                                        <?php else: ?>
                                            📁 Data disimpan di komputer server dan bukti tagihan fisik (Bukti Pembayaran Fisik)
                                        <?php endif; ?>
                                    </small>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="form-group">
                            <label>Metode Pembayaran:</label>
                            <select name="method" required>
                                <option value="">Pilih Metode</option>
                                <?php foreach ($available_methods as $method): ?>
                                    <option value="<?= $method['code'] ?>"><?= $method['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div id="total-box" class="total-box" style="display:none;">
                            <h3>Total: <span id="total-amount">Rp 0</span></h3>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-full" id="pay-btn" disabled>Bayar Sekarang</button>
                    </form>
                <?php endif; ?>
                
            <?php elseif ($current_tab == 'pending'): ?>
                <?php 
                $pending_payments = array_filter($my_payments, function($p) { 
                    return $p['status'] == 'pending' && $p['source'] == 'tripay' && time() < strtotime($p['expired_at']); 
                });
                ?>
                
                <?php if (empty($pending_payments)): ?>
                    <div class="empty-state">
                        <h3>Tidak Ada Pembayaran Menunggu</h3>
                        <p>Semua pembayaran sudah selesai atau kadaluarsa.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pending_payments as $payment): ?>
                        <div class="payment-item">
                            <h4>Pembayaran Menunggu</h4>
                            <p><strong>Tagihan:</strong> <?= htmlspecialchars($payment['tagihan']) ?></p>
                            <p><strong>Jumlah:</strong> Rp <?= number_format($payment['nominal'], 0, ',', '.') ?></p>
                            <p><strong>Metode:</strong> <?= htmlspecialchars($payment['method_name']) ?></p>
                            <p><strong>Berakhir:</strong> <?= date('d/m/Y H:i', strtotime($payment['expired_at'])) ?></p>
                            
                            <button class="btn btn-primary" onclick="showPaymentDetails('<?= $payment['payment_id'] ?>')">
                                Lihat Detail Pembayaran
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
            <?php elseif ($current_tab == 'tagihan'): ?>
                <?php if (empty($tagihan_history)): ?>
                    <div class="empty-state">
                        <h3>Belum Ada Riwayat Tagihan</h3>
                        <p>Riwayat penambahan dan perubahan tagihan akan muncul di sini.</p>
                    </div>
                <?php else: ?>
                    <div style="margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <p style="font-size: 13px; color: #666; margin: 0;">
                            <strong>Total Riwayat:</strong> <?= count($tagihan_history) ?> perubahan tagihan
                        </p>
                    </div>
                    
                    <?php foreach ($tagihan_history as $history): ?>
                        <?php 
                        $action_type = $history['action_type'] ?? 'updated';
                        $action_labels = [
                            'added' => 'Ditambahkan',
                            'updated' => 'Diperbarui',
                            'reduced' => 'Dikurangi',
                            'removed' => 'Dihapus',
                            'created' => 'Dibuat',
                            'modified' => 'Dimodifikasi'
                        ];
                        $action_label = $action_labels[$action_type] ?? ucfirst($action_type);
                        
                        // Determine action class for badge color
                        $action_class = $action_type;
                        if (in_array($action_type, ['created', 'added'])) {
                            $action_class = 'added';
                        } elseif (in_array($action_type, ['modified', 'updated'])) {
                            $action_class = 'updated';
                        }
                        ?>
                        <div class="tagihan-history-item">
                            <h4>
                                <?= htmlspecialchars($history['nama_tagihan']) ?>
                                <span class="history-action-badge action-<?= $action_class ?>">
                                    <?= $action_label ?>
                                </span>
                            </h4>
                            
                            <div class="history-detail">
                                <strong>Jumlah:</strong> 
                                Rp <?= number_format($history['jumlah'], 0, ',', '.') ?>
                            </div>
                            
                            <div class="history-timestamp">
                                📅 <?= date('d/m/Y H:i:s', strtotime($history['created_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
            <?php elseif ($current_tab == 'history'): ?>
                <?php if (empty($my_payments)): ?>
                    <div class="empty-state">
                        <h3>Belum Ada Riwayat</h3>
                        <p>Riwayat pembayaran akan muncul di sini.</p>
                    </div>
                <?php else: ?>
                    <div style="margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <p style="font-size: 13px; color: #666; margin: 0;">
                            <strong>Total Riwayat:</strong> <?= count($my_payments) ?> pembayaran
                        </p>
                    </div>
                    
                    <table>
                        <tr>
                            <th>Tanggal</th>
                            <th>Tagihan</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                        <?php foreach ($my_payments as $payment): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($payment['waktu_input'])) ?></td>
                                <td><?= htmlspecialchars($payment['tagihan']) ?></td>
                                <td>Rp <?= number_format($payment['nominal'], 0, ',', '.') ?></td>
                                <td>
                                    <span class="status-<?= $payment['status'] ?>">
                                        <?php 
                                        switch($payment['status']) {
                                            case 'berhasil': echo 'LUNAS'; break;
                                            case 'pending': echo 'MENUNGGU'; break;
                                            default: echo 'GAGAL';
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($payment['status'] == 'berhasil'): ?>
                                        <button onclick="showReceipt('<?= htmlspecialchars($payment['payment_id']) ?>')" class="btn btn-primary btn-small">📄 Cetak</button>
                                    <?php elseif ($payment['status'] == 'pending' && $payment['source'] == 'tripay'): ?>
                                        <button onclick="showPaymentDetails('<?= htmlspecialchars($payment['payment_id']) ?>')" class="btn btn-primary btn-small">👁️ Lihat</button>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            <?php elseif ($current_tab == 'absensi'): ?>
                <?php
                // Get absensi data for current student
                $stmt = $conn->prepare("
                    SELECT a.*, d.nama as nama_dosen 
                    FROM absensi a 
                    LEFT JOIN dosen d ON a.dosen_id = d.id 
                    WHERE a.student_id = ? 
                    ORDER BY a.tanggal DESC, a.mata_kuliah ASC
                ");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $absensi_data = [];
                while ($row = $result->fetch_assoc()) {
                    $absensi_data[] = $row;
                }
                $stmt->close();
                ?>
                
                <div class="card">
                    <h2 style="margin-bottom: 20px;">Rekap Absensi</h2>
                    <?php if (empty($absensi_data)): ?>
                        <div class="empty-state">
                            <h3>Belum Ada Data Absensi</h3>
                            <p>Data absensi akan muncul setelah dosen menginput kehadiran Anda.</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Mata Kuliah</th>
                                    <th>Status</th>
                                    <th>Dosen</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($absensi_data as $abs): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($abs['tanggal'])) ?></td>
                                        <td><?= htmlspecialchars($abs['mata_kuliah']) ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            switch($abs['status']) {
                                                case 'hadir':
                                                    $status_class = 'status-berhasil';
                                                    $status_text = 'Hadir';
                                                    break;
                                                case 'izin':
                                                    $status_class = 'status-pending';
                                                    $status_text = 'Izin';
                                                    break;
                                                case 'sakit':
                                                    $status_class = 'status-pending';
                                                    $status_text = 'Sakit';
                                                    break;
                                                case 'alpha':
                                                    $status_class = 'status-pending';
                                                    $status_text = 'Alpha';
                                                    break;
                                            }
                                            ?>
                                            <span class="<?= $status_class ?>"><?= $status_text ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($abs['nama_dosen'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($abs['keterangan'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <?php
                        // Calculate statistics
                        $total = count($absensi_data);
                        $hadir = count(array_filter($absensi_data, fn($a) => $a['status'] == 'hadir'));
                        $izin = count(array_filter($absensi_data, fn($a) => $a['status'] == 'izin'));
                        $sakit = count(array_filter($absensi_data, fn($a) => $a['status'] == 'sakit'));
                        $alpha = count(array_filter($absensi_data, fn($a) => $a['status'] == 'alpha'));
                        $persentase = $total > 0 ? round(($hadir / $total) * 100, 2) : 0;
                        ?>
                        <div class="total-box" style="margin-top: 20px;">
                            <h3>Statistik Kehadiran</h3>
                            <p style="margin: 10px 0;">Total: <?= $total ?> | Hadir: <?= $hadir ?> | Izin: <?= $izin ?> | Sakit: <?= $sakit ?> | Alpha: <?= $alpha ?></p>
                            <p style="font-size: 20px; margin-top: 10px;"><strong>Persentase Kehadiran: <?= $persentase ?>%</strong></p>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($current_tab == 'belanja'): ?>
                <?php
                // Get available items
                $stmt = $conn->prepare("SELECT * FROM barang WHERE status = 'aktif' AND stok > 0 ORDER BY nama_barang ASC");
                $stmt->execute();
                $result = $stmt->get_result();
                $barang_list = [];
                while ($row = $result->fetch_assoc()) {
                    $barang_list[] = $row;
                }
                $stmt->close();
                
                ?>
                
                <div class="card" style="padding: 16px;">
                    <h2 style="margin-bottom: 12px; font-size: 16px; font-weight: 600; color: #1a1a1a;">Daftar Barang</h2>
                    <?php if (empty($barang_list)): ?>
                        <div class="empty-state">
                            <h3>Tidak Ada Barang Tersedia</h3>
                            <p>Barang akan muncul setelah admin menambahkan produk.</p>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <?php foreach ($barang_list as $barang): ?>
                                <div class="product-list-item" onclick="addToCart(<?= $barang['id'] ?>, '<?= htmlspecialchars($barang['nama_barang'], ENT_QUOTES) ?>', <?= $barang['harga'] ?>, <?= $barang['stok'] ?>)">
                                    <div style="flex: 1;">
                                        <div class="product-name"><?= htmlspecialchars($barang['nama_barang']) ?></div>
                                        <div class="product-info">Rp <?= number_format($barang['harga'], 0, ',', '.') ?> • Stok: <?= $barang['stok'] ?></div>
                                    </div>
                                    <div style="margin-left: 12px;">
                                        <span class="product-add-btn">+</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div id="cart-section" style="display: none;">
                            <div class="card">
                                <h2>Keranjang Belanja</h2>
                                <div id="cart-items"></div>
                                <div id="cart-total" style="background: #f7fafc; padding: 20px; border-radius: 8px; margin-top: 20px; text-align: right;"></div>
                                <button class="btn btn-primary btn-full" onclick="checkout()" style="margin-top: 20px;">Checkout</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($current_tab == 'voucher'): ?>
                <?php
                // Get vouchers for current student (hanya yang belum diredeem)
                $stmt = $conn->prepare("
                    SELECT v.*, p.total_harga, p.status as status_pesanan, p.order_id,
                           (SELECT GROUP_CONCAT(b.nama_barang SEPARATOR ', ') 
                            FROM detail_pesanan dp 
                            LEFT JOIN barang b ON dp.barang_id = b.id 
                            WHERE dp.pesanan_id = p.id) as daftar_barang
                    FROM voucher_pembayaran v
                    LEFT JOIN pesanan_belanja p ON v.pesanan_id = p.id
                    WHERE v.student_id = ? AND v.status = 'pending'
                    ORDER BY v.created_at DESC
                ");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $vouchers = [];
                while ($row = $result->fetch_assoc()) {
                    $vouchers[] = $row;
                }
                $stmt->close();
                ?>
                
                <div class="card">
                    <h2 style="margin-bottom: 20px;">Voucher Pembayaran Saya</h2>
                    <p style="margin-bottom: 20px; padding: 15px; background: #e3f2fd; border-radius: 8px; color: #1976d2; font-size: 14px;">
                        <strong>Info:</strong> Voucher akan muncul setelah pembayaran belanja berhasil. Setelah admin redeem voucher, voucher akan hilang dari daftar ini.
                    </p>
                    
                    <?php if (empty($vouchers)): ?>
                        <div class="empty-state">
                            <h3>Belum Ada Voucher</h3>
                            <p>Anda belum memiliki voucher pembayaran yang aktif. Voucher akan muncul setelah pembayaran belanja berhasil.</p>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Kode Voucher</th>
                                    <th>Order ID</th>
                                    <th>Total Pembayaran</th>
                                    <th>Barang yang Dibeli</th>
                                    <th>Status Pesanan</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vouchers as $voucher): ?>
                                    <tr>
                                        <td><strong style="font-size: 16px; color: #10b981;"><?= htmlspecialchars($voucher['voucher_code']) ?></strong></td>
                                        <td><?= htmlspecialchars($voucher['order_id'] ?? '-') ?></td>
                                        <td>Rp <?= number_format($voucher['total_harga'], 0, ',', '.') ?></td>
                                        <td><?= htmlspecialchars($voucher['daftar_barang'] ?? '-') ?></td>
                                        <td>
                                            <span class="<?= $voucher['status_pesanan'] == 'berhasil' ? 'status-berhasil' : 'status-pending' ?>">
                                                <?= ucfirst($voucher['status_pesanan']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($voucher['created_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-primary btn-small" onclick="printVoucher('<?= $voucher['voucher_code'] ?>')">
                                                🖨️ Cetak Voucher
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p style="margin-top: 15px; padding: 15px; background: #fff3cd; border-radius: 8px; color: #856404; font-size: 14px;">
                            <strong>📋 Catatan Penting:</strong><br>
                            1. Setelah pembayaran belanja berhasil, voucher akan otomatis muncul di sini.<br>
                            2. Cetak voucher dan tunjukkan ke admin kampus untuk menukarkan barang yang dibeli.<br>
                            3. Setelah admin redeem voucher, voucher akan hilang dari daftar ini dan tidak bisa digunakan lagi.
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            </div>
            
            <!-- Bottom Navigation Bar -->
            <nav class="bottom-nav">
                <a href="?tab=bayar" class="bottom-nav-item <?= $current_tab == 'bayar' ? 'active' : '' ?>">
                    <div class="bottom-nav-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                    </div>
                    <div class="bottom-nav-label">Bayar</div>
                </a>
                <a href="?tab=belanja" class="bottom-nav-item <?= $current_tab == 'belanja' ? 'active' : '' ?>">
                    <div class="bottom-nav-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                    </div>
                    <div class="bottom-nav-label">Belanja</div>
                </a>
                <a href="?tab=voucher" class="bottom-nav-item <?= $current_tab == 'voucher' ? 'active' : '' ?>">
                    <div class="bottom-nav-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12c0 1.66-1.34 3-3 3H6c-1.66 0-3-1.34-3-3s1.34-3 3-3h12c1.66 0 3 1.34 3 3z"></path>
                            <path d="M9 12h6"></path>
                        </svg>
                    </div>
                    <div class="bottom-nav-label">Voucher</div>
                </a>
                <a href="?tab=absensi" class="bottom-nav-item <?= $current_tab == 'absensi' ? 'active' : '' ?>">
                    <div class="bottom-nav-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <div class="bottom-nav-label">Absensi</div>
                </a>
            </nav>
            </div>
        <?php endif; ?>
    </div>

    <!-- Payment Modal -->
    <div id="payment-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close-modal" onclick="closeModal()">&times;</span>
                <h3>Detail Pembayaran <span id="polling-status"></span></h3>
            </div>
            <div class="modal-body" id="modal-body">
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div id="receipt-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close-modal" onclick="closeReceiptModal()">&times;</span>
                <h3>Bukti Pembayaran</h3>
            </div>
            <div class="modal-body" id="receipt-body">
            </div>
            <div style="padding: 20px; text-align: center; border-top: 1px solid #e9ecef;">
                <button class="btn btn-primary" onclick="printReceipt()" style="margin-right: 10px;">🖨️ Cetak</button>
                <button class="btn btn-primary" onclick="closeReceiptModal()">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        let selectedBills = new Set();
        let paymentMethods = <?= json_encode($available_methods) ?>;
        let allPayments = <?= json_encode($my_payments) ?>;
        let billData = <?= json_encode($student_bills) ?>;
        let pollingInterval = null;
        let currentPaymentId = null;

        function toggleBill(bill) {
            const checkbox = document.querySelector(`input[value="${bill}"]`);
            checkbox.checked = !checkbox.checked;
            
            if (checkbox.checked) {
                selectedBills.add(bill);
                checkbox.closest('.bill-item').classList.add('selected');
            } else {
                selectedBills.delete(bill);
                checkbox.closest('.bill-item').classList.remove('selected');
            }
            
            updateTotal();
        }

        function updateTotal() {
            let total = 0;
            
            selectedBills.forEach(bill => {
                total += parseFloat(billData[bill]) || 0;
            });
            
            const totalBox = document.getElementById('total-box');
            const totalAmount = document.getElementById('total-amount');
            const payBtn = document.getElementById('pay-btn');
            
            if (total > 0) {
                totalBox.style.display = 'block';
                totalAmount.textContent = 'Rp ' + total.toLocaleString('id-ID');
                payBtn.disabled = false;
            } else {
                totalBox.style.display = 'none';
                payBtn.disabled = true;
            }
        }

        // ===== AUTO POLLING FUNCTION =====
        function startPolling(paymentId) {
            stopPolling(); // Stop any existing polling
            
            currentPaymentId = paymentId;
            const pollingStatus = document.getElementById('polling-status');
            
            if (pollingStatus) {
                pollingStatus.innerHTML = '<span class="polling-indicator"></span>';
            }
            
            // Poll setiap 5 detik
            pollingInterval = setInterval(async () => {
                await checkPaymentStatusSilent(paymentId);
            }, 5000);
            
            console.log('Polling started for payment:', paymentId);
        }

        function stopPolling() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
                console.log('Polling stopped');
            }
            
            const pollingStatus = document.getElementById('polling-status');
            if (pollingStatus) {
                pollingStatus.innerHTML = '';
            }
        }

        async function checkPaymentStatusSilent(paymentId) {
            const formData = new FormData();
            formData.append('action', 'check_payment');
            formData.append('payment_id', paymentId);
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (result.status === 'berhasil') {
                        stopPolling();
                        
                        // Show success message
                        alert('✅ Pembayaran berhasil!\n\nHalaman akan dimuat ulang untuk menampilkan data terbaru.');
                        
                        // Reload page after 1 second
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else if (result.status === 'expired' || result.status === 'gagal') {
                        stopPolling();
                        alert('Pembayaran ' + result.status + '. Silakan buat pembayaran baru.');
                        closeModal();
                        location.reload();
                    }
                    // Jika masih pending, polling akan terus berjalan
                }
            } catch (error) {
                console.error('Silent polling error:', error);
                // Tidak perlu alert, polling akan coba lagi
            }
        }

        document.getElementById('payment-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (selectedBills.size === 0) {
                alert('Pilih minimal satu tagihan');
                return;
            }
            
            const method = document.querySelector('select[name="method"]').value;
            if (!method) {
                alert('Pilih metode pembayaran');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'create_payment');
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

            selectedBills.forEach(bill => {
                formData.append('tagihan[]', bill);
            });

            formData.append('method', method);
            
            const payBtn = document.getElementById('pay-btn');
            const originalText = payBtn.textContent;
            payBtn.textContent = 'Memproses...';
            payBtn.disabled = true;
            
            try {
                // Show loading modal immediately
                const modal = document.getElementById('payment-modal');
                const modalBody = document.getElementById('modal-body');
                modalBody.innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <div style="border: 4px solid #f3f3f3; border-top: 4px solid #10b981; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
                        <p style="margin-top: 20px; color: #666;">Sedang memproses pembayaran...</p>
                    </div>
                `;
                modal.style.display = 'block';
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Immediately show payment details
                    showPaymentModal(result.payment_data);
                    
                    // Start auto polling
                    startPolling(result.payment_data.payment_id);
                    
                    selectedBills.clear();
                    document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                        cb.checked = false;
                        cb.closest('.bill-item').classList.remove('selected');
                    });
                    document.querySelector('select[name="method"]').value = '';
                    updateTotal();
                } else {
                    modal.style.display = 'none';
                    alert('Error: ' + result.message);
                    payBtn.textContent = originalText;
                    payBtn.disabled = false;
                }
            } catch (error) {
                console.error('Payment creation error:', error);
                document.getElementById('payment-modal').style.display = 'none';
                alert('Terjadi kesalahan: ' + error.message);
                payBtn.textContent = originalText;
                payBtn.disabled = false;
            }
        });

        function showPaymentDetails(paymentId) {
            const payment = allPayments.find(p => p.payment_id === paymentId);
            if (payment) {
                showPaymentModal(payment);
                
                // Start polling jika status pending
                if (payment.status === 'pending') {
                    startPolling(paymentId);
                }
            } else {
                location.reload();
            }
        }

        function showPaymentModal(payment) {
            const modal = document.getElementById('payment-modal');
            const modalBody = document.getElementById('modal-body');
            
            let content = `
                <div class="payment-info">
                    <h4>${payment.tagihan}</h4>
                    <p><strong>Jumlah:</strong> Rp ${parseInt(payment.nominal).toLocaleString('id-ID')}</p>
                    <p><strong>Metode:</strong> ${payment.method_name}</p>
                    <p><strong>Berakhir:</strong> ${new Date(payment.expired_at).toLocaleString('id-ID')}</p>
                    <p style="font-size: 12px; color: #666; margin-top: 15px;">
                        💡 Setelah transfer, pembayaran akan otomatis terdeteksi dan halaman akan refresh
                    </p>
                </div>
            `;

            if (payment.pay_code && !payment.qr_string) {
                content += `
                    <div class="va-number">
                        ${payment.pay_code}
                    </div>
                    <p style="text-align: center; font-size: 12px; color: #666;">
                        Nomor Virtual Account
                    </p>
                    <button class="btn btn-primary btn-small" onclick="copyToClipboard('${payment.pay_code}')" style="margin: 10px 0;">
                        Salin Nomor
                    </button>
                `;
            }

            if (payment.qr_string) {
                content += `
                    <div class="qr-container">
                        <canvas id="qr-canvas"></canvas>
                        <p style="font-size: 12px; color: #666; margin-top: 10px;">
                            Scan QR Code untuk membayar
                        </p>
                    </div>
                `;
            }

            if (payment.pay_url) {
                content += `
                    <a href="${payment.pay_url}" target="_blank" class="btn btn-primary btn-full" style="margin: 10px 0;">
                        Bayar Sekarang
                    </a>
                `;
            }

            if (payment.instructions && payment.instructions.length > 0) {
                content += `
                    <div class="instruction-list">
                        <strong>Cara Pembayaran:</strong>
                        <ol>
                `;
                
                payment.instructions.forEach(instruction => {
                    if (typeof instruction === 'object' && instruction.title) {
                        content += `<li><strong>${instruction.title}</strong>`;
                        if (instruction.steps && instruction.steps.length > 0) {
                            instruction.steps.forEach(step => {
                                content += `<br>- ${step}`;
                            });
                        }
                        content += `</li>`;
                    } else {
                        content += `<li>${instruction}</li>`;
                    }
                });
                
                content += `
                        </ol>
                    </div>
                `;
            }

            if (payment.status === 'pending') {
                content += `
                    <button class="btn btn-primary btn-full" onclick="checkPaymentStatus('${payment.payment_id}')" style="margin-top: 15px;">
                        Periksa Status Manual
                    </button>
                `;
            }

            modalBody.innerHTML = content;
            
            if (payment.qr_string) {
                setTimeout(() => {
                    generateQRCode(payment.qr_string);
                }, 100);
            }
            
            modal.style.display = 'block';
        }

        async function showReceipt(paymentId) {
            console.log('showReceipt called with ID:', paymentId);
            
            // Find payment data from allPayments array (already loaded)
            const payment = allPayments.find(p => p.payment_id === paymentId);
            
            if (!payment) {
                alert('Data pembayaran tidak ditemukan');
                console.error('Payment not found:', paymentId);
                return;
            }
            
            console.log('Payment data:', payment);
            
            // Generate receipt HTML directly from client-side data
            const receiptHTML = generateReceiptHTMLClient(payment);
            
            // Show modal
            const modal = document.getElementById('receipt-modal');
            const receiptBody = document.getElementById('receipt-body');
            
            receiptBody.innerHTML = receiptHTML;
            modal.style.display = 'block';
        }
        
        function generateReceiptHTMLClient(payment) {
            // Format tanggal
            const tanggalBayar = new Date(payment.waktu_input);
            const bulanIndo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
                              'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            
            const tanggalStr = `${tanggalBayar.getDate()} ${bulanIndo[tanggalBayar.getMonth()]} ${tanggalBayar.getFullYear()}, ${String(tanggalBayar.getHours()).padStart(2,'0')}:${String(tanggalBayar.getMinutes()).padStart(2,'0')}`;
            
            const now = new Date();
            const tanggalCetak = `${now.getDate()} ${bulanIndo[now.getMonth()]} ${now.getFullYear()}, ${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}:${String(now.getSeconds()).padStart(2,'0')}`;
            
            // Determine source label
            let sourceLabel = 'Online Payment';
            if (payment.source === 'manual') {
                sourceLabel = 'Pembayaran Tunai/Manual';
            } else if (payment.source === 'json') {
                sourceLabel = 'Pembayaran Manual (Arsip)';
            }
            
            const nominal = parseFloat(payment.nominal) || 0;
            
            return `
                <div class="receipt-paper">
                    <div class="receipt-header">
                        <img src="https://ibnuzubair.ypi-khairaummah.sch.id/logo.jpeg" alt="Logo YPI" class="receipt-logo" onerror="this.style.display='none'">
                        <h1>Mahad Ibnu Zuabair</h1>
                        <p class="school-address">Mahad Ibnu Zubair</p>
                        <p class="school-address">Telp: (0778) 123456</p>
                        <div class="divider"></div>
                        <h2 class="receipt-title">BUKTI PEMBAYARAN</h2>
                    </div>
                    
                    <div class="receipt-body">
                        <table class="receipt-table">
                            <tr>
                                <td class="label">No. Transaksi</td>
                                <td class="colon">:</td>
                                <td class="value">${escapeHtml(payment.payment_id)}</td>
                            </tr>
                            <tr>
                                <td class="label">Tanggal Bayar</td>
                                <td class="colon">:</td>
                                <td class="value">${tanggalStr} WIB</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="section-divider"></td>
                            </tr>
                            <tr>
                                <td class="label">Nama Siswa</td>
                                <td class="colon">:</td>
                                <td class="value">${escapeHtml(payment.name)}</td>
                            </tr>
                            <tr>
                                <td class="label">Kelas</td>
                                <td class="colon">:</td>
                                <td class="value">${escapeHtml(payment.class)}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="section-divider"></td>
                            </tr>
                            <tr>
                                <td class="label">Jenis Tagihan</td>
                                <td class="colon">:</td>
                                <td class="value">${escapeHtml(payment.tagihan)}</td>
                            </tr>
                            <tr>
                                <td class="label">Jumlah Bayar</td>
                                <td class="colon">:</td>
                                <td class="value amount">Rp ${nominal.toLocaleString('id-ID')}</td>
                            </tr>
                            <tr>
                                <td class="label">Metode Bayar</td>
                                <td class="colon">:</td>
                                <td class="value">${escapeHtml(payment.method_name)}</td>
                            </tr>
                            <tr>
                                <td class="label">Dibayar Melalui</td>
                                <td class="colon">:</td>
                                <td class="value">${sourceLabel}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="section-divider"></td>
                            </tr>
                            <tr>
                                <td class="label">Status</td>
                                <td class="colon">:</td>
                                <td class="value"><span class="status-paid">LUNAS</span></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="receipt-footer">
                        <div class="divider"></div>
                        <p class="footer-note">Bukti pembayaran ini sah dan dikeluarkan oleh sistem</p>
                        <p class="footer-note">YMahad Ibnu Zubar.</p>
                        <p class="print-time">Dicetak pada: ${tanggalCetak} WIB</p>
                        <p class="thank-you">*** Terima Kasih ***</p>
                    </div>
                    
                    <div class="watermark">LUNAS</div>
                </div>
            `;
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function generateQRCode(text) {
            const canvas = document.getElementById('qr-canvas');
            if (!canvas) return;
            
            // Prioritas: gunakan library QRious jika tersedia
            if (typeof QRious !== 'undefined') {
                try {
                    new QRious({
                        element: canvas,
                        value: text,
                        size: 250,
                        background: 'white',
                        foreground: 'black',
                        level: 'H' // High error correction untuk QRIS
                    });
                    return;
                } catch (error) {
                    console.error('QRious error:', error);
                }
            }
            
            // Fallback ke API QR Generator
            const qrContainer = canvas.parentElement;
            canvas.style.display = 'none';
            
            const img = document.createElement('img');
            img.src = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(text)}&margin=10`;
            img.alt = 'QR Code Pembayaran';
            img.style.maxWidth = '250px';
            img.style.height = 'auto';
            img.style.border = '1px solid #dee2e6';
            img.style.borderRadius = '8px';
            img.style.margin = '10px auto';
            img.style.display = 'block';
            
            img.onerror = function() {
                this.style.display = 'none';
                const errorMsg = document.createElement('p');
                errorMsg.textContent = 'Gagal memuat QR Code. Gunakan metode pembayaran alternatif.';
                errorMsg.style.color = '#dc3545';
                errorMsg.style.textAlign = 'center';
                errorMsg.style.padding = '20px';
                qrContainer.appendChild(errorMsg);
            };
            
            qrContainer.appendChild(img);
        }

        async function checkPaymentStatus(paymentId) {
            const formData = new FormData();
            formData.append('action', 'check_payment');
            formData.append('payment_id', paymentId);
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (result.status === 'berhasil') {
                        stopPolling();
                        alert('Pembayaran berhasil! Halaman akan dimuat ulang.');
                        location.reload();
                    } else if (result.status === 'pending') {
                        alert('Pembayaran masih dalam proses. Sistem akan otomatis memperbarui status.');
                    } else {
                        stopPolling();
                        alert('Status: ' + result.message);
                        location.reload();
                    }
                } else {
                    alert('Gagal memeriksa status pembayaran');
                }
            } catch (error) {
                console.error('Error checking payment:', error);
                alert('Terjadi kesalahan saat memeriksa status');
            }
        }

        function copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    showCopyNotification();
                }).catch(() => {
                    fallbackCopyToClipboard(text);
                });
            } else {
                fallbackCopyToClipboard(text);
            }
        }

        function fallbackCopyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            
            try {
                document.execCommand('copy');
                showCopyNotification();
            } catch (err) {
                alert('Gagal menyalin. Silakan salin manual: ' + text);
            }
            
            document.body.removeChild(textArea);
        }

        function showCopyNotification() {
            const notification = document.getElementById('copy-notification');
            notification.style.display = 'block';
            setTimeout(() => {
                notification.style.display = 'none';
            }, 2000);
        }

        function closeModal() {
            stopPolling(); // Stop polling saat modal ditutup
            document.getElementById('payment-modal').style.display = 'none';
        }

        function closeReceiptModal() {
            document.getElementById('receipt-modal').style.display = 'none';
        }

        function printReceipt() {
            window.print();
        }

        window.onclick = function(event) {
            const paymentModal = document.getElementById('payment-modal');
            const receiptModal = document.getElementById('receipt-modal');
            if (event.target === paymentModal) {
                closeModal();
            }
            if (event.target === receiptModal) {
                closeReceiptModal();
            }
        }

        // Cleanup polling saat page unload
        window.addEventListener('beforeunload', function() {
            stopPolling();
        });

        document.addEventListener('DOMContentLoaded', function() {
            updateTotal();
            
            console.log('Total payments loaded:', allPayments.length);
            
            // Auto-start polling jika ada pending payment di halaman saat ini
            const urlParams = new URLSearchParams(window.location.search);
            const currentTab = urlParams.get('tab');
            
            if (currentTab === 'pending') {
                // Jika ada pending payment, cek otomatis setelah 2 detik
                setTimeout(() => {
                    const firstPendingBtn = document.querySelector('.payment-item .btn-primary');
                    if (firstPendingBtn) {
                        console.log('Auto-checking first pending payment...');
                    }
                }, 2000);
            }
        });

        // Shopping cart functionality
        let cart = [];
        
        function addToCart(id, nama, harga, stok) {
            const existing = cart.find(item => item.id === id);
            if (existing) {
                if (existing.jumlah >= stok) {
                    alert('Stok tidak mencukupi!');
                    return;
                }
                existing.jumlah++;
            } else {
                cart.push({id: id, nama: nama, harga: harga, jumlah: 1, stok: stok});
            }
            updateCartDisplay();
        }
        
        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            updateCartDisplay();
        }
        
        function updateQuantity(id, delta) {
            const item = cart.find(item => item.id === id);
            if (item) {
                item.jumlah += delta;
                if (item.jumlah <= 0) {
                    removeFromCart(id);
                } else if (item.jumlah > item.stok) {
                    item.jumlah = item.stok;
                    alert('Stok tidak mencukupi!');
                }
                updateCartDisplay();
            }
        }
        
        function updateCartDisplay() {
            const cartSection = document.getElementById('cart-section');
            const cartItems = document.getElementById('cart-items');
            const cartTotal = document.getElementById('cart-total');
            
            if (cart.length === 0) {
                cartSection.style.display = 'none';
                return;
            }
            
            cartSection.style.display = 'block';
            let html = '<table style="width: 100%; border-collapse: collapse; font-size: 14px;"><thead><tr><th style="padding: 12px 8px; border: 1px solid #e9ecef; text-align: left; background: #f8f9fa; font-weight: 600;">Barang</th><th style="padding: 12px 8px; border: 1px solid #e9ecef; text-align: left; background: #f8f9fa; font-weight: 600;">Harga</th><th style="padding: 12px 8px; border: 1px solid #e9ecef; text-align: left; background: #f8f9fa; font-weight: 600;">Jumlah</th><th style="padding: 12px 8px; border: 1px solid #e9ecef; text-align: left; background: #f8f9fa; font-weight: 600;">Subtotal</th><th style="padding: 12px 8px; border: 1px solid #e9ecef; text-align: left; background: #f8f9fa; font-weight: 600;">Aksi</th></tr></thead><tbody>';
            let total = 0;
            
            cart.forEach(item => {
                const subtotal = item.harga * item.jumlah;
                total += subtotal;
                html += `
                    <tr>
                        <td style="padding: 12px 8px; border: 1px solid #e9ecef;">${item.nama}</td>
                        <td style="padding: 12px 8px; border: 1px solid #e9ecef;">Rp ${item.harga.toLocaleString('id-ID')}</td>
                        <td style="padding: 12px 8px; border: 1px solid #e9ecef;">
                            <button class="btn btn-primary btn-small" onclick="updateQuantity(${item.id}, -1)">-</button>
                            ${item.jumlah}
                            <button class="btn btn-primary btn-small" onclick="updateQuantity(${item.id}, 1)">+</button>
                        </td>
                        <td style="padding: 12px 8px; border: 1px solid #e9ecef;">Rp ${subtotal.toLocaleString('id-ID')}</td>
                        <td style="padding: 12px 8px; border: 1px solid #e9ecef;"><button class="btn btn-danger btn-small" onclick="removeFromCart(${item.id})">Hapus</button></td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            cartItems.innerHTML = html;
            cartTotal.innerHTML = `<strong style="font-size: 20px;">Total: Rp ${total.toLocaleString('id-ID')}</strong>`;
        }
        
        function checkout() {
            if (cart.length === 0) {
                alert('Keranjang belanja kosong!');
                return;
            }
            
            if (confirm('Apakah Anda yakin ingin checkout? Anda akan diarahkan ke halaman pembayaran.')) {
                // Redirect to belanja checkout page
                window.location.href = 'belanja_checkout.php?cart=' + encodeURIComponent(JSON.stringify(cart));
            }
        }
        
        function printVoucher(code) {
            window.open('cetak_voucher.php?code=' + encodeURIComponent(code), '_blank');
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>