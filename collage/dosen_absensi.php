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
$conn->close();
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
    </style>
</head>
<body>
    <div class="header">
        <a href="?logout=1" class="logout">Logout</a>
        <h1>Input Absensi</h1>
        <p>Dosen: <?= htmlspecialchars($dosen_nama) ?></p>
    </div>

    <div class="container">
        <?php if (isset($success)): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

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
</body>
</html>
<?php
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: dosen_absensi.php');
    exit;
}
?>

