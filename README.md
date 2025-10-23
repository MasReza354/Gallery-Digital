# 🖌️ Galeri Karya Siswa

Website galeri digital untuk karya kreatif siswa dengan sistem review dan publikasi modern.

## 📋 Fitur Utama

### 👥 **Role-Based Access**

- **Administrator**: Manajemen pengguna, approve akun, overview sistem
- **Guru/Kurator**: Review karya, beri feedback, setujui/tolak karya
- **Siswa**: Upload karya, edit profil, monitor status karya
- **Publik**: Jelajahi galeri, beri like pada karya

### 🎨 **Fitur Lengkap**

- ✨ Upload karya (gambar/video/dokumen) dengan drag-drop
- 🔍 Pencarian & filter galeri yang advanced
- 👍 Sistem like IP-based untuk publik
- 📝 Review sistem dengan feedback dari guru
- 🔔 Notifikasi real-time
- 🌙 Dark/Light mode toggle
- 📱 Responsive design untuk semua device
- 🎬 Animasi logo pada loading screen

## 🚀 **Cara Setup**

### 📋 **Persyaratan Sistem**

- PHP 8.1+
- MySQL 5.7+ atau MariaDB 10.0+
- Apache/Nginx web server
- Browser modern (Chrome, Firefox, Safari, Edge)

### 🗄️ **1. Setup Database**

#### **Option A: Menggunakan file database.sql (Recommended)**

```bash
# Import database melalui command line
mysql -u root -p < database.sql

# Atau melalui phpMyAdmin:
# 1. Buat database baru: galeri_karya
# 2. Import file database.sql
```

#### **Option B: Setup Web-based (Alternatif)**

```bash
# Jalankan server PHP
php -S localhost:8080

# Akses installer melalui browser
# http://localhost:8080/install.php
```

### 📁 **2. Upload Files**

Upload semua file ke web server Anda. Pastikan:

```
/public_html/ atau /var/www/html/
├── config/
├── includes/
├── dashboard/
├── uploads/  <-- Pastikan writable (chmod 755)
└── *.php files
```

### ⚙️ **3. Konfigurasi**

Edit `config/database.php` jika perlu:

```php
define('DB_HOST', 'localhost');  // Ganti jika berbeda
define('DB_USER', 'root');       // Username database
define('DB_PASS', '');          // Password database
define('DB_NAME', 'galeri_karya'); // Nama database
```

### 🌐 **4. Akses Aplikasi**

- **Homepage**: `http://localhost/index.php`
- **Admin Login**:
  - Username: `admin`
  - Password: `admin123`
- **Register**: Buat akun siswa/guru baru

## 📊 **Struktur Database**

### Tabel Utama:

- **`Pengguna`**: Data user (admin/guru/siswa)
- **`Kategori`**: Kategori karya (seni, teknologi, dll)
- **`Karya`**: Data karya yang diupload
- **`Umpan_Balik`**: Feedback dari guru ke siswa
- **`Suka`**: Like system (IP-based)
- **`Notifikasi`**: System notifications

### Constraints & Relations:

- Foreign key relationships antar tabel
- Unique constraints untuk like system
- Indexes untuk performance optimal

## 🛠️ **Testing**

### 📝 **Test Data**

File `database.sql` sudah menyertakan:

- Admin user (admin/admin123)
- Sample guru & siswa accounts
- Sample categories (8 kategori default)
- Sample artworks untuk testing

### 🔍 **Test Checklist**

- [ ] Login sebagai admin
- [ ] Register akun baru
- [ ] Upload karya sebagai siswa
- [ ] Review karya sebagai guru
- [ ] Like karya sebagai publik
- [ ] Dark mode toggle
- [ ] Mobile responsive

## 📚 **Dokumentasi**

Untuk dokumentasi lengkap, lihat file `tech_doc.md`

## 🐛 **Troubleshooting**

### Error: Database Connection Failed

```bash
# Pastikan MySQL service running
sudo service mysql start

# Cek credentials di config/database.php
```

### Error: Permission Denied on Uploads

```bash
# Set permission untuk folder uploads
chmod -R 755 uploads/
chown -R www-data:www-data uploads/
```

### Error: Undefined Function

```bash
# Pastikan semua file diupload dengan benar
# Cek include paths dan function definitions
```

## 📄 **License**

Proyek ini dibuat untuk tujuan edukasi dan dapat digunakan bebas dengan atribusi yang sesuai.

## 👥 **Kontributor**

- **Prompting Expert** - Ahli Promting
- **Development** - AI Assistant

---

> 🎨 **"Kreativitas dimulai dari ide, tapi terwujud melalui aksi."**
