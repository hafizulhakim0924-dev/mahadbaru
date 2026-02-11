<?php
session_start();

// Database Config
$servername = "localhost";
$username = "ypikhair_admin";
$password = "hakim123123123";
$dbname = "ypikhair_datautama";

// Check if admin is logged in - redirect to unified login if not
if (!isset($_SESSION['admin_id'])) {
    header('Location: login_siswa.php');
    exit;
}

// Admin is logged in
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Initialize variables
$success = '';
$error = '';

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
    } elseif ($_POST['action'] == 'import_barang') {
        // Import bulk dari spreadsheet
        $data_paste = trim($_POST['data_paste'] ?? '');
        
        if (empty($data_paste)) {
            $error = "Data tidak boleh kosong!";
        } else {
            // Parse data yang di-paste
            $lines = explode("\n", $data_paste);
            $barang_data = [];
            $errors = [];
            $success_count = 0;
            
            foreach ($lines as $line_num => $line) {
                $line = trim($line);
                if (empty($line)) continue; // Skip baris kosong
                
                // Deteksi separator: tab atau koma
                if (strpos($line, "\t") !== false) {
                    $cols = explode("\t", $line);
                } else {
                    $cols = explode(",", $line);
                }
                
                // Bersihkan setiap kolom
                $cols = array_map('trim', $cols);
                
                // Skip header jika ada
                if ($line_num == 0 && (strtolower($cols[0]) == 'nama_barang' || strtolower($cols[0]) == 'nama' || strtolower($cols[0]) == 'barang')) {
                    continue;
                }
                
                // Validasi jumlah kolom (minimal 3: nama, harga, stok)
                if (count($cols) < 3) {
                    $errors[] = "Baris " . ($line_num + 1) . ": Data tidak lengkap (minimal: Nama, Harga, Stok)";
                    continue;
                }
                
                // Parse data
                $nama_barang = $cols[0] ?? '';
                $deskripsi = $cols[1] ?? '';
                $harga = isset($cols[2]) ? floatval(str_replace(['Rp', 'rp', '.', ','], '', $cols[2])) : 0;
                $stok = isset($cols[3]) ? intval($cols[3]) : 0;
                $status = isset($cols[4]) ? (strtolower(trim($cols[4])) == 'nonaktif' ? 'nonaktif' : 'aktif') : 'aktif';
                
                // Validasi
                if (empty($nama_barang)) {
                    $errors[] = "Baris " . ($line_num + 1) . ": Nama barang tidak boleh kosong";
                    continue;
                }
                
                if ($harga <= 0) {
                    $errors[] = "Baris " . ($line_num + 1) . ": Harga harus lebih dari 0";
                    continue;
                }
                
                if ($stok < 0) {
                    $errors[] = "Baris " . ($line_num + 1) . ": Stok tidak boleh negatif";
                    continue;
                }
                
                // Simpan untuk insert
                $barang_data[] = [
                    'nama_barang' => $nama_barang,
                    'deskripsi' => $deskripsi,
                    'harga' => $harga,
                    'stok' => $stok,
                    'status' => $status
                ];
            }
            
            // Insert ke database
            if (!empty($barang_data)) {
                $conn->begin_transaction();
                
                try {
                    $stmt = $conn->prepare("INSERT INTO barang (nama_barang, deskripsi, harga, stok, status) VALUES (?, ?, ?, ?, ?)");
                    
                    foreach ($barang_data as $data) {
                        $stmt->bind_param("ssdis", 
                            $data['nama_barang'],
                            $data['deskripsi'],
                            $data['harga'],
                            $data['stok'],
                            $data['status']
                        );
                        
                        if ($stmt->execute()) {
                            $success_count++;
                        } else {
                            $errors[] = "Gagal insert: " . $data['nama_barang'] . " - " . $stmt->error;
                        }
                    }
                    
                    $stmt->close();
                    
                    if ($success_count > 0) {
                        $conn->commit();
                        $success = "Berhasil mengimport $success_count barang!";
                        if (!empty($errors)) {
                            $error = "Beberapa data gagal diimport:<br>" . implode("<br>", array_map('htmlspecialchars', $errors));
                        }
                    } else {
                        $conn->rollback();
                        $error = "Tidak ada data yang berhasil diimport.<br>" . implode("<br>", array_map('htmlspecialchars', $errors));
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error: " . $e->getMessage();
                }
            } else {
                $error = "Tidak ada data valid untuk diimport.<br>" . implode("<br>", array_map('htmlspecialchars', $errors));
            }
        }
    }
}

// Get all barang
$barang_list = [];
try {
    $stmt = $conn->prepare("SELECT * FROM barang ORDER BY nama_barang ASC");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $barang_list[] = $row;
        }
        $stmt->close();
    } else {
        $error = "Error preparing query: " . $conn->error;
    }
} catch (Exception $e) {
    $error = "Error fetching barang: " . $e->getMessage();
}

