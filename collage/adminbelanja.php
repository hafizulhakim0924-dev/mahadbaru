<?php
/**
 * Admin Belanja - Kelola Barang, Voucher, dan Tagihan
 * 
 * Fitur:
 * - Kelola Barang (CRUD)
 * - Import Barang Massal
 * - Redeem Voucher Pembayaran
 * - Daftar Siswa & Tagihan
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/adminbelanja_errors.log');

session_start();

// ============================================
// CONFIGURATION
// ============================================
$config = [
    'db_host' => 'localhost',
    'db_user' => 'ypikhair_admin',
    'db_pass' => 'hakim123123123',
    'db_name' => 'ypikhair_datautama',
    'charset' => 'utf8mb4'
];

// ============================================
// SECURITY CHECK
// ============================================
if (!isset($_SESSION['admin_id'])) {
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
        error_log("AdminBelanja DB Error: " . $db_error);
        throw new Exception($db_error);
    }
    $conn->set_charset($config['charset']);
} catch (Exception $e) {
    $error_message = $e->getMessage();
    error_log("AdminBelanja Exception: " . $error_message);
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h2>Database Error</h2><p>' . htmlspecialchars($error_message) . '</p><p>Silakan hubungi administrator.</p><p style="font-size:12px;color:#666;">Error logged to: adminbelanja_errors.log</p></body></html>');
}

// ============================================
// INITIALIZE VARIABLES
// ============================================
$admin_id = $_SESSION['admin_id'];
$admin_nama = $_SESSION['admin_nama'] ?? 'Admin';
$success = '';
$error = '';
$barang_list = [];
$pending_vouchers = [];
$students_with_tagihan = [];

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Get all barang
 */
function getBarangList($conn) {
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
            $error_msg = "Error preparing barang query: " . $conn->error;
            error_log("AdminBelanja: " . $error_msg);
        }
    } catch (Exception $e) {
        $error_msg = "Error getting barang list: " . $e->getMessage();
        error_log("AdminBelanja: " . $error_msg);
    }
    return $barang_list;
}

/**
 * Get pending vouchers
 */
function getPendingVouchers($conn) {
    $vouchers = [];
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
                $vouchers[] = $row;
            }
            $stmt->close();
        } else {
            $error_msg = "Error preparing vouchers query: " . $conn->error;
            error_log("AdminBelanja: " . $error_msg);
        }
    } catch (Exception $e) {
        $error_msg = "Error getting vouchers: " . $e->getMessage();
        error_log("AdminBelanja: " . $error_msg);
    }
    return $vouchers;
}

/**
 * Get students with tagihan
 */
function getStudentsWithTagihan($conn) {
    $students = [];
    try {
        $stmt = $conn->prepare("
            SELECT 
                s.id,
                s.name,
                s.class,
                COUNT(t.id) as jumlah_tagihan,
                GROUP_CONCAT(CONCAT(t.nama_tagihan, ' (Rp ', FORMAT(t.jumlah, 0), ')') SEPARATOR ', ') as list_tagihan,
                COALESCE(SUM(t.jumlah), 0) as total_tagihan
            FROM students s
            LEFT JOIN tagihan t ON s.id = t.student_id
            GROUP BY s.id, s.name, s.class
            ORDER BY s.name ASC
        ");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $students[] = $row;
            }
            $stmt->close();
        } else {
            $error_msg = "Error preparing students query: " . $conn->error;
            error_log("AdminBelanja: " . $error_msg);
        }
    } catch (Exception $e) {
        $error_msg = "Error getting students with tagihan: " . $e->getMessage();
        error_log("AdminBelanja: " . $error_msg);
    }
    return $students;
}

