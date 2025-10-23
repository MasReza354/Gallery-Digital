-- =================================================
-- Galeri Karya Siswa - Database Schema
-- Dibuat untuk proyek website galeri karya kreatif siswa
-- =================================================

-- Membuat database dengan charset UTF-8
CREATE DATABASE IF NOT EXISTS galeri_karya CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE galeri_karya;

-- =================================================
-- Tabel Utama
-- =================================================

-- Tabel untuk menyimpan semua data pengguna (administrator, guru, siswa)
CREATE TABLE IF NOT EXISTS Pengguna (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk menyimpan kategori karya
CREATE TABLE IF NOT EXISTS Kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL UNIQUE,
    deskripsi TEXT DEFAULT NULL,
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk menyimpan detail karya yang diunggah oleh siswa
CREATE TABLE IF NOT EXISTS Karya (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    deskripsi TEXT,
    siswa_id INT NOT NULL,
    kategori_id INT,
    status ENUM('menunggu', 'disetujui', 'ditolak', 'revisi') DEFAULT 'menunggu',
    unggulan BOOLEAN DEFAULT FALSE,
    media_url VARCHAR(255) NOT NULL,
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES Pengguna(id) ON DELETE CASCADE,
    FOREIGN KEY (kategori_id) REFERENCES Kategori(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk menyimpan umpan balik dari guru/kurator
CREATE TABLE IF NOT EXISTS Umpan_Balik (
    id INT AUTO_INCREMENT PRIMARY KEY,
    karya_id INT NOT NULL,
    guru_id INT NOT NULL,
    umpan_balik TEXT NOT NULL,
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    diperbarui_pada DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (karya_id) REFERENCES Karya(id) ON DELETE CASCADE,
    FOREIGN KEY (guru_id) REFERENCES Pengguna(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk mencatat 'suka' dari pengunjung publik
CREATE TABLE IF NOT EXISTS Suka (
    id INT AUTO_INCREMENT PRIMARY KEY,
    karya_id INT NOT NULL,
    user_ip VARCHAR(255) NOT NULL,
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unik_suka (karya_id, user_ip),
    FOREIGN KEY (karya_id) REFERENCES Karya(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk menyimpan notifikasi bagi pengguna
CREATE TABLE IF NOT EXISTS Notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pengguna_id INT NOT NULL,
    pesan TEXT NOT NULL,
    link_tujuan VARCHAR(255) DEFAULT '#',
    status ENUM('belum dibaca', 'dibaca') DEFAULT 'belum dibaca',
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pengguna_id) REFERENCES Pengguna(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================
-- Data Default
-- =================================================

-- Insert default administrator account
-- Password: admin123 (hashed)
INSERT INTO Pengguna (username, email, password, peran, nama_lengkap, status) VALUES
('admin', 'admin@galeri.com', '$2y$10$ELfeQOj6QIeQsXBcPY69heAjff01NrVu0.2wGUagpx0A81H.pbqay', 'administrator', 'Administrator', 'aktif');

-- Insert default categories
INSERT INTO Kategori (nama, deskripsi) VALUES
('Seni Rupa', 'Karya seni visual seperti lukisan, gambar, dan ilustrasi'),
('Sains', 'Proyek dan penelitian ilmiah'),
('Teknologi', 'Inovasi dan karya berbasis teknologi'),
('Sastra', 'Karya tulis seperti puisi, cerpen, dan esai'),
('Musik', 'Komposisi musik dan audio'),
('Video', 'Film pendek, animasi, dan konten video'),
('Fotografi', 'Karya fotografi dan visual'),
('Desain', 'Desain grafis, UI/UX, dan produk');

-- =================================================
-- Data Sample (Opsional - untuk testing)
-- =================================================

-- Sample users for testing
INSERT INTO Pengguna (username, email, password, peran, nama_lengkap, status) VALUES
('guru1', 'guru1@example.com', '$2y$10$ELfeQOj6QIeQsXBcPY69heAjff01NrVu0.2wGUagpx0A81H.pbqay', 'guru', 'Guru Kurator 1', 'aktif'),
('siswa1', 'siswa1@example.com', '$2y$10$ELfeQOj6QIeQsXBcPY69heAjff01NrVu0.2wGUagpx0A81H.pbqay', 'siswa', 'Siswa Kreatif', 'aktif');

-- Sample artwork for testing
INSERT INTO Karya (judul, deskripsi, siswa_id, kategori_id, status, unggulan, media_url) VALUES
('Lukisan Alam', 'Karya seni rupa yang menggambarkan keindahan alam Indonesia', 3, 1, 'disetujui', TRUE, 'sample-artwork-1.jpg'),
('Proyek Robot', 'Robot sederhana menggunakan Arduino untuk pendidikan STEM', 3, 3, 'disetujui', FALSE, 'sample-robot-project.mp4');

-- =================================================
-- Indexes untuk Performance
-- =================================================

-- Index untuk pencarian berdasarkan status karya
CREATE INDEX idx_karya_status ON Karya(status);
CREATE INDEX idx_karya_unggulan ON Karya(unggulan);
CREATE INDEX idx_karya_siswa ON Karya(siswa_id);
CREATE INDEX idx_karya_kategori ON Karya(kategori_id);

-- Index untuk notifikasi
CREATE INDEX idx_notifikasi_pengguna ON Notifikasi(pengguna_id);
CREATE INDEX idx_notifikasi_status ON Notifikasi(status);

-- Index untuk feedback
CREATE INDEX idx_feedback_karya ON Umpan_Balik(karya_id);
CREATE INDEX idx_feedback_guru ON Umpan_Balik(guru_id);

-- =================================================
-- Setup Selesai
-- =================================================

-- Tampilkan summary tables
SELECT 'Database galeri_karya berhasil dibuat dengan semua tabel dan data default' as Status;
