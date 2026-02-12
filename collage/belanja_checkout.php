<?php
session_start();

// Database Config
$servername = "localhost";
$username = "ypikhair_adminmahadzubair";
$password = "Hakim123!";
$dbname = "ypikhair_mahadzubair";

// Tripay Config
define('TRIPAY_API_KEY', 'ytprKupP1zxpZg6XeFBkpe6oJjrT7jaae1zROemR');
define('TRIPAY_PRIVATE_KEY', 'RlGRM-dPVm0-4gxYN-AakNR-pI3Li');
define('TRIPAY_MERCHANT_CODE', 'T47806');
define('TRIPAY_API_URL', 'https://tripay.co.id/api');

// Check login
if (!isset($_SESSION['student']['id']) && !isset($_SESSION['user_id'])) {
    header('Location: login_siswa.php');
    exit;
}

$student_id = isset($_SESSION['student']['id'])
    ? intval($_SESSION['student']['id'])
    : intval($_SESSION['user_id']);

// Get cart from URL or session
$cart_json = $_GET['cart'] ?? $_SESSION['cart'] ?? '[]';
$cart = json_decode($cart_json, true);

    if (empty($cart)) {
        header('Location: profile.php?tab=belanja');
        exit;
    }

// Save cart to session
$_SESSION['cart'] = $cart_json;

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Get student data
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

