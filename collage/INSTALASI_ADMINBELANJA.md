# Instalasi Database untuk Admin Belanja

## Langkah-langkah Instalasi

### 1. Pastikan Database Sudah Dibuat
Pastikan database `ypikhair_datautama` sudah dibuat di MySQL/MariaDB.

Jika belum, buat dengan perintah:
```sql
CREATE DATABASE IF NOT EXISTS ypikhair_datautama CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Import SQL Schema
Jalankan file `adminbelanja_schema.sql` di database `ypikhair_datautama`.

**Cara 1: Via phpMyAdmin**
1. Buka phpMyAdmin
2. Pilih database `ypikhair_datautama`
3. Klik tab "Import"
4. Pilih file `adminbelanja_schema.sql`
5. Klik "Go" atau "Import"

**Cara 2: Via Command Line**
```bash
mysql -u ypikhair_admin -p ypikhair_datautama < adminbelanja_schema.sql
```

**Cara 3: Via MySQL Client**
1. Login ke MySQL:
```bash
mysql -u ypikhair_admin -p
```
2. Pilih database:
```sql
USE ypikhair_datautama;
```
3. Jalankan file SQL:
```sql
SOURCE /path/to/adminbelanja_schema.sql;
```

### 3. Verifikasi Tabel
Setelah import, pastikan semua tabel sudah dibuat dengan menjalankan:
```sql
SHOW TABLES;
```

Tabel yang harus ada:
- ✅ `students`
- ✅ `barang`
- ✅ `pesanan_belanja`
- ✅ `detail_pesanan`
- ✅ `voucher_pembayaran`
- ✅ `tagihan`
- ✅ `pembayaran`
- ✅ `admin`

### 4. Cek Data Default
Pastikan admin default sudah terbuat:
```sql
SELECT * FROM admin WHERE username = 'admin';
```

Username: `admin`
Password: `admin123`

### 5. Test Halaman Admin
1. Buka `adminbelanja.php` di browser
2. Login dengan kredensial admin
3. Pastikan semua tab bisa dibuka tanpa error

## Troubleshooting

### Error: "Table doesn't exist"
- Pastikan file SQL sudah diimport dengan benar
- Cek apakah database yang dipilih sudah benar
- Pastikan user database memiliki hak CREATE TABLE

### Error: "Connection failed"
- Cek kredensial database di `adminbelanja.php`:
  - `$servername`
  - `$username`
  - `$password`
  - `$dbname`

### Error: "Access denied"
- Pastikan user database memiliki hak akses yang cukup:
  - SELECT
  - INSERT
  - UPDATE
  - DELETE
  - CREATE TABLE (jika perlu)

### Halaman Kosong atau Error 500
- Aktifkan error reporting di PHP untuk melihat error detail
- Cek error log server
- Pastikan semua tabel sudah dibuat dengan benar

## Catatan Penting

1. **IF NOT EXISTS**: Semua tabel menggunakan `IF NOT EXISTS`, jadi aman untuk dijalankan berulang kali
2. **Data Default**: Data default hanya akan diinsert jika belum ada (menggunakan `ON DUPLICATE KEY UPDATE`)
3. **Charset**: Semua tabel menggunakan `utf8mb4` untuk mendukung karakter unicode
4. **Engine**: Semua tabel menggunakan `InnoDB` untuk mendukung foreign key dan transaction

## Support

Jika masih ada masalah, pastikan:
- PHP version >= 7.0
- MySQL/MariaDB version >= 5.7
- Extension mysqli sudah aktif
- Error reporting aktif untuk debugging

