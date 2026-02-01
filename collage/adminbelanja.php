<?php
session_start();

// Database Config
$servername = "localhost";
$username = "ypikhair_admin";
$password = "hakim123123123";
$dbname = "ypikhair_datautama";

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    // Handle login
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password_input = trim($_POST['password'] ?? '');
        
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        if (!$conn->connect_error) {
            $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? AND password = ?");
            $stmt->bind_param("ss", $username, $password_input);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $admin = $result->fetch_assoc();
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_nama'] = $admin['nama'];
                header('Location: adminbelanja.php');
                exit;
            } else {
                $error = "Username atau password salah!";
            }
            $stmt->close();
            $conn->close();
        }
    }
    
    // Show login form
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Admin - Kelola Belanja</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
            .container { background: white; padding: 40px; border-radius: 12px; width: 100%; max-width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); }
            h1 { color: #667eea; margin-bottom: 10px; text-align: center; }
            .subtitle { text-align: center; color: #666; margin-bottom: 30px; }
            .form-group { margin-bottom: 20px; }
            label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; }
            input { width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; }
            input:focus { outline: none; border-color: #667eea; }
            .btn { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; }
            .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
            .error { background: #fee; color: #c33; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Login Admin</h1>
            <p class="subtitle">Kelola Barang Belanja</p>
            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn">Masuk</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Admin is logged in
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$admin_id = $_SESSION['admin_id'];
$admin_nama = $_SESSION['admin_nama'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'tambah_barang') {
        $nama_barang = trim($_POST['nama_barang']);
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $harga = floatval($_POST['harga']);
        $stok = intval($_POST['stok']);
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("INSERT INTO barang (nama_barang, deskripsi, harga, stok, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdis", $nama_barang, $deskripsi, $harga, $stok, $status);
        
        if ($stmt->execute()) {
            $success = "Barang berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan barang: " . $stmt->error;
        }
        $stmt->close();
    } elseif ($_POST['action'] == 'edit_barang') {
        $id = intval($_POST['id']);
        $nama_barang = trim($_POST['nama_barang']);
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $harga = floatval($_POST['harga']);
        $stok = intval($_POST['stok']);
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE barang SET nama_barang = ?, deskripsi = ?, harga = ?, stok = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssdisi", $nama_barang, $deskripsi, $harga, $stok, $status, $id);
        
        if ($stmt->execute()) {
            $success = "Barang berhasil diupdate!";
        } else {
            $error = "Gagal mengupdate barang: " . $stmt->error;
        }
        $stmt->close();
    } elseif ($_POST['action'] == 'hapus_barang') {
        $id = intval($_POST['id']);
        
        $stmt = $conn->prepare("DELETE FROM barang WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = "Barang berhasil dihapus!";
        } else {
            $error = "Gagal menghapus barang: " . $stmt->error;
        }
        $stmt->close();
    } elseif ($_POST['action'] == 'redeem_voucher') {
        $voucher_code = trim($_POST['voucher_code']);
        
        $stmt = $conn->prepare("SELECT * FROM voucher_pembayaran WHERE voucher_code = ? AND status = 'pending'");
        $stmt->bind_param("s", $voucher_code);
        $stmt->execute();
        $result = $stmt->get_result();
        $voucher = $result->fetch_assoc();
        $stmt->close();
        
        if ($voucher) {
            // Update voucher status
            $stmt = $conn->prepare("UPDATE voucher_pembayaran SET status = 'redeemed', redeemed_at = NOW(), redeemed_by = ? WHERE id = ?");
            $stmt->bind_param("ii", $admin_id, $voucher['id']);
            
            if ($stmt->execute()) {
                $success = "Voucher berhasil diredeem!";
            } else {
                $error = "Gagal redeem voucher: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Voucher tidak ditemukan atau sudah diredeem!";
        }
    }
}

// Get all barang
$stmt = $conn->prepare("SELECT * FROM barang ORDER BY nama_barang ASC");
$stmt->execute();
$result = $stmt->get_result();
$barang_list = [];
while ($row = $result->fetch_assoc()) {
    $barang_list[] = $row;
}
$stmt->close();

// Get pending vouchers
$stmt = $conn->prepare("
    SELECT v.*, s.name as student_name, s.class, p.total_harga 
    FROM voucher_pembayaran v
    LEFT JOIN students s ON v.student_id = s.id
    LEFT JOIN pesanan_belanja p ON v.pesanan_id = p.id
    WHERE v.status = 'pending'
    ORDER BY v.created_at DESC
");
$stmt->execute();
$result = $stmt->get_result();
$pending_vouchers = [];
while ($row = $result->fetch_assoc()) {
    $pending_vouchers[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Kelola Belanja</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; }
        .header h1 { margin-bottom: 5px; }
        .header p { opacity: 0.9; }
        .logout { float: right; background: rgba(255,255,255,0.2); color: white; padding: 8px 16px; text-decoration: none; border-radius: 20px; font-size: 14px; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card h2 { color: #2d3748; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #2d3748; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 14px; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #667eea; }
        .btn { padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; margin: 5px; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        .btn-danger { background: #e53e3e; }
        .btn-success { background: #48bb78; }
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        table th { background: #667eea; color: white; padding: 12px; text-align: left; font-weight: 600; }
        table td { padding: 12px; border-bottom: 1px solid #e2e8f0; }
        table tr:hover { background: #f7fafc; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #c6f6d5; color: #22543d; }
        .badge-warning { background: #feebc8; color: #7c2d12; }
        .badge-danger { background: #fed7d7; color: #742a2a; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab-btn { padding: 10px 20px; background: #e2e8f0; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; }
        .tab-btn.active { background: #667eea; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <div class="header">
        <a href="?logout=1" class="logout">Logout</a>
        <h1>Admin - Kelola Belanja</h1>
        <p>Admin: <?= htmlspecialchars($admin_nama) ?></p>
    </div>

    <div class="container">
        <?php if (isset($success)): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('barang')">Kelola Barang</button>
            <button class="tab-btn" onclick="showTab('voucher')">Redeem Voucher</button>
        </div>

        <!-- Tab Kelola Barang -->
        <div id="tab-barang" class="tab-content active">
            <div class="card">
                <h2>Tambah Barang Baru</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="tambah_barang">
                    
                    <div class="form-group">
                        <label>Nama Barang</label>
                        <input type="text" name="nama_barang" required>
                    </div>

                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Harga</label>
                        <input type="number" name="harga" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label>Stok</label>
                        <input type="number" name="stok" min="0" required>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" required>
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>

                    <button type="submit" class="btn">Tambah Barang</button>
                </form>
            </div>

            <div class="card">
                <h2>Daftar Barang</h2>
                <?php if (empty($barang_list)): ?>
                    <p style="text-align: center; color: #666; padding: 40px;">
                        Belum ada barang yang terdaftar.
                    </p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Barang</th>
                                <th>Deskripsi</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($barang_list as $barang): ?>
                                <tr>
                                    <td><?= htmlspecialchars($barang['nama_barang']) ?></td>
                                    <td><?= htmlspecialchars($barang['deskripsi'] ?? '-') ?></td>
                                    <td>Rp <?= number_format($barang['harga'], 0, ',', '.') ?></td>
                                    <td><?= $barang['stok'] ?></td>
                                    <td>
                                        <span class="badge <?= $barang['status'] == 'aktif' ? 'badge-success' : 'badge-danger' ?>">
                                            <?= ucfirst($barang['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-small" onclick="editBarang(<?= htmlspecialchars(json_encode($barang)) ?>)">Edit</button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin hapus barang ini?')">
                                            <input type="hidden" name="action" value="hapus_barang">
                                            <input type="hidden" name="id" value="<?= $barang['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-small">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Redeem Voucher -->
        <div id="tab-voucher" class="tab-content">
            <div class="card">
                <h2>Redeem Voucher Pembayaran</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="redeem_voucher">
                    
                    <div class="form-group">
                        <label>Kode Voucher</label>
                        <input type="text" name="voucher_code" required placeholder="Masukkan kode voucher">
                    </div>

                    <button type="submit" class="btn btn-success">Redeem Voucher</button>
                </form>
            </div>

            <div class="card">
                <h2>Daftar Voucher Pending</h2>
                <?php if (empty($pending_vouchers)): ?>
                    <p style="text-align: center; color: #666; padding: 40px;">
                        Tidak ada voucher yang pending.
                    </p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Kode Voucher</th>
                                <th>Siswa</th>
                                <th>Total Pembayaran</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_vouchers as $voucher): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($voucher['voucher_code']) ?></strong></td>
                                    <td><?= htmlspecialchars($voucher['student_name']) ?> (<?= htmlspecialchars($voucher['class']) ?>)</td>
                                    <td>Rp <?= number_format($voucher['total_harga'], 0, ',', '.') ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($voucher['created_at'])) ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="redeem_voucher">
                                            <input type="hidden" name="voucher_code" value="<?= htmlspecialchars($voucher['voucher_code']) ?>">
                                            <button type="submit" class="btn btn-success btn-small">Redeem</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            
            document.getElementById('tab-' + tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        function editBarang(barang) {
            if (confirm('Edit barang: ' + barang.nama_barang + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="edit_barang">
                    <input type="hidden" name="id" value="${barang.id}">
                    <div class="form-group">
                        <label>Nama Barang</label>
                        <input type="text" name="nama_barang" value="${barang.nama_barang}" required>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" rows="3">${barang.deskripsi || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Harga</label>
                        <input type="number" name="harga" step="0.01" value="${barang.harga}" required>
                    </div>
                    <div class="form-group">
                        <label>Stok</label>
                        <input type="number" name="stok" value="${barang.stok}" required>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" required>
                            <option value="aktif" ${barang.status == 'aktif' ? 'selected' : ''}>Aktif</option>
                            <option value="nonaktif" ${barang.status == 'nonaktif' ? 'selected' : ''}>Nonaktif</option>
                        </select>
                    </div>
                    <button type="submit" class="btn">Update Barang</button>
                `;
                
                const modal = document.createElement('div');
                modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000;';
                const modalContent = document.createElement('div');
                modalContent.style.cssText = 'background: white; padding: 30px; border-radius: 10px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;';
                modalContent.innerHTML = '<h2 style="margin-bottom: 20px;">Edit Barang</h2>';
                modalContent.appendChild(form);
                modal.appendChild(modalContent);
                document.body.appendChild(modal);
                
                modal.onclick = function(e) {
                    if (e.target === modal) {
                        document.body.removeChild(modal);
                    }
                };
            }
        }
    </script>
</body>
</html>
<?php
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: adminbelanja.php');
    exit;
}
?>

