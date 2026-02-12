<?php
session_start();

// Database Config
$servername = "localhost";
$username = "ypikhair_admin";
$password = "hakim123123123";
$dbname = "ypikhair_datautama";

// Tripay Config
define('TRIPAY_API_KEY', 'Hfdqxnb7S2wPkU9AwghJkBoP7BwUmeZ5emhGC0rQ');
define('TRIPAY_API_URL', 'https://tripay.co.id/api');

header('Content-Type: application/json');

$order_id = $_GET['order_id'] ?? '';

if (empty($order_id)) {
    echo json_encode(['success' => false, 'message' => 'Order ID tidak valid']);
    exit;
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}
$conn->set_charset("utf8mb4");

// Get pesanan
$stmt = $conn->prepare("SELECT * FROM pesanan_belanja WHERE order_id = ?");
$stmt->bind_param("s", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$pesanan = $result->fetch_assoc();
$stmt->close();

if (!$pesanan) {
    echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan']);
    $conn->close();
    exit;
}

if ($pesanan['status'] === 'berhasil') {
    echo json_encode(['success' => true, 'status' => 'berhasil']);
    $conn->close();
    exit;
}

// Check with Tripay
$headers = [
    'Authorization: Bearer ' . TRIPAY_API_KEY,
    'Content-Type: application/json'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, TRIPAY_API_URL . '/transaction/detail?reference=' . urlencode($pesanan['tripay_ref']));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    $tripay_data = json_decode($response, true);
    
    if (isset($tripay_data['success']) && $tripay_data['success']) {
        $status = $tripay_data['data']['status'] ?? '';
        
        if ($status === 'PAID') {
            // Update status
            $stmt = $conn->prepare("UPDATE pesanan_belanja SET status = 'berhasil' WHERE order_id = ?");
            $stmt->bind_param("s", $order_id);
            $stmt->execute();
            $stmt->close();
            
            echo json_encode(['success' => true, 'status' => 'berhasil']);
        } else {
            echo json_encode(['success' => true, 'status' => 'pending']);
        }
    } else {
        echo json_encode(['success' => true, 'status' => 'pending']);
    }
} else {
    echo json_encode(['success' => true, 'status' => 'pending']);
}

$conn->close();
?>

