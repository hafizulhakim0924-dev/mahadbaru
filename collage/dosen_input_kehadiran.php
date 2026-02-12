<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/dosen_input_kehadiran_errors.log');

session_start();

// Database Config
$servername = "localhost";
$username = "ypikhair_adminmahadzubair";
$password = "Hakim123!";
$dbname = "ypikhair_mahadzubair";

// Initialize variables
$success = '';
$error = '';
$dosen_id = null;
$dosen_nama = null;
$conn = null;

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: dosen_input_kehadiran.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'login') {
    $login_username = trim($_POST['username'] ?? '');
    $login_password = trim($_POST['password'] ?? '');
    
    if (empty($login_username) || empty($login_password)) {
        $error = "Username dan password harus diisi!";
    } else {
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            $error = "Koneksi database gagal: " . $conn->connect_error;
        } else {
            $conn->set_charset("utf8mb4");
            
            $stmt = $conn->prepare("SELECT * FROM dosen WHERE username = ? AND status = 'aktif'");
            if ($stmt) {
                $stmt->bind_param("s", $login_username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $dosen = $result->fetch_assoc();
                    
                    if ($dosen['password'] === $login_password) {
                        $_SESSION['dosen_id'] = $dosen['id'];
                        $_SESSION['dosen_nama'] = $dosen['nama'];
                        $_SESSION['last_activity'] = time();
                        
                        $stmt->close();
                        $conn->close();
                        
                        header('Location: dosen_input_kehadiran.php');
                        exit;
                    } else {
                        $error = "Password salah!";
                    }
                } else {
                    $error = "Username tidak ditemukan atau tidak aktif!";
                }
                $stmt->close();
            } else {
                $error = "Error preparing query: " . $conn->error;
            }
            $conn->close();
        }
    }
}

