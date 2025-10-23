# Dokumen Teknis: Website Galeri Karya Kreativitas Siswa

**Versi:** 1.0
**Tanggal:** 19 Oktober 2025
**Penulis:** Ahli Promting

## 1. Ringkasan Proyek

Proyek ini bertujuan untuk membangun sebuah platform website yang responsif, interaktif, dan modern sebagai etalase untuk karya-karya kreatif siswa. Website ini akan berfungsi sebagai galeri digital di mana siswa dapat mengunggah karya mereka, guru/kurator dapat meninjau dan memberikan umpan balik, serta publik dapat melihat dan mengapresiasi karya yang telah disetujui. Desain website akan mengutamakan estetika minimalis dan pengalaman pengguna yang intuitif, dengan inspirasi dari platform modern seperti Weverse.

---

## 2. Fitur Fungsional

### 2.1. Fitur Umum

- **Desain Responsif & Modern**: Tampilan website akan beradaptasi secara mulus di berbagai perangkat (desktop, tablet, dan mobile) dengan antarmuka yang elegan, bersih, dan berfokus pada kenyamanan pengguna.
- **Tampilan Website Interaktif**:
  - _Hero Section_ di halaman utama yang menampilkan gambar atau video dinamis terkait kreativitas siswa.
  - Galeri karya unggulan dan terbaru di halaman utama sebagai _highlight_.
  - Halaman galeri terdedikasi dengan fitur pencarian dan filter yang komprehensif.
- **Mode Gelap dan Terang (Dark/Light Mode)**: Pengguna dapat beralih tema tampilan sesuai preferensi untuk kenyamanan visual.
- **Animasi Logo pada Loading Screen**: Menampilkan animasi logo saat memuat halaman untuk memberikan pengalaman yang lebih dinamis dan profesional.
- **Interaksi Pengguna**: Sistem interaksi yang jelas antara siswa, kurator, dan pengunjung publik.

### 2.2. Fitur Berdasarkan Peran Pengguna

#### 2.2.1. Administrator

- Akses penuh untuk mengelola semua aspek website.
- Mengelola data pengguna (guru, siswa), karya, dan galeri.
- Melakukan verifikasi dan aktivasi akun guru dan siswa yang baru terdaftar.

#### 2.2.2. Guru / Kurator

- Meninjau, menyetujui, atau menolak karya yang diunggah siswa.
- Memberikan umpan balik konstruktif pada setiap karya.
- Mengubah status karya (`menunggu`, `disetujui`, `revisi`).
- Memilih dan menandai karya untuk ditampilkan di galeri unggulan.

#### 2.2.3. Siswa

- Melakukan registrasi dan login ke akun pribadi.
- Mengunggah karya dalam berbagai format (gambar, video, dokumen).
- Mengelola karya yang telah diunggah (edit/hapus jika belum disetujui).
- Memantau status karya melalui dasbor pribadi.
- Menerima notifikasi dan umpan balik dari kurator.
- Mengelola profil pribadi (foto, biodata).

#### 2.2.4. Pengunjung (Publik)

- Mengakses dan menjelajahi galeri karya yang telah disetujui tanpa perlu login.
- Menggunakan fitur pencarian dan filter untuk menemukan karya.
- Memberikan "suka" (_like_) pada karya (dibatasi satu kali per perangkat/IP).

---

## 3. Halaman Website

1.  **Halaman Utama (Homepage)**: Gerbang utama website yang berisi _hero section_, galeri karya unggulan, galeri karya terbaru, dan _call-to-action_ untuk menuju halaman galeri lengkap.
2.  **Halaman Galeri**: Menampilkan semua karya yang telah disetujui dalam format grid. Dilengkapi fitur pencarian berdasarkan judul, nama siswa, dll., serta filter berdasarkan kategori.
3.  **Halaman Detail Karya**: Menampilkan karya dalam resolusi penuh, beserta informasi detail seperti judul, deskripsi, nama kreator (siswa), dan tanggal unggah.
4.  **Halaman Profil Pengguna**:
    - **Profil Publik**: Menampilkan biodata singkat siswa, foto, dan kumpulan karyanya yang telah disetujui.
    - **Edit Profil**: Halaman privat yang hanya bisa diakses oleh pengguna untuk mengubah data diri.
