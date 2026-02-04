<?php
session_start();

// Database Config
$servername = "localhost";
$username = "ypikhair_admin";
$password = "hakim123123123";
$dbname = "ypikhair_datautama";

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
        // Import students massal dari spreadsheet
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
                if ($line_num == 0 && (strtolower($cols[0]) == 'name' || strtolower($cols[0]) == 'nama' || strtolower($cols[0]) == 'id')) {
                    continue;
                }
                
                // Format: name, class, phone_no, password (minimal name dan class)
                if (count($cols) < 2) {
                    $errors[] = "Baris " . ($line_num + 1) . ": Data tidak lengkap (minimal: Nama, Kelas)";
                    continue;
                }
                
                $name = $cols[0] ?? '';
                $class = $cols[1] ?? '';
                $phone_no = $cols[2] ?? '';
                $password = $cols[3] ?? '123456'; // Default password
                $tingkat = $cols[4] ?? '';
                $spp_bulanan = isset($cols[5]) ? intval($cols[5]) : 0;
                
                if (empty($name) || empty($class)) {
                    $errors[] = "Baris " . ($line_num + 1) . ": Nama dan Kelas tidak boleh kosong";
                    continue;
                }
                
                $students_data[] = [
                    'name' => $name,
                    'class' => $class,
                    'phone_no' => $phone_no,
                    'password' => $password,
                    'tingkat' => $tingkat,
                    'spp_bulanan' => $spp_bulanan
                ];
            }
            
            // Insert ke database
            if (!empty($students_data)) {
                $conn->begin_transaction();
                
                try {
                    // Cek struktur tabel students
                    $table_check = $conn->query("SHOW COLUMNS FROM students LIKE 'tingkat'");
                    $has_tingkat = $table_check && $table_check->num_rows > 0;
                    
                    if ($has_tingkat) {
                        $stmt = $conn->prepare("INSERT INTO students (name, class, phone_no, password, tingkat, spp_bulanan) VALUES (?, ?, ?, ?, ?, ?)");
                    } else {
                        $stmt = $conn->prepare("INSERT INTO students (name, class, phone_no, password) VALUES (?, ?, ?, ?)");
                    }
                    
                    foreach ($students_data as $data) {
                        if ($has_tingkat) {
                            $stmt->bind_param("sssssi", $data['name'], $data['class'], $data['phone_no'], $data['password'], $data['tingkat'], $data['spp_bulanan']);
                        } else {
                            $stmt->bind_param("ssss", $data['name'], $data['class'], $data['phone_no'], $data['password']);
                        }
                        
                        if ($stmt->execute()) {
                            $success_count++;
                        } else {
                            $errors[] = "Gagal insert: " . $data['name'] . " - " . $stmt->error;
                        }
                    }
                    
                    $stmt->close();
                    
                    if ($success_count > 0) {
                        $conn->commit();
                        $success = "Berhasil mengimport $success_count siswa!";
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
    } elseif ($_POST['action'] == 'import_tagihan') {
        // Import tagihan massal dari spreadsheet
        $data_paste = trim($_POST['data_paste'] ?? '');
        
        if (empty($data_paste)) {
            $error = "Data tidak boleh kosong!";
        } else {
            $lines = explode("\n", $data_paste);
            $tagihan_data = [];
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
                if ($line_num == 0 && (strtolower($cols[0]) == 'student_id' || strtolower($cols[0]) == 'id' || strtolower($cols[0]) == 'nama')) {
                    continue;
                }
                
                // Format: student_id, nama_tagihan, jumlah, keterangan
                // Atau: student_id, nama_tagihan1, jumlah1, nama_tagihan2, jumlah2, ... (multiple tagihan per student)
                if (count($cols) < 3) {
                    $errors[] = "Baris " . ($line_num + 1) . ": Data tidak lengkap (minimal: ID, Nama Tagihan, Jumlah)";
                    continue;
                }
                
                $student_id = intval($cols[0] ?? 0);
                if ($student_id <= 0) {
                    $errors[] = "Baris " . ($line_num + 1) . ": ID siswa tidak valid";
                    continue;
                }
                
                // Cek apakah student_id ada
                $stmt_check = $conn->prepare("SELECT id FROM students WHERE id = ?");
                $stmt_check->bind_param("i", $student_id);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();
                if ($result_check->num_rows == 0) {
                    $errors[] = "Baris " . ($line_num + 1) . ": Student ID $student_id tidak ditemukan";
                    $stmt_check->close();
                    continue;
                }
                $stmt_check->close();
                
                // Parse multiple tagihan (format: id, tagihan1, jumlah1, tagihan2, jumlah2, ...)
                // Format sederhana: setiap pasangan nama_tagihan dan jumlah
                $idx = 1;
                while ($idx < count($cols) - 1) {
                    $nama_tagihan = trim($cols[$idx] ?? '');
                    
                    if (empty($nama_tagihan)) {
                        $idx++;
                        continue;
                    }
                    
                    // Kolom berikutnya harus jumlah
                    $jumlah_str = isset($cols[$idx + 1]) ? trim($cols[$idx + 1]) : '';
                    $jumlah = floatval(str_replace(['Rp', 'rp', '.', ','], '', $jumlah_str));
                    
                    if ($jumlah <= 0) {
                        $errors[] = "Baris " . ($line_num + 1) . ": Jumlah tagihan '$nama_tagihan' tidak valid";
                        $idx += 2;
                        continue;
                    }
                    
                    // Keterangan opsional (kolom setelah jumlah, jika ada dan bukan angka)
                    $keterangan = '';
                    if (isset($cols[$idx + 2])) {
                        $next_col = trim($cols[$idx + 2]);
                        // Jika kolom berikutnya bukan angka, anggap sebagai keterangan
                        $next_col_clean = str_replace(['Rp', 'rp', '.', ','], '', $next_col);
                        if (!empty($next_col) && !is_numeric($next_col_clean)) {
                            $keterangan = $next_col;
                            $idx += 3; // Skip: nama_tagihan, jumlah, keterangan
                        } else {
                            $idx += 2; // Skip: nama_tagihan, jumlah
                        }
                    } else {
                        $idx += 2; // Skip: nama_tagihan, jumlah
                    }
                    
                    $tagihan_data[] = [
                        'student_id' => $student_id,
                        'nama_tagihan' => $nama_tagihan,
                        'jumlah' => $jumlah,
                        'keterangan' => $keterangan
                    ];
                }
            }
            
            // Insert ke database
            if (!empty($tagihan_data)) {
                $conn->begin_transaction();
                
                try {
                    $stmt = $conn->prepare("INSERT INTO tagihan (student_id, nama_tagihan, jumlah, keterangan) VALUES (?, ?, ?, ?)");
                    
                    foreach ($tagihan_data as $data) {
                        $stmt->bind_param("isds", 
                            $data['student_id'],
                            $data['nama_tagihan'],
                            $data['jumlah'],
                            $data['keterangan']
                        );
                        
                        if ($stmt->execute()) {
                            $success_count++;
                        } else {
                            $errors[] = "Gagal insert tagihan: " . $data['nama_tagihan'] . " untuk student ID " . $data['student_id'] . " - " . $stmt->error;
                        }
                    }
                    
                    $stmt->close();
                    
                    if ($success_count > 0) {
                        $conn->commit();
                        $success = "Berhasil mengimport $success_count tagihan!";
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
                    <strong>Cara penggunaan:</strong><br>
                    1. Copy data dari spreadsheet (Excel/Google Sheets)<br>
                    2. Paste di textarea di bawah ini<br>
                    3. Format: <strong>Nama | Kelas | Phone No | Password | Tingkat | SPP Bulanan</strong><br>
                    4. Dipisah dengan <strong>Tab</strong> atau <strong>Koma (,)</strong><br>
                    5. Minimal: Nama dan Kelas (Password default: 123456 jika kosong)
                </p>
                
                <div style="background: #f0f4ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #667eea;">
                    <strong>Contoh Format:</strong><br>
                    <code style="display: block; margin-top: 8px; padding: 8px; background: white; border-radius: 4px; font-size: 12px;">
                        Ahmad Fauzi	XII IPA 1	081234567890	123456	XII	500000<br>
                        Siti Nurhaliza	XI IPS 1	081234567891	123456	XI	450000<br>
                        Budi Santoso	X IPA 1	081234567892	123456	X	400000
                    </code>
                    <small style="color: #666; display: block; margin-top: 8px;">
                        * Kolom: Nama, Kelas, Phone No (opsional), Password (opsional, default: 123456), Tingkat (opsional), SPP Bulanan (opsional)
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
                            placeholder="Paste data dari spreadsheet di sini...&#10;&#10;Contoh:&#10;Ahmad Fauzi	XII IPA 1	081234567890	123456	XII	500000"
                            required
                        ></textarea>
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
                    1. Copy data dari spreadsheet (Excel/Google Sheets)<br>
                    2. Paste di textarea di bawah ini<br>
                    3. Format: <strong>Student ID | Nama Tagihan 1 | Jumlah 1 | Nama Tagihan 2 | Jumlah 2 | ...</strong><br>
                    4. Dipisah dengan <strong>Tab</strong> atau <strong>Koma (,)</strong><br>
                    5. Satu baris = satu student dengan multiple tagihan
                </p>
                
                <div style="background: #f0f4ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #667eea;">
                    <strong>Contoh Format (1 student, 5 tagihan):</strong><br>
                    <code style="display: block; margin-top: 8px; padding: 8px; background: white; border-radius: 4px; font-size: 12px;">
                        1	SPP Januari	500000	SPP Februari	500000	SPP Maret	500000	Uang Gedung	2000000	Uang Seragam	500000<br>
                        2	SPP Januari	500000	SPP Februari	500000	Uang Gedung	2000000
                    </code>
                    <small style="color: #666; display: block; margin-top: 8px;">
                        * Format: Student ID, kemudian pasangan Nama Tagihan dan Jumlah (bisa multiple)<br>
                        * Contoh: ID 1 (Ahmad) langsung ditambahkan 5 tagihan sekaligus
                    </small>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="import_tagihan">
                    
                    <div class="form-group">
                        <label>Paste Data Tagihan dari Spreadsheet</label>
                        <textarea 
                            name="data_paste" 
                            rows="15" 
                            style="font-family: 'Courier New', monospace; font-size: 13px;"
                            placeholder="Paste data dari spreadsheet di sini...&#10;&#10;Contoh:&#10;1	SPP Januari	500000	SPP Februari	500000	SPP Maret	500000"
                            required
                        ></textarea>
                    </div>

                    <button type="submit" class="btn">Import Tagihan</button>
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
    </script>
</body>
</html>
<?php
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: dosen_absensi.php');
    exit;
}

// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>

