<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/keuangan_dashboard_errors.log');

session_start();

// Database Config
$servername = "localhost";
$username = "ypikhair_adminmahadzubair";
$password = "Hakim123!";
$dbname = "ypikhair_mahadzubair";

// Initialize variables
$success = '';
$error = '';
$keuangan_id = null;
$keuangan_nama = null;
$keuangan_username = null;
$conn = null;

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login_siswa.php');
    exit;
}

// Check if keuangan is logged in - redirect to unified login if not
if (!isset($_SESSION['keuangan_id'])) {
    header('Location: login_siswa.php');
    exit;
}

// Get session variables
$keuangan_id = $_SESSION['keuangan_id'] ?? null;
$keuangan_username = $_SESSION['keuangan_username'] ?? 'Unknown';
$keuangan_nama = $_SESSION['keuangan_nama'] ?? $keuangan_username;

// Connect to database
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log("KeuanganDashboard: Database connection error: " . $e->getMessage());
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h2>Database Error</h2><p>' . htmlspecialchars($e->getMessage()) . '</p><p>Silakan hubungi administrator.</p></body></html>');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'tambah_tagihan') {
        try {
            $student_id = intval($_POST['student_id'] ?? 0);
            $nama_tagihan = trim($_POST['nama_tagihan'] ?? '');
            $jumlah = floatval($_POST['jumlah'] ?? 0);
            $keterangan = trim($_POST['keterangan'] ?? '');
            
            if ($student_id <= 0) {
                throw new Exception("Pilih siswa terlebih dahulu!");
            }
            if (empty($nama_tagihan)) {
                throw new Exception("Nama tagihan harus diisi!");
            }
            if ($jumlah <= 0) {
                throw new Exception("Jumlah harus lebih dari 0!");
            }
            
            $stmt = $conn->prepare("INSERT INTO tagihan (student_id, nama_tagihan, jumlah, keterangan) VALUES (?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Failed to prepare query: " . $conn->error);
            }
            
            $stmt->bind_param("isds", $student_id, $nama_tagihan, $jumlah, $keterangan);
            
            if ($stmt->execute()) {
                $success = "Tagihan berhasil ditambahkan!";
            } else {
                throw new Exception("Failed to execute query: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Gagal menambahkan tagihan: " . $e->getMessage();
            error_log("KeuanganDashboard: Error adding tagihan: " . $e->getMessage());
        }
    } elseif ($_POST['action'] == 'tambah_pembayaran_manual') {
        try {
            $student_id = intval($_POST['student_id'] ?? 0);
            $nama_tagihan = trim($_POST['nama_tagihan'] ?? '');
            $jumlah = floatval($_POST['jumlah'] ?? 0);
            $metode = trim($_POST['metode'] ?? 'Tunai');
            $keterangan = trim($_POST['keterangan'] ?? '');
            
            if ($student_id <= 0) {
                throw new Exception("Pilih siswa terlebih dahulu!");
            }
            if (empty($nama_tagihan)) {
                throw new Exception("Nama tagihan harus diisi!");
            }
            if ($jumlah <= 0) {
                throw new Exception("Jumlah harus lebih dari 0!");
            }
            
            // Insert ke tabel pembayaran
            $stmt = $conn->prepare("INSERT INTO pembayaran (student_id, nama_tagihan, jumlah, metode, keterangan, keuangan_id) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Failed to prepare pembayaran query: " . $conn->error);
            }
            
            $stmt->bind_param("isdssi", $student_id, $nama_tagihan, $jumlah, $metode, $keterangan, $keuangan_id);
            
            if ($stmt->execute()) {
                // Hapus tagihan yang sudah dibayar
                $stmt2 = $conn->prepare("DELETE FROM tagihan WHERE student_id = ? AND nama_tagihan = ?");
                if ($stmt2) {
                    $stmt2->bind_param("is", $student_id, $nama_tagihan);
                    $stmt2->execute();
                    $stmt2->close();
                }
                
                $success = "Pembayaran manual berhasil dicatat!";
            } else {
                throw new Exception("Failed to execute pembayaran query: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Gagal mencatat pembayaran: " . $e->getMessage();
            error_log("KeuanganDashboard: Error adding pembayaran: " . $e->getMessage());
        }
    } elseif ($_POST['action'] == 'hapus_tagihan') {
        try {
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception("ID tagihan tidak valid!");
            }
            
            $stmt = $conn->prepare("DELETE FROM tagihan WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Failed to prepare delete query: " . $conn->error);
            }
            
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success = "Tagihan berhasil dihapus!";
            } else {
                throw new Exception("Failed to execute delete query: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Gagal menghapus tagihan: " . $e->getMessage();
            error_log("KeuanganDashboard: Error deleting tagihan: " . $e->getMessage());
        }
    }
}

