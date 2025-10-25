-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 25, 2025 at 11:30 AM
-- Server version: 5.7.39
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `galeri_karya`
--
CREATE DATABASE IF NOT EXISTS `galeri_karya` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `galeri_karya`;

-- --------------------------------------------------------

--
-- Table structure for table `karya`
--

CREATE TABLE `karya` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `siswa_id` int(11) NOT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `status` enum('menunggu','disetujui','ditolak','revisi') COLLATE utf8mb4_unicode_ci DEFAULT 'menunggu',
  `unggulan` tinyint(1) DEFAULT 0,
  `media_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dibuat_pada` datetime DEFAULT CURRENT_TIMESTAMP,
  `diperbarui_pada` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'fa-palette',
  `dibuat_pada` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id`, `nama`, `deskripsi`, `icon`) VALUES
(1, 'Seni Rupa', 'Karya seni visual seperti lukisan, gambar, dan ilustrasi', 'fa-paint-brush'),
(2, 'Sains', 'Proyek dan penelitian ilmiah', 'fa-flask'),
(3, 'Teknologi', 'Inovasi dan karya berbasis teknologi', 'fa-robot'),
(4, 'Sastra', 'Karya tulis seperti puisi, cerpen, dan esai', 'fa-book-open'),
(5, 'Musik', 'Komposisi musik dan audio', 'fa-music'),
(6, 'Video', 'Film pendek, animasi, dan konten video', 'fa-video'),
(7, 'Fotografi', 'Karya fotografi dan visual', 'fa-camera'),
(8, 'Desain', 'Desain grafis, UI/UX, dan produk', 'fa-drafting-compass');

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id` int(11) NOT NULL,
  `pengguna_id` int(11) NOT NULL,
  `pesan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `link_tujuan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '#',
  `status` enum('belum dibaca','dibaca') COLLATE utf8mb4_unicode_ci DEFAULT 'belum dibaca',
  `dibuat_pada` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengguna`
--

CREATE TABLE `pengguna` (
  `id` int(11) NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `peran` enum('administrator','guru','siswa') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_lengkap` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `foto_profil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default_avatar.png',
  `dibuat_pada` datetime DEFAULT CURRENT_TIMESTAMP,
  `diperbarui_pada` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('aktif','tidak aktif','menunggu') COLLATE utf8mb4_unicode_ci DEFAULT 'menunggu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengguna`
--

INSERT INTO `pengguna` (`id`, `username`, `email`, `password`, `peran`, `nama_lengkap`, `status`) VALUES
(1, 'admin', 'admin@galeri.com', '$2y$10$ELfeQOj6QIeQsXBcPY69heAjff01NrVu0.2wGUagpx0A81H.pbqay', 'administrator', 'Administrator', 'aktif');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `name`, `value`) VALUES
(1, 'hero_background', 'default_hero.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `suka`
--

CREATE TABLE `suka` (
  `id` int(11) NOT NULL,
  `karya_id` int(11) NOT NULL,
  `user_ip` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dibuat_pada` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `umpan_balik`
--

CREATE TABLE `umpan_balik` (
  `id` int(11) NOT NULL,
  `karya_id` int(11) NOT NULL,
  `guru_id` int(11) NOT NULL,
  `umpan_balik` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dibuat_pada` datetime DEFAULT CURRENT_TIMESTAMP,
  `diperbarui_pada` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

ALTER TABLE `karya` ADD PRIMARY KEY (`id`), ADD KEY `siswa_id` (`siswa_id`), ADD KEY `kategori_id` (`kategori_id`);
ALTER TABLE `kategori` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `nama` (`nama`);
ALTER TABLE `notifikasi` ADD PRIMARY KEY (`id`), ADD KEY `pengguna_id` (`pengguna_id`);
ALTER TABLE `pengguna` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `username` (`username`), ADD UNIQUE KEY `email` (`email`);
ALTER TABLE `password_resets` ADD PRIMARY KEY (`id`), ADD KEY `email` (`email`);
ALTER TABLE `settings` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `name` (`name`);
ALTER TABLE `suka` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `unik_suka` (`karya_id`,`user_ip`);
ALTER TABLE `umpan_balik` ADD PRIMARY KEY (`id`), ADD KEY `karya_id` (`karya_id`), ADD KEY `guru_id` (`guru_id`);

--
-- AUTO_INCREMENT for dumped tables
--

ALTER TABLE `karya` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `kategori` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
ALTER TABLE `notifikasi` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `pengguna` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `password_resets` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `settings` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
ALTER TABLE `suka` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `umpan_balik` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

ALTER TABLE `karya`
  ADD CONSTRAINT `karya_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `pengguna` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `karya_ibfk_2` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE SET NULL;

ALTER TABLE `notifikasi`
  ADD CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`pengguna_id`) REFERENCES `pengguna` (`id`) ON DELETE CASCADE;

ALTER TABLE `suka`
  ADD CONSTRAINT `suka_ibfk_1` FOREIGN KEY (`karya_id`) REFERENCES `karya` (`id`) ON DELETE CASCADE;

ALTER TABLE `umpan_balik`
  ADD CONSTRAINT `umpan_balik_ibfk_1` FOREIGN KEY (`karya_id`) REFERENCES `karya` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `umpan_balik_ibfk_2` FOREIGN KEY (`guru_id`) REFERENCES `pengguna` (`id`) ON DELETE CASCADE;
COMMIT;