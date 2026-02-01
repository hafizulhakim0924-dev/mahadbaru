<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// Database Config
$servername = "localhost";
$username = "ypikhair_admin";
$password = "hakim123123123";
$dbname = "ypikhair_datautama";

// Create database connection
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die('<!DOCTYPE html><html><head><title>Error</title></head><body style="font-family:sans-serif;text-align:center;padding:50px;"><h2>Sistem Sedang Dalam Perbaikan</h2><p>Silakan coba beberapa saat lagi.</p></body></html>');
}

$message = '';
$message_type = '';

// Handle AJAX search request
if (isset($_GET['search']) && $_GET['search'] === 'students') {
    $search_term = trim($_GET['q'] ?? '');
    $results = [];
    
    if (strlen($search_term) >= 2) {
        $stmt = $conn->prepare("
            SELECT id, name, class 
            FROM students 
            WHERE name LIKE ? OR class LIKE ?
            ORDER BY class ASC, name ASC
            LIMIT 20
        ");
        $search_pattern = '%' . $search_term . '%';
        $stmt->bind_param("ss", $search_pattern, $search_pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $results[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'class' => $row['class'],
                'display' => $row['name'] . ' - ' . $row['class']
            ];
        }
        $stmt->close();
    }
    
    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $mata_kuliah = trim($_POST['mata_kuliah'] ?? '');
    $status = $_POST['status'] ?? 'hadir';
    $keterangan = trim($_POST['keterangan'] ?? '');
    $dosen_id = !empty($_POST['dosen_id']) ? intval($_POST['dosen_id']) : null;
    
    if ($student_id > 0 && !empty($mata_kuliah) && !empty($status)) {
        // Check if absensi already exists for this student, date, and mata_kuliah
        $stmt_check = $conn->prepare("
            SELECT id FROM absensi 
            WHERE student_id = ? AND tanggal = ? AND mata_kuliah = ?
        ");
        $stmt_check->bind_param("iss", $student_id, $tanggal, $mata_kuliah);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            // Update existing
            $row = $result_check->fetch_assoc();
            $stmt = $conn->prepare("
                UPDATE absensi 
                SET status = ?, keterangan = ?, dosen_id = ?, updated_at = NOW()
                WHERE id = ?
            ");
            if ($dosen_id !== null) {
                $stmt->bind_param("ssii", $status, $keterangan, $dosen_id, $row['id']);
            } else {
                $stmt = $conn->prepare("
                    UPDATE absensi 
                    SET status = ?, keterangan = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->bind_param("ssi", $status, $keterangan, $row['id']);
            }
            if ($stmt->execute()) {
                $message = "Absensi berhasil diperbarui!";
                $message_type = "success";
            } else {
                $message = "Gagal memperbarui absensi: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        } else {
            // Insert new
            $stmt = $conn->prepare("
                INSERT INTO absensi (student_id, tanggal, mata_kuliah, status, keterangan, dosen_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            if ($dosen_id !== null) {
                $stmt->bind_param("issssi", $student_id, $tanggal, $mata_kuliah, $status, $keterangan, $dosen_id);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO absensi (student_id, tanggal, mata_kuliah, status, keterangan, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->bind_param("issss", $student_id, $tanggal, $mata_kuliah, $status, $keterangan);
            }
            if ($stmt->execute()) {
                $message = "Absensi berhasil disimpan!";
                $message_type = "success";
            } else {
                $message = "Gagal menyimpan absensi: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
        $stmt_check->close();
    } else {
        $message = "Mohon lengkapi semua field yang wajib!";
        $message_type = "error";
    }
}

// Get all students
$stmt = $conn->prepare("SELECT id, name, class FROM students ORDER BY class ASC, name ASC");
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
    ORDER BY a.tanggal DESC, a.created_at DESC
    LIMIT 20
");
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
    <title>Dashboard Dosen - Input Absensi</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            text-align: center;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 8px;
        }
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card h2 {
            font-size: 18px;
            margin-bottom: 20px;
            color: #1a1a1a;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        .btn-full {
            width: 100%;
        }
        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #1a1a1a;
        }
        table tr:hover {
            background: #f8f9fa;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-hadir {
            background: #d1fae5;
            color: #065f46;
        }
        .status-izin {
            background: #fef3c7;
            color: #92400e;
        }
        .status-sakit {
            background: #dbeafe;
            color: #1e40af;
        }
        .status-alpha {
            background: #fee2e2;
            color: #991b1b;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6b7280;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
        .search-container {
            position: relative;
        }
        .search-input {
            width: 100%;
            padding: 12px 40px 12px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s;
        }
        .search-input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            pointer-events: none;
        }
        .autocomplete-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .autocomplete-dropdown.show {
            display: block;
        }
        .autocomplete-item {
            padding: 12px;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s;
        }
        .autocomplete-item:hover,
        .autocomplete-item.selected {
            background: #f0fdf4;
        }
        .autocomplete-item:last-child {
            border-bottom: none;
        }
        .autocomplete-item-name {
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 4px;
        }
        .autocomplete-item-class {
            font-size: 12px;
            color: #6b7280;
        }
        .selected-student {
            margin-top: 12px;
            padding: 12px;
            background: #f0fdf4;
            border: 1px solid #10b981;
            border-radius: 8px;
            display: none;
        }
        .selected-student.show {
            display: block;
        }
        .selected-student-name {
            font-weight: 600;
            color: #065f46;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Dashboard Dosen</h1>
            <p>Input Absensi Mahad Ibnu Zubair</p>
        </div>

        <?php if ($message): ?>
        <div class="message <?= $message_type ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <div class="grid-2">
            <div class="card">
                <h2>Input Absensi</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="student_search">Cari Nama Siswa *</label>
                        <div class="search-container">
                            <input 
                                type="text" 
                                id="student_search" 
                                class="search-input" 
                                placeholder="Ketik nama siswa atau kelas..."
                                autocomplete="off"
                                required
                            >
                            <span class="search-icon">🔍</span>
                            <div class="autocomplete-dropdown" id="autocomplete_dropdown"></div>
                            <input type="hidden" name="student_id" id="student_id" required>
                        </div>
                        <div class="selected-student" id="selected_student">
                            <div class="selected-student-name" id="selected_student_name"></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="tanggal">Tanggal *</label>
                        <input type="date" name="tanggal" id="tanggal" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="mata_kuliah">Mata Kuliah / Pelajaran *</label>
                        <input type="text" name="mata_kuliah" id="mata_kuliah" placeholder="Contoh: Matematika, Bahasa Arab" required>
                    </div>

                    <div class="form-group">
                        <label for="status">Status Kehadiran *</label>
                        <select name="status" id="status" required>
                            <option value="hadir">Hadir</option>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="alpha">Alpha</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="keterangan">Keterangan</label>
                        <textarea name="keterangan" id="keterangan" placeholder="Opsional: Tambahkan keterangan jika perlu"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="dosen_id">ID Dosen (Opsional)</label>
                        <input type="number" name="dosen_id" id="dosen_id" placeholder="Kosongkan jika tidak ada" min="0">
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">Simpan Absensi</button>
                </form>
            </div>

            <div class="card">
                <h2>Riwayat Absensi Terbaru</h2>
                <?php if (empty($recent_absensi)): ?>
                    <div class="empty-state">
                        <p>Belum ada data absensi</p>
                    </div>
                <?php else: ?>
                    <div style="max-height: 500px; overflow-y: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Siswa</th>
                                    <th>Mata Kuliah</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_absensi as $abs): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($abs['tanggal'])) ?></td>
                                        <td><?= htmlspecialchars($abs['student_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($abs['mata_kuliah']) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= $abs['status'] ?>">
                                                <?= ucfirst($abs['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        let searchTimeout;
        let selectedStudentId = null;
        let selectedStudentName = null;
        let currentFocus = -1;

        const studentSearch = document.getElementById('student_search');
        const studentIdInput = document.getElementById('student_id');
        const autocompleteDropdown = document.getElementById('autocomplete_dropdown');
        const selectedStudentDiv = document.getElementById('selected_student');
        const selectedStudentNameDiv = document.getElementById('selected_student_name');

        studentSearch.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                autocompleteDropdown.classList.remove('show');
                selectedStudentDiv.classList.remove('show');
                studentIdInput.value = '';
                selectedStudentId = null;
                return;
            }

            searchTimeout = setTimeout(() => {
                searchStudents(query);
            }, 300);
        });

        studentSearch.addEventListener('keydown', function(e) {
            const items = autocompleteDropdown.querySelectorAll('.autocomplete-item');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                currentFocus++;
                if (currentFocus >= items.length) currentFocus = 0;
                setActive(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                currentFocus--;
                if (currentFocus < 0) currentFocus = items.length - 1;
                setActive(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (currentFocus > -1 && items[currentFocus]) {
                    items[currentFocus].click();
                }
            } else if (e.key === 'Escape') {
                autocompleteDropdown.classList.remove('show');
                currentFocus = -1;
            }
        });

        function setActive(items) {
            items.forEach(item => item.classList.remove('selected'));
            if (items[currentFocus]) {
                items[currentFocus].classList.add('selected');
                items[currentFocus].scrollIntoView({ block: 'nearest' });
            }
        }

        function searchStudents(query) {
            fetch(`?search=students&q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    displayResults(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function displayResults(results) {
            autocompleteDropdown.innerHTML = '';
            currentFocus = -1;

            if (results.length === 0) {
                autocompleteDropdown.innerHTML = '<div class="autocomplete-item" style="color: #6b7280; cursor: default;">Tidak ada hasil</div>';
                autocompleteDropdown.classList.add('show');
                return;
            }

            results.forEach(result => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.innerHTML = `
                    <div class="autocomplete-item-name">${escapeHtml(result.name)}</div>
                    <div class="autocomplete-item-class">Kelas: ${escapeHtml(result.class)}</div>
                `;
                item.addEventListener('click', () => {
                    selectStudent(result.id, result.display);
                });
                autocompleteDropdown.appendChild(item);
            });

            autocompleteDropdown.classList.add('show');
        }

        function selectStudent(id, display) {
            selectedStudentId = id;
            selectedStudentName = display;
            studentIdInput.value = id;
            studentSearch.value = display;
            selectedStudentNameDiv.textContent = '✓ Terpilih: ' + display;
            selectedStudentDiv.classList.add('show');
            autocompleteDropdown.classList.remove('show');
            currentFocus = -1;
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-container')) {
                autocompleteDropdown.classList.remove('show');
                currentFocus = -1;
            }
        });

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Clear selection when user starts typing again
        studentSearch.addEventListener('focus', function() {
            if (this.value !== selectedStudentName) {
                selectedStudentDiv.classList.remove('show');
                studentIdInput.value = '';
                selectedStudentId = null;
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>

