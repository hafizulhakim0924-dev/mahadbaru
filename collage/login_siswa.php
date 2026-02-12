<?php
session_start();

// Database Config
$servername = "localhost";
$username = "ypikhair_adminmahadzubair";
$password = "Hakim123!";
$dbname = "ypikhair_mahadzubair";

// Redirect jika sudah login
if (isset($_SESSION['student']['id']) || isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_id = trim($_POST['login_id'] ?? '');
    $login_password = trim($_POST['login_password'] ?? '');
    $user_type = $_POST['user_type'] ?? 'student';
    
    // Validasi
    if (empty($login_id)) {
        $error = "ID/Username harus diisi!";
    } elseif (empty($login_password)) {
        $error = "Password harus diisi!";
    } else {
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            $error = "Koneksi database gagal!";
        } else {
            $conn->set_charset("utf8mb4");
            
            // SUPERADMIN: username "tes" dengan password "tes123" bisa login sebagai admin, dosen, atau keuangan
            if ($login_id === 'tes' && $login_password === 'tes123') {
                // Superadmin bisa login sebagai admin, dosen, atau keuangan
                if ($user_type == 'admin') {
                    $_SESSION['admin_id'] = 999;
                    $_SESSION['admin_username'] = 'tes';
                    $_SESSION['is_superadmin'] = true;
                    $_SESSION['last_activity'] = time();
                    $conn->close();
                    header('Location: admin_dashboard.php');
                    exit;
                } elseif ($user_type == 'dosen') {
                    $_SESSION['dosen_id'] = 999;
                    $_SESSION['dosen_nama'] = 'Superadmin';
                    $_SESSION['is_superadmin'] = true;
                    $_SESSION['last_activity'] = time();
                    $conn->close();
                    header('Location: dosen_absensi.php');
                    exit;
                } elseif ($user_type == 'keuangan') {
                    $_SESSION['keuangan_id'] = 999;
                    $_SESSION['keuangan_username'] = 'tes';
                    $_SESSION['is_superadmin'] = true;
                    $_SESSION['last_activity'] = time();
                    $conn->close();
                    header('Location: keuangan_dashboard.php');
                    exit;
                } else {
                    $error = "Superadmin hanya bisa login sebagai Admin, Dosen, atau Keuangan!";
                }
            } elseif ($user_type == 'student') {
                // Login sebagai siswa menggunakan database ypikhair_mahadzubair dan table students
                // Database: ypikhair_mahadzubair
                // Table: students
                $student_conn = new mysqli($servername, $username, $password, $dbname);
                
                if ($student_conn->connect_error) {
                    $error = "Koneksi database gagal!";
                } else {
                    $student_conn->set_charset("utf8mb4");
                    
                    // Login siswa menggunakan ID dan password dari table students
                    // Database: ypikhair_mahadzubair, Table: students
                    $stmt = $student_conn->prepare("SELECT * FROM students WHERE id = ?");
                    $login_id_int = intval($login_id);
                    $stmt->bind_param("i", $login_id_int);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $student = $result->fetch_assoc();
                        
                        // Check password (plain text comparison)
                        if ($student['password'] === $login_password) {
                            // Set session
                            $_SESSION['student']['id'] = $student['id'];
                            $_SESSION['student']['name'] = $student['name'];
                            $_SESSION['student']['class'] = $student['class'] ?? '';
                            $_SESSION['user_id'] = $student['id']; // Compatibility
                            $_SESSION['last_activity'] = time();
                            
                            $stmt->close();
                            $student_conn->close();
                            
                            header('Location: profile.php');
                            exit;
                        } else {
                            $error = "Password salah!";
                        }
                    } else {
                        $error = "ID tidak ditemukan!";
                    }
                    $stmt->close();
                    $student_conn->close();
                }
                } elseif ($user_type == 'dosen') {
                // Login sebagai dosen
                $stmt = $conn->prepare("SELECT * FROM dosen WHERE username = ? OR id = ?");
                $stmt->bind_param("ss", $login_id, $login_id);
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
                        
                        header('Location: dosen_absensi.php');
                        exit;
                    } else {
                        $error = "Password salah!";
                    }
                } else {
                    $error = "Username/ID tidak ditemukan!";
                }
                $stmt->close();
            } elseif ($user_type == 'admin') {
                // Login sebagai admin
                $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ? OR id = ?");
                $stmt->bind_param("ss", $login_id, $login_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $admin = $result->fetch_assoc();
                    
                    if ($admin['password'] === $login_password) {
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        $_SESSION['last_activity'] = time();
                        
                        $stmt->close();
                        $conn->close();
                        
                        header('Location: admin_dashboard.php');
                        exit;
                    } else {
                        $error = "Password salah!";
                    }
                } else {
                    $error = "Username/ID tidak ditemukan!";
                }
                $stmt->close();
            } elseif ($user_type == 'keuangan') {
                // Login sebagai keuangan
                $stmt = $conn->prepare("SELECT * FROM keuangan WHERE username = ? OR id = ?");
                $stmt->bind_param("ss", $login_id, $login_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $keuangan = $result->fetch_assoc();
                    
                    if ($keuangan['password'] === $login_password) {
                        $_SESSION['keuangan_id'] = $keuangan['id'];
                        $_SESSION['keuangan_username'] = $keuangan['username'];
                        $_SESSION['last_activity'] = time();
                        
                        $stmt->close();
                        $conn->close();
                        
                        header('Location: keuangan_dashboard.php');
                        exit;
                    } else {
                        $error = "Password salah!";
                    }
                } else {
                    $error = "Username/ID tidak ditemukan!";
                }
                $stmt->close();
            }
            
            if (isset($conn)) {
                $conn->close();
            }
        }
    }
}

