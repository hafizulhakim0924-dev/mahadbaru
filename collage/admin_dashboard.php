<?php
/**
 * Admin Dashboard - Superadmin Panel
 * 
 * Fitur:
 * - Kelola Barang (CRUD)
 * - Tambah Tagihan Satuan
 * - Bayar Manual Belanja
 * - Kelola Voucher
 * - Daftar Siswa & Tagihan
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/admin_dashboard_errors.log');

session_start();

// ============================================
// CONFIGURATION
// ============================================
$config = [
    'db_host' => 'localhost',
    'db_user' => 'ypikhair_adminmahadzubair',
    'db_pass' => 'Hakim123!',
    'db_name' => 'ypikhair_mahadzubair',
    'charset' => 'utf8mb4'
];

// ============================================
// SECURITY CHECK
// ============================================
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['is_superadmin'])) {
    header('Location: login_siswa.php');
    exit;
}

// ============================================
// DATABASE CONNECTION
// ============================================
$conn = null;
$db_error = '';
try {
    $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
    if ($conn->connect_error) {
        $db_error = "Database connection failed: " . $conn->connect_error;
        error_log("AdminDashboard DB Error: " . $db_error);
        throw new Exception($db_error);
    }
    $conn->set_charset($config['charset']);
} catch (Exception $e) {
    $error_message = $e->getMessage();
    error_log("AdminDashboard Exception: " . $error_message);
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h2>Database Error</h2><p>' . htmlspecialchars($error_message) . '</p><p>Silakan hubungi administrator.</p></body></html>');
}

// ============================================
// INITIALIZE VARIABLES
// ============================================
$admin_id = $_SESSION['admin_id'] ?? 999;
$admin_username = $_SESSION['admin_username'] ?? 'Superadmin';
$is_superadmin = isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'];
$success = '';
$error = '';
$current_tab = $_GET['tab'] ?? 'dashboard';

// ============================================
// HELPER FUNCTIONS
// ============================================
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// ============================================
// HANDLE FORM SUBMISSIONS
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Tambah Tagihan Satuan
    if ($action == 'tambah_tagihan_satuan') {
        $student_id = intval($_POST['student_id'] ?? 0);
        $nama_tagihan = trim($_POST['nama_tagihan'] ?? '');
        $jumlah = floatval($_POST['jumlah'] ?? 0);
        $keterangan = trim($_POST['keterangan'] ?? '');
        
        if ($student_id <= 0) {
            $error = "Pilih siswa terlebih dahulu!";
        } elseif (empty($nama_tagihan)) {
            $error = "Nama tagihan tidak boleh kosong!";
        } elseif ($jumlah <= 0) {
            $error = "Jumlah harus lebih dari 0!";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO tagihan (student_id, nama_tagihan, jumlah, keterangan) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isds", $student_id, $nama_tagihan, $jumlah, $keterangan);
                
                if ($stmt->execute()) {
                    $success = "Tagihan berhasil ditambahkan!";
                } else {
                    $error = "Gagal menambahkan tagihan: " . $stmt->error;
                }
                $stmt->close();
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
                error_log("AdminDashboard - Tambah Tagihan Error: " . $e->getMessage());
            }
        }
    }
    
    // Bayar Manual Belanja
    elseif ($action == 'bayar_manual_belanja') {
        $pesanan_id = intval($_POST['pesanan_id'] ?? 0);
        $metode = trim($_POST['metode'] ?? 'Tunai');
        $keterangan = trim($_POST['keterangan'] ?? '');
        
        if ($pesanan_id <= 0) {
            $error = "Pilih pesanan terlebih dahulu!";
        } else {
            try {
                $conn->begin_transaction();
                
                // Update status pesanan
                $stmt = $conn->prepare("UPDATE pesanan_belanja SET status = 'berhasil', updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("i", $pesanan_id);
                if (!$stmt->execute()) {
                    throw new Exception("Gagal update pesanan: " . $stmt->error);
                }
                $stmt->close();
                
                // Get pesanan data
                $stmt = $conn->prepare("SELECT student_id, total_harga FROM pesanan_belanja WHERE id = ?");
                $stmt->bind_param("i", $pesanan_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $pesanan = $result->fetch_assoc();
                $stmt->close();
                
                if (!$pesanan) {
                    throw new Exception("Pesanan tidak ditemukan!");
                }
                
                // Create voucher
                $voucher_code = 'VCH-' . strtoupper(substr(md5($pesanan_id . time() . rand()), 0, 10));
                $stmt = $conn->prepare("INSERT INTO voucher_pembayaran (student_id, pesanan_id, voucher_code, status, keterangan) VALUES (?, ?, ?, 'pending', ?)");
                $keterangan_full = "Pembayaran Manual - Metode: $metode" . (!empty($keterangan) ? " - $keterangan" : "");
                $stmt->bind_param("iiss", $pesanan['student_id'], $pesanan_id, $voucher_code, $keterangan_full);
                
                if (!$stmt->execute()) {
                    throw new Exception("Gagal membuat voucher: " . $stmt->error);
                }
                $stmt->close();
                
                $conn->commit();
                $success = "Pembayaran manual berhasil! Voucher: $voucher_code";
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error: " . $e->getMessage();
                error_log("AdminDashboard - Bayar Manual Error: " . $e->getMessage());
            }
        }
    }
    
    // Tambah Barang
    elseif ($action == 'tambah_barang') {
        $nama_barang = trim($_POST['nama_barang'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $harga = floatval($_POST['harga'] ?? 0);
        $stok = intval($_POST['stok'] ?? 0);
        $status = in_array($_POST['status'] ?? '', ['aktif', 'nonaktif']) ? $_POST['status'] : 'aktif';
        
        if (empty($nama_barang)) {
            $error = "Nama barang tidak boleh kosong!";
        } elseif ($harga <= 0) {
            $error = "Harga harus lebih dari 0!";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO barang (nama_barang, deskripsi, harga, stok, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssdis", $nama_barang, $deskripsi, $harga, $stok, $status);
                
                if ($stmt->execute()) {
                    $success = "Barang berhasil ditambahkan!";
                } else {
                    $error = "Gagal menambahkan barang: " . $stmt->error;
                }
                $stmt->close();
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
    
    // Edit Barang
    elseif ($action == 'edit_barang') {
        $id = intval($_POST['id'] ?? 0);
        $nama_barang = trim($_POST['nama_barang'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $harga = floatval($_POST['harga'] ?? 0);
        $stok = intval($_POST['stok'] ?? 0);
        $status = in_array($_POST['status'] ?? '', ['aktif', 'nonaktif']) ? $_POST['status'] : 'aktif';
        
        if ($id <= 0 || empty($nama_barang) || $harga <= 0) {
            $error = "Data tidak valid!";
        } else {
            try {
                $stmt = $conn->prepare("UPDATE barang SET nama_barang = ?, deskripsi = ?, harga = ?, stok = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssdisi", $nama_barang, $deskripsi, $harga, $stok, $status, $id);
                
                if ($stmt->execute()) {
                    $success = "Barang berhasil diupdate!";
                } else {
                    $error = "Gagal mengupdate barang: " . $stmt->error;
                }
                $stmt->close();
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
    
    // Hapus Barang
    elseif ($action == 'hapus_barang') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $error = "ID tidak valid!";
        } else {
            try {
                $stmt = $conn->prepare("DELETE FROM barang WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $success = "Barang berhasil dihapus!";
                } else {
                    $error = "Gagal menghapus barang: " . $stmt->error;
                }
                $stmt->close();
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
    
    // Redeem Voucher
    elseif ($action == 'redeem_voucher') {
        $voucher_id = intval($_POST['voucher_id'] ?? 0);
        
        if ($voucher_id <= 0) {
            $error = "ID voucher tidak valid!";
        } else {
            try {
                $stmt = $conn->prepare("UPDATE voucher_pembayaran SET status = 'redeemed', updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("i", $voucher_id);
                
                if ($stmt->execute()) {
                    $success = "Voucher berhasil diredeem!";
                } else {
                    $error = "Gagal redeem voucher: " . $stmt->error;
                }
                $stmt->close();
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

// ============================================
// HANDLE LOGOUT
// ============================================
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login_siswa.php');
    exit;
}

// ============================================
// FETCH DATA
// ============================================
$barang_list = [];
$students_list = [];
$pending_vouchers = [];
$pending_orders = [];
$students_with_tagihan = [];

try {
    // Get barang
    $stmt = $conn->prepare("SELECT * FROM barang ORDER BY nama_barang ASC");
    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $barang_list[] = $row;
        }
    }
    $stmt->close();
    
    // Get students
    $stmt = $conn->prepare("SELECT id, name, class FROM students ORDER BY name ASC");
    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $students_list[] = $row;
        }
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
    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $pending_vouchers[] = $row;
        }
    }
    $stmt->close();
    
    // Get pending orders
    $stmt = $conn->prepare("
        SELECT p.*, s.name as student_name, s.class
        FROM pesanan_belanja p
        LEFT JOIN students s ON p.student_id = s.id
        WHERE p.status = 'pending'
        ORDER BY p.created_at DESC
    ");
    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $pending_orders[] = $row;
        }
    }
    $stmt->close();
    
    // Get students with tagihan
    $stmt = $conn->prepare("
        SELECT 
            s.id,
            s.name,
            s.class,
            COUNT(t.id) as jumlah_tagihan,
            COALESCE(SUM(t.jumlah), 0) as total_tagihan
        FROM students s
        LEFT JOIN tagihan t ON s.id = t.student_id
        GROUP BY s.id, s.name, s.class
        ORDER BY s.name ASC
    ");
    if ($stmt && $stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $students_with_tagihan[] = $row;
        }
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("AdminDashboard - Fetch Data Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Superadmin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f5f7fa; 
            color: #333;
        }
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 20px; 
            position: relative;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header h1 { margin-bottom: 5px; font-size: 24px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .logout { 
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.2); 
            color: white; 
            padding: 10px 20px; 
            text-decoration: none; 
            border-radius: 25px; 
            font-size: 14px;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.3);
        }
        .logout:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        .container { 
            max-width: 1600px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .menu-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
            border-color: #667eea;
        }
        .menu-card.active {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
        }
        .menu-card-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        .menu-card h3 {
            color: #2d3748;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .menu-card p {
            color: #666;
            font-size: 14px;
        }
        .content-area {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-height: 400px;
        }
        .content-section {
            display: none;
        }
        .content-section.active {
            display: block;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            color: #2d3748; 
            font-weight: 600; 
        }
        .form-group input, 
        .form-group select, 
        .form-group textarea { 
            width: 100%; 
            padding: 12px; 
            border: 2px solid #e2e8f0; 
            border-radius: 8px; 
            font-size: 14px; 
            font-family: inherit;
            transition: all 0.3s;
        }
        .form-group input:focus, 
        .form-group select:focus, 
        .form-group textarea:focus { 
            outline: none; 
            border-color: #667eea; 
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn { 
            padding: 12px 24px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 600;
            margin: 5px;
            transition: all 0.3s;
            font-size: 14px;
        }
        .btn:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); 
        }
        .btn-danger { background: #e53e3e; }
        .btn-success { background: #48bb78; }
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }
        .success { 
            background: #d4edda; 
            color: #155724; 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            border: 1px solid #c3e6cb;
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            border: 1px solid #f5c6cb;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
        }
        table th { 
            background: #667eea; 
            color: white; 
            padding: 12px; 
            text-align: left; 
            font-weight: 600; 
        }
        table td { 
            padding: 12px; 
            border-bottom: 1px solid #e2e8f0; 
        }
        table tr:hover { 
            background: #f7fafc; 
        }
        .badge { 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-size: 12px; 
            font-weight: 600; 
            display: inline-block;
        }
        .badge-success { background: #c6f6d5; color: #22543d; }
        .badge-warning { background: #feebc8; color: #7c2d12; }
        .badge-danger { background: #fed7d7; color: #742a2a; }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .empty-state-icon {
            font-size: 60px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="?logout=1" class="logout">🚪 Logout</a>
        <h1>Admin Dashboard - Superadmin</h1>
        <p>Selamat datang, <?= sanitize($admin_username) ?> <?= $is_superadmin ? '(Superadmin)' : '' ?></p>
    </div>

    <div class="container">
        <?php if ($success): ?>
            <div class="success">✅ <?= sanitize($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error">❌ <?= sanitize($error) ?></div>
        <?php endif; ?>
        
        <!-- Menu Grid -->
        <div class="menu-grid">
            <div class="menu-card <?= $current_tab == 'dashboard' ? 'active' : '' ?>" onclick="showSection('dashboard')">
                <div class="menu-card-icon">📊</div>
                <h3>Dashboard</h3>
                <p>Ringkasan data dan statistik</p>
            </div>
            <div class="menu-card <?= $current_tab == 'barang' ? 'active' : '' ?>" onclick="showSection('barang')">
                <div class="menu-card-icon">📦</div>
                <h3>Kelola Barang</h3>
                <p>Tambah, edit, hapus barang</p>
            </div>
            <div class="menu-card <?= $current_tab == 'tagihan' ? 'active' : '' ?>" onclick="showSection('tagihan')">
                <div class="menu-card-icon">💰</div>
                <h3>Tambah Tagihan Satuan</h3>
                <p>Tambah tagihan untuk siswa</p>
            </div>
            <div class="menu-card <?= $current_tab == 'bayar_manual' ? 'active' : '' ?>" onclick="showSection('bayar_manual')">
                <div class="menu-card-icon">💳</div>
                <h3>Bayar Manual Belanja</h3>
                <p>Konfirmasi pembayaran offline</p>
            </div>
            <div class="menu-card <?= $current_tab == 'voucher' ? 'active' : '' ?>" onclick="showSection('voucher')">
                <div class="menu-card-icon">🎫</div>
                <h3>Kelola Voucher</h3>
                <p>Redeem voucher pembayaran</p>
            </div>
            <div class="menu-card <?= $current_tab == 'siswa' ? 'active' : '' ?>" onclick="showSection('siswa')">
                <div class="menu-card-icon">👥</div>
                <h3>Daftar Siswa & Tagihan</h3>
                <p>Lihat siswa dan tagihannya</p>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-area">
            <!-- Dashboard Section -->
            <div id="section-dashboard" class="content-section <?= $current_tab == 'dashboard' ? 'active' : '' ?>">
                <h2 style="margin-bottom: 20px; color: #2d3748;">📊 Dashboard</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px;">
                        <h3 style="font-size: 14px; opacity: 0.9; margin-bottom: 10px;">Total Barang</h3>
                        <p style="font-size: 36px; font-weight: bold;"><?= count($barang_list) ?></p>
                    </div>
                    <div style="background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); color: white; padding: 25px; border-radius: 12px;">
                        <h3 style="font-size: 14px; opacity: 0.9; margin-bottom: 10px;">Voucher Pending</h3>
                        <p style="font-size: 36px; font-weight: bold;"><?= count($pending_vouchers) ?></p>
                    </div>
                    <div style="background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%); color: white; padding: 25px; border-radius: 12px;">
                        <h3 style="font-size: 14px; opacity: 0.9; margin-bottom: 10px;">Pesanan Pending</h3>
                        <p style="font-size: 36px; font-weight: bold;"><?= count($pending_orders) ?></p>
                    </div>
                    <div style="background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%); color: white; padding: 25px; border-radius: 12px;">
                        <h3 style="font-size: 14px; opacity: 0.9; margin-bottom: 10px;">Total Siswa</h3>
                        <p style="font-size: 36px; font-weight: bold;"><?= count($students_list) ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Barang Section -->
            <div id="section-barang" class="content-section <?= $current_tab == 'barang' ? 'active' : '' ?>">
                <h2 style="margin-bottom: 20px; color: #2d3748;">📦 Kelola Barang</h2>
                
                <!-- Form Tambah Barang -->
                <div style="background: #f7fafc; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                    <h3 style="margin-bottom: 15px; color: #2d3748;">Tambah Barang Baru</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="tambah_barang">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                            <div class="form-group">
                                <label>Nama Barang *</label>
                                <input type="text" name="nama_barang" required>
                            </div>
                            <div class="form-group">
                                <label>Harga *</label>
                                <input type="number" name="harga" min="0" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label>Stok</label>
                                <input type="number" name="stok" min="0" value="0">
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status">
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Deskripsi</label>
                            <textarea name="deskripsi" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn">➕ Tambah Barang</button>
                    </form>
                </div>
                
                <!-- Daftar Barang -->
                <?php if (empty($barang_list)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📦</div>
                        <h3>Tidak Ada Barang</h3>
                        <p>Tambahkan barang pertama Anda</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
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
                                    <td><?= $barang['id'] ?></td>
                                    <td><?= sanitize($barang['nama_barang']) ?></td>
                                    <td><?= sanitize($barang['deskripsi'] ?? '-') ?></td>
                                    <td>Rp <?= number_format($barang['harga'], 0, ',', '.') ?></td>
                                    <td><?= $barang['stok'] ?></td>
                                    <td>
                                        <span class="badge <?= $barang['status'] == 'aktif' ? 'badge-success' : 'badge-danger' ?>">
                                            <?= $barang['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-small" onclick="editBarang(<?= htmlspecialchars(json_encode($barang)) ?>)">✏️ Edit</button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin hapus?')">
                                            <input type="hidden" name="action" value="hapus_barang">
                                            <input type="hidden" name="id" value="<?= $barang['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-small">🗑️ Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Tagihan Satuan Section -->
            <div id="section-tagihan" class="content-section <?= $current_tab == 'tagihan' ? 'active' : '' ?>">
                <h2 style="margin-bottom: 20px; color: #2d3748;">💰 Tambah Tagihan Satuan</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="tambah_tagihan_satuan">
                    <div class="form-group">
                        <label>Pilih Siswa *</label>
                        <select name="student_id" required>
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach ($students_list as $student): ?>
                                <option value="<?= $student['id'] ?>">
                                    <?= sanitize($student['name']) ?> (ID: <?= $student['id'] ?>) - <?= sanitize($student['class'] ?? '-') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nama Tagihan *</label>
                        <input type="text" name="nama_tagihan" placeholder="Contoh: SPP Bulan Januari" required>
                    </div>
                    <div class="form-group">
                        <label>Jumlah (Rp) *</label>
                        <input type="number" name="jumlah" min="0" step="0.01" placeholder="0" required>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan" rows="3" placeholder="Keterangan tambahan (opsional)"></textarea>
                    </div>
                    <button type="submit" class="btn">➕ Tambah Tagihan</button>
                </form>
            </div>
            
            <!-- Bayar Manual Belanja Section -->
            <div id="section-bayar_manual" class="content-section <?= $current_tab == 'bayar_manual' ? 'active' : '' ?>">
                <h2 style="margin-bottom: 20px; color: #2d3748;">💳 Bayar Manual Belanja</h2>
                <?php if (empty($pending_orders)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">💳</div>
                        <h3>Tidak Ada Pesanan Pending</h3>
                        <p>Semua pesanan sudah terbayar</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Siswa</th>
                                <th>Total</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_orders as $order): ?>
                                <tr>
                                    <td><?= sanitize($order['order_id']) ?></td>
                                    <td><?= sanitize($order['student_name'] ?? '-') ?> (<?= sanitize($order['class'] ?? '-') ?>)</td>
                                    <td>Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <button class="btn btn-success btn-small" onclick="showBayarManual(<?= htmlspecialchars(json_encode($order)) ?>)">💳 Bayar Manual</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Voucher Section -->
            <div id="section-voucher" class="content-section <?= $current_tab == 'voucher' ? 'active' : '' ?>">
                <h2 style="margin-bottom: 20px; color: #2d3748;">🎫 Kelola Voucher</h2>
                <?php if (empty($pending_vouchers)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🎫</div>
                        <h3>Tidak Ada Voucher Pending</h3>
                        <p>Semua voucher sudah diredeem</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Voucher Code</th>
                                <th>Siswa</th>
                                <th>Total</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_vouchers as $voucher): ?>
                                <tr>
                                    <td><strong><?= sanitize($voucher['voucher_code']) ?></strong></td>
                                    <td><?= sanitize($voucher['student_name'] ?? '-') ?> (<?= sanitize($voucher['class'] ?? '-') ?>)</td>
                                    <td>Rp <?= number_format($voucher['total_harga'] ?? 0, 0, ',', '.') ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($voucher['created_at'])) ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="redeem_voucher">
                                            <input type="hidden" name="voucher_id" value="<?= $voucher['id'] ?>">
                                            <button type="submit" class="btn btn-success btn-small">✅ Redeem</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Siswa & Tagihan Section -->
            <div id="section-siswa" class="content-section <?= $current_tab == 'siswa' ? 'active' : '' ?>">
                <h2 style="margin-bottom: 20px; color: #2d3748;">👥 Daftar Siswa & Tagihan</h2>
                <?php if (empty($students_with_tagihan)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">👥</div>
                        <h3>Tidak Ada Data Siswa</h3>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Jumlah Tagihan</th>
                                <th>Total Tagihan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students_with_tagihan as $student): ?>
                                <tr>
                                    <td><?= $student['id'] ?></td>
                                    <td><?= sanitize($student['name']) ?></td>
                                    <td><?= sanitize($student['class'] ?? '-') ?></td>
                                    <td><?= $student['jumlah_tagihan'] ?></td>
                                    <td>Rp <?= number_format($student['total_tagihan'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal Edit Barang -->
    <div id="modal-edit" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 12px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <h2 style="margin-bottom: 20px;">✏️ Edit Barang</h2>
            <form method="POST" id="form-edit">
                <input type="hidden" name="action" value="edit_barang">
                <input type="hidden" name="id" id="edit-id">
                <div class="form-group">
                    <label>Nama Barang *</label>
                    <input type="text" name="nama_barang" id="edit-nama" required>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" id="edit-deskripsi" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Harga *</label>
                    <input type="number" name="harga" id="edit-harga" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Stok</label>
                    <input type="number" name="stok" id="edit-stok" min="0">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit-status">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn">💾 Simpan</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal()">❌ Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Bayar Manual -->
    <div id="modal-bayar" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%;">
            <h2 style="margin-bottom: 20px;">💳 Bayar Manual</h2>
            <form method="POST" id="form-bayar">
                <input type="hidden" name="action" value="bayar_manual_belanja">
                <input type="hidden" name="pesanan_id" id="bayar-pesanan-id">
                <div class="form-group">
                    <label>Order ID</label>
                    <input type="text" id="bayar-order-id" readonly style="background: #f7fafc;">
                </div>
                <div class="form-group">
                    <label>Total</label>
                    <input type="text" id="bayar-total" readonly style="background: #f7fafc;">
                </div>
                <div class="form-group">
                    <label>Metode Pembayaran *</label>
                    <select name="metode" required>
                        <option value="Tunai">Tunai</option>
                        <option value="Transfer">Transfer</option>
                        <option value="Kartu">Kartu</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Keterangan</label>
                    <textarea name="keterangan" rows="3" placeholder="Keterangan tambahan (opsional)"></textarea>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">✅ Konfirmasi Pembayaran</button>
                    <button type="button" class="btn btn-danger" onclick="closeBayarModal()">❌ Batal</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showSection(tab) {
            // Update URL
            window.location.href = '?tab=' + tab;
        }
        
        function editBarang(barang) {
            document.getElementById('edit-id').value = barang.id;
            document.getElementById('edit-nama').value = barang.nama_barang;
            document.getElementById('edit-deskripsi').value = barang.deskripsi || '';
            document.getElementById('edit-harga').value = barang.harga;
            document.getElementById('edit-stok').value = barang.stok;
            document.getElementById('edit-status').value = barang.status;
            document.getElementById('modal-edit').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('modal-edit').style.display = 'none';
        }
        
        function showBayarManual(order) {
            document.getElementById('bayar-pesanan-id').value = order.id;
            document.getElementById('bayar-order-id').value = order.order_id;
            document.getElementById('bayar-total').value = 'Rp ' + parseInt(order.total_harga).toLocaleString('id-ID');
            document.getElementById('modal-bayar').style.display = 'flex';
        }
        
        function closeBayarModal() {
            document.getElementById('modal-bayar').style.display = 'none';
        }
        
        // Close modal on outside click
        window.onclick = function(event) {
            const modalEdit = document.getElementById('modal-edit');
            const modalBayar = document.getElementById('modal-bayar');
            if (event.target == modalEdit) {
                closeModal();
            }
            if (event.target == modalBayar) {
                closeBayarModal();
            }
        }
    </script>
</body>
</html>

