<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIAKAD - Sistem Informasi Akademik</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: #2d3748;
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .logo {
            padding: 0 20px 20px;
            border-bottom: 1px solid #4a5568;
            margin-bottom: 20px;
        }

        .logo h2 {
            font-size: 24px;
            color: #667eea;
        }

        .logo p {
            font-size: 12px;
            color: #a0aec0;
            margin-top: 5px;
        }

        .user-info {
            padding: 15px 20px;
            background: #1a202c;
            margin: 0 10px 20px;
            border-radius: 8px;
        }

        .user-info h3 {
            font-size: 14px;
            margin-bottom: 5px;
        }

        .user-info p {
            font-size: 12px;
            color: #a0aec0;
        }

        .menu-item {
            padding: 12px 20px;
            cursor: pointer;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .menu-item:hover {
            background: #4a5568;
            border-left-color: #667eea;
        }

        .menu-item.active {
            background: #4a5568;
            border-left-color: #667eea;
        }

        .menu-item i {
            margin-right: 10px;
            width: 20px;
            display: inline-block;
        }

        .content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #2d3748;
            font-size: 28px;
        }

        .logout-btn {
            background: #e53e3e;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }

        .logout-btn:hover {
            background: #c53030;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .card h2 {
            color: #2d3748;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 10px;
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .stat-card h3 {
            font-size: 14px;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .stat-card p {
            font-size: 32px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        table tr:hover {
            background: #f7fafc;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-success {
            background: #48bb78;
            color: white;
        }

        .btn-success:hover {
            background: #38a169;
        }

        .btn-danger {
            background: #e53e3e;
            color: white;
        }

        .btn-danger:hover {
            background: #c53030;
        }

        .btn-warning {
            background: #ed8936;
            color: white;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #c6f6d5;
            color: #22543d;
        }

        .badge-warning {
            background: #feebc8;
            color: #7c2d12;
        }

        .badge-danger {
            background: #fed7d7;
            color: #742a2a;
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

        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e0;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .page {
            display: none;
        }

        .page.active {
            display: block;
        }

        .info-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-row strong {
            color: #4a5568;
        }

        .semester-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .semester-tab {
            padding: 10px 20px;
            background: #e2e8f0;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .semester-tab.active {
            background: #667eea;
            color: white;
        }

        .total-sks {
            background: #f7fafc;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            text-align: right;
        }

        .jadwal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }

        .jadwal-card {
            background: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .jadwal-card h4 {
            color: #2d3748;
            margin-bottom: 10px;
        }

        .jadwal-card p {
            color: #4a5568;
            font-size: 14px;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo">
                <h2>🎓 SIAKAD</h2>
                <p>Sistem Informasi Akademik</p>
            </div>

            <div class="user-info">
                <h3>Muhammad Rizki</h3>
                <p>NIM: 2021010123</p>
                <p>Teknik Informatika</p>
            </div>

            <div class="menu-item active" onclick="showPage('dashboard')">
                <i>📊</i> Dashboard
            </div>
            <div class="menu-item" onclick="showPage('profil')">
                <i>👤</i> Profil Mahasiswa
            </div>
            <div class="menu-item" onclick="showPage('krs')">
                <i>📝</i> Kartu Rencana Studi
            </div>
            <div class="menu-item" onclick="showPage('khs')">
                <i>📄</i> Kartu Hasil Studi
            </div>
            <div class="menu-item" onclick="showPage('nilai')">
                <i>📈</i> Transkrip Nilai
            </div>
            <div class="menu-item" onclick="showPage('jadwal')">
                <i>📅</i> Jadwal Kuliah
            </div>
            <div class="menu-item" onclick="showPage('matakuliah')">
                <i>📚</i> Daftar Mata Kuliah
            </div>
            <div class="menu-item" onclick="showPage('pembayaran')">
                <i>💳</i> Pembayaran
            </div>
            <div class="menu-item" onclick="showPage('pengumuman')">
                <i>📢</i> Pengumuman
            </div>
        </div>

        <div class="content">
            <!-- Dashboard -->
            <div id="dashboard" class="page active">
                <div class="header">
                    <h1>Dashboard</h1>
                    <button class="logout-btn">Logout</button>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Semester Aktif</h3>
                        <p>6</p>
                    </div>
                    <div class="stat-card">
                        <h3>IPK</h3>
                        <p>3.75</p>
                    </div>
                    <div class="stat-card">
                        <h3>SKS Diambil</h3>
                        <p>20</p>
                    </div>
                    <div class="stat-card">
                        <h3>Total SKS</h3>
                        <p>110</p>
                    </div>
                </div>

                <div class="card">
                    <h2>Pengumuman Terbaru</h2>
                    <table>
                        <tr>
                            <td><strong>Jadwal UTS Semester Genap 2024/2025</strong><br>
                                <small>Ujian Tengah Semester akan dilaksanakan tanggal 15-20 Maret 2025</small>
                            </td>
                            <td>28 Des 2024</td>
                        </tr>
                        <tr>
                            <td><strong>Perpanjangan Pembayaran UKT</strong><br>
                                <small>Batas akhir pembayaran diperpanjang hingga 5 Januari 2025</small>
                            </td>
                            <td>25 Des 2024</td>
                        </tr>
                        <tr>
                            <td><strong>Libur Akhir Tahun</strong><br>
                                <small>Kampus libur tanggal 30 Des 2024 - 2 Jan 2025</small>
                            </td>
                            <td>20 Des 2024</td>
                        </tr>
                    </table>
                </div>

                <div class="card">
                    <h2>Jadwal Kuliah Hari Ini</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Mata Kuliah</th>
                                <th>Dosen</th>
                                <th>Ruangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>08:00 - 10:00</td>
                                <td>Pemrograman Web</td>
                                <td>Dr. Budi Santoso, M.Kom</td>
                                <td>Lab. Komputer 1</td>
                            </tr>
                            <tr>
                                <td>10:00 - 12:00</td>
                                <td>Basis Data Lanjut</td>
                                <td>Prof. Dr. Siti Aminah, M.T</td>
                                <td>Ruang 301</td>
                            </tr>
                            <tr>
                                <td>13:00 - 15:00</td>
                                <td>Kecerdasan Buatan</td>
                                <td>Ahmad Fauzi, S.Kom, M.Cs</td>
                                <td>Ruang 205</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Profil Mahasiswa -->
            <div id="profil" class="page">
                <div class="header">
                    <h1>Profil Mahasiswa</h1>
                    <button class="logout-btn">Logout</button>
                </div>

                <div class="card">
                    <h2>Data Pribadi</h2>
                    <div class="info-row">
                        <strong>NIM</strong>
                        <span>2021010123</span>
                    </div>
                    <div class="info-row">
                        <strong>Nama Lengkap</strong>
                        <span>Muhammad Rizki</span>
                    </div>
                    <div class="info-row">
                        <strong>Tempat, Tanggal Lahir</strong>
                        <span>Jakarta, 15 Agustus 2003</span>
                    </div>
                    <div class="info-row">
                        <strong>Jenis Kelamin</strong>
                        <span>Laki-laki</span>
                    </div>
                    <div class="info-row">
                        <strong>Agama</strong>
                        <span>Islam</span>
                    </div>
                    <div class="info-row">
                        <strong>Alamat</strong>
                        <span>Jl. Merdeka No. 123, Jakarta Selatan</span>
                    </div>
                    <div class="info-row">
                        <strong>No. Telepon</strong>
                        <span>081234567890</span>
                    </div>
                    <div class="info-row">
                        <strong>Email</strong>
                        <span>muhammad.rizki@student.univ.ac.id</span>
                    </div>
                </div>

                <div class="card">
                    <h2>Data Akademik</h2>
                    <div class="info-row">
                        <strong>Program Studi</strong>
                        <span>Teknik Informatika</span>
                    </div>
                    <div class="info-row">
                        <strong>Fakultas</strong>
                        <span>Fakultas Teknik dan Ilmu Komputer</span>
                    </div>
                    <div class="info-row">
                        <strong>Angkatan</strong>
                        <span>2021</span>
                    </div>
                    <div class="info-row">
                        <strong>Status</strong>
                        <span><span class="badge badge-success">Aktif</span></span>
                    </div>
                    <div class="info-row">
                        <strong>Dosen Wali</strong>
                        <span>Dr. Budi Santoso, M.Kom</span>
                    </div>
                    <div class="info-row">
                        <strong>IPK</strong>
                        <span>3.75</span>
                    </div>
                    <div class="info-row">
                        <strong>Total SKS Lulus</strong>
                        <span>110 / 144</span>
                    </div>
                </div>
            </div>

            <!-- KRS -->
            <div id="krs" class="page">
                <div class="header">
                    <h1>Kartu Rencana Studi (KRS)</h1>
                    <button class="logout-btn">Logout</button>
                </div>

                <div class="card">
                    <h2>Pengisian KRS Semester 6 (2024/2025 Genap)</h2>
                    <p style="margin-bottom: 20px; color: #4a5568;">Status: <span class="badge badge-success">Disetujui Dosen Wali</span></p>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Mata Kuliah</th>
                                <th>SKS</th>
                                <th>Kelas</th>
                                <th>Dosen</th>
                                <th>Jadwal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>TIF601</td>
                                <td>Pemrograman Web</td>
                                <td>3</td>
                                <td>A</td>
                                <td>Dr. Budi Santoso</td>
                                <td>Senin, 08:00-10:00</td>
                                <td><button class="btn btn-danger">Hapus</button></td>
                            </tr>
                            <tr>
                                <td>TIF602</td>
                                <td>Basis Data Lanjut</td>
                                <td>3</td>
                                <td>A</td>
                                <td>Prof. Dr. Siti Aminah</td>
                                <td>Senin, 10:00-12:00</td>
                                <td><button class="btn btn-danger">Hapus</button></td>
                            </tr>
                            <tr>
                                <td>TIF603</td>
                                <td>Kecerdasan Buatan</td>
                                <td>3</td>
                                <td>B</td>
                                <td>Ahmad Fauzi, M.Cs</td>
                                <td>Senin, 13:00-15:00</td>
                                <td><button class="btn btn-danger">Hapus</button></td>
                            </tr>
                            <tr>
                                <td>TIF604</td>
                                <td>Sistem Terdistribusi</td>
                                <td>3</td>
                                <td>A</td>
                                <td>Dr. Rina Wati</td>
                                <td>Selasa, 08:00-10:00</td>
                                <td><button class="btn btn-danger">Hapus</button></td>
                            </tr>
                            <tr>
                                <td>TIF605</td>
                                <td>Keamanan Jaringan</td>
                                <td>3</td>
                                <td>A</td>
                                <td>Indra Gunawan, Ph.D</td>
                                <td>Rabu, 10:00-12:00</td>
                                <td><button class="btn btn-danger">Hapus</button></td>
                            </tr>
                            <tr>
                                <td>TIF606</td>
                                <td>Manajemen Proyek TI</td>
                                <td>2</td>
                                <td>A</td>
                                <td>Dr. Lisa Permata</td>
                                <td>Kamis, 13:00-15:00</td>
                                <td><button class="btn btn-danger">Hapus</button></td>
                            </tr>
                            <tr>
                                <td>UNV301</td>
                                <td>Bahasa Inggris Teknik</td>
                                <td>2</td>
                                <td>B</td>
                                <td>Maya Sari, M.Pd</td>
                                <td>Jumat, 08:00-10:00</td>
                                <td><button class="btn btn-danger">Hapus</button></td>
                            </tr>
                            <tr>
                                <td>TIF607</td>
                                <td>Etika Profesi</td>
                                <td>2</td>
                                <td>A</td>
                                <td>Dr. Hendra Wijaya</td>
                                <td>Jumat, 10:00-12:00</td>
                                <td><button class="btn btn-danger">Hapus</button></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="total-sks">
                        <strong>Total SKS yang Diambil: 21</strong><br>
                        <small>Batas Maksimal: 24 SKS (IPK ≥ 3.50)</small>
                    </div>

                    <div style="margin-top: 20px;">
                        <button class="btn btn-primary">Tambah Mata Kuliah</button>
                        <button class="btn btn-success">Ajukan Persetujuan</button>
                        <button class="btn btn-warning">Cetak KRS</button>
                    </div>
                </div>
            </div>

            <!-- KHS -->
            <div id="khs" class="page">
                <div class="header">
                    <h1>Kartu Hasil Studi (KHS)</h1>
                    <button class="logout-btn">Logout</button>
                </div>

                <div class="semester-tabs">
                    <button class="semester-tab active" onclick="showSemester(1)">Semester 1</button>
                    <button class="semester-tab" onclick="showSemester(2)">Semester 2</button>
                    <button class="semester-tab" onclick="showSemester(3)">Semester 3</button>
                    <button class="semester-tab" onclick="showSemester(4)">Semester 4</button>
                    <button class="semester-tab" onclick="showSemester(5)">Semester 5</button>
                </div>

                <div class="card semester-content" id="semester1">
                    <h2>Semester 1 - Ganjil 2021/2022</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Mata Kuliah</th>
                                <th>SKS</th>
                                <th>Nilai Huruf</th>
                                <th>Nilai Angka</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>TIF101</td>
                                <td>Pengantar Teknologi Informasi</td>
                                <td>3</td>
                                <td>A</td>
                                <td>4.00</td>
                            </tr>
                            <tr>
                                <td>TIF102</td>
                                <td>Algoritma dan Pemrograman</td>
                                <td>4</td>
                                <td>A</td>
                                <td>4.00</td>
                            </tr>
                            <tr>
                                <td>MAT101</td>
                                <td>Kalkulus I</td>
                                <td>3</td>
                                <td>B+</td>
                                <td>3.50</td>
                            </tr>
                            <tr>
                                <td>TIF103</td>
                                <td>Matematika Diskrit</td>
                                <td>3</td>
                                <td>A-</td>
                                <td>3.75</td>
                            </tr>
                            <tr>
                                <td>UNV101</td>
                                <td>Bahasa Indonesia</td>
                                <td>2</td>
                                <td>A</td>
                                <td>4.00</td>
                            </tr>
                            <tr>
                                <td>UNV102</td>
                                <td>Pendidikan Pancasila</td>
                                <td>2</td>
                                <td>A</td>
                                <td>4.00</td>
                            </tr>
                            <tr>
                                <td>UNV103</td>
                                <td>Pendidikan Agama</td>
                                <td>2</td>
                                <td>A</td>
                                <td>4.00</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="total-sks">
                        <strong>Total SKS: 19</strong><br>
                        <strong>IP Semester: 3.89</strong><br>
                        <strong>IPK: 3.89</strong>
                    </div>
                    <div style="margin-top: 20px;">
                        <button class="btn btn-warning">Cetak KHS</button>
                    </div>
                </div>

                <div class="card semester-content" id="semester2" style="display: none;">
                    <h2>Semester 2 - Genap 2021/2022</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Mata Kuliah</th>
                                <th>SKS</th>
                                <th>Nilai Huruf</th>
                                <th>Nilai Angka</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>TIF201</td>
                                <td>Struktur Data</td>
                                <td>4</td>
                                <td>A</td>
                                <td>4.00</td>
                            </tr>
                            <tr>
                                <td>TIF202</td>
                                <td>Pemrograman Berorientasi Objek</td>
                                <td>3</td>
                                <td>A-</td>
                                <td>3.75</td>
                            </tr>
                            <tr>
                                <td>TIF203</td>
                                <td>Basis Data</td>
                                <td>3</td>
                                <td>A</td>
                                <td>4.00</td>
                            </tr>
                            <tr>
                                <td>MAT201</td>
                                <td>Aljabar Linear</td>
                                <td>3</td>
                                <td>B+</td>
                                <td>3.50</td>
                            </tr>
                            <tr>
                                <td>FIS101</td>
                                <td>Fisika Dasar</td>
                                <td>3</td>
                                <td>B+</td>
                                <td>3.50</td>
                            </tr>
                            <tr>
                                <td>UNV201</td>
                                <td>Kewarganegaraan</td>
                                <td>2</td>
                                <td>A</td>
                                <td>4.00</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="total-sks">
                        <strong>Total SKS: 18</strong><br>
                        <strong>IP Semester: 3.78</strong><br>
                        <strong>IPK: 3.83</strong>
                    </div>
                </div>
            </div>

            <!-- Transkrip Nilai -->
            <div id="nilai" class="page">
                <div class="header">
                    <h1>Transkrip Nilai</h1>
                    <button class="logout-btn">Logout</button>
                </div>

                <div class="card">
                    <h2>Transkrip Nilai Sementara</h2>
                    <p style="margin-bottom: 20px;"><strong>IPK: 3.75</strong> | Total SKS Lulus: 110</p>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Semester</th>
                                <th>Kode</th>
                                <th>Mata Kuliah</th>
                                <th>SKS</th>
                                <th>Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td rowspan="7"><strong>Semester 1</strong></td>
                                <td>TIF101</td>
                                <td>Pengantar Teknologi Informasi</td>
                                <td>3</td>
                                <td>A</td>
                            </tr>
                            <tr>
                                <td>TIF102</td>
                                <td>Algoritma dan Pemrograman</td>
                                <td>4</td>
                                <td>A</td>
                            </tr>
                            <tr>
                                <td>MAT101</td>
                                <td>Kalkulus I</td>
                                <td>3</td>
                                <td>B+</td>
                            </tr>
                            <tr>
                                <td>TIF103</td>
                                <td>Matematika Diskrit</td>
                                <td>3</td>
                                <td>A-</td>
                            </tr>
                            <tr>
                                <td>UNV101</td>
                                <td>Bahasa Indonesia</td>
                                <td>2</td>
                                <td>A</td>
                            </tr>
                            <tr>
                                <td>UNV102</td>
                                <td>Pendidikan Pancasila</td>
                                <td>2</td>
                                <td>A</td>
                            </tr>
                            <tr>
                                <td>UNV103</td>
                                <td>Pendidikan Agama</td>
                                <td>2</td>
                                <td>A</td>
                            </tr>
                            <tr>
                                <td rowspan="6"><strong>Semester 2</strong></td>
                                <td>TIF201</td>
                                <td>Struktur Data</td>
                                <td>4</td>
                                <td>A</td>
                            </tr>
                            <tr>
                                <td>TIF202</td>
                                <td>Pemrograman Berorientasi Objek</td>
                                <td>3</td>
                                <td>A-</td>
                            </tr>
                            <tr>
                                <td>TIF203</td>
                                <td>Basis Data</td>
                                <td>3</td>
                                <td>A</td>
                            </tr>
                            <tr>
                                <td>MAT201</td>
                                <td>Aljabar Linear</td>
                                <td>3</td>
                                <td>B+</td>
                            </tr>
                            <tr>
                                <td>FIS101</td>
                                <td>Fisika Dasar</td>
                                <td>3</td>
                                <td>B+</td>
                            </tr>
                            <tr>
                                <td>UNV201</td>
                                <td>Kewarganegaraan</td>
                                <td>2</td>
                                <td>A</td>
                            </tr>
                            <tr>
                                <td rowspan="7"><strong>Semester 3</strong></td>
                                <td>TIF301</td>
                                <td>Sistem Operasi</td>
                                <td>3</td>
                                <td>A</td>
                            </tr>
                            <tr>
                                <td>TIF302</td>
                                <td>Jaringan Komputer</td>
                                <td>3</td>
                                <td>A-</td>
                            </tr>
                            <tr>
                                <td>TIF303</td>
                                <td>Analisis dan Desain Sistem</td>
                                <td>3</td>
                                <td>A</td>
                            </tr>
                            <tr>
                                <td>TIF304</td>
                                <td>Pemrograman Mobile</td>
                                <td>3</td>
                                <td>B+</td>
                            </tr>
                            <tr>
                                <td>MAT301</td>
                                <td>Statistika dan Probabilitas</td>
                                <td>3</td>
                                <td>A-</td>
                            </tr>
                            <tr>
                                <td>TIF305</td>
                                <td>Rekayasa Perangkat Lunak</td>
                                <td>3</td>
                                <td>A</td>
                            </tr>
                            <tr>
                                <td>UNV301</td>
                                <td>Bahasa Inggris I</td>
                                <td>2</td>
                                <td>A</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="total-sks">
                        <strong>Total SKS Lulus: 110</strong><br>
                        <strong>IPK Kumulatif: 3.75</strong>
                    </div>
                    <div style="margin-top: 20px;">
                        <button class="btn btn-warning">Cetak Transkrip</button>
                    </div>
                </div>
            </div>

            <!-- Jadwal Kuliah -->
            <div id="jadwal" class="page">
                <div class="header">
                    <h1>Jadwal Kuliah</h1>
                    <button class="logout-btn">Logout</button>
                </div>

                <div class="card">
                    <h2>Jadwal Kuliah Semester 6 (2024/2025 Genap)</h2>
                    
                    <h3 style="margin: 20px 0 15px; color: #2d3748;">Senin</h3>
                    <div class="jadwal-grid">
                        <div class="jadwal-card">
                            <h4>Pemrograman Web</h4>
                            <p>⏰ 08:00 - 10:00</p>
                            <p>👨‍🏫 Dr. Budi Santoso, M.Kom</p>
                            <p>📍 Lab. Komputer 1</p>
                            <p>📚 3 SKS</p>
                        </div>
                        <div class="jadwal-card">
                            <h4>Basis Data Lanjut</h4>
                            <p>⏰ 10:00 - 12:00</p>
                            <p>👨‍🏫 Prof. Dr. Siti Aminah, M.T</p>
                            <p>📍 Ruang 301</p>
                            <p>📚 3 SKS</p>
                        </div>
                        <div class="jadwal-card">
                            <h4>Kecerdasan Buatan</h4>
                            <p>⏰ 13:00 - 15:00</p>
                            <p>👨‍🏫 Ahmad Fauzi, S.Kom, M.Cs</p>
                            <p>📍 Ruang 205</p>
                            <p>📚 3 SKS</p>
                        </div>
                    </div>

                    <h3 style="margin: 20px 0 15px; color: #2d3748;">Selasa</h3>
                    <div class="jadwal-grid">
                        <div class="jadwal-card">
                            <h4>Sistem Terdistribusi</h4>
                            <p>⏰ 08:00 - 10:00</p>
                            <p>👨‍🏫 Dr. Rina Wati, M.T</p>
                            <p>📍 Ruang 302</p>
                            <p>📚 3 SKS</p>
                        </div>
                    </div>

                    <h3 style="margin: 20px 0 15px; color: #2d3748;">Rabu</h3>
                    <div class="jadwal-grid">
                        <div class="jadwal-card">
                            <h4>Keamanan Jaringan</h4>
                            <p>⏰ 10:00 - 12:00</p>
                            <p>👨‍🏫 Indra Gunawan, Ph.D</p>
                            <p>📍 Lab. Jaringan</p>
                            <p>📚 3 SKS</p>
                        </div>
                    </div>

                    <h3 style="margin: 20px 0 15px; color: #2d3748;">Kamis</h3>
                    <div class="jadwal-grid">
                        <div class="jadwal-card">
                            <h4>Manajemen Proyek TI</h4>
                            <p>⏰ 13:00 - 15:00</p>
                            <p>👨‍🏫 Dr. Lisa Permata, M.M</p>
                            <p>📍 Ruang 201</p>
                            <p>📚 2 SKS</p>
                        </div>
                    </div>

                    <h3 style="margin: 20px 0 15px; color: #2d3748;">Jumat</h3>
                    <div class="jadwal-grid">
                        <div class="jadwal-card">
                            <h4>Bahasa Inggris Teknik</h4>
                            <p>⏰ 08:00 - 10:00</p>
                            <p>👨‍🏫 Maya Sari, M.Pd</p>
                            <p>📍 Ruang 105</p>
                            <p>📚 2 SKS</p>
                        </div>
                        <div class="jadwal-card">
                            <h4>Etika Profesi</h4>
                            <p>⏰ 10:00 - 12:00</p>
                            <p>👨‍🏫 Dr. Hendra Wijaya, M.Si</p>
                            <p>📍 Ruang 203</p>
                            <p>📚 2 SKS</p>
                        </div>
                    </div>

                    <div style="margin-top: 20px;">
                        <button class="btn btn-warning">Cetak Jadwal</button>
                    </div>
                </div>
            </div>

            <!-- Daftar Mata Kuliah -->
            <div id="matakuliah" class="page">
                <div class="header">
                    <h1>Daftar Mata Kuliah</h1>
                    <button class="logout-btn">Logout</button>
                </div>

                <div class="card">
                    <h2>Katalog Mata Kuliah Program Studi Teknik Informatika</h2>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Mata Kuliah</th>
                                <th>SKS</th>
                                <th>Semester</th>
                                <th>Prasyarat</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>TIF101</td>
                                <td>Pengantar Teknologi Informasi</td>
                                <td>3</td>
                                <td>1</td>
                                <td>-</td>
                                <td><span class="badge badge-success">Lulus</span></td>
                            </tr>
                            <tr>
                                <td>TIF102</td>
                                <td>Algoritma dan Pemrograman</td>
                                <td>4</td>
                                <td>1</td>
                                <td>-</td>
                                <td><span class="badge badge-success">Lulus</span></td>
                            </tr>
                            <tr>
                                <td>TIF201</td>
                                <td>Struktur Data</td>
                                <td>4</td>
                                <td>2</td>
                                <td>TIF102</td>
                                <td><span class="badge badge-success">Lulus</span></td>
                            </tr>
                            <tr>
                                <td>TIF301</td>
                                <td>Sistem Operasi</td>
                                <td>3</td>
                                <td>3</td>
                                <td>TIF201</td>
                                <td><span class="badge badge-success">Lulus</span></td>
                            </tr>
                            <tr>
                                <td>TIF601</td>
                                <td>Pemrograman Web</td>
                                <td>3</td>
                                <td>6</td>
                                <td>TIF203</td>
                                <td><span class="badge badge-warning">Sedang Diambil</span></td>
                            </tr>
                            <tr>
                                <td>TIF701</td>
                                <td>Proyek Akhir I</td>
                                <td>4</td>
                                <td>7</td>
                                <td>110 SKS</td>
                                <td><span class="badge badge-danger">Belum</span></td>
                            </tr>
                            <tr>
                                <td>TIF702</td>
                                <td>Machine Learning</td>
                                <td>3</td>
                                <td>7</td>
                                <td>TIF603</td>
                                <td><span class="badge badge-danger">Belum</span></td>
                            </tr>
                            <tr>
                                <td>TIF801</td>
                                <td>Proyek Akhir II</td>
                                <td>4</td>
                                <td>8</td>
                                <td>TIF701</td>
                                <td><span class="badge badge-danger">Belum</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

           <!-- Pembayaran -->
<div id="pembayaran" class="page">
    <div class="header">
        <h1>Pembayaran</h1>
        <button class="logout-btn">Logout</button>
    </div>

    <div class="card" style="padding:0; overflow:hidden;">
        <iframe 
            src="https://ypi-khairaummah.sch.id/dashboard.php"
            style="
                width:100%;
                height:80vh;
                border:none;
                border-radius:10px;
            "
            loading="lazy"
            referrerpolicy="no-referrer"
        ></iframe>
    </div>
</div>


            <!-- Pengumuman -->
            <div id="pengumuman" class="page">
                <div class="header">
                    <h1>Pengumuman</h1>
                    <button class="logout-btn">Logout</button>
                </div>

                <div class="card">
                    <h2>📢 Pengumuman Akademik</h2>
                    
                    <div style="border-left: 4px solid #667eea; padding: 15px; margin-bottom: 15px; background: #f7fafc;">
                        <h3 style="color: #2d3748; margin-bottom: 10px;">Jadwal UTS Semester Genap 2024/2025</h3>
                        <p style="color: #4a5568; margin-bottom: 10px;">Ujian Tengah Semester akan dilaksanakan pada tanggal 15-20 Maret 2025. Mahasiswa diharapkan mempersiapkan diri dengan baik dan mengikuti ujian sesuai jadwal yang telah ditentukan.</p>
                        <small style="color: #718096;">Dipublikasikan: 28 Desember 2024</small>
                    </div>

                    <div style="border-left: 4px solid #48bb78; padding: 15px; margin-bottom: 15px; background: #f7fafc;">
                        <h3 style="color: #2d3748; margin-bottom: 10px;">Perpanjangan Pembayaran UKT</h3>
                        <p style="color: #4a5568; margin-bottom: 10px;">Mengingat banyaknya permintaan, batas akhir pembayaran UKT Semester Genap 2024/2025 diperpanjang hingga tanggal 5 Januari 2025. Mohon segera melakukan pembayaran untuk menghindari sanksi akademik.</p>
                        <small style="color: #718096;">Dipublikasikan: 25 Desember 2024</small>
                    </div>

                    <div style="border-left: 4px solid #ed8936; padding: 15px; margin-bottom: 15px; background: #f7fafc;">
                        <h3 style="color: #2d3748; margin-bottom: 10px;">Libur Akhir Tahun 2024</h3>
                        <p style="color: #4a5568; margin-bottom: 10px;">Dalam rangka merayakan Hari Raya Natal dan Tahun Baru 2025, kampus akan libur pada tanggal 30 Desember 2024 - 2 Januari 2025. Kegiatan perkuliahan akan dimulai kembali pada tanggal 3 Januari 2025.</p>
                        <small style="color: #718096;">Dipublikasikan: 20 Desember 2024</small>
                    </div>

                    <div style="border-left: 4px solid #667eea; padding: 15px; margin-bottom: 15px; background: #f7fafc;">
                        <h3 style="color: #2d3748; margin-bottom: 10px;">Pendaftaran Seminar Proposal Tugas Akhir</h3>
                        <p style="color: #4a5568; margin-bottom: 10px;">Bagi mahasiswa yang akan mengambil mata kuliah Proyek Akhir, pendaftaran seminar proposal dibuka mulai tanggal 6 Januari 2025. Silakan hubungi koordinator tugas akhir untuk informasi lebih lanjut.</p>
                        <small style="color: #718096;">Dipublikasikan: 18 Desember 2024</small>
                    </div>

                    <div style="border-left: 4px solid #9f7aea; padding: 15px; margin-bottom: 15px; background: #f7fafc;">
                        <h3 style="color: #2d3748; margin-bottom: 10px;">Workshop: Introduction to Cloud Computing</h3>
                        <p style="color: #4a5568; margin-bottom: 10px;">Himpunan Mahasiswa Teknik Informatika akan mengadakan workshop tentang Cloud Computing pada tanggal 10 Januari 2025. Pendaftaran gratis dan terbuka untuk semua mahasiswa. Daftar di website HMTI.</p>
                        <small style="color: #718096;">Dipublikasikan: 15 Desember 2024</small>
                    </div>

                    <div style="border-left: 4px solid #e53e3e; padding: 15px; margin-bottom: 15px; background: #f7fafc;">
                        <h3 style="color: #2d3748; margin-bottom: 10px;">Peringatan: Batas Waktu Pengisian KRS</h3>
                        <p style="color: #4a5568; margin-bottom: 10px;">Batas akhir pengisian KRS untuk Semester Genap 2024/2025 adalah tanggal 8 Januari 2025 pukul 23:59 WIB. Mahasiswa yang terlambat mengisi KRS akan dikenakan sanksi.</p>
                        <small style="color: #718096;">Dipublikasikan: 12 Desember 2024</small>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function showPage(pageId) {
            // Hide all pages
            const pages = document.querySelectorAll('.page');
            pages.forEach(page => page.classList.remove('active'));
            
            // Remove active from all menu items
            const menuItems = document.querySelectorAll('.menu-item');
            menuItems.forEach(item => item.classList.remove('active'));
            
            // Show selected page
            document.getElementById(pageId).classList.add('active');
            
            // Add active to clicked menu
            event.currentTarget.classList.add('active');
        }

        function showSemester(semesterId) {
            // Hide all semester contents
            const contents = document.querySelectorAll('.semester-content');
            contents.forEach(content => content.style.display = 'none');
            
            // Remove active from all tabs
            const tabs = document.querySelectorAll('.semester-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected semester
            document.getElementById('semester' + semesterId).style.display = 'block';
            
            // Add active to clicked tab
            event.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>