// Check for timeout or error messages
if (isset($_GET['timeout'])) {
    $error = "Session Anda telah berakhir. Silakan login kembali.";
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] == 'invalid') {
        $error = "Session tidak valid. Silakan login kembali.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mahad Ibnu Zubair</title>
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
            -webkit-tap-highlight-color: transparent; 
        }
        input, textarea, select { 
            -webkit-user-select: text; 
        }
        body { 
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 20px; 
        }
        .container { 
            background: #fff; 
            padding: 40px; 
            border-radius: 16px; 
            width: 100%; 
            max-width: 450px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.2); 
        }
        .logo { 
            width: 80px; 
            height: 80px; 
            margin: 0 auto 20px; 
            display: block; 
            border-radius: 12px; 
            background: #f8f9fa; 
            padding: 8px; 
            object-fit: contain;
        }
        .title { 
            color: #1e40af; 
            text-align: center; 
            font-size: 1.75rem; 
            font-weight: 700; 
            margin-bottom: 8px; 
        }
        .subtitle { 
            color: #64748b; 
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
            color: #333; 
            font-weight: 600; 
            font-size: 14px; 
        }
        .form-group input, 
        .form-group select { 
            width: 100%; 
            padding: 14px; 
            border: 1px solid #d1d5db; 
            border-radius: 8px; 
            font-size: 16px; 
            font-family: system-ui; 
            transition: all 0.2s;
        }
        .form-group input:focus, 
        .form-group select:focus { 
            outline: none; 
            border-color: #667eea; 
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
            font-size: 1rem; 
            font-weight: 600; 
            transition: all 0.3s;
            margin-top: 10px;
        }
        .btn:hover { 
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        .btn:active { 
            transform: translateY(0);
        }
        .btn2 { 
            width: 100%; 
            padding: 12px; 
            background: transparent; 
            color: #667eea; 
            border: 1px solid #667eea; 
            border-radius: 8px; 
            text-align: center; 
            text-decoration: none; 
            display: block; 
            font-weight: 500; 
            margin-top: 15px;
            transition: all 0.3s;
        }
        .btn2:hover {
            background: #667eea;
            color: white;
        }
        .error { 
            background: #fef2f2; 
            border: 1px solid #fca5a5; 
            color: #dc2626; 
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            text-align: center; 
            font-size: 14px; 
        }
        .success { 
            background: #d1fae5; 
            border: 1px solid #86efac; 
            color: #065f46; 
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            text-align: center; 
            font-size: 14px; 
        }
        .divider { 
            text-align: center; 
            margin: 25px 0; 
            color: #9ca3af; 
            font-size: 14px; 
            position: relative;
        }
        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #e5e7eb;
        }
        .divider::before {
            left: 0;
        }
        .divider::after {
            right: 0;
        }
        .info { 
            background: #dbeafe; 
            border: 1px solid #93c5fd; 
            color: #1e40af; 
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            font-size: 12px; 
            text-align: center; 
        }
        .user-type-select {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .user-type-option {
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #fff;
            font-size: 14px;
            font-weight: 500;
            color: #64748b;
        }
        .user-type-option:hover {
            border-color: #667eea;
            background: #f0f4ff;
        }
        .user-type-option input[type="radio"] {
            display: none;
        }
        .user-type-option input[type="radio"]:checked + label {
            color: #667eea;
        }
        .user-type-option.selected {
            border-color: #667eea;
            background: #f0f4ff;
            color: #667eea;
        }
        .user-type-option label {
            cursor: pointer;
            display: block;
            width: 100%;
        }
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }
            .title {
                font-size: 1.5rem;
            }
            .user-type-select {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="https://ibnuzubair.ypi-khairaummah.sch.id/logo.jpeg" class="logo" alt="Logo" onerror="this.style.display='none'">

        <h1 class="title">Login</h1>
        <p class="subtitle">Mahad Ibnu Zubair</p>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label>Pilih Tipe Pengguna</label>
                <div class="user-type-select">
                    <div class="user-type-option selected" onclick="selectUserType('student', this)">
                        <input type="radio" name="user_type" value="student" id="type_student" checked>
                        <label for="type_student">Siswa</label>
                    </div>
                    <div class="user-type-option" onclick="selectUserType('dosen', this)">
                        <input type="radio" name="user_type" value="dosen" id="type_dosen">
                        <label for="type_dosen">Dosen</label>
                    </div>
                    <div class="user-type-option" onclick="selectUserType('admin', this)">
                        <input type="radio" name="user_type" value="admin" id="type_admin">
                        <label for="type_admin">Admin</label>
                    </div>
                    <div class="user-type-option" onclick="selectUserType('keuangan', this)">
                        <input type="radio" name="user_type" value="keuangan" id="type_keuangan">
                        <label for="type_keuangan">Keuangan</label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="login_id" id="login_id_label">ID Siswa / Nomor Telepon</label>
                <input 
                    type="text" 
                    name="login_id" 
                    id="login_id"
                    required 
                    autofocus
                    placeholder="Masukkan ID atau nomor telepon"
                    value="<?= htmlspecialchars($_POST['login_id'] ?? '') ?>"
                >
            </div>

            <div class="form-group">
                <label for="login_password">Password</label>
                <input 
                    type="password" 
                    name="login_password" 
                    id="login_password"
                    required 
                    placeholder="Masukkan password"
                >
            </div>

            <button type="submit" class="btn">Login</button>
        </form>

        <div class="divider"><span>atau</span></div>
        <a href="register_siswa.php" class="btn2">Belum punya akun? Daftar</a>
    </div>

    <script>
        function selectUserType(type, element) {
            // Remove selected class from all options
            document.querySelectorAll('.user-type-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            element.classList.add('selected');
            
            // Set radio button
            document.getElementById('type_' + type).checked = true;
            
            // Update label placeholder
            const label = document.getElementById('login_id_label');
            const input = document.getElementById('login_id');
            
            switch(type) {
                case 'student':
                    label.textContent = 'ID Siswa / Nomor Telepon';
                    input.placeholder = 'Masukkan ID atau nomor telepon';
                    break;
                case 'dosen':
                    label.textContent = 'Username / ID Dosen';
                    input.placeholder = 'Masukkan username atau ID dosen';
                    break;
                case 'admin':
                    label.textContent = 'Username / ID Admin';
                    input.placeholder = 'Masukkan username atau ID admin';
                    break;
                case 'keuangan':
                    label.textContent = 'Username / ID Keuangan';
                    input.placeholder = 'Masukkan username atau ID keuangan';
                    break;
            }
        }

        // Handle form submission
        document.getElementById('loginForm')?.addEventListener('submit', function(e) {
            const loginId = document.getElementById('login_id').value.trim();
            const loginPassword = document.getElementById('login_password').value.trim();

            if (!loginId) {
                e.preventDefault();
                alert('ID/Username harus diisi!');
                return false;
            }

            if (!loginPassword) {
                e.preventDefault();
                alert('Password harus diisi!');
                return false;
            }
        });
    </script>
</body>
</html>
