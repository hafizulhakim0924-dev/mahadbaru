<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/dosen_absensi_errors.log');

session_start();

// Database Config
$servername = "localhost";
$username = "ypikhair_adminmahadzubair";
$password = "Hakim123!";
$dbname = "ypikhair_mahadzubair";

// Check if dosen is logged in - redirect to unified login if not
if (!isset($_SESSION['dosen_id'])) {
    header('Location: login_siswa.php');
    exit;
}

// Dosen is logged in, show main page
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$dosen_id = $_SESSION['dosen_id'];
$dosen_nama = $_SESSION['dosen_nama'];

// Initialize variables
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'input_absensi') {
        $student_id = intval($_POST['student_id']);
        $mata_kuliah = trim($_POST['mata_kuliah']);
        $tanggal = $_POST['tanggal'];
        $status = $_POST['status'];
        $keterangan = trim($_POST['keterangan'] ?? '');
        
        $stmt = $conn->prepare("INSERT INTO absensi (student_id, mata_kuliah, tanggal, status, keterangan, dosen_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssi", $student_id, $mata_kuliah, $tanggal, $status, $keterangan, $dosen_id);
        
        if ($stmt->execute()) {
            $success = "Absensi berhasil diinput!";
        } else {
            $error = "Gagal menginput absensi: " . $stmt->error;
        }
        $stmt->close();
    } elseif ($_POST['action'] == 'import_students') {
        // Import students massal dengan ID manual
        $data_paste = trim($_POST['data_paste'] ?? '');
        
        if (empty($data_paste)) {
            $error = "Data tidak boleh kosong!";
        } else {
            $lines = explode("\n", $data_paste);
            $students_data = [];
            $errors = [];
            $success_count = 0;
            
            foreach ($lines as $line_num => $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                // Deteksi separator: tab atau koma
                if (strpos($line, "\t") !== false) {
                    $cols = explode("\t", $line);
                } else {
                    $cols = explode(",", $line);
                }
                
                $cols = array_map('trim', $cols);
                
                // Skip header
                if ($line_num == 0 && (strtolower($cols[0]) == 'id' || strtolower($cols[0]) == 'name' || strtolower($cols[0]) == 'nama')) {
                    continue;
                }
                
                // Format urutan: ID, Nama, Kelas, Tingkat, SPP Bulanan, Tambahan, Biaya Tambahan, Password, Phone No, Balance
                if (count($cols) < 2) {
                    $errors[] = "Baris " . ($line_num + 1) . ": Data tidak lengkap (minimal: ID, Nama)";
                    continue;
                }
                
                // Urutan: ID (kolom 0), Nama (kolom 1), kemudian kolom lainnya
                $id = !empty($cols[0]) ? intval($cols[0]) : null;
                $name = $cols[1] ?? '';
                $class = $cols[2] ?? '';
                $tingkat = $cols[3] ?? '';
                $spp_bulanan = isset($cols[4]) && $cols[4] !== '' ? intval($cols[4]) : null;
                $tambahan = $cols[5] ?? '';
                $biayatambahan = isset($cols[6]) && $cols[6] !== '' ? intval($cols[6]) : null;
                $password = $cols[7] ?? '123456'; // Default password
                $phone_no = isset($cols[8]) && $cols[8] !== '' ? $cols[8] : null;
                $balance = isset($cols[9]) && $cols[9] !== '' ? intval($cols[9]) : null;
                
                if (empty($name)) {
                    $errors[] = "Baris " . ($line_num + 1) . ": Nama tidak boleh kosong";
                    continue;
                }
                
                if ($id === null || $id <= 0) {
                    $errors[] = "Baris " . ($line_num + 1) . ": ID harus diisi dan berupa angka positif";
                    continue;
                }
                
                // Cek apakah ID sudah ada
                $stmt_check = $conn->prepare("SELECT id FROM students WHERE id = ?");
                $stmt_check->bind_param("i", $id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                
                if ($result_check->num_rows > 0) {
                    $errors[] = "Baris " . ($line_num + 1) . ": ID $id sudah terdaftar";
                    $stmt_check->close();
                    continue;
                }
                $stmt_check->close();
                
                $students_data[] = [
                    'id' => $id,
                    'name' => $name,
                    'class' => $class,
                    'tingkat' => $tingkat,
                    'spp_bulanan' => $spp_bulanan,
                    'tambahan' => $tambahan,
                    'biayatambahan' => $biayatambahan,
                    'password' => $password,
                    'phone_no' => $phone_no,
                    'balance' => $balance
                ];
            }
            
            // Insert ke database
            if (!empty($students_data)) {
                $conn->begin_transaction();
                
                try {
                    foreach ($students_data as $data) {
                        // Build query dengan ID manual (harus selalu include id)
                        $fields = ['id', 'name'];
                        $values = [$data['id'], $data['name']];
                        $types = 'is';
                        $placeholders = '?,?';
                        
                        // Tambahkan kolom yang tidak kosong
                        if (!empty($data['class'])) {
                            $fields[] = 'class';
                            $values[] = $data['class'];
                            $types .= 's';
                            $placeholders .= ',?';
                        }
                        if (!empty($data['tingkat'])) {
                            $fields[] = 'tingkat';
                            $values[] = $data['tingkat'];
                            $types .= 's';
                            $placeholders .= ',?';
                        }
                        if ($data['spp_bulanan'] !== null) {
                            $fields[] = 'spp_bulanan';
                            $values[] = $data['spp_bulanan'];
                            $types .= 'i';
                            $placeholders .= ',?';
                        }
                        if (!empty($data['tambahan'])) {
                            $fields[] = 'tambahan';
                            $values[] = $data['tambahan'];
                            $types .= 's';
                            $placeholders .= ',?';
                        }
                        if ($data['biayatambahan'] !== null) {
                            $fields[] = 'biayatambahan';
                            $values[] = $data['biayatambahan'];
                            $types .= 'i';
                            $placeholders .= ',?';
                        }
                        if (!empty($data['password'])) {
                            $fields[] = 'password';
                            $values[] = $data['password'];
                            $types .= 's';
                            $placeholders .= ',?';
                        }
                        if ($data['phone_no'] !== null && $data['phone_no'] !== '') {
                            $fields[] = 'phone_no';
                            $values[] = $data['phone_no'];
                            $types .= 's';
                            $placeholders .= ',?';
                        }
                        if ($data['balance'] !== null) {
                            $fields[] = 'balance';
                            $values[] = $data['balance'];
                            $types .= 'i';
                            $placeholders .= ',?';
                        }
                        
                        // Gunakan INSERT dengan ID eksplisit (akan override AUTO_INCREMENT jika ID belum ada)
                        $sql = "INSERT INTO students (" . implode(', ', $fields) . ") VALUES (" . $placeholders . ")";
                        $stmt = $conn->prepare($sql);
                        
                        if ($stmt) {
                            $stmt->bind_param($types, ...$values);
                            
                            if ($stmt->execute()) {
                                $success_count++;
                            } else {
                                $errors[] = "Gagal insert ID {$data['id']} ({$data['name']}): " . $stmt->error;
                            }
                            $stmt->close();
                        } else {
                            $errors[] = "Gagal prepare query untuk ID {$data['id']}: " . $conn->error;
                        }
                    }
                    
                    if ($success_count > 0) {
                        $conn->commit();
                        $success = "Berhasil mengimport $success_count siswa!";
                        if (!empty($errors)) {
                            $error = "Beberapa data gagal diimport:<br>" . implode("<br>", array_map('htmlspecialchars', array_slice($errors, 0, 10)));
                        }
                    } else {
                        $conn->rollback();
                        $error = "Tidak ada data yang berhasil diimport.<br>" . implode("<br>", array_map('htmlspecialchars', array_slice($errors, 0, 10)));
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error: " . $e->getMessage();
                }
            } else {
                $error = "Tidak ada data valid untuk diimport.<br>" . implode("<br>", array_map('htmlspecialchars', array_slice($errors, 0, 10)));
            }
        }
    } elseif ($_POST['action'] == 'import_tagihan') {
        // Import tagihan massal dari format baru (setiap siswa punya textarea sendiri)
        $tagihan_input = $_POST['tagihan'] ?? [];
        $tagihan_data = [];
        $errors = [];
        $success_count = 0;
        
        if (empty($tagihan_input)) {
            $error = "Tidak ada data tagihan yang diinput!";
        } else {
            foreach ($tagihan_input as $student_id => $data_paste) {
                $student_id = intval($student_id);
                $data_paste = trim($data_paste);
                
                if (empty($data_paste)) {
                    continue; // Skip jika kosong
                }
                
                if ($student_id <= 0) {
                    $errors[] = "ID siswa tidak valid: $student_id";
                    continue;
                }
                
                // Cek apakah student_id ada
                $stmt_check = $conn->prepare("SELECT id, name FROM students WHERE id = ?");
                $stmt_check->bind_param("i", $student_id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                if ($result_check->num_rows == 0) {
                    $errors[] = "Student ID $student_id tidak ditemukan";
                    $stmt_check->close();
                    continue;
                }
                $student_info = $result_check->fetch_assoc();
                $stmt_check->close();
                
                // Parse data tagihan (format: nama_tagihan, jumlah, nama_tagihan, jumlah, ...)
                // Deteksi separator: tab atau koma
                if (strpos($data_paste, "\t") !== false) {
                    $cols = explode("\t", $data_paste);
                } else {
                    $cols = explode(",", $data_paste);
                }
                
                $cols = array_map('trim', $cols);
                $cols = array_filter($cols); // Hapus empty values
                $cols = array_values($cols); // Re-index
                
                // Parse pasangan nama_tagihan dan jumlah
                $idx = 0;
                while ($idx < count($cols) - 1) {
                    $nama_tagihan = trim($cols[$idx] ?? '');
                    
                    if (empty($nama_tagihan)) {
                        $idx++;
                        continue;
                    }
                    
                    // Kolom berikutnya harus jumlah
                    $jumlah_str = isset($cols[$idx + 1]) ? trim($cols[$idx + 1]) : '';
                    $jumlah = intval(str_replace(['Rp', 'rp', '.', ','], '', $jumlah_str));
                    
                    if ($jumlah <= 0) {
                        $errors[] = "Siswa " . $student_info['name'] . " (ID: $student_id): Jumlah tagihan '$nama_tagihan' tidak valid";
                        error_log("DosenAbsensi - Import Tagihan: Jumlah tidak valid untuk siswa ID $student_id, tagihan: $nama_tagihan");
                        $idx += 2;
                        continue;
                    }
                    
                    // Validasi panjang nama_tagihan (max 150 karakter sesuai struktur tabel)
                    if (strlen($nama_tagihan) > 150) {
                        $nama_tagihan = substr($nama_tagihan, 0, 150);
                        error_log("DosenAbsensi - Import Tagihan: Nama tagihan dipotong untuk siswa ID $student_id");
                    }
                    
                    $tagihan_data[] = [
                        'student_id' => $student_id,
                        'nama_tagihan' => $nama_tagihan,
                        'jumlah' => $jumlah
                    ];
                    
                    $idx += 2; // Skip: nama_tagihan, jumlah
                }
            }
            
            // Insert ke database
            if (!empty($tagihan_data)) {
                $conn->begin_transaction();
                
                try {
                    // Sesuai struktur tabel: hanya student_id, nama_tagihan, jumlah (tanpa keterangan)
                    $stmt = $conn->prepare("INSERT INTO tagihan (student_id, nama_tagihan, jumlah) VALUES (?, ?, ?)");
                    
                    if (!$stmt) {
                        throw new Exception("Error preparing query: " . $conn->error);
                    }
                    
                    foreach ($tagihan_data as $data) {
                        $stmt->bind_param("isi", 
                            $data['student_id'],
                            $data['nama_tagihan'],
                            $data['jumlah']
                        );
                        
                        if ($stmt->execute()) {
                            $success_count++;
                        } else {
                            $error_msg = "Gagal insert tagihan: " . $data['nama_tagihan'] . " untuk student ID " . $data['student_id'] . " - " . $stmt->error;
                            $errors[] = $error_msg;
                            error_log("DosenAbsensi - Import Tagihan Error: " . $error_msg);
                        }
                    }
                    
                    $stmt->close();
                    
                    if ($success_count > 0) {
                        $conn->commit();
                        $success = "Berhasil mengimport $success_count tagihan!";
                        if (!empty($errors)) {
                            $error = "Beberapa data gagal diimport:<br>" . implode("<br>", array_map('htmlspecialchars', array_slice($errors, 0, 10)));
                        }
                    } else {
                        $conn->rollback();
                        $error = "Tidak ada data yang berhasil diimport.<br>" . implode("<br>", array_map('htmlspecialchars', array_slice($errors, 0, 10)));
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $error_msg = "Error: " . $e->getMessage();
                    $error = $error_msg;
                    error_log("DosenAbsensi - Import Tagihan Exception: " . $error_msg);
                }
            } else {
                $error = "Tidak ada data valid untuk diimport. Pastikan format tagihan benar (Nama Tagihan | Jumlah).";
                error_log("DosenAbsensi - Import Tagihan: Tidak ada data valid");
            }
        }
    }
}

// Get list of students
$stmt = $conn->prepare("SELECT id, name, class FROM students ORDER BY name ASC");
$stmt->execute();
$result = $stmt->get_result();
$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();

// Get recent absensi
$stmt = $conn->prepare("
    SELECT a.*, s.name as student_name, s.class 
    FROM absensi a 
    LEFT JOIN students s ON a.student_id = s.id 
    WHERE a.dosen_id = ? 
    ORDER BY a.tanggal DESC, a.created_at DESC 
    LIMIT 50
");
$stmt->bind_param("i", $dosen_id);
$stmt->execute();
$result = $stmt->get_result();
$recent_absensi = [];
while ($row = $result->fetch_assoc()) {
    $recent_absensi[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Absensi - Dosen</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; }
        .header h1 { margin-bottom: 5px; }
        .header p { opacity: 0.9; }
        .logout { float: right; background: rgba(255,255,255,0.2); color: white; padding: 8px 16px; text-decoration: none; border-radius: 20px; font-size: 14px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card h2 { color: #2d3748; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #2d3748; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 5px; font-size: 14px; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #667eea; }
        .btn { padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
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
        @media (max-width: 768px) {
            div[style*="grid-template-columns: 1fr 1fr"] {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="?logout=1" class="logout">Logout</a>
        <h1>Dashboard Dosen</h1>
        <p>Dosen: <?= htmlspecialchars($dosen_nama) ?></p>
    </div>

    <div class="container">
        <?php if (isset($success)): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <!-- Error Debug Panel -->
        <?php if (isset($conn) && $conn->errno): ?>
            <div class="error" style="background: #fff3cd; border: 1px solid #ffc107; color: #856404;">
                <strong>⚠️ Database Error:</strong><br>
                <?= htmlspecialchars($conn->error) ?><br>
                <small>Error Code: <?= $conn->errno ?></small>
            </div>
        <?php endif; ?>
        
        <?php 
        $php_errors = error_get_last();
        if ($php_errors && $php_errors['type'] === E_ERROR): ?>
            <div class="error" style="background: #f8d7da; border: 1px solid #dc3545; color: #721c24;">
                <strong>❌ PHP Error:</strong><br>
                <?= htmlspecialchars($php_errors['message']) ?><br>
                <small>File: <?= htmlspecialchars($php_errors['file']) ?> | Line: <?= $php_errors['line'] ?></small>
            </div>
        <?php endif; ?>
        
        <!-- Debug Info -->
        <details style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 12px;">
            <summary style="cursor: pointer; font-weight: 600; color: #667eea;">🔍 Debug Information</summary>
            <div style="margin-top: 10px; padding: 10px; background: white; border-radius: 5px;">
                <strong>Database:</strong> <?= isset($conn) && $conn ? '✓ Connected' : '✗ Not Connected' ?><br>
                <?php if (isset($conn) && $conn && $conn->errno): ?>
                    <strong>Error:</strong> <?= htmlspecialchars($conn->error) ?> (Code: <?= $conn->errno ?>)<br>
                <?php endif; ?>
                <strong>Session:</strong> Dosen ID: <?= isset($_SESSION['dosen_id']) ? $_SESSION['dosen_id'] : 'Not set' ?><br>
                <strong>Error Log:</strong> <code><?= __DIR__ ?>/dosen_absensi_errors.log</code>
            </div>
        </details>

        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('absensi')">Input Absensi</button>
            <button class="tab-btn" onclick="showTab('import_students')">Import Students</button>
            <button class="tab-btn" onclick="showTab('import_tagihan')">Import Tagihan</button>
        </div>

        <!-- Tab Input Absensi -->
        <div id="tab-absensi" class="tab-content active">
        <div class="card">
            <h2>Input Absensi Baru</h2>
            <form method="POST">
                <input type="hidden" name="action" value="input_absensi">
                
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

                <button type="submit" class="btn">Simpan Absensi</button>
            </form>
        </div>

        <div class="card">
            <h2>Riwayat Input Absensi Terbaru</h2>
            <?php if (empty($recent_absensi)): ?>
                <p style="text-align: center; color: #666; padding: 40px;">
                    Belum ada data absensi yang diinput.
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
                        <?php foreach ($recent_absensi as $abs): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($abs['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($abs['student_name']) ?> (<?= htmlspecialchars($abs['class']) ?>)</td>
                                <td><?= htmlspecialchars($abs['mata_kuliah']) ?></td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    $status_text = '';
                                    switch($abs['status']) {
                                        case 'hadir': $status_class = 'badge-success'; $status_text = 'Hadir'; break;
                                        case 'izin': $status_class = 'badge-warning'; $status_text = 'Izin'; break;
                                        case 'sakit': $status_class = 'badge-warning'; $status_text = 'Sakit'; break;
                                        case 'alpha': $status_class = 'badge-danger'; $status_text = 'Alpha'; break;
                                    }
                                    ?>
                                    <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                                </td>
                                <td><?= htmlspecialchars($abs['keterangan'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        </div>

        <!-- Tab Import Students -->
        <div id="tab-import_students" class="tab-content">
            <div class="card">
                <h2>Import Students Massal</h2>
                <p style="margin-bottom: 20px; color: #666;">
                    Import siswa secara massal dengan format CSV. <strong>ID harus diisi manual oleh dosen</strong> sebelum nama.
                </p>
                
                <div style="background: #f0f4ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #667eea;">
                    <strong>Urutan Format:</strong> <strong>ID, Nama</strong>, Kelas, Tingkat, SPP Bulanan, Tambahan, Biaya Tambahan, Password, Phone No, Balance<br>
                    <code style="display: block; margin-top: 8px; padding: 8px; background: white; border-radius: 4px; font-size: 12px;">
                        1001, Ahmad Zaki, X IPA 1, X, 500000, , , 123456, 081234567890, 0<br>
                        1002, Siti Nurhaliza, X IPA 1, X, 500000, , , 123456, 081234567891, 0<br>
                        1003, Budi Santoso, XI IPS 1, XI, 450000, , , 123456, 081234567892, 0
                    </code>
                    <small style="color: #666; display: block; margin-top: 8px;">
                        <strong>Catatan:</strong> <strong>ID harus diisi manual oleh dosen</strong> dan harus unik. Kolom setelah Nama bisa dikosongkan (biarkan kosong atau isi dengan koma).
                    </small>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="import_students">
                    
                    <div class="form-group">
                        <label>Paste Data Students dari Spreadsheet</label>
                        <textarea 
                            name="data_paste" 
                            rows="15" 
                            style="font-family: 'Courier New', monospace; font-size: 13px;"
                            placeholder="Urutan: ID, Nama, Kelas, Tingkat, SPP Bulanan, Tambahan, Biaya Tambahan, Password, Phone No, Balance&#10;&#10;Contoh:&#10;1001, Ahmad Zaki, X IPA 1, X, 500000, , , 123456, 081234567890, 0&#10;1002, Siti Nurhaliza, X IPA 1, X, 500000, , , 123456, 081234567891, 0"
                            required
                        ></textarea>
                        <small style="color: #666; font-size: 12px; display: block; margin-top: 4px;">
                            <strong>Urutan Format:</strong> <strong>ID, Nama</strong>, Kelas, Tingkat, SPP Bulanan, Tambahan, Biaya Tambahan, Password, Phone No, Balance<br>
                            <strong>Catatan:</strong> <strong>ID harus diisi manual oleh dosen</strong> dan harus unik. Kolom setelah Nama bisa dikosongkan (biarkan kosong atau isi dengan koma).
                        </small>
                    </div>

                    <button type="submit" class="btn">Import Students</button>
                </form>
            </div>
        </div>

        <!-- Tab Import Tagihan -->
        <div id="tab-import_tagihan" class="tab-content">
            <div class="card">
                <h2>Import Tagihan Massal</h2>
                <p style="margin-bottom: 20px; color: #666;">
                    <strong>Cara penggunaan:</strong><br>
                    1. Copy data tagihan dari spreadsheet (hanya kolom tagihan saja, tanpa ID dan Nama)<br>
                    2. Paste di textarea "Paste Data dari Spreadsheet" di bawah<br>
                    3. Format: <strong>Nama Tagihan 1 | Jumlah 1 | Nama Tagihan 2 | Jumlah 2 | ...</strong><br>
                    4. Urutan baris harus sesuai dengan urutan siswa di tabel<br>
                    5. Klik "Auto Fill ke Tabel" untuk mengisi otomatis berdasarkan urutan<br>
                    6. Atau paste langsung per baris di kolom "Paste Tagihan"<br>
                    7. Klik "Import Tagihan" untuk menyimpan
                </p>
                
                <div style="background: #f0f4ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #667eea;">
                    <strong>Contoh Format dari Spreadsheet (Hanya Tagihan):</strong><br>
                    <code style="display: block; margin-top: 8px; padding: 8px; background: white; border-radius: 4px; font-size: 12px; font-family: monospace;">
                        SPP Januari	500000	SPP Februari	500000	Uang Gedung	2000000<br>
                        SPP Januari	500000	SPP Maret	500000<br>
                        SPP Januari	500000	SPP Februari	500000	SPP Maret	500000
                    </code>
                    <small style="color: #666; display: block; margin-top: 8px;">
                        * Format: Nama Tagihan | Jumlah | Nama Tagihan | Jumlah | ...<br>
                        * <strong>PENTING:</strong> Urutan baris harus sesuai dengan urutan siswa di tabel (baris 1 = siswa pertama, baris 2 = siswa kedua, dst)<br>
                        * Bisa paste 100+ baris sekaligus, sistem akan mengisi berdasarkan urutan baris
                    </small>
                </div>
                
                <!-- Area Paste Massal -->
                <div style="background: #fff9e6; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 2px solid #ffc107;">
                    <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #2d3748;">
                        📋 Paste Data dari Spreadsheet (Semua Baris Sekaligus)
                    </label>
                    <textarea 
                        id="bulk_paste_area" 
                        rows="8" 
                        style="width: 100%; font-family: 'Courier New', monospace; font-size: 13px; padding: 10px; border: 2px solid #ffc107; border-radius: 5px;"
                        placeholder="Paste data tagihan dari spreadsheet di sini (hanya tagihan, tanpa ID dan Nama)...&#10;&#10;Contoh:&#10;SPP Januari	500000	SPP Februari	500000	Uang Gedung	2000000&#10;SPP Januari	500000	SPP Maret	500000&#10;SPP Januari	500000	SPP Februari	500000	SPP Maret	500000"
                    ></textarea>
                    <div style="margin-top: 10px;">
                        <button type="button" class="btn" onclick="autoFillFromBulk()" style="background: #ffc107; color: #000;">
                            🔄 Auto Fill ke Tabel
                        </button>
                        <button type="button" class="btn" onclick="clearBulkPaste()" style="background: #6c757d; margin-left: 10px;">
                            Clear
                        </button>
                    </div>
                </div>
                
                <form method="POST" id="form_import_tagihan">
                    <input type="hidden" name="action" value="import_tagihan">
                    
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="background: #667eea; color: white; padding: 12px; text-align: left; font-weight: 600; min-width: 60px;">ID</th>
                                    <th style="background: #667eea; color: white; padding: 12px; text-align: left; font-weight: 600; min-width: 200px;">Nama</th>
                                    <th style="background: #667eea; color: white; padding: 12px; text-align: left; font-weight: 600; min-width: 120px;">Kelas</th>
                                    <th style="background: #667eea; color: white; padding: 12px; text-align: left; font-weight: 600; min-width: 400px;">Paste Tagihan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                    <tr>
                                        <td colspan="4" style="padding: 20px; text-align: center; color: #666;">
                                            Tidak ada data siswa
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($students as $student): ?>
                                        <tr style="border-bottom: 1px solid #e2e8f0;" data-student-id="<?= $student['id'] ?>" data-student-name="<?= htmlspecialchars(strtolower($student['name'])) ?>">
                                            <td style="padding: 10px; font-weight: 600; color: #667eea; vertical-align: top;">
                                                <?= htmlspecialchars($student['id']) ?>
                                            </td>
                                            <td style="padding: 10px; vertical-align: top;">
                                                <?= htmlspecialchars($student['name']) ?>
                                            </td>
                                            <td style="padding: 10px; color: #666; vertical-align: top;">
                                                <?= htmlspecialchars($student['class'] ?? '-') ?>
                                            </td>
                                            <td style="padding: 10px; vertical-align: top;">
                                                <textarea 
                                                    name="tagihan[<?= $student['id'] ?>]" 
                                                    id="tagihan_<?= $student['id'] ?>"
                                                    rows="2" 
                                                    style="width: 100%; font-family: 'Courier New', monospace; font-size: 12px; padding: 8px; border: 1px solid #cbd5e0; border-radius: 4px; resize: vertical;"
                                                    placeholder="Contoh: SPP Januari	500000	SPP Februari	500000"
                                                ></textarea>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn">Import Tagihan</button>
                        <button type="button" class="btn" onclick="clearAllTagihan()" style="background: #e53e3e; margin-left: 10px;">Clear All</button>
                    </div>
                </form>
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
        
        function clearAllTagihan() {
            if (confirm('Yakin ingin menghapus semua input tagihan?')) {
                const textareas = document.querySelectorAll('textarea[name^="tagihan["]');
                textareas.forEach(textarea => {
                    textarea.value = '';
                });
            }
        }
        
        function clearBulkPaste() {
            document.getElementById('bulk_paste_area').value = '';
        }
        
        function autoFillFromBulk() {
            const bulkData = document.getElementById('bulk_paste_area').value.trim();
            if (!bulkData) {
                alert('Silakan paste data terlebih dahulu!');
                return;
            }
            
            const lines = bulkData.split('\n');
            const textareas = document.querySelectorAll('textarea[name^="tagihan["]');
            let filledCount = 0;
            let skippedCount = 0;
            
            lines.forEach((line, lineNum) => {
                const trimmedLine = line.trim();
                if (!trimmedLine) {
                    skippedCount++;
                    return; // Skip baris kosong
                }
                
                // Cek apakah masih ada textarea yang tersedia
                if (lineNum >= textareas.length) {
                    skippedCount++;
                    return; // Skip jika baris lebih banyak dari jumlah siswa
                }
                
                // Ambil textarea berdasarkan urutan baris
                const textarea = textareas[lineNum];
                if (textarea) {
                    // Deteksi separator: tab atau koma
                    const separator = trimmedLine.indexOf('\t') !== -1 ? '\t' : ',';
                    
                    // Langsung isi dengan data tagihan (tanpa perlu parse ID/Nama)
                    textarea.value = trimmedLine;
                    filledCount++;
                    
                    // Highlight row untuk feedback visual
                    const row = textarea.closest('tr');
                    row.style.backgroundColor = '#d4edda';
                    setTimeout(() => {
                        row.style.backgroundColor = '';
                    }, 2000);
                }
            });
            
            // Tampilkan hasil
            let message = `Berhasil mengisi ${filledCount} baris!`;
            if (skippedCount > 0) {
                message += `\n\n${skippedCount} baris dilewati (kosong atau melebihi jumlah siswa).`;
            }
            if (lines.length > textareas.length) {
                message += `\n\nPeringatan: Data yang di-paste (${lines.length} baris) lebih banyak dari jumlah siswa (${textareas.length}). Baris kelebihan akan diabaikan.`;
            }
            alert(message);
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

