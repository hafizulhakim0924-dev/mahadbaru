<?php
session_start();

// Database Config
$servername = "localhost";
$username = "ypikhair_admin";
$password = "hakim123123123";
$dbname = "ypikhair_datautama";

// Redirect jika sudah login
if (isset($_SESSION['student']['id'])) {
    header('Location: profile.php');
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $class = trim($_POST['class'] ?? '');
    $phone_no = trim($_POST['phone_no'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');
    
    // Validasi
    if (empty($name)) {
        $error = "Nama lengkap harus diisi!";
    } elseif (empty($class)) {
        $error = "Kelas harus diisi!";
    } elseif (empty($phone_no)) {
        $error = "Nomor telepon harus diisi!";
    } elseif (empty($password)) {
        $error = "Password harus diisi!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif ($password !== $password_confirm) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        $conn = new mysqli($servername, $username, $password, $dbname);
        
        if ($conn->connect_error) {
            $error = "Koneksi database gagal!";
        } else {
            // Cek apakah nomor telepon sudah terdaftar
            $stmt = $conn->prepare("SELECT id FROM students WHERE phone_no = ?");
            $stmt->bind_param("s", $phone_no);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Nomor telepon sudah terdaftar!";
                $stmt->close();
            } else {
                $stmt->close();
                
                // Insert siswa baru (ID akan auto increment)
                $stmt = $conn->prepare("INSERT INTO students (name, class, phone_no, password) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $name, $class, $phone_no, $password);
                
                if ($stmt->execute()) {
                    $new_id = $stmt->insert_id; // Get auto-generated ID
                    $success = "Pendaftaran berhasil! ID Anda: <strong>$new_id</strong>. Silakan login dengan ID dan password Anda.";
                    $stmt->close();
                    // Clear form
                    $_POST = [];
                } else {
                    $error = "Gagal mendaftar: " . $stmt->error;
                    $stmt->close();
                }
            }
            
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Siswa - Mahad Ibnu Zubair</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        input, textarea, select { -webkit-user-select: text; }
        body { 
            font-family: system-ui, sans-serif; 
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
            border-radius: 12px; 
            width: 100%; 
            max-width: 450px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.15); 
        }
        .logo { 
            width: 80px; 
            height: 80px; 
            margin: 0 auto 15px; 
            display: block; 
            border-radius: 8px; 
            background: #f8f9fa; 
            padding: 5px; 
        }
        .title { 
            color: #1e40af; 
            text-align: center; 
            font-size: 1.6rem; 
            font-weight: 700; 
            margin-bottom: 8px; 
        }
        .subtitle { 
            color: #64748b; 
            text-align: center; 
            margin-bottom: 25px; 
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
        .form-group input { 
            width: 100%; 
            padding: 14px; 
            border: 1px solid #d1d5db; 
            border-radius: 6px; 
            font-size: 16px; 
            font-family: system-ui; 
        }
        .form-group input:focus { 
            outline: none; 
            border-color: #1e40af; 
            box-shadow: 0 0 0 2px rgba(30,64,175,0.1); 
        }
        .btn { 
            width: 100%; 
            padding: 14px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: #fff; 
            border: 0; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 1rem; 
            font-weight: 600; 
            transition: all 0.3s;
        }
        .btn:hover { 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn:active { 
            background: #1e3a8a; 
        }
        .btn2 { 
            width: 100%; 
            padding: 12px; 
            background: transparent; 
            color: #dc2626; 
            border: 1px solid #dc2626; 
            border-radius: 6px; 
            text-align: center; 
            text-decoration: none; 
            display: block; 
            font-weight: 500; 
            margin-top: 10px;
            transition: all 0.3s;
        }
        .btn2:hover {
            background: #dc2626;
            color: white;
        }
        .error { 
            background: #fef2f2; 
            border: 1px solid #fca5a5; 
            color: #dc2626; 
            padding: 12px; 
            border-radius: 6px; 
            margin-bottom: 20px; 
            text-align: center; 
            font-size: 14px; 
        }
        .success { 
            background: #d1fae5; 
            border: 1px solid #86efac; 
            color: #065f46; 
            padding: 12px; 
            border-radius: 6px; 
            margin-bottom: 20px; 
            text-align: center; 
            font-size: 14px; 
        }
        .divider { 
            text-align: center; 
            margin: 20px 0; 
            color: #9ca3af; 
            font-size: 14px; 
        }
        .info { 
            background: #dbeafe; 
            border: 1px solid #93c5fd; 
            color: #1e40af; 
            padding: 10px; 
            border-radius: 6px; 
            margin-bottom: 20px; 
            font-size: 12px; 
            text-align: center; 
        }
        .password-strength {
            font-size: 12px;
            margin-top: 5px;
            color: #666;
        }
        .password-strength.weak { color: #dc2626; }
        .password-strength.medium { color: #f59e0b; }
        .password-strength.strong { color: #10b981; }
    </style>
</head>
<body>
    <div class="container">
        <img src="https://ibnuzubair.ypi-khairaummah.sch.id/logo.jpeg" class="logo" alt="Logo" onerror="this.style.display='none'">

        <h1 class="title">Pendaftaran Siswa</h1>
        <p class="subtitle">Mahad Ibnu Zubair</p>

        <div class="info">
            📝 Isi form di bawah ini untuk mendaftar sebagai siswa baru
        </div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="login_siswa.php" class="btn" style="text-decoration: none; display: inline-block; width: auto; padding: 12px 30px;">Login Sekarang</a>
            </div>
        <?php else: ?>
            <form method="POST" id="registerForm">
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input 
                        type="text" 
                        name="name" 
                        required 
                        autofocus
                        placeholder="Masukkan nama lengkap"
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Kelas *</label>
                    <input 
                        type="text" 
                        name="class" 
                        required 
                        placeholder="Contoh: X IPA 1, XI IPS 2"
                        value="<?= htmlspecialchars($_POST['class'] ?? '') ?>"
                    >
                </div>

                <div class="form-group">
                    <label>Nomor Telepon *</label>
                    <input 
                        type="tel" 
                        name="phone_no" 
                        required 
                        placeholder="08xxxxxxxxxx"
                        pattern="[0-9]{10,15}"
                        value="<?= htmlspecialchars($_POST['phone_no'] ?? '') ?>"
                    >
                    <small style="color: #666; font-size: 12px;">Gunakan nomor yang aktif (10-15 digit)</small>
                </div>

                <div class="form-group">
                    <label>Password *</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password"
                        required 
                        minlength="6"
                        placeholder="Minimal 6 karakter"
                        oninput="checkPasswordStrength(this.value)"
                    >
                    <div id="password-strength" class="password-strength"></div>
                </div>

                <div class="form-group">
                    <label>Konfirmasi Password *</label>
                    <input 
                        type="password" 
                        name="password_confirm" 
                        required 
                        minlength="6"
                        placeholder="Ulangi password"
                        oninput="checkPasswordMatch()"
                    >
                    <div id="password-match" style="font-size: 12px; margin-top: 5px;"></div>
                </div>

                <button type="submit" class="btn">Daftar</button>
            </form>
        <?php endif; ?>

        <div class="divider"><span>atau</span></div>
        <a href="login_siswa.php" class="btn2">Sudah punya akun? Login</a>
    </div>

    <script>
        function checkPasswordStrength(password) {
            const strengthDiv = document.getElementById('password-strength');
            if (!password) {
                strengthDiv.textContent = '';
                return;
            }

            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;

            if (strength <= 2) {
                strengthDiv.textContent = 'Kekuatan password: Lemah';
                strengthDiv.className = 'password-strength weak';
            } else if (strength <= 3) {
                strengthDiv.textContent = 'Kekuatan password: Sedang';
                strengthDiv.className = 'password-strength medium';
            } else {
                strengthDiv.textContent = 'Kekuatan password: Kuat';
                strengthDiv.className = 'password-strength strong';
            }
        }

        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const passwordConfirm = document.querySelector('input[name="password_confirm"]').value;
            const matchDiv = document.getElementById('password-match');

            if (!passwordConfirm) {
                matchDiv.textContent = '';
                return;
            }

            if (password === passwordConfirm) {
                matchDiv.textContent = '✓ Password cocok';
                matchDiv.style.color = '#10b981';
            } else {
                matchDiv.textContent = '✗ Password tidak cocok';
                matchDiv.style.color = '#dc2626';
            }
        }

        document.getElementById('registerForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const passwordConfirm = document.querySelector('input[name="password_confirm"]').value;

            if (password !== passwordConfirm) {
                e.preventDefault();
                alert('Password dan konfirmasi password tidak cocok!');
                return false;
            }

            if (password.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter!');
                return false;
            }
        });
    </script>
</body>
</html>