5.  **Halaman Registrasi**: Formulir pendaftaran untuk siswa dan guru dengan verifikasi keamanan (captcha perhitungan).
6.  **Halaman Login**: Formulir untuk masuk ke akun, dilengkapi dengan fitur "Lupa Password" yang terintegrasi dengan email.

---

## 4. Teknologi yang Digunakan

- **Frontend**: **PHP Native** untuk rendering sisi server dan logika tampilan, serta **TailwindCSS** untuk styling. Pendekatan ini dipilih untuk kemudahan pengembangan dan pemeliharaan bagi pemula.
- **Backend**: **PHP Native** untuk mengelola logika bisnis, otentikasi, dan interaksi dengan database.
- **Database**: **MySQL** untuk penyimpanan data yang terstruktur, andal, dan mudah dikelola.
- **Web Server**: Apache atau Nginx.

---

## 5. Struktur Database

Berikut adalah struktur skema database MySQL yang akan digunakan untuk proyek ini.

```sql
-- Tabel untuk menyimpan semua data pengguna (administrator, guru, siswa)
CREATE TABLE Pengguna (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    peran ENUM('administrator', 'guru', 'siswa') NOT NULL,
    nama_lengkap VARCHAR(255) NOT NULL,
    foto_profil VARCHAR(255) DEFAULT 'default_avatar.png',
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('aktif', 'tidak aktif', 'menunggu') DEFAULT 'menunggu'
);

-- Tabel untuk menyimpan kategori karya, seperti Seni, Sains, Teknologi, dll.
CREATE TABLE Kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL UNIQUE,
    deskripsi TEXT DEFAULT NULL,
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabel untuk menyimpan detail karya yang diunggah oleh siswa
CREATE TABLE Karya (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    deskripsi TEXT,
    siswa_id INT NOT NULL,
    kategori_id INT,
    status ENUM('menunggu', 'disetujui', 'ditolak', 'revisi') DEFAULT 'menunggu',
    unggulan BOOLEAN DEFAULT FALSE,
    media_url VARCHAR(255) NOT NULL, -- Path ke file (gambar/video/dokumen)
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES Pengguna(id) ON DELETE CASCADE,
    FOREIGN KEY (kategori_id) REFERENCES Kategori(id) ON DELETE SET NULL
);

-- Tabel untuk menyimpan umpan balik dari guru/kurator terhadap sebuah karya
CREATE TABLE Umpan_Balik (
    id INT AUTO_INCREMENT PRIMARY KEY,
    karya_id INT NOT NULL,
    guru_id INT NOT NULL,
    umpan_balik TEXT NOT NULL,
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (karya_id) REFERENCES Karya(id) ON DELETE CASCADE,
    FOREIGN KEY (guru_id) REFERENCES Pengguna(id) ON DELETE CASCADE
);

-- Tabel untuk mencatat 'suka' dari pengunjung publik
CREATE TABLE Suka (
    id INT AUTO_INCREMENT PRIMARY KEY,
    karya_id INT NOT NULL,
    user_ip VARCHAR(255) NOT NULL,
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unik_suka (karya_id, user_ip), -- Mencegah user IP yang sama menyukai karya yang sama lebih dari sekali
    FOREIGN KEY (karya_id) REFERENCES Karya(id) ON DELETE CASCADE
);

-- Tabel untuk menyimpan notifikasi bagi pengguna
CREATE TABLE Notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pengguna_id INT NOT NULL,
    pesan TEXT NOT NULL,
    link_tujuan VARCHAR(255) DEFAULT '#',
    status ENUM('belum dibaca', 'dibaca') DEFAULT 'belum dibaca',
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pengguna_id) REFERENCES Pengguna(id) ON DELETE CASCADE
);

-- Tabel Galeri tidak diperlukan karena status 'unggulan' dan 'terbaru'
-- dapat diperoleh langsung dari query pada tabel 'Karya'.
-- Contoh Query untuk Karya Unggulan: SELECT * FROM Karya WHERE unggulan = TRUE AND status = 'disetujui'
-- Contoh Query untuk Karya Terbaru: SELECT * FROM Karya WHERE status = 'disetujui' ORDER BY dibuat_pada DESC
```