// Get all students
$students = [];
try {
    $stmt = $conn->prepare("SELECT id, name, class FROM students ORDER BY name ASC");
    if (!$stmt) {
        throw new Exception("Failed to prepare students query: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("KeuanganDashboard: Error fetching students: " . $e->getMessage());
    $error .= "Error fetching students: " . $e->getMessage() . "<br>";
}

// Get all tagihan
$tagihan_list = [];
try {
    $stmt = $conn->prepare("
        SELECT t.*, s.name as student_name, s.class 
        FROM tagihan t
        LEFT JOIN students s ON t.student_id = s.id
        ORDER BY t.id DESC
    ");
    if (!$stmt) {
        throw new Exception("Failed to prepare tagihan query: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $tagihan_list[] = $row;
        }
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("KeuanganDashboard: Error fetching tagihan: " . $e->getMessage());
    $error .= "Error fetching tagihan: " . $e->getMessage() . "<br>";
}

// Get all pembayaran manual
$pembayaran_list = [];
try {
    $stmt = $conn->prepare("
        SELECT p.*, s.name as student_name, s.class 
        FROM pembayaran p
        LEFT JOIN students s ON p.student_id = s.id
        ORDER BY p.tanggal DESC
        LIMIT 50
    ");
    if (!$stmt) {
        throw new Exception("Failed to prepare pembayaran query: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $pembayaran_list[] = $row;
        }
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("KeuanganDashboard: Error fetching pembayaran: " . $e->getMessage());
    $error .= "Error fetching pembayaran: " . $e->getMessage() . "<br>";
}

// Get statistics
$total_tagihan = 0;
$pembayaran_hari_ini = 0;
$total_siswa = 0;

try {
    $stmt = $conn->query("SELECT SUM(jumlah) as total_tagihan FROM tagihan");
    if ($stmt) {
        $row = $stmt->fetch_assoc();
        $total_tagihan = $row['total_tagihan'] ?? 0;
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("KeuanganDashboard: Error fetching total_tagihan: " . $e->getMessage());
}

try {
    $stmt = $conn->query("SELECT SUM(jumlah) as total_pembayaran FROM pembayaran WHERE DATE(tanggal) = CURDATE()");
    if ($stmt) {
        $row = $stmt->fetch_assoc();
        $pembayaran_hari_ini = $row['total_pembayaran'] ?? 0;
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("KeuanganDashboard: Error fetching pembayaran_hari_ini: " . $e->getMessage());
}

try {
    $stmt = $conn->query("SELECT COUNT(*) as total_siswa FROM students");
    if ($stmt) {
        $row = $stmt->fetch_assoc();
        $total_siswa = $row['total_siswa'] ?? 0;
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("KeuanganDashboard: Error fetching total_siswa: " . $e->getMessage());
}

if ($conn) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keuangan - Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; }
        .header h1 { margin-bottom: 5px; }
        .header p { opacity: 0.9; }
        .logout { float: right; background: rgba(255,255,255,0.2); color: white; padding: 8px 16px; text-decoration: none; border-radius: 20px; font-size: 14px; }
        .logout:hover { background: rgba(255,255,255,0.3); }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stat-card h3 { color: #6b7280; font-size: 14px; margin-bottom: 10px; }
        .stat-card .value { color: #1f2937; font-size: 32px; font-weight: 700; }
        .stat-card .value.green { color: #10b981; }
        .stat-card .value.blue { color: #3b82f6; }
        .stat-card .value.orange { color: #f59e0b; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card h2 { color: #2d3748; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #10b981; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #2d3748; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 14px; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #10b981; }
        .btn { padding: 10px 20px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; margin: 5px; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4); }
        .btn-danger { background: #e53e3e; }
        .btn-danger:hover { box-shadow: 0 5px 15px rgba(229, 62, 62, 0.4); }
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        table th { background: #10b981; color: white; padding: 12px; text-align: left; font-weight: 600; }
        table td { padding: 12px; border-bottom: 1px solid #e2e8f0; }
        table tr:hover { background: #f7fafc; }
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #c6f6d5; color: #22543d; }
        .badge-warning { background: #feebc8; color: #7c2d12; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab-btn { padding: 10px 20px; background: #e2e8f0; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; }
        .tab-btn.active { background: #10b981; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <div class="header">
        <a href="?logout=1" class="logout">Logout</a>
        <h1>Dashboard Keuangan</h1>
        <p>Keuangan: <?= htmlspecialchars($keuangan_nama ?? $keuangan_username ?? 'Unknown') ?></p>
    </div>

    <div class="container">
        <?php if (isset($success) && !empty($success)): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (isset($error) && !empty($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <!-- Error Debug Panel -->
        <?php if (isset($conn) && $conn && $conn->errno): ?>
            <div class="error" style="background: #fff3cd; border: 1px solid #ffc107; color: #856404;">
                <strong>⚠️ Database Error:</strong><br>
                <?= htmlspecialchars($conn->error) ?><br>
                <small>Error Code: <?= $conn->errno ?></small>
            </div>
        <?php endif; ?>
        
        <?php 
        $php_errors = error_get_last();
        if ($php_errors && ($php_errors['type'] === E_ERROR || $php_errors['type'] === E_PARSE || $php_errors['type'] === E_WARNING)): ?>
            <div class="error" style="background: #f8d7da; border: 1px solid #dc3545; color: #721c24;">
                <strong>❌ PHP Error:</strong><br>
                <?= htmlspecialchars($php_errors['message']) ?><br>
                <small>File: <?= htmlspecialchars($php_errors['file']) ?> | Line: <?= $php_errors['line'] ?></small>
            </div>
        <?php endif; ?>
        
        <!-- Debug Info -->
        <details style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 12px;">
            <summary style="cursor: pointer; font-weight: 600; color: #10b981;">🔍 Debug Information</summary>
            <div style="margin-top: 10px; padding: 10px; background: white; border-radius: 5px;">
                <strong>Database:</strong> <?= isset($conn) && $conn ? '✓ Connected' : '✗ Not Connected' ?><br>
                <strong>Database Name:</strong> <?= htmlspecialchars($dbname) ?><br>
                <?php if (isset($conn) && $conn && $conn->errno): ?>
                    <strong>Error:</strong> <?= htmlspecialchars($conn->error) ?> (Code: <?= $conn->errno ?>)<br>
                <?php endif; ?>
                <strong>Session:</strong> Keuangan ID: <?= htmlspecialchars($keuangan_id ?? 'Not set') ?><br>
                <strong>Keuangan Username:</strong> <?= htmlspecialchars($keuangan_username ?? 'Not set') ?><br>
                <strong>Keuangan Nama:</strong> <?= htmlspecialchars($keuangan_nama ?? 'Not set') ?><br>
                <strong>Error Log:</strong> <code><?= __DIR__ ?>/keuangan_dashboard_errors.log</code>
            </div>
        </details>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Tagihan Belum Lunas</h3>
                <div class="value orange">Rp <?= number_format($total_tagihan, 0, ',', '.') ?></div>
            </div>
            <div class="stat-card">
                <h3>Pembayaran Hari Ini</h3>
                <div class="value green">Rp <?= number_format($pembayaran_hari_ini, 0, ',', '.') ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Siswa</h3>
                <div class="value blue"><?= $total_siswa ?></div>
            </div>
        </div>

        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('tagihan')">Kelola Tagihan</button>
            <button class="tab-btn" onclick="showTab('pembayaran')">Pembayaran Manual</button>
            <button class="tab-btn" onclick="showTab('riwayat')">Riwayat Pembayaran</button>
        </div>

        <!-- Tab Kelola Tagihan -->
        <div id="tab-tagihan" class="tab-content active">
            <div class="card">
                <h2>Tambah Tagihan Baru</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="tambah_tagihan">
                    
                    <div class="form-group">
                        <label>Pilih Siswa</label>
                        <select name="student_id" required>
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= $student['id'] ?>">
                                    <?= htmlspecialchars($student['name']) ?> - <?= htmlspecialchars($student['class']) ?>
                                </option>
                            <?php endforeach; ?>
                    </select>
                    </div>

                    <div class="form-group">
                        <label>Nama Tagihan</label>
                        <input type="text" name="nama_tagihan" required placeholder="Contoh: SPP Bulan Januari 2024">
                    </div>

                    <div class="form-group">
                        <label>Jumlah (Rp)</label>
                        <input type="number" name="jumlah" step="0.01" min="0" required placeholder="0">
                    </div>

                    <div class="form-group">
                        <label>Keterangan (Opsional)</label>
                        <textarea name="keterangan" rows="3" placeholder="Tambahkan keterangan jika diperlukan"></textarea>
                    </div>

                    <button type="submit" class="btn">Tambah Tagihan</button>
                </form>
            </div>

            <div class="card">
                <h2>Daftar Tagihan</h2>
                <?php if (empty($tagihan_list)): ?>
                    <p style="text-align: center; color: #666; padding: 40px;">
                        Tidak ada tagihan yang terdaftar.
                    </p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Siswa</th>
                                <th>Nama Tagihan</th>
                                <th>Jumlah</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tagihan_list as $tagihan): ?>
                                <tr>
                                    <td><?= htmlspecialchars($tagihan['student_name']) ?> (<?= htmlspecialchars($tagihan['class']) ?>)</td>
                                    <td><?= htmlspecialchars($tagihan['nama_tagihan']) ?></td>
                                    <td><strong>Rp <?= number_format($tagihan['jumlah'], 0, ',', '.') ?></strong></td>
                                    <td><?= htmlspecialchars($tagihan['keterangan'] ?? '-') ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin hapus tagihan ini?')">
                                            <input type="hidden" name="action" value="hapus_tagihan">
                                            <input type="hidden" name="id" value="<?= $tagihan['id'] ?>">
                                            <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Pembayaran Manual -->
        <div id="tab-pembayaran" class="tab-content">
            <div class="card">
                <h2>Catat Pembayaran Manual</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="tambah_pembayaran_manual">
                    
                    <div class="form-group">
                        <label>Pilih Siswa</label>
                        <select name="student_id" required>
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= $student['id'] ?>">
                                    <?= htmlspecialchars($student['name']) ?> - <?= htmlspecialchars($student['class']) ?>
                                </option>
                            <?php endforeach; ?>
                    </select>
                    </div>

                    <div class="form-group">
                        <label>Nama Tagihan yang Dibayar</label>
                        <input type="text" name="nama_tagihan" required placeholder="Contoh: SPP Bulan Januari 2024">
                    </div>

                    <div class="form-group">
                        <label>Jumlah (Rp)</label>
                        <input type="number" name="jumlah" step="0.01" min="0" required placeholder="0">
                    </div>

                    <div class="form-group">
                        <label>Metode Pembayaran</label>
                        <select name="metode" required>
                            <option value="Tunai">Tunai</option>
                            <option value="Transfer Bank">Transfer Bank</option>
                            <option value="Kartu Debit">Kartu Debit</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Keterangan (Opsional)</label>
                        <textarea name="keterangan" rows="3" placeholder="Tambahkan keterangan jika diperlukan"></textarea>
                    </div>

                    <button type="submit" class="btn">Catat Pembayaran</button>
                </form>
            </div>
        </div>

        <!-- Tab Riwayat Pembayaran -->
        <div id="tab-riwayat" class="tab-content">
            <div class="card">
                <h2>Riwayat Pembayaran Manual</h2>
                <?php if (empty($pembayaran_list)): ?>
                    <p style="text-align: center; color: #666; padding: 40px;">
                        Belum ada riwayat pembayaran.
                    </p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Siswa</th>
                                <th>Nama Tagihan</th>
                                <th>Jumlah</th>
                                <th>Metode</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pembayaran_list as $pembayaran): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($pembayaran['tanggal'])) ?></td>
                                    <td><?= htmlspecialchars($pembayaran['student_name']) ?> (<?= htmlspecialchars($pembayaran['class']) ?>)</td>
                                    <td><?= htmlspecialchars($pembayaran['nama_tagihan']) ?></td>
                                    <td><strong>Rp <?= number_format($pembayaran['jumlah'], 0, ',', '.') ?></strong></td>
                                    <td><span class="badge badge-success"><?= htmlspecialchars($pembayaran['metode']) ?></span></td>
                                    <td><?= htmlspecialchars($pembayaran['keterangan'] ?? '-') ?></td>
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
    </script>
</body>
</html>

