<?php
// Database Config
$servername = "localhost";
$username   = "ypikhair_admin";
$password   = "hakim123123123";
$dbname     = "ypikhair_datautama";

// Koneksi
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// ===================== CEK ID AJAX (UNTUK TOMBOL) =====================
if (isset($_POST['mode']) && $_POST['mode'] == "check_id") {
    $id = intval($_POST['id']);
    
    // Query cek ID
    $stmt = $conn->prepare("SELECT id FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "used";
    } else {
        echo "available";
    }
    
    $stmt->close();
    $conn->close();
    exit; // WAJIB exit agar tidak lanjut ke bawah
}

// ===================== LOAD LIST KELAS =====================
$list_kelas = [];
$q = $conn->query("SELECT DISTINCT class FROM students ORDER BY class ASC");
while ($r = $q->fetch_assoc()) {
    if (!empty($r['class'])) $list_kelas[] = $r['class'];
}

// ===================== SIMPAN DATA (LANGSUNG TANPA CEK ID LAGI) =====================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['name'])) {

    $id      = intval($_POST['id']);
    $name    = trim($_POST['name']);
    $class   = trim($_POST['class']);
    $tingkat = trim($_POST['tingkat']);
    $spp     = intval($_POST['spp_bulanan']);
    $tambahan = trim($_POST['tambahan']);
    $biayatambahan = intval($_POST['biayatambahan']);
    $phone   = trim($_POST['phone_no']);

    $password_plain = "1234";
    $balance = 0;

    // LANGSUNG INSERT tanpa cek ID
    $stmt = $conn->prepare("
        INSERT INTO students 
        (id, name, class, tingkat, spp_bulanan, tambahan, biayatambahan, password, phone_no, balance)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("isssissssi",
        $id, $name, $class, $tingkat, $spp, $tambahan,
        $biayatambahan, $password_plain, $phone, $balance
    );

    if ($stmt->execute()) {
        echo "<script>
                alert('User berhasil disimpan dengan ID: $id');
                window.location.href='tambahidterbarugess.php';
              </script>";
        $stmt->close();
        $conn->close();
        exit;
    } else {
        // Jika error karena duplicate ID
        if ($conn->errno == 1062) {
            echo "<script>alert('ID $id sudah digunakan! Gunakan ID lain.');</script>";
        } else {
            echo "<script>alert('Error: " . $stmt->error . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Student</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 500px; }
        input, select { 
            padding: 8px; 
            width: 100%; 
            margin-bottom: 15px; 
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button { 
            padding: 10px 20px; 
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover { background: #0056b3; }
        .btn-check { 
            background: #28a745; 
            padding: 8px 15px;
        }
        .btn-check:hover { background: #218838; }
        #result { 
            font-weight: bold; 
            margin-left: 10px; 
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
        }
        label { font-weight: bold; display: block; margin-bottom: 5px; }
        .id-row { display: flex; gap: 10px; align-items: flex-start; margin-bottom: 15px; }
        .id-row input { margin-bottom: 0; flex: 1; }
    </style>
</head>
<body>

<div class="container">
    <h2>Tambah Student Baru</h2>

    <form method="POST">

        <label>ID (wajib unik):</label>
        <div class="id-row">
            <input type="number" id="id_input" name="id" required min="1">
            <button type="button" class="btn-check" onclick="cekID()">Cek ID</button>
        </div>
        <span id="result"></span>

        <label>Nama:</label>
        <input type="text" name="name" required>

        <label>Class:</label>
        <select name="class" required>
            <option value="">-- Pilih Kelas --</option>
            <?php foreach ($list_kelas as $kelas): ?>
                <option value="<?= htmlspecialchars($kelas) ?>"><?= htmlspecialchars($kelas) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Tingkat:</label>
        <input type="text" name="tingkat" required>

        <label>SPP Bulanan:</label>
        <input type="number" name="spp_bulanan" required min="0">

        <label>Tambahan:</label>
        <input type="text" name="tambahan">

        <label>Biaya Tambahan:</label>
        <input type="number" name="biayatambahan" min="0" value="0">

        <label>Phone No:</label>
        <input type="text" name="phone_no" required>

        <button type="submit">Simpan Student</button>
    </form>
</div>

<script>
// AJAX CEK ID
function cekID() {
    let id = document.getElementById('id_input').value;
    let result = document.getElementById('result');

    if (id === "" || id <= 0) {
        alert("Masukkan ID yang valid dahulu!");
        return;
    }

    result.innerHTML = "⏳ Mengecek...";
    result.style.color = "#666";
    result.style.background = "#f0f0f0";

    let fd = new FormData();
    fd.append("mode", "check_id");
    fd.append("id", id);

    // Gunakan path yang sama dengan file ini
    fetch(window.location.pathname, { 
        method: "POST", 
        body: fd 
    })
    .then(response => response.text())
    .then(data => {
        console.log("Response dari server:", data); // Debug
        
        let trimmed = data.trim();
        
        if (trimmed === "used") {
            result.style.color = "white";
            result.style.background = "#dc3545";
            result.innerHTML = "❌ ID sudah digunakan";
        } else if (trimmed === "available") {
            result.style.color = "white";
            result.style.background = "#28a745";
            result.innerHTML = "✔ ID tersedia";
        } else {
            result.style.color = "red";
            result.style.background = "#fff";
            result.innerHTML = "⚠ Response tidak valid: " + trimmed;
        }
    })
    .catch(error => {
        console.error("Error:", error);
        result.style.color = "red";
        result.style.background = "#fff";
        result.innerHTML = "⚠ Error koneksi";
    });
}
</script>

</body>
</html>