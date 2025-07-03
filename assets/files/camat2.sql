-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 02, 2025 at 07:09 PM
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

-- --------------------------------------------------------

--
-- Table structure for table `tb_permohonan`
--

CREATE TABLE `tb_permohonan` (
  `id_permohonan` bigint(11) NOT NULL,
  `id_masyarakat` bigint(11) NOT NULL,
  `id_verifikator` bigint(11) NOT NULL,
  `judul_permohonan` varchar(255) NOT NULL,
  `deskripsi permohonan` text DEFAULT NULL,
  `kategori_permohonan` enum('Surat Permohonan Rekomendasi','Surat Permohonan Izin','Surat Permohonan Bantuan','Surat Kartu Keluarga') NOT NULL,
  `status_permohonan` enum('diajukan','diverifikasi','selesai','') NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(4, 'Tri Setiawan', 'poseidonseal03@gmail.com', '$2y$10$raM07WAI9XP5281UGTntOe4DncL1gtYWwPM2.kicwu5/C1lUoDIDm', 'Administrator', '1751469994_68654faaa8af2.jpg', '2025-07-02 22:26:34', '2025-07-02 22:31:07');

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
  MODIFY `id_file_permohonan` bigint(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_permohonan`
--
ALTER TABLE `tb_permohonan`
  MODIFY `id_permohonan` bigint(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_user`
--
ALTER TABLE `tb_user`
  MODIFY `id_user` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
