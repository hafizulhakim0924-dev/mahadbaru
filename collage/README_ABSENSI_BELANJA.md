# Dokumentasi Sistem Absensi dan Belanja

## Instalasi Database

1. Import file `database_schema.sql` ke database MySQL Anda
2. File ini akan membuat tabel-tabel berikut:
   - `absensi` - untuk menyimpan data absensi siswa
   - `dosen` - untuk menyimpan data dosen
   - `barang` - untuk menyimpan data barang yang dijual
   - `pesanan_belanja` - untuk menyimpan data pesanan belanja
   - `detail_pesanan` - untuk menyimpan detail item dalam pesanan
   - `voucher_pembayaran` - untuk menyimpan voucher pembayaran
   - `admin` - untuk menyimpan data admin

## Fitur Absensi

### Untuk Dosen:
1. Akses halaman `dosen_absensi.php`
2. Login dengan kredensial dosen (default: username `dosen1`, password `dosen123`)
3. Input absensi siswa dengan memilih:
   - Nama siswa
   - Mata kuliah
   - Tanggal
   - Status (Hadir/Izin/Sakit/Alpha)
   - Keterangan (opsional)

### Untuk Siswa:
1. Login ke dashboard siswa
2. Klik tab "Absensi"
3. Lihat rekapan kehadiran dengan statistik:
   - Total absensi
   - Jumlah hadir, izin, sakit, alpha
   - Persentase kehadiran

## Fitur Belanja

### Untuk Admin:
1. Akses halaman `adminbelanja.php`
2. Login dengan kredensial admin (default: username `admin`, password `admin123`)
3. **Kelola Barang:**
   - Tambah barang baru
   - Edit barang yang ada
   - Hapus barang
   - Set status aktif/nonaktif
4. **Redeem Voucher:**
   - Masukkan kode voucher
   - Klik "Redeem" untuk menghapus voucher dari akun siswa
   - Voucher yang sudah diredeem tidak bisa digunakan lagi

### Untuk Siswa:
1. Login ke dashboard siswa
2. Klik tab "Belanja"
3. **Belanja:**
   - Pilih barang yang tersedia
   - Tambah ke keranjang
   - Checkout dan pilih metode pembayaran (Tripay)
   - Setelah pembayaran berhasil, akan mendapat voucher
4. **Voucher:**
   - Lihat daftar voucher yang dimiliki
   - Cetak voucher untuk ditukarkan ke admin kampus
   - Setelah ditukarkan, admin akan menghapus voucher dari akun

## Alur Pembayaran Belanja

1. Siswa memilih barang dan checkout
2. Sistem membuat pesanan dan payment gateway Tripay
3. Siswa melakukan pembayaran melalui Tripay
4. Setelah pembayaran berhasil (callback dari Tripay):
   - Status pesanan diupdate menjadi "berhasil"
   - Sistem otomatis membuat voucher untuk siswa
5. Siswa mencetak voucher
6. Siswa menukarkan voucher ke admin kampus (offline)
7. Admin login ke `adminbelanja.php` dan redeem voucher
8. Voucher dihapus dari akun siswa (tidak bisa diredeem lagi)

## File-file yang Dibuat

1. **database_schema.sql** - Script SQL untuk membuat tabel
2. **dosen_absensi.php** - Halaman dosen untuk input absensi
3. **adminbelanja.php** - Halaman admin untuk kelola barang dan redeem voucher
4. **belanja_checkout.php** - Halaman checkout belanja
5. **belanja_payment.php** - Halaman pembayaran belanja
6. **check_payment_belanja.php** - API untuk cek status pembayaran
7. **callback.php** - Callback dari Tripay untuk tagihan/SPP dan belanja (gabungan)
8. **cetak_voucher.php** - Halaman untuk mencetak voucher

## Kredensial Default

### Admin:
- Username: `admin`
- Password: `admin123`

### Dosen:
- Username: `dosen1`, `dosen2`, `dosen3`
- Password: `dosen123`

## Catatan Penting

1. Pastikan konfigurasi Tripay sudah benar di semua file yang menggunakan payment gateway
2. Callback URL untuk semua pembayaran: `https://ypi-khairaummah.sch.id/callback.php` (untuk tagihan/SPP dan belanja)
3. Voucher hanya bisa diredeem sekali oleh admin
4. Setelah voucher diredeem, statusnya berubah menjadi "redeemed" dan tidak bisa digunakan lagi

## Integrasi dengan Dashboard

Tab "Absensi" dan "Belanja" sudah ditambahkan di `dashboard.php` dan akan otomatis muncul setelah login sebagai siswa.