// Get pending vouchers
$pending_vouchers = [];
try {
    $stmt = $conn->prepare("
        SELECT v.*, s.name as student_name, s.class, p.total_harga 
        FROM voucher_pembayaran v
        LEFT JOIN students s ON v.student_id = s.id
        LEFT JOIN pesanan_belanja p ON v.pesanan_id = p.id
        WHERE v.status = 'pending'
        ORDER BY v.created_at DESC
    ");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $pending_vouchers[] = $row;
        }
        $stmt->close();
    } else {
        // Table might not exist, set empty array
        $pending_vouchers = [];
    }
} catch (Exception $e) {
    // Table might not exist, set empty array
    $pending_vouchers = [];
}

// Get students with their tagihan grouped
$students_with_tagihan = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            s.id,
            s.name,
            s.class,
            COUNT(t.id) as jumlah_tagihan,
            GROUP_CONCAT(CONCAT(t.nama_tagihan, ' (Rp ', FORMAT(t.jumlah, 0), ')') SEPARATOR ', ') as list_tagihan,
            SUM(t.jumlah) as total_tagihan
        FROM students s
        LEFT JOIN tagihan t ON s.id = t.student_id
        GROUP BY s.id, s.name, s.class
        ORDER BY s.name ASC
    ");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $students_with_tagihan[] = $row;
        }
        $stmt->close();
    } else {
        // Table might not exist, set empty array
        $students_with_tagihan = [];
    }
} catch (Exception $e) {
    // Table might not exist, set empty array
    $students_with_tagihan = [];
}

// Don't close connection here, close it at the end of file
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
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('barang')">Kelola Barang</button>
            <button class="tab-btn" onclick="showTab('import')">Import Barang</button>
            <button class="tab-btn" onclick="showTab('voucher')">Redeem Voucher</button>
            <button class="tab-btn" onclick="showTab('tagihan')">Daftar Siswa & Tagihan</button>
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

        <!-- Tab Import Barang -->
        <div id="tab-import" class="tab-content">
            <div class="card">
                <h2>Import Barang dari Spreadsheet</h2>
                <p style="margin-bottom: 20px; color: #666;">
                    <strong>Cara penggunaan:</strong><br>
                    1. Copy data dari spreadsheet (Excel/Google Sheets)<br>
                    2. Paste di textarea di bawah ini<br>
                    3. Format: <strong>Nama Barang | Deskripsi | Harga | Stok | Status</strong><br>
                    4. Dipisah dengan <strong>Tab</strong> atau <strong>Koma (,)</strong><br>
                    5. Klik "Import Barang" untuk menyimpan ke database
                </p>
                
                <div style="background: #f0f4ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #667eea;">
                    <strong>Contoh Format:</strong><br>
                    <code style="display: block; margin-top: 8px; padding: 8px; background: white; border-radius: 4px;">
                        Buku Tulis	Buku tulis 38 lembar	5000	100	aktif<br>
                        Pensil 2B	Pensil 2B Faber Castell	3000	150	aktif<br>
                        Penghapus	Penghapus Faber Castell	2000	200	nonaktif
                    </code>
                    <small style="color: #666; display: block; margin-top: 8px;">
                        * Status: aktif atau nonaktif (default: aktif jika kosong)<br>
                        * Harga bisa dengan format: 5000, Rp 5.000, atau 5000.00
                    </small>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="import_barang">
                    
                    <div class="form-group">
                        <label>Paste Data dari Spreadsheet</label>
                        <textarea 
                            name="data_paste" 
                            rows="15" 
                            style="font-family: 'Courier New', monospace; font-size: 13px;"
                            placeholder="Paste data dari spreadsheet di sini...&#10;&#10;Contoh:&#10;Buku Tulis	Buku tulis 38 lembar	5000	100	aktif&#10;Pensil 2B	Pensil 2B Faber Castell	3000	150	aktif"
                            required
                        ></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">Import Barang</button>
                </form>
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

        <!-- Tab Daftar Siswa & Tagihan -->
        <div id="tab-tagihan" class="tab-content">
            <div class="card">
                <h2>Daftar Siswa dan Tagihan</h2>
                <p style="margin-bottom: 20px; color: #666;">
                    Berikut adalah daftar semua siswa beserta tagihan yang mereka miliki.
                </p>
                <?php if (empty($students_with_tagihan)): ?>
                    <p style="text-align: center; color: #666; padding: 40px;">
                        Tidak ada data siswa yang ditemukan.
                    </p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Jumlah Tagihan</th>
                                <th>List Tagihan</th>
                                <th>Total Tagihan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($students_with_tagihan as $student): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($student['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($student['class'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge <?= $student['jumlah_tagihan'] > 0 ? 'badge-warning' : 'badge-success' ?>">
                                            <?= $student['jumlah_tagihan'] ?> tagihan
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($student['jumlah_tagihan'] > 0): ?>
                                            <div style="max-width: 400px;">
                                                <?= htmlspecialchars($student['list_tagihan']) ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #48bb78;">Tidak ada tagihan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['total_tagihan'] > 0): ?>
                                            <strong style="color: #e53e3e;">Rp <?= number_format($student['total_tagihan'], 0, ',', '.') ?></strong>
                                        <?php else: ?>
                                            <span style="color: #48bb78;">Rp 0</span>
                                        <?php endif; ?>
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
    header('Location: login_siswa.php');
    exit;
}

// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>