// Check if dosen is logged in
if (!isset($_SESSION['dosen_id'])) {
    // Show login form
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Dosen - Input Kehadiran</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .login-container {
                background: white;
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                max-width: 400px;
                width: 100%;
            }
            .login-container h1 {
                color: #2d3748;
                margin-bottom: 10px;
                text-align: center;
            }
            .login-container p {
                color: #718096;
                text-align: center;
                margin-bottom: 30px;
                font-size: 14px;
            }
            .form-group {
                margin-bottom: 20px;
            }
            .form-group label {
                display: block;
                margin-bottom: 8px;
                color: #2d3748;
                font-weight: 600;
            }
            .form-group input {
                width: 100%;
                padding: 12px;
                border: 1px solid #cbd5e0;
                border-radius: 8px;
                font-size: 14px;
            }
            .form-group input:focus {
                outline: none;
                border-color: #667eea;
            }
            .btn {
                width: 100%;
                padding: 12px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
                font-size: 16px;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            }
            .error {
                background: #f8d7da;
                color: #721c24;
                padding: 12px;
                border-radius: 8px;
                margin-bottom: 20px;
            }
            .success {
                background: #d4edda;
                color: #155724;
                padding: 12px;
                border-radius: 8px;
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h1>Login Dosen</h1>
            <p>Input Kehadiran Siswa</p>
            
            <?php if (isset($error) && !empty($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" class="btn">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Dosen is logged in, show main page
$dosen_id = $_SESSION['dosen_id'];
$dosen_nama = $_SESSION['dosen_nama'];

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'input_kehadiran') {
        // Single input kehadiran
        $student_id = intval($_POST['student_id'] ?? 0);
        $mata_kuliah = trim($_POST['mata_kuliah'] ?? '');
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
        $status = $_POST['status'] ?? 'hadir';
        $keterangan = trim($_POST['keterangan'] ?? '');
        
        if ($student_id <= 0) {
            $error = "Pilih siswa terlebih dahulu!";
        } elseif (empty($mata_kuliah)) {
            $error = "Mata kuliah harus diisi!";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO absensi (student_id, dosen_id, mata_kuliah, tanggal, status, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
                
                if (!$stmt) {
                    throw new Exception("Failed to prepare query: " . $conn->error);
                }
                
                $stmt->bind_param("iissss", $student_id, $dosen_id, $mata_kuliah, $tanggal, $status, $keterangan);
                
                if ($stmt->execute()) {
                    $success = "Kehadiran berhasil diinput!";
                } else {
                    throw new Exception("Failed to execute query: " . $stmt->error);
                }
                $stmt->close();
            } catch (Exception $e) {
                $error = "Gagal menginput kehadiran: " . $e->getMessage();
                error_log("DosenInputKehadiran Error: " . $e->getMessage());
            }
        }
    } elseif ($_POST['action'] == 'import_kehadiran') {
        // Bulk import kehadiran
        $kehadiran_input = $_POST['kehadiran'] ?? [];
        $kehadiran_data = [];
        $errors_import = [];
        $success_count = 0;
        
        if (empty($kehadiran_input)) {
            $error = "Tidak ada data kehadiran yang diinput!";
        } else {
            foreach ($kehadiran_input as $student_id => $data_paste) {
                $student_id = intval($student_id);
                $data_paste = trim($data_paste);
                
                if (empty($data_paste)) {
                    continue;
                }
                
                if ($student_id <= 0) {
                    $errors_import[] = "ID siswa tidak valid: $student_id";
                    continue;
                }
                
                // Check if student_id exists
                try {
                    $stmt_check = $conn->prepare("SELECT id, name FROM students WHERE id = ?");
                    if (!$stmt_check) {
                        throw new Exception("Failed to prepare student check query: " . $conn->error);
                    }
                    $stmt_check->bind_param("i", $student_id);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
                    if ($result_check->num_rows == 0) {
                        $errors_import[] = "Student ID $student_id tidak ditemukan";
                        $stmt_check->close();
                        continue;
                    }
                    $student_info = $result_check->fetch_assoc();
                    $stmt_check->close();
                } catch (Exception $e) {
                    $errors_import[] = "Database error checking student ID $student_id: " . $e->getMessage();
                    error_log("DosenInputKehadiran: " . $e->getMessage());
                    continue;
                }
                
                // Parse kehadiran data (format: tanggal, mata_kuliah, status, keterangan | tanggal, mata_kuliah, status, keterangan | ...)
                // Deteksi separator: tab atau koma atau pipe
                if (strpos($data_paste, "\t") !== false) {
                    $rows = explode("\n", $data_paste);
                } else {
                    $rows = explode("\n", $data_paste);
                }
                
                foreach ($rows as $row) {
                    $row = trim($row);
                    if (empty($row)) continue;
                    
                    // Deteksi separator dalam row
                    if (strpos($row, "\t") !== false) {
                        $cols = explode("\t", $row);
                    } elseif (strpos($row, "|") !== false) {
                        $cols = explode("|", $row);
                    } else {
                        $cols = explode(",", $row);
                    }
                    
                    $cols = array_map('trim', $cols);
                    $cols = array_filter($cols);
                    $cols = array_values($cols);
                    
                    // Format: tanggal, mata_kuliah, status, keterangan (opsional)
                    if (count($cols) < 3) {
                        $errors_import[] = "Siswa " . $student_info['name'] . " (ID: $student_id): Format data tidak valid (minimal: tanggal, mata_kuliah, status)";
                        continue;
                    }
                    
                    $tanggal = trim($cols[0] ?? '');
                    $mata_kuliah = trim($cols[1] ?? '');
                    $status = strtolower(trim($cols[2] ?? 'hadir'));
                    $keterangan = trim($cols[3] ?? '');
                    
                    // Validate tanggal
                    if (empty($tanggal)) {
                        $errors_import[] = "Siswa " . $student_info['name'] . " (ID: $student_id): Tanggal tidak boleh kosong";
                        continue;
                    }
                    
                    // Convert tanggal format (d/m/Y atau Y-m-d)
                    $tanggal_formatted = $tanggal;
                    if (strpos($tanggal, '/') !== false) {
                        $parts = explode('/', $tanggal);
                        if (count($parts) == 3) {
                            $tanggal_formatted = $parts[2] . '-' . str_pad($parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                        }
                    }
                    
                    // Validate status
                    if (!in_array($status, ['hadir', 'izin', 'sakit', 'alpha'])) {
                        $status = 'hadir'; // Default
                    }
                    
                    if (empty($mata_kuliah)) {
                        $errors_import[] = "Siswa " . $student_info['name'] . " (ID: $student_id): Mata kuliah tidak boleh kosong";
                        continue;
                    }
                    
                    $kehadiran_data[] = [
                        'student_id' => $student_id,
                        'tanggal' => $tanggal_formatted,
                        'mata_kuliah' => $mata_kuliah,
                        'status' => $status,
                        'keterangan' => $keterangan,
                    ];
                }
            }
            
            // Insert into database
            if (!empty($kehadiran_data)) {
                $conn->begin_transaction();
                
                try {
                    $stmt = $conn->prepare("INSERT INTO absensi (student_id, dosen_id, mata_kuliah, tanggal, status, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
                    if (!$stmt) {
                        throw new Exception("Failed to prepare absensi insert query: " . $conn->error);
                    }
                    
                    foreach ($kehadiran_data as $data) {
                        $stmt->bind_param("iissss",
                            $data['student_id'],
                            $dosen_id,
                            $data['mata_kuliah'],
                            $data['tanggal'],
                            $data['status'],
                            $data['keterangan']
                        );
                        
                        if ($stmt->execute()) {
                            $success_count++;
                        } else {
                            $errors_import[] = "Gagal insert kehadiran untuk student ID " . $data['student_id'] . " - " . $stmt->error;
                            error_log("DosenInputKehadiran: Failed to insert kehadiran for student ID " . $data['student_id'] . ": " . $stmt->error);
                        }
                    }
                    
                    $stmt->close();
                    
                    if ($success_count > 0) {
                        $conn->commit();
                        $success = "Berhasil mengimport $success_count kehadiran!";
                        if (!empty($errors_import)) {
                            $error = "Beberapa data gagal diimport:<br>" . implode("<br>", array_map('htmlspecialchars', array_slice($errors_import, 0, 10)));
                        }
                    } else {
                        $conn->rollback();
                        $error = "Tidak ada data yang berhasil diimport.<br>" . implode("<br>", array_map('htmlspecialchars', array_slice($errors_import, 0, 10)));
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error saat transaksi database: " . $e->getMessage();
                    error_log("DosenInputKehadiran: Transaction error: " . $e->getMessage());
                }
            } else {
                $error = "Tidak ada data valid untuk diimport. Pastikan format kehadiran benar (Tanggal | Mata Kuliah | Status | Keterangan).";
            }
        }
    }
}

// Get all students
$students = [];
try {
    $stmt = $conn->prepare("SELECT id, name, class FROM students ORDER BY name ASC");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("DosenInputKehadiran: Error fetching students: " . $e->getMessage());
}

// Get recent kehadiran
$recent_kehadiran = [];
try {
    $stmt = $conn->prepare("
        SELECT a.*, s.name as student_name, s.class 
        FROM absensi a 
        LEFT JOIN students s ON a.student_id = s.id 
        WHERE a.dosen_id = ? 
        ORDER BY a.tanggal DESC, a.created_at DESC 
        LIMIT 20
    ");
    if ($stmt) {
        $stmt->bind_param("i", $dosen_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $recent_kehadiran[] = $row;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("DosenInputKehadiran: Error fetching recent kehadiran: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Kehadiran - Dosen</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; }
        .header h1 { margin-bottom: 5px; }
        .header p { opacity: 0.9; }
        .logout { float: right; background: rgba(255,255,255,0.2); color: white; padding: 8px 16px; text-decoration: none; border-radius: 20px; font-size: 14px; }
        .logout:hover { background: rgba(255,255,255,0.3); }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card h2 { color: #2d3748; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #2d3748; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 14px; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #667eea; }
        .btn { padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        .btn-secondary { background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); }
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
        .student-list-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .student-item { border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .student-item-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .student-name { font-weight: 600; color: #2d3748; }
        .student-class { font-size: 12px; color: #718096; }
        .kehadiran-textarea { width: 100%; min-height: 80px; padding: 8px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 12px; font-family: monospace; }
        .bulk-paste-area { margin-bottom: 20px; }
        .bulk-paste-area textarea { width: 100%; min-height: 150px; padding: 10px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 12px; font-family: monospace; }
        .bulk-paste-info { background: #e6fffa; border-left: 4px solid #38b2ac; padding: 10px; margin-bottom: 15px; font-size: 12px; color: #234e52; }
        @media (max-width: 768px) {
            .student-list-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="?logout=1" class="logout">Logout</a>
        <h1>Input Kehadiran Siswa</h1>
        <p>Dosen: <?= htmlspecialchars($dosen_nama) ?></p>
    </div>

    <div class="container">
        <?php if (isset($success) && !empty($success)): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (isset($error) && !empty($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('single')">Input Tunggal</button>
            <button class="tab-btn" onclick="showTab('bulk')">Import Massal</button>
            <button class="tab-btn" onclick="showTab('history')">Riwayat</button>
        </div>

        <!-- Tab Input Tunggal -->
        <div id="tab-single" class="tab-content active">
            <div class="card">
                <h2>Input Kehadiran Tunggal</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="input_kehadiran">
                    
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
                        <label>Mata Kuliah</label>
                        <input type="text" name="mata_kuliah" required placeholder="Contoh: Pemrograman Web">
                    </div>

                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" required value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" required>
                            <option value="hadir">Hadir</option>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="alpha">Alpha</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Keterangan (Opsional)</label>
                        <textarea name="keterangan" rows="3" placeholder="Tambahkan keterangan jika diperlukan"></textarea>
                    </div>

                    <button type="submit" class="btn">Simpan Kehadiran</button>
                </form>
            </div>
        </div>

        <!-- Tab Import Massal -->
        <div id="tab-bulk" class="tab-content">
            <div class="card">
                <h2>Import Kehadiran Massal</h2>
                
                <div class="bulk-paste-info">
                    <strong>📋 Format Data:</strong><br>
                    Setiap baris: <code>Tanggal | Mata Kuliah | Status | Keterangan</code><br>
                    Contoh: <code>2024-02-12 | Pemrograman Web | hadir | Hadir tepat waktu</code><br>
                    Status: hadir, izin, sakit, atau alpha<br>
                    Keterangan opsional. Bisa paste dari spreadsheet (tab atau koma sebagai separator).
                </div>
                
                <div class="bulk-paste-area">
                    <label><strong>Bulk Paste (untuk semua siswa):</strong></label>
                    <textarea id="bulk-paste" placeholder="Paste data dari spreadsheet di sini...&#10;Format: Tanggal | Mata Kuliah | Status | Keterangan"></textarea>
                    <button type="button" class="btn btn-secondary" onclick="autoFillFromBulk()" style="margin-top: 10px;">Auto Fill ke Semua Siswa</button>
                </div>
                
                <form method="POST" id="bulk-form">
                    <input type="hidden" name="action" value="import_kehadiran">
                    
                    <div class="student-list-container">
                        <?php foreach ($students as $student): ?>
                            <div class="student-item">
                                <div class="student-item-header">
                                    <div>
                                        <div class="student-name"><?= htmlspecialchars($student['name']) ?></div>
                                        <div class="student-class">ID: <?= $student['id'] ?> | <?= htmlspecialchars($student['class']) ?></div>
                                    </div>
                                </div>
                                <textarea 
                                    name="kehadiran[<?= $student['id'] ?>]" 
                                    class="kehadiran-textarea" 
                                    placeholder="Tanggal | Mata Kuliah | Status | Keterangan&#10;Contoh: 2024-02-12 | Pemrograman Web | hadir | Hadir tepat waktu"
                                    data-student-id="<?= $student['id'] ?>"
                                ></textarea>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="submit" class="btn" style="margin-top: 20px;">Import Kehadiran</button>
                </form>
            </div>
        </div>

        <!-- Tab Riwayat -->
        <div id="tab-history" class="tab-content">
            <div class="card">
                <h2>Riwayat Input Kehadiran</h2>
                <?php if (empty($recent_kehadiran)): ?>
                    <p style="text-align: center; color: #666; padding: 40px;">
                        Belum ada data kehadiran yang diinput.
                    </p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Siswa</th>
                                <th>Mata Kuliah</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_kehadiran as $kehadiran): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($kehadiran['tanggal'])) ?></td>
                                    <td><?= htmlspecialchars($kehadiran['student_name']) ?> (<?= htmlspecialchars($kehadiran['class']) ?>)</td>
                                    <td><?= htmlspecialchars($kehadiran['mata_kuliah']) ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        switch($kehadiran['status']) {
                                            case 'hadir': $status_class = 'badge-success'; $status_text = 'Hadir'; break;
                                            case 'izin': $status_class = 'badge-warning'; $status_text = 'Izin'; break;
                                            case 'sakit': $status_class = 'badge-warning'; $status_text = 'Sakit'; break;
                                            case 'alpha': $status_class = 'badge-danger'; $status_text = 'Alpha'; break;
                                        }
                                        ?>
                                        <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($kehadiran['keterangan'] ?? '-') ?></td>
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
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById('tab-' + tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        function autoFillFromBulk() {
            const bulkText = document.getElementById('bulk-paste').value.trim();
            if (!bulkText) {
                alert('Silakan paste data terlebih dahulu!');
                return;
            }
            
            const rows = bulkText.split('\n').filter(row => row.trim());
            const textareas = document.querySelectorAll('.kehadiran-textarea');
            
            // Clear all textareas first
            textareas.forEach(textarea => {
                textarea.value = '';
            });
            
            // Fill textareas based on row order
            rows.forEach((row, index) => {
                if (index < textareas.length) {
                    textareas[index].value = row.trim();
                }
            });
            
            alert('Data berhasil diisi ke ' + Math.min(rows.length, textareas.length) + ' siswa!');
        }
    </script>
</body>
</html>