// Get barang details and calculate total
$total = 0;
$items = [];
foreach ($cart as $item) {
    $stmt = $conn->prepare("SELECT * FROM barang WHERE id = ? AND status = 'aktif'");
    $stmt->bind_param("i", $item['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $barang = $result->fetch_assoc();
    $stmt->close();
    
    if ($barang && $barang['stok'] >= $item['jumlah']) {
        $subtotal = $barang['harga'] * $item['jumlah'];
        $total += $subtotal;
        $items[] = [
            'barang' => $barang,
            'jumlah' => $item['jumlah'],
            'subtotal' => $subtotal
        ];
    }
}

// Get payment methods
function getTripayPaymentMethods() {
    $headers = [
        'Authorization: Bearer ' . TRIPAY_API_KEY,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, TRIPAY_API_URL . '/merchant/payment-channel');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if (isset($data['success']) && $data['success']) {
            return $data['data'];
        }
    }
    
    // Fallback
    return [
        ['code' => 'BRIVA', 'name' => 'BRI Virtual Account', 'type' => 'virtual_account'],
        ['code' => 'BNIVA', 'name' => 'BNI Virtual Account', 'type' => 'virtual_account'],
        ['code' => 'QRIS', 'name' => 'QRIS', 'type' => 'qr_code'],
    ];
}

$available_methods = getTripayPaymentMethods();

// Handle payment creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['method'])) {
    $method = $_POST['method'];
    
    // Create order
    $order_id = 'BLJ-' . $student_id . '-' . date('YmdHis') . '-' . rand(1000, 9999);
    
    // Create Tripay payment
    $privateKey = TRIPAY_PRIVATE_KEY;
    $merchantCode = TRIPAY_MERCHANT_CODE;
    $signature = hash_hmac('sha256', $merchantCode . $order_id . intval($total), $privateKey);
    
    $order_items = [];
    foreach ($items as $item) {
        $order_items[] = [
            'sku' => 'BRG' . $item['barang']['id'],
            'name' => $item['barang']['nama_barang'],
            'price' => intval($item['barang']['harga']),
            'quantity' => intval($item['jumlah'])
        ];
    }
    
    $tripay_data = [
        'method' => $method,
        'merchant_ref' => $order_id,
        'amount' => intval($total), // Pastikan integer
        'customer_name' => $student['name'],
        'customer_email' => ($student['phone_no'] ?? 'student') . '@example.com',
        'order_items' => $order_items,
        'return_url' => 'https://ypi-khairaummah.sch.id/profile.php?tab=voucher',
        'expired_time' => (time() + 3600),
        'signature' => $signature,
        'callback_url' => 'https://ypi-khairaummah.sch.id/callback.php'
    ];
    
    $headers = [
        'Authorization: Bearer ' . TRIPAY_API_KEY,
        'Content-Type: application/json'
    ];
    
    error_log('Belanja Tripay Request: ' . json_encode($tripay_data));
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, TRIPAY_API_URL . '/transaction/create');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($tripay_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    error_log('Belanja Tripay Response Code: ' . $httpCode);
    error_log('Belanja Tripay Response: ' . $response);
    if ($curl_error) {
        error_log('Belanja Tripay CURL Error: ' . $curl_error);
    }
    
    if ($httpCode == 200) {
        $tripay_response = json_decode($response, true);
        
        if (isset($tripay_response['success']) && $tripay_response['success']) {
            $tripay_result = $tripay_response['data'];
            
            // Save to database
            $stmt = $conn->prepare("
                INSERT INTO pesanan_belanja 
                (student_id, order_id, total_harga, status, tripay_ref, pay_code, pay_url, qr_string, method_code, method_name, expired_at) 
                VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $method_name = $tripay_result['payment_name'] ?? $method;
            $tripay_ref = $tripay_result['reference'];
            $pay_code = $tripay_result['pay_code'] ?? '';
            $pay_url = $tripay_result['checkout_url'] ?? '';
            $qr_string = $tripay_result['qr_string'] ?? '';
            $expired_at = date('Y-m-d H:i:s', $tripay_result['expired_time']);
            
            $stmt->bind_param("isssssssss", $student_id, $order_id, $total, $tripay_ref, $pay_code, $pay_url, $qr_string, $method, $method_name, $expired_at);
            $stmt->execute();
            $pesanan_id = $stmt->insert_id;
            $stmt->close();
            
            // Save detail pesanan
            foreach ($items as $item) {
                $stmt = $conn->prepare("
                    INSERT INTO detail_pesanan (pesanan_id, barang_id, jumlah, harga_satuan, subtotal) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("iiidd", $pesanan_id, $item['barang']['id'], $item['jumlah'], $item['barang']['harga'], $item['subtotal']);
                $stmt->execute();
                $stmt->close();
            }
            
            // Redirect to payment page
            header('Location: belanja_payment.php?order_id=' . $order_id);
            exit;
        } else {
            $error_msg = $tripay_response['message'] ?? 'Gagal membuat pembayaran';
            error_log('Belanja Tripay Error: ' . $error_msg);
            $error = "Gagal membuat pembayaran: " . $error_msg;
        }
    } else {
        $error_response = json_decode($response, true);
        $error_msg = $error_response['message'] ?? 'Koneksi ke payment gateway gagal';
        error_log('Belanja Tripay HTTP Error ' . $httpCode . ': ' . $error_msg);
        $error = "Gagal membuat pembayaran: " . $error_msg;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Belanja</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; }
        .header h1 { margin-bottom: 5px; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card h2 { color: #2d3748; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea; }
        table { width: 100%; border-collapse: collapse; }
        table th { background: #667eea; color: white; padding: 12px; text-align: left; }
        table td { padding: 12px; border-bottom: 1px solid #e2e8f0; }
        .total-box { background: #f7fafc; padding: 20px; border-radius: 8px; margin-top: 20px; text-align: right; }
        .total-box h3 { color: #2d3748; font-size: 24px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #2d3748; font-weight: 600; }
        .form-group select { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; }
        .btn { width: 100%; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Checkout Belanja</h1>
        <p><?= htmlspecialchars($student['name']) ?> - <?= htmlspecialchars($student['class']) ?></p>
    </div>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Detail Pesanan</h2>
            <table>
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th>Harga</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['barang']['nama_barang']) ?></td>
                            <td>Rp <?= number_format($item['barang']['harga'], 0, ',', '.') ?></td>
                            <td><?= $item['jumlah'] ?></td>
                            <td>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="total-box">
                <h3>Total: Rp <?= number_format($total, 0, ',', '.') ?></h3>
            </div>
        </div>

        <div class="card">
            <h2>Metode Pembayaran</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Pilih Metode Pembayaran</label>
                    <select name="method" required>
                        <option value="">-- Pilih Metode --</option>
                        <?php foreach ($available_methods as $method): ?>
                            <option value="<?= $method['code'] ?>"><?= $method['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn">Lanjutkan Pembayaran</button>
            </form>
        </div>
    </div>
</body>
</html>

