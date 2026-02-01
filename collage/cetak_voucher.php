<?php
session_start();

// Database Config
$servername = "localhost";
$username = "ypikhair_admin";
$password = "hakim123123123";
$dbname = "ypikhair_datautama";

$voucher_code = $_GET['code'] ?? '';

if (empty($voucher_code)) {
    die('Kode voucher tidak valid');
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Get voucher data
$stmt = $conn->prepare("
    SELECT v.*, s.name as student_name, s.class, p.total_harga, p.order_id, p.created_at as pesanan_date
    FROM voucher_pembayaran v
    LEFT JOIN students s ON v.student_id = s.id
    LEFT JOIN pesanan_belanja p ON v.pesanan_id = p.id
    WHERE v.voucher_code = ?
");
$stmt->bind_param("s", $voucher_code);
$stmt->execute();
$result = $stmt->get_result();
$voucher = $result->fetch_assoc();
$stmt->close();

if (!$voucher) {
    die('Voucher tidak ditemukan');
}

// Get detail pesanan
$stmt = $conn->prepare("
    SELECT d.*, b.nama_barang
    FROM detail_pesanan d
    LEFT JOIN barang b ON d.barang_id = b.id
    WHERE d.pesanan_id = ?
");
$stmt->bind_param("i", $voucher['pesanan_id']);
$stmt->execute();
$result = $stmt->get_result();
$detail_items = [];
while ($row = $result->fetch_assoc()) {
    $detail_items[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Voucher</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', Courier, monospace; background: white; padding: 20px; }
        .voucher-paper { max-width: 400px; margin: 0 auto; padding: 30px; border: 2px dashed #333; }
        .voucher-header { text-align: center; margin-bottom: 20px; }
        .voucher-header h1 { font-size: 20px; margin-bottom: 10px; }
        .voucher-code { text-align: center; font-size: 24px; font-weight: bold; padding: 15px; background: #f0f0f0; border: 2px solid #333; margin: 20px 0; letter-spacing: 3px; }
        .voucher-body { margin: 20px 0; }
        .voucher-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .voucher-table td { padding: 5px 0; }
        .voucher-table .label { width: 40%; }
        .voucher-table .value { font-weight: bold; }
        .divider { border-top: 2px solid #333; margin: 15px 0; }
        .items-list { margin: 15px 0; }
        .items-list table { width: 100%; border-collapse: collapse; font-size: 11px; }
        .items-list table th, .items-list table td { padding: 5px; border: 1px solid #333; }
        .footer { text-align: center; margin-top: 30px; font-size: 11px; }
        .status-badge { display: inline-block; padding: 5px 15px; background: #333; color: white; border-radius: 5px; font-weight: bold; }
        @media print {
            body { padding: 0; }
            .voucher-paper { border: none; }
            button { display: none; }
        }
    </style>
</head>
<body>
    <div class="voucher-paper">
        <div class="voucher-header">
            <h1>VOUCHER PEMBAYARAN</h1>
            <p>Mahad Ibnu Zubair</p>
        </div>

        <div class="voucher-code">
            <?= htmlspecialchars($voucher_code) ?>
        </div>

        <div class="voucher-body">
            <table class="voucher-table">
                <tr>
                    <td class="label">Nama Siswa:</td>
                    <td class="value"><?= htmlspecialchars($voucher['student_name']) ?></td>
                </tr>
                <tr>
                    <td class="label">Kelas:</td>
                    <td class="value"><?= htmlspecialchars($voucher['class']) ?></td>
                </tr>
                <tr>
                    <td class="label">Order ID:</td>
                    <td class="value"><?= htmlspecialchars($voucher['order_id']) ?></td>
                </tr>
                <tr>
                    <td class="label">Total Pembayaran:</td>
                    <td class="value">Rp <?= number_format($voucher['total_harga'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td class="label">Tanggal:</td>
                    <td class="value"><?= date('d/m/Y H:i', strtotime($voucher['pesanan_date'])) ?></td>
                </tr>
            </table>

            <div class="divider"></div>

            <div class="items-list">
                <strong>Barang yang Dibeli:</strong>
                <table>
                    <thead>
                        <tr>
                            <th>Barang</th>
                            <th>Jumlah</th>
                            <th>Harga</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detail_items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['nama_barang']) ?></td>
                                <td><?= $item['jumlah'] ?></td>
                                <td>Rp <?= number_format($item['harga_satuan'], 0, ',', '.') ?></td>
                                <td>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="divider"></div>

            <p style="text-align: center; margin: 20px 0;">
                <span class="status-badge">
                    <?= $voucher['status'] == 'redeemed' ? 'SUDAH DITUKARKAN' : 'BELUM DITUKARKAN' ?>
                </span>
            </p>

            <p style="font-size: 11px; text-align: center; margin-top: 20px;">
                <strong>Catatan:</strong> Tunjukkan voucher ini ke admin kampus untuk menukarkan barang yang dibeli.
            </p>
        </div>

        <div class="footer">
            <p>Voucher ini sah dan dikeluarkan oleh sistem</p>
            <p>Mahad Ibnu Zubair</p>
            <p>Dicetak: <?= date('d/m/Y H:i:s') ?></p>
        </div>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
            Cetak Voucher
        </button>
    </div>
</body>
</html>

