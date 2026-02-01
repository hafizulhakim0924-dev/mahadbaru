<?php
session_start();

// Database Config
$servername = "localhost";
$username = "ypikhair_admin";
$password = "hakim123123123";
$dbname = "ypikhair_datautama";

// Check login
if (!isset($_SESSION['student']['id']) && !isset($_SESSION['user_id'])) {
    header('Location: login_siswa.php');
    exit;
}

$student_id = isset($_SESSION['student']['id'])
    ? intval($_SESSION['student']['id'])
    : intval($_SESSION['user_id']);

$order_id = $_GET['order_id'] ?? '';

if (empty($order_id)) {
    header('Location: profile.php?tab=belanja');
    exit;
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Get pesanan data
$stmt = $conn->prepare("SELECT * FROM pesanan_belanja WHERE order_id = ? AND student_id = ?");
$stmt->bind_param("si", $order_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();
$pesanan = $result->fetch_assoc();
$stmt->close();

if (!$pesanan) {
    header('Location: profile.php?tab=belanja');
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Belanja</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card h2 { color: #2d3748; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea; }
        .payment-info { text-align: center; margin-bottom: 20px; }
        .va-number { font-size: 20px; font-weight: bold; font-family: monospace; background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0; border: 2px dashed #667eea; }
        .qr-container { text-align: center; margin: 20px 0; }
        .btn { width: 100%; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
    </style>
</head>
<body>
    <div class="header">
        <h1>Pembayaran Belanja</h1>
        <p>Order ID: <?= htmlspecialchars($order_id) ?></p>
    </div>

    <div class="container">
        <div class="card">
            <h2>Instruksi Pembayaran</h2>
            <div class="payment-info">
                <p><strong>Total Pembayaran:</strong> Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></p>
                <p><strong>Metode:</strong> <?= htmlspecialchars($pesanan['method_name']) ?></p>
                <p><strong>Berakhir:</strong> <?= date('d/m/Y H:i', strtotime($pesanan['expired_at'])) ?></p>
            </div>

            <?php if ($pesanan['pay_code']): ?>
                <div class="va-number">
                    <?= htmlspecialchars($pesanan['pay_code']) ?>
                </div>
                <p style="text-align: center; font-size: 12px; color: #666;">
                    Nomor Virtual Account
                </p>
            <?php endif; ?>

            <?php if ($pesanan['qr_string']): ?>
                <div class="qr-container">
                    <canvas id="qr-canvas"></canvas>
                    <p style="font-size: 12px; color: #666; margin-top: 10px;">
                        Scan QR Code untuk membayar
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($pesanan['pay_url']): ?>
                <a href="<?= htmlspecialchars($pesanan['pay_url']) ?>" target="_blank" class="btn">
                    Bayar Sekarang
                </a>
            <?php endif; ?>

            <p style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 8px; color: #856404; font-size: 14px;">
                <strong>Catatan:</strong> Setelah pembayaran berhasil, Anda akan menerima voucher yang dapat ditukarkan ke admin kampus untuk mengambil barang.
            </p>
        </div>
    </div>

    <script>
        <?php if ($pesanan['qr_string']): ?>
        // Generate QR Code
        setTimeout(() => {
            if (typeof QRious !== 'undefined') {
                new QRious({
                    element: document.getElementById('qr-canvas'),
                    value: '<?= addslashes($pesanan['qr_string']) ?>',
                    size: 250,
                    background: 'white',
                    foreground: 'black',
                    level: 'H'
                });
            } else {
                const canvas = document.getElementById('qr-canvas');
                const img = document.createElement('img');
                img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent('<?= addslashes($pesanan['qr_string']) ?>');
                img.style.maxWidth = '250px';
                img.style.height = 'auto';
                canvas.parentElement.appendChild(img);
                canvas.style.display = 'none';
            }
        }, 100);
        <?php endif; ?>

        // Auto check payment status
        setInterval(() => {
            fetch('check_payment_belanja.php?order_id=<?= urlencode($order_id) ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.status === 'berhasil') {
                        alert('Pembayaran berhasil! Anda akan diarahkan ke halaman voucher.');
                        window.location.href = 'profile.php?tab=voucher';
                    }
                })
                .catch(err => console.error('Error:', err));
        }, 5000);
    </script>
</body>
</html>

