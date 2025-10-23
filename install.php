<?php
require_once 'config/database.php';

// Initialize database
initializeDatabase();

// Get connection to the database
$conn = getDBConnection();

// SQL to create tables
$sql = "
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
);

-- Tabel untuk menyimpan kategori karya
CREATE TABLE IF NOT EXISTS Kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL UNIQUE,
    deskripsi TEXT DEFAULT NULL,
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP
);

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
);

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
);

-- Tabel untuk mencatat 'suka' dari pengunjung publik
CREATE TABLE IF NOT EXISTS Suka (
    id INT AUTO_INCREMENT PRIMARY KEY,
    karya_id INT NOT NULL,
    user_ip VARCHAR(255) NOT NULL,
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unik_suka (karya_id, user_ip),
    FOREIGN KEY (karya_id) REFERENCES Karya(id) ON DELETE CASCADE
);

-- Tabel untuk menyimpan notifikasi bagi pengguna
CREATE TABLE IF NOT EXISTS Notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pengguna_id INT NOT NULL,
    pesan TEXT NOT NULL,
    link_tujuan VARCHAR(255) DEFAULT '#',
    status ENUM('belum dibaca', 'dibaca') DEFAULT 'belum dibaca',
    dibuat_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pengguna_id) REFERENCES Pengguna(id) ON DELETE CASCADE
);
";

// Execute multi query
if ($conn->multi_query($sql)) {
  do {
    // Store first result set
    if ($result = $conn->store_result()) {
      $result->free();
    }
  } while ($conn->next_result());

  echo "Database tables created successfully!<br>";
} else {
  echo "Error creating tables: " . $conn->error . "<br>";
}

// Insert default administrator
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$admin_check = $conn->query("SELECT id FROM Pengguna WHERE username = 'admin'");

if ($admin_check->num_rows == 0) {
  $stmt = $conn->prepare("INSERT INTO Pengguna (username, email, password, peran, nama_lengkap, status) VALUES (?, ?, ?, 'administrator', ?, 'aktif')");
  $username = 'admin';
  $email = 'admin@galeri.com';
  $nama = 'Administrator';
  $stmt->bind_param("ssss", $username, $email, $admin_password, $nama);

  if ($stmt->execute()) {
    echo "Default administrator account created!<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
  }
  $stmt->close();
}

// Insert default categories
$categories = [
  ['Seni Rupa', 'Karya seni visual seperti lukisan, gambar, dan ilustrasi'],
  ['Sains', 'Proyek dan penelitian ilmiah'],
  ['Teknologi', 'Inovasi dan karya berbasis teknologi'],
  ['Sastra', 'Karya tulis seperti puisi, cerpen, dan esai'],
  ['Musik', 'Komposisi musik dan audio'],
  ['Video', 'Film pendek, animasi, dan konten video'],
  ['Fotografi', 'Karya fotografi dan visual'],
  ['Desain', 'Desain grafis, UI/UX, dan produk']
];

foreach ($categories as $cat) {
  $check = $conn->query("SELECT id FROM Kategori WHERE nama = '" . $conn->real_escape_string($cat[0]) . "'");
  if ($check->num_rows == 0) {
    $stmt = $conn->prepare("INSERT INTO Kategori (nama, deskripsi) VALUES (?, ?)");
    $stmt->bind_param("ss", $cat[0], $cat[1]);
    $stmt->execute();
    $stmt->close();
  }
}

echo "Default categories inserted!<br>";

// Create uploads directory
$directories = ['uploads/karya', 'uploads/profil'];
foreach ($directories as $dir) {
  if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
    echo "Directory created: $dir<br>";
  }
}

echo "<br><strong>Installation completed successfully!</strong><br>";
echo "<a href='index.php'>Go to Homepage</a> | <a href='login.php'>Login</a>";

$conn->close();