// ============================================
// HANDLE FORM SUBMISSIONS
// ============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Handle tambah barang
    if ($action == 'tambah_barang') {
        $nama_barang = trim($_POST['nama_barang'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $harga = floatval($_POST['harga'] ?? 0);
        $stok = intval($_POST['stok'] ?? 0);
        $status = in_array($_POST['status'] ?? '', ['aktif', 'nonaktif']) ? $_POST['status'] : 'aktif';
        
        if (empty($nama_barang)) {
            $error = "Nama barang tidak boleh kosong!";
        } elseif ($harga <= 0) {
            $error = "Harga harus lebih dari 0!";
        } elseif ($stok < 0) {
            $error = "Stok tidak boleh negatif!";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO barang (nama_barang, deskripsi, harga, stok, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssdis", $nama_barang, $deskripsi, $harga, $stok, $status);
                
                if ($stmt->execute()) {
                    $success = "Barang berhasil ditambahkan!";
                } else {
                    $error_msg = "Gagal menambahkan barang: " . $stmt->error;
                    error_log("AdminBelanja - Tambah Barang Error: " . $error_msg);
                    $error = $error_msg;
                }
                $stmt->close();
            } catch (Exception $e) {
                $error_msg = "Error: " . $e->getMessage();
                error_log("AdminBelanja - Tambah Barang Exception: " . $error_msg);
                $error = $error_msg;
            }
        }
    }
    
    // Handle edit barang
    elseif ($action == 'edit_barang') {
        $id = intval($_POST['id'] ?? 0);
        $nama_barang = trim($_POST['nama_barang'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $harga = floatval($_POST['harga'] ?? 0);
        $stok = intval($_POST['stok'] ?? 0);
        $status = in_array($_POST['status'] ?? '', ['aktif', 'nonaktif']) ? $_POST['status'] : 'aktif';
        
        if ($id <= 0) {
            $error = "ID barang tidak valid!";
        } elseif (empty($nama_barang)) {
            $error = "Nama barang tidak boleh kosong!";
        } elseif ($harga <= 0) {
            $error = "Harga harus lebih dari 0!";
        } elseif ($stok < 0) {
            $error = "Stok tidak boleh negatif!";
        } else {
            try {
                $stmt = $conn->prepare("UPDATE barang SET nama_barang = ?, deskripsi = ?, harga = ?, stok = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssdisi", $nama_barang, $deskripsi, $harga, $stok, $status, $id);
                
                if ($stmt->execute()) {
                    $success = "Barang berhasil diupdate!";
                } else {
                    $error_msg = "Gagal mengupdate barang: " . $stmt->error;
                    error_log("AdminBelanja - Edit Barang Error: " . $error_msg);
                    $error = $error_msg;
                }
                $stmt->close();
            } catch (Exception $e) {
                $error_msg = "Error: " . $e->getMessage();
                error_log("AdminBelanja - Edit Barang Exception: " . $error_msg);
                $error = $error_msg;
            }
        }
    }
    
    // Handle hapus barang
    elseif ($action == 'hapus_barang') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            $error = "ID barang tidak valid!";
        } else {
            try {
                $stmt = $conn->prepare("DELETE FROM barang WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $success = "Barang berhasil dihapus!";
                } else {
                    $error_msg = "Gagal menghapus barang: " . $stmt->error;
                    error_log("AdminBelanja - Hapus Barang Error: " . $error_msg);
                    $error = $error_msg;
                }
                $stmt->close();
            } catch (Exception $e) {
                $error_msg = "Error: " . $e->getMessage();
                error_log("AdminBelanja - Hapus Barang Exception: " . $error_msg);
                $error = $error_msg;
            }
        }
    }
    
    // Handle redeem voucher
    elseif ($action == 'redeem_voucher') {
        $voucher_code = trim($_POST['voucher_code'] ?? '');
        
        if (empty($voucher_code)) {
            $error = "Kode voucher tidak boleh kosong!";
        } else {
            try {
                $stmt = $conn->prepare("SELECT * FROM voucher_pembayaran WHERE voucher_code = ? AND status = 'pending'");
                $stmt->bind_param("s", $voucher_code);
                $stmt->execute();
                $result = $stmt->get_result();
                $voucher = $result->fetch_assoc();
                $stmt->close();
                
                if ($voucher) {
                    $stmt = $conn->prepare("UPDATE voucher_pembayaran SET status = 'redeemed', redeemed_at = NOW(), redeemed_by = ? WHERE id = ?");
                    $stmt->bind_param("ii", $admin_id, $voucher['id']);
                    
                    if ($stmt->execute()) {
                        $success = "Voucher berhasil diredeem!";
                    } else {
                        $error_msg = "Gagal redeem voucher: " . $stmt->error;
                        error_log("AdminBelanja - Redeem Voucher Error: " . $error_msg);
                        $error = $error_msg;
                    }
                    $stmt->close();
                } else {
                    $error_msg = "Voucher tidak ditemukan atau sudah diredeem!";
                    error_log("AdminBelanja - Redeem Voucher: " . $error_msg);
                    $error = $error_msg;
                }
            } catch (Exception $e) {
                $error_msg = "Error: " . $e->getMessage();
                error_log("AdminBelanja - Redeem Voucher Exception: " . $error_msg);
                $error = $error_msg;
            }
        }
    }
    
    // Handle import barang
    elseif ($action == 'import_barang') {
        $data_paste = trim($_POST['data_paste'] ?? '');
        
        if (empty($data_paste)) {
            $error = "Data tidak boleh kosong!";
        } else {
            $lines = explode("\n", $data_paste);
            $barang_data = [];
            $errors = [];
            $success_count = 0;
            
            foreach ($lines as $line_num => $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                // Deteksi separator
                if (strpos($line, "\t") !== false) {
                    $cols = explode("\t", $line);
                } else {
                    $cols = explode(",", $line);
                }
                
                $cols = array_map('trim', $cols);
                
                // Skip header
                if ($line_num == 0 && (strtolower($cols[0]) == 'nama_barang' || strtolower($cols[0]) == 'nama' || strtolower($cols[0]) == 'barang')) {
                    continue;
                }
                
                if (count($cols) < 3) {
                    $errors[] = "Baris " . ($line_num + 1) . ": Data tidak lengkap";
                    continue;
                }
                
                $nama_barang = $cols[0] ?? '';
                $deskripsi = $cols[1] ?? '';
                $harga = isset($cols[2]) ? floatval(str_replace(['Rp', 'rp', '.', ','], '', $cols[2])) : 0;
                $stok = isset($cols[3]) ? intval($cols[3]) : 0;
                $status = isset($cols[4]) ? (strtolower(trim($cols[4])) == 'nonaktif' ? 'nonaktif' : 'aktif') : 'aktif';
                
                if (empty($nama_barang)) {
                    $errors[] = "Baris " . ($line_num + 1) . ": Nama barang kosong";
                    continue;
                }
                
                if ($harga <= 0) {
                    $errors[] = "Baris " . ($line_num + 1) . ": Harga tidak valid";
                    continue;
                }
                
                $barang_data[] = [
                    'nama_barang' => $nama_barang,
                    'deskripsi' => $deskripsi,
                    'harga' => $harga,
                    'stok' => $stok,
                    'status' => $status
                ];
            }
            
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
                            $errors[] = "Gagal insert: " . $data['nama_barang'];
                        }
                    }
                    
                    $stmt->close();
                    
                    if ($success_count > 0) {
                        $conn->commit();
                        $success = "Berhasil mengimport $success_count barang!";
                        if (!empty($errors)) {
                            $error = "Beberapa data gagal: " . implode(", ", array_slice($errors, 0, 5));
                        }
                    } else {
                        $conn->rollback();
                        $error = "Tidak ada data yang berhasil diimport.";
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error: " . $e->getMessage();
                }
            } else {
                $error = "Tidak ada data valid untuk diimport.";
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
$barang_list = getBarangList($conn);
$pending_vouchers = getPendingVouchers($conn);
$students_with_tagihan = getStudentsWithTagihan($conn);

// Log any database errors
if ($conn->errno) {
    $db_error_msg = "Database error: " . $conn->error;
    error_log("AdminBelanja: " . $db_error_msg);
    if (empty($error)) {
        $error = $db_error_msg;
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Kelola Belanja</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f8f9fa; 
            color: #333;
        }
        .header { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 20px; 
            position: relative;
        }
        .header h1 { margin-bottom: 5px; }
        .header p { opacity: 0.9; }
        .logout { 
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.2); 
            color: white; 
            padding: 8px 16px; 
            text-decoration: none; 
            border-radius: 20px; 
            font-size: 14px;
            transition: all 0.3s;
        }
        .logout:hover {
            background: rgba(255,255,255,0.3);
        }
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        .card { 
            background: white; 
            padding: 25px; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
            margin-bottom: 20px; 
        }
        .card h2 { 
            color: #2d3748; 
            margin-bottom: 20px; 
            padding-bottom: 10px; 
            border-bottom: 2px solid #667eea; 
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
            padding: 10px; 
            border: 1px solid #cbd5e0; 
            border-radius: 5px; 
            font-size: 14px; 
            font-family: inherit;
        }
        .form-group input:focus, 
        .form-group select:focus, 
        .form-group textarea:focus { 
            outline: none; 
            border-color: #667eea; 
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn { 
            padding: 10px 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-weight: 600; 
            margin: 5px;
            transition: all 0.3s;
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
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            border: 1px solid #c3e6cb;
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            border: 1px solid #f5c6cb;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
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
        .tabs { 
            display: flex; 
            gap: 10px; 
            margin-bottom: 20px; 
            flex-wrap: wrap;
        }
        .tab-btn { 
            padding: 10px 20px; 
            background: #e2e8f0; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-weight: 600;
            transition: all 0.3s;
        }
        .tab-btn:hover {
            background: #cbd5e0;
        }
        .tab-btn.active { 
            background: #667eea; 
            color: white; 
        }
        .tab-content { 
            display: none; 
        }
        .tab-content.active { 
            display: block; 
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="?logout=1" class="logout">Logout</a>
        <h1>Admin - Kelola Belanja</h1>
        <p>Admin: <?= sanitize($admin_nama) ?></p>
    </div>

    <div class="container">
        <?php if ($success): ?>
            <div class="success"><?= sanitize($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?= sanitize($error) ?></div>
        <?php endif; ?>
        
        <!-- Debug Info Panel -->
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #667eea;">
            <details>
                <summary style="cursor: pointer; font-weight: 600; color: #667eea;">🔍 Debug Information (Klik untuk melihat)</summary>
                <div style="margin-top: 15px; padding: 15px; background: white; border-radius: 5px; font-size: 12px; font-family: monospace;">
                    <strong>Database Connection:</strong><br>
                    <?php if ($conn): ?>
                        <span style="color: green;">✓ Connected</span><br>
                        <?php if ($conn->errno): ?>
                            <span style="color: red;">✗ Error: <?= sanitize($conn->error) ?> (Code: <?= $conn->errno ?>)</span><br>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color: red;">✗ Not Connected</span><br>
                    <?php endif; ?>
                    
                    <br><strong>Session Info:</strong><br>
                    Admin ID: <?= isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 'Not set' ?><br>
                    Admin Nama: <?= isset($_SESSION['admin_nama']) ? sanitize($_SESSION['admin_nama']) : 'Not set' ?><br>
                    
                    <br><strong>Data Count:</strong><br>
                    Barang: <?= count($barang_list) ?><br>
                    Vouchers Pending: <?= count($pending_vouchers) ?><br>
                    Students with Tagihan: <?= count($students_with_tagihan) ?><br>
                    
                    <br><strong>PHP Errors:</strong><br>
                    <?php 
                    $php_errors = error_get_last();
                    if ($php_errors): ?>
                        <span style="color: red;">
                            Type: <?= $php_errors['type'] ?><br>
                            Message: <?= sanitize($php_errors['message']) ?><br>
                            File: <?= sanitize($php_errors['file']) ?><br>
                            Line: <?= $php_errors['line'] ?>
                        </span>
                    <?php else: ?>
                        <span style="color: green;">✓ No PHP errors</span>
                    <?php endif; ?>
                    
                    <br><br><strong>Error Log File:</strong><br>
                    <code><?= __DIR__ ?>/adminbelanja_errors.log</code>
                </div>
            </details>
        </div>

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
                        <label>Nama Barang *</label>
                        <input type="text" name="nama_barang" required>
                    </div>

                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Harga *</label>
                        <input type="number" name="harga" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label>Stok *</label>
                        <input type="number" name="stok" min="0" required>
                    </div>

                    <div class="form-group">
                        <label>Status *</label>
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
                    <div class="empty-state">
                        <p>Belum ada barang yang terdaftar.</p>
                    </div>
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
                                    <td><?= sanitize($barang['nama_barang']) ?></td>
                                    <td><?= sanitize($barang['deskripsi'] ?? '-') ?></td>
                                    <td>Rp <?= number_format($barang['harga'], 0, ',', '.') ?></td>
                                    <td><?= $barang['stok'] ?></td>
                                    <td>
                                        <span class="badge <?= $barang['status'] == 'aktif' ? 'badge-success' : 'badge-danger' ?>">
                                            <?= ucfirst($barang['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-small" onclick="editBarang(<?= htmlspecialchars(json_encode($barang), ENT_QUOTES, 'UTF-8') ?>)">Edit</button>
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
                    4. Dipisah dengan <strong>Tab</strong> atau <strong>Koma (,)</strong>
                </p>
                
                <div style="background: #f0f4ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #667eea;">
                    <strong>Contoh Format:</strong><br>
                    <code style="display: block; margin-top: 8px; padding: 8px; background: white; border-radius: 4px; font-family: monospace;">
                        Buku Tulis	Buku tulis 38 lembar	5000	100	aktif<br>
                        Pensil 2B	Pensil 2B Faber Castell	3000	150	aktif
                    </code>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="import_barang">
                    
                    <div class="form-group">
                        <label>Paste Data dari Spreadsheet</label>
                        <textarea 
                            name="data_paste" 
                            rows="15" 
                            style="font-family: 'Courier New', monospace; font-size: 13px;"
                            placeholder="Paste data dari spreadsheet di sini..."
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
                        <label>Kode Voucher *</label>
                        <input type="text" name="voucher_code" required placeholder="Masukkan kode voucher">
                    </div>

                    <button type="submit" class="btn btn-success">Redeem Voucher</button>
                </form>
            </div>

            <div class="card">
                <h2>Daftar Voucher Pending</h2>
                <?php if (empty($pending_vouchers)): ?>
                    <div class="empty-state">
                        <p>Tidak ada voucher yang pending.</p>
                    </div>
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
                                    <td><strong><?= sanitize($voucher['voucher_code']) ?></strong></td>
                                    <td><?= sanitize($voucher['student_name'] ?? 'N/A') ?> (<?= sanitize($voucher['class'] ?? '-') ?>)</td>
                                    <td>Rp <?= number_format($voucher['total_harga'] ?? 0, 0, ',', '.') ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($voucher['created_at'])) ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="redeem_voucher">
                                            <input type="hidden" name="voucher_code" value="<?= sanitize($voucher['voucher_code']) ?>">
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
                    <div class="empty-state">
                        <p>Tidak ada data siswa yang ditemukan.</p>
                    </div>
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
                                    <td><strong><?= sanitize($student['name']) ?></strong></td>
                                    <td><?= sanitize($student['class'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge <?= $student['jumlah_tagihan'] > 0 ? 'badge-warning' : 'badge-success' ?>">
                                            <?= $student['jumlah_tagihan'] ?> tagihan
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($student['jumlah_tagihan'] > 0): ?>
                                            <div style="max-width: 400px;">
                                                <?= sanitize($student['list_tagihan'] ?? '-') ?>
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
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            
            // Show selected tab
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
                        <input type="text" name="nama_barang" value="${escapeHtml(barang.nama_barang)}" required>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" rows="3">${escapeHtml(barang.deskripsi || '')}</textarea>
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
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
<?php
// Close database connection
if ($conn) {
    $conn->close();
}
?>

