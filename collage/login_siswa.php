<?php 
session_start();

$servername = "localhost";
$username = "ypikhair_admin";
$password = "hakim123123123";
$dbname = "ypikhair_datautama";

// Redirect jika sudah login (check semua role)
if (isset($_SESSION['student'])) {
    $action = $_GET['action'] ?? null;
    $tab_map = [
        'bayar' => 'bayar',
        'belanja' => 'belanja',
        'voucher' => 'voucher',
        'absensi' => 'absensi'
    ];
    $redirect_tab = isset($tab_map[$action]) ? '?tab=' . $tab_map[$action] : '';
    header('Location: profile.php' . $redirect_tab);
    exit;
}

if (isset($_SESSION['dosen_id'])) {
    header('Location: dosen_absensi.php');
    exit;
}

if (isset($_SESSION['admin_id'])) {
    header('Location: adminbelanja.php');
    exit;
}

if (isset($_SESSION['keuangan_id'])) {
    header('Location: keuangan_dashboard.php');
    exit;
}

// Get action parameter
$action = $_GET['action'] ?? $_POST['action'] ?? null;

// ======================================================
// AUTO LOGIN UNTUK RETURNING USER
// ======================================================
if (isset($_GET['device_id']) && isset($_GET['token'])) {
    $device_id = $_GET['device_id'];
    $fcm_token = $_GET['token'];

    $conn = new mysqli($servername, $username, $password, $dbname);

    if (!$conn->connect_error) {
        $stmt = $conn->prepare("
            SELECT students.* 
            FROM students
            JOIN tokens ON tokens.user_id = students.id
            WHERE tokens.device_id = ? 
            LIMIT 1
        ");
        
        if ($stmt) {
            $stmt->bind_param("s", $device_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $student = $result->fetch_assoc();
                $_SESSION['student'] = $student;
                
                $stmt2 = $conn->prepare("UPDATE tokens SET token = ? WHERE device_id = ? AND user_id = ?");
                $stmt2->bind_param("ssi", $fcm_token, $device_id, $student['id']);
                $stmt2->execute();
                $stmt2->close();
                
                $conn->close();
                $tab_map = ['bayar' => 'bayar', 'belanja' => 'belanja', 'voucher' => 'voucher', 'absensi' => 'absensi'];
                $redirect_tab = isset($tab_map[$action]) ? '?tab=' . $tab_map[$action] : '';
                header("Location: profile.php" . $redirect_tab);
                exit;
            }
            $stmt->close();
        }
    }
    $conn->close();
}

// ======================================================
// MANUAL LOGIN
// ======================================================
$error = '';
$device_id = $_GET['device_id'] ?? $_POST['device_id'] ?? null;
$fcm_token = $_GET['token'] ?? $_POST['token'] ?? null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username_input = trim($_POST['username'] ?? '');
    $password_input = trim($_POST['password'] ?? '');
    $user_type_selected = trim($_POST['user_type'] ?? 'student');

    // Admin khusus
    if ($username_input === 'khalid' && $password_input === 'syakila') {
        $_SESSION['admin_khusus'] = true;
        header('Location: penarikan.php');
        exit;
    }

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        $error = "Koneksi database gagal!";
    } else {
        $logged_in = false;
        $user_type = null;

        // Login berdasarkan tab yang dipilih
        switch ($user_type_selected) {
            case 'student':
                // Login untuk Students menggunakan tabel students
                // Struktur tabel: id (int, PK), name, class, tingkat, spp_bulanan, tambahan, biayatambahan, password (varchar 100), phone_no, balance
                $student_id_int = is_numeric($username_input) ? intval($username_input) : 0;
                if ($student_id_int > 0) {
                    // Query login: cek id dan password dari tabel students
                    // Menggunakan SELECT * untuk mengambil semua field sesuai struktur tabel
                    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ? AND password = ?");
                    $stmt->bind_param("is", $student_id_int, $password_input);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        // Login berhasil - simpan semua data student ke session
                        // Data yang tersimpan: id, name, class, tingkat, spp_bulanan, tambahan, biayatambahan, password, phone_no, balance
                        $student = $result->fetch_assoc();
                        $_SESSION['student'] = $student;
                        $logged_in = true;
                        $user_type = 'student';

                        if (!empty($device_id) && !empty($fcm_token)) {
                            $stmt_check = $conn->prepare("SELECT id FROM tokens WHERE device_id = ? AND user_id = ?");
                            $stmt_check->bind_param("si", $device_id, $student['id']);
                            $stmt_check->execute();
                            $check_result = $stmt_check->get_result();

                            if ($check_result->num_rows > 0) {
                                $stmt_update = $conn->prepare("UPDATE tokens SET token = ?, expired_at = DATE_ADD(NOW(), INTERVAL 7 DAY) WHERE device_id = ? AND user_id = ?");
                                $stmt_update->bind_param("ssi", $fcm_token, $device_id, $student['id']);
                                $stmt_update->execute();
                                $stmt_update->close();
                            } else {
                                $token = bin2hex(random_bytes(20));
                                $stmt_insert = $conn->prepare("INSERT INTO tokens (user_id, token, device_id, expired_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))");
                                $stmt_insert->bind_param("iss", $student['id'], $token, $device_id);
                                $stmt_insert->execute();
                                $stmt_insert->close();
                            }
                            $stmt_check->close();
                        }
                    }
                    $stmt->close();
                }
                break;

            case 'dosen':
                if ($username_input === 'super123' && $password_input === 'super123') {
                    $_SESSION['dosen_id'] = 999;
                    $_SESSION['dosen_nama'] = 'Dosen Tester';
                    $logged_in = true;
                    $user_type = 'dosen';
                } else {
                    $stmt = $conn->prepare("SELECT * FROM dosen WHERE username = ? AND password = ?");
                    $stmt->bind_param("ss", $username_input, $password_input);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $dosen = $result->fetch_assoc();
                        $_SESSION['dosen_id'] = $dosen['id'];
                        $_SESSION['dosen_nama'] = $dosen['nama'];
                        $logged_in = true;
                        $user_type = 'dosen';
                    }
                    $stmt->close();
                }
                break;

            case 'admin':
                if ($username_input === 'super123' && $password_input === 'super123') {
                    $_SESSION['admin_id'] = 999;
                    $_SESSION['admin_nama'] = 'Admin Tester';
                    $logged_in = true;
                    $user_type = 'admin';
                } else {
                    $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? AND password = ?");
                    $stmt->bind_param("ss", $username_input, $password_input);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        $admin = $result->fetch_assoc();
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_nama'] = $admin['nama'];
                        $logged_in = true;
                        $user_type = 'admin';
                    }
                    $stmt->close();
                }
                break;

            case 'keuangan':
                if ($username_input === 'super123' && $password_input === 'super123') {
                    $_SESSION['keuangan_id'] = 999;
                    $_SESSION['keuangan_nama'] = 'Keuangan Tester';
                    $logged_in = true;
                    $user_type = 'keuangan';
                } else {
                    $table_check = $conn->query("SHOW TABLES LIKE 'keuangan'");
                    if ($table_check && $table_check->num_rows > 0) {
                        $stmt = $conn->prepare("SELECT * FROM keuangan WHERE username = ? AND password = ?");
                        $stmt->bind_param("ss", $username_input, $password_input);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            $keuangan = $result->fetch_assoc();
                            $_SESSION['keuangan_id'] = $keuangan['id'];
                            $_SESSION['keuangan_nama'] = $keuangan['nama'];
                            $logged_in = true;
                            $user_type = 'keuangan';
                        }
                        $stmt->close();
                    }
                }
                break;
        }

        $conn->close();

        // Redirect setelah login berhasil
        if ($logged_in && $user_type) {
            $redirect_url = '';
            switch ($user_type) {
                case 'student':
                    $tab_map = ['bayar' => 'bayar', 'belanja' => 'belanja', 'voucher' => 'voucher', 'absensi' => 'absensi'];
                    $redirect_tab = isset($tab_map[$action]) ? '?tab=' . $tab_map[$action] : '';
                    $redirect_url = 'profile.php' . $redirect_tab;
                    break;
                case 'dosen':
                    $redirect_url = 'dosen_absensi.php';
                    break;
                case 'admin':
                    $redirect_url = 'adminbelanja.php';
                    break;
                case 'keuangan':
                    $redirect_url = 'keuangan_dashboard.php';
                    break;
            }
            
            if ($redirect_url) {
                header("Location: " . $redirect_url);
                exit();
            }
        } else {
            $error = "Username/ID atau Password salah!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mahad Ibnu Zubair</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        input, textarea, select { -webkit-user-select: text; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 20px; 
        }
        .container { 
            background: #fff; 
            padding: 48px 32px; 
            border-radius: 16px; 
            width: 100%; 
            max-width: 400px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.1); 
        }
        .logo { 
            width: 72px; 
            height: 72px; 
            margin: 0 auto 24px; 
            display: block; 
            border-radius: 12px; 
            object-fit: cover;
        }
        .title { 
            color: #1a1a1a; 
            text-align: center; 
            font-size: 24px; 
            font-weight: 600; 
            margin-bottom: 6px; 
            letter-spacing: -0.5px;
        }
        .subtitle { 
            color: #6b7280; 
            text-align: center; 
            margin-bottom: 32px; 
            font-size: 14px; 
        }
        .input { 
            width: 100%; 
            padding: 14px 16px; 
            border: 1.5px solid #e5e7eb; 
            border-radius: 8px; 
            margin-bottom: 16px; 
            font-size: 15px; 
            font-family: inherit; 
            transition: all 0.2s;
            background: #fafafa;
        }
        .input:focus { 
            outline: none; 
            border-color: #667eea; 
            background: #fff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); 
        }
        .btn { 
            width: 100%; 
            padding: 14px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff; 
            border: 0; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 15px; 
            font-weight: 600; 
            transition: transform 0.1s, box-shadow 0.2s;
            margin-top: 8px;
        }
        .btn:hover { 
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        .btn:active { 
            transform: translateY(0);
        }
        .btn2 { 
            width: 100%; 
            padding: 12px; 
            background: transparent; 
            color: #6b7280; 
            border: 1.5px solid #e5e7eb; 
            border-radius: 8px; 
            text-align: center; 
            text-decoration: none; 
            display: block; 
            font-weight: 500; 
            font-size: 14px;
            transition: all 0.2s;
            margin-top: 10px;
        }
        .btn2:hover {
            border-color: #d1d5db;
            background: #f9fafb;
        }
        .btn2.primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
            border: none;
        }
        .btn2.primary:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        .error { 
            background: #fef2f2; 
            border: 1.5px solid #fecaca; 
            color: #dc2626; 
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 16px; 
            text-align: center; 
            font-size: 14px; 
        }
        .divider { 
            text-align: center; 
            margin: 24px 0; 
            color: #9ca3af; 
            font-size: 13px; 
            position: relative;
        }
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e5e7eb;
        }
        .divider span {
            background: #fff;
            padding: 0 12px;
            position: relative;
        }
        .info { 
            background: #eff6ff; 
            border: 1.5px solid #bfdbfe; 
            color: #1e40af; 
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            font-size: 13px; 
            text-align: center; 
        }
        .info.new { 
            background: #fffbeb; 
            border: 1.5px solid #fde68a; 
            color: #92400e; 
        }
        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 2px solid #e5e7eb;
        }
        .tab-btn {
            flex: 1;
            padding: 12px 8px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            color: #6b7280;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        .tab-btn:hover {
            color: #667eea;
            background: #f9fafb;
        }
        .tab-btn.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: #f0f4ff;
        }
        @media (max-width: 480px) {
            .tabs {
                flex-wrap: wrap;
            }
            .tab-btn {
                font-size: 12px;
                padding: 10px 6px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="https://ibnuzubair.ypi-khairaummah.sch.id/logo.jpeg" class="logo" alt="Logo">

        <h1 class="title">Mahad Ibnu Zubair</h1>
        <p class="subtitle">Portal Login Terpadu</p>

        <?php if(!empty($device_id)): ?>
        <div class="info new">
            Login dari aplikasi mobile
        </div>
        <?php endif; ?>

        <!-- Tab Selector -->
        <div class="tabs">
            <button type="button" class="tab-btn active" data-tab="student">Siswa</button>
            <button type="button" class="tab-btn" data-tab="dosen">Dosen</button>
            <button type="button" class="tab-btn" data-tab="admin">Admin</button>
            <button type="button" class="tab-btn" data-tab="keuangan">Keuangan</button>
        </div>

        <form method="POST" id="loginForm">
            <input type="hidden" name="user_type" id="user_type" value="student">
            
            <input 
                type="text" 
                name="username" 
                id="username_input"
                class="input" 
                placeholder="ID Siswa" 
                required
                autofocus
                autocomplete="username"
            >
            
            <input 
                type="password" 
                name="password" 
                class="input" 
                placeholder="Password" 
                required
                autocomplete="current-password"
            >
            
            <?php if(!empty($device_id)): ?>
            <input type="hidden" name="device_id" value="<?= htmlspecialchars($device_id) ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($fcm_token) ?>">
            <?php endif; ?>
            
            <button type="submit" class="btn">Masuk</button>
        </form>

        <?php if($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="divider"><span>atau</span></div>
        <a href="register_siswa.php" class="btn2 primary">Daftar Akun Siswa</a>
        <a href="change_password.php" class="btn2">Ubah Password</a>
    </div>

    <script>
        // Tab switching - Sederhana dan langsung
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Update active tab button
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Update user_type
                const tabType = this.getAttribute('data-tab');
                document.getElementById('user_type').value = tabType;
                
                // Update placeholder
                const usernameInput = document.getElementById('username_input');
                const placeholders = {
                    'student': 'ID Siswa',
                    'dosen': 'Username Dosen',
                    'admin': 'Username Admin',
                    'keuangan': 'Username Keuangan'
                };
                if (usernameInput && placeholders[tabType]) {
                    usernameInput.placeholder = placeholders[tabType];
                    usernameInput.focus();
                }
            });
        });
        
        // Pastikan user_type terupdate saat submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const activeTab = document.querySelector('.tab-btn.active');
            if (activeTab) {
                const tabType = activeTab.getAttribute('data-tab');
                document.getElementById('user_type').value = tabType;
            }
        });
    </script>
</body>
</html>
