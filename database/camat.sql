-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 03, 2025 at 05:26 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `camat`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_file_permohonan`
--

CREATE TABLE `tb_file_permohonan` (
  `id_file_permohonan` bigint(11) NOT NULL,
  `id_permohonan` bigint(20) NOT NULL,
  `file_permohonan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_file_permohonan`
--

INSERT INTO `tb_file_permohonan` (`id_file_permohonan`, `id_permohonan`, `file_permohonan`) VALUES
(1, 2, 'camat.sql'),
(2, 2, 'camat2.sql'),
(3, 4, '1751522408_0_68661c6820ea4.png'),
(4, 4, '1751522408_1_68661c6822ade.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `tb_permohonan`
--

CREATE TABLE `tb_permohonan` (
  `id_permohonan` bigint(11) NOT NULL,
  `id_masyarakat` bigint(11) DEFAULT NULL,
  `id_verifikator` bigint(11) DEFAULT NULL,
  `judul_permohonan` varchar(255) NOT NULL,
  `deskripsi permohonan` text DEFAULT NULL,
  `kategori_permohonan` enum('Surat Permohonan Rekomendasi','Surat Permohonan Izin','Surat Permohonan Bantuan','Surat Kartu Keluarga') NOT NULL,
  `status_permohonan` enum('diajukan','diverifikasi') NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_permohonan`
--

INSERT INTO `tb_permohonan` (`id_permohonan`, `id_masyarakat`, `id_verifikator`, `judul_permohonan`, `deskripsi permohonan`, `kategori_permohonan`, `status_permohonan`, `created_at`, `updated_at`) VALUES
(2, 9, 8, 'tes permohonan 1', 'tes 1', 'Surat Permohonan Rekomendasi', 'diverifikasi', '2025-07-03 00:32:36', '2025-07-03 12:34:32'),
(4, 9, NULL, 'Judul Permohonan Hari INI', 'Deskripsi Permohonan Hari INI', 'Surat Permohonan Izin', 'diajukan', '2025-07-03 13:00:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_user`
--

CREATE TABLE `tb_user` (
  `id_user` bigint(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Administrator','Masyarakat','Pegawai','') NOT NULL,
  `photo_profile` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_user`
--

INSERT INTO `tb_user` (`id_user`, `nama`, `email`, `password`, `role`, `photo_profile`, `created_at`, `updated_at`) VALUES
(4, 'Tri Setiawan', 'poseidonseal03@gmail.com', '$2y$10$.JzbJMMX90gKWEon8MZM9u8DzV7A4ug47i8PetA1Qp4jUesNwkGPC', 'Administrator', '1751516550_68660586b5b9e.png', '2025-07-02 22:26:34', '2025-07-03 11:24:50'),
(7, 'Adamas', 'poseidonseal888@gmail.com', '$2y$10$b7GbLVXvQheizOlsw2TO.uC55NxphDJVqupgGqh7OJ8jk8JvGrrOW', 'Masyarakat', NULL, '2025-07-03 00:30:53', NULL),
(8, 'pegwai1', 'poseidonseal003@gmail.com', '$2y$10$/oQ.rMxOtUQBhyJk7kJu..nN/er62lpfq/6aiF73JQVSBd9jExaqy', 'Pegawai', '1751520468_686614d476e98.png', '2025-07-03 12:27:48', NULL),
(9, 'Masyarakat 1', 'cdefilter@gmail.com', '$2y$10$DqrLgQYbx4okvAthDp72B.Q0Ai/OPjrmR0MPLF0VWYTBqEy8g2s0q', 'Masyarakat', NULL, '2025-07-03 12:55:44', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_file_permohonan`
--
ALTER TABLE `tb_file_permohonan`
  ADD PRIMARY KEY (`id_file_permohonan`),
  ADD KEY `id_permohonan` (`id_permohonan`);

--
-- Indexes for table `tb_permohonan`
--
ALTER TABLE `tb_permohonan`
  ADD PRIMARY KEY (`id_permohonan`),
  ADD KEY `id_masyarakat` (`id_masyarakat`),
  ADD KEY `id_verifikator` (`id_verifikator`);

--
-- Indexes for table `tb_user`
--
ALTER TABLE `tb_user`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_file_permohonan`
--
ALTER TABLE `tb_file_permohonan`
  MODIFY `id_file_permohonan` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tb_permohonan`
--
ALTER TABLE `tb_permohonan`
  MODIFY `id_permohonan` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tb_user`
--
ALTER TABLE `tb_user`
  MODIFY `id_user` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_file_permohonan`
--
ALTER TABLE `tb_file_permohonan`
  ADD CONSTRAINT `tb_file_permohonan_ibfk_1` FOREIGN KEY (`id_permohonan`) REFERENCES `tb_permohonan` (`id_permohonan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_permohonan`
--
ALTER TABLE `tb_permohonan`
  ADD CONSTRAINT `tb_permohonan_ibfk_1` FOREIGN KEY (`id_masyarakat`) REFERENCES `tb_user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_permohonan_ibfk_2` FOREIGN KEY (`id_verifikator`) REFERENCES `tb_user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
