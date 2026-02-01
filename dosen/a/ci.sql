-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 10, 2022 at 01:47 PM
-- Server version: 10.4.22-MariaDB
-- PHP Version: 7.4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ci`
--

-- --------------------------------------------------------

--
-- Table structure for table `mp_aset`
--

CREATE TABLE `mp_aset` (
  `id` int(11) NOT NULL,
  `nama_aset` varchar(128) NOT NULL,
  `tanggal_perolehan` date NOT NULL,
  `jumlah_unit` varchar(128) NOT NULL,
  `umur_manfaat` varchar(128) NOT NULL,
  `harga_perolehan` varchar(128) NOT NULL,
  `akumulasi_penyusutan` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `mp_aset`
--

INSERT INTO `mp_aset` (`id`, `nama_aset`, `tanggal_perolehan`, `jumlah_unit`, `umur_manfaat`, `harga_perolehan`, `akumulasi_penyusutan`) VALUES
(3, 'Mobil', '2022-06-17', '4', '12', '100000000', '33333333.333333332'),
(4, 'Motor', '2022-06-17', '5', '36', '10000000', '1388888.888888889'),
(5, 'Bangunan', '2022-06-17', '1', '12', '100000000', '8333333.333333333'),
(6, 'Ruko', '2022-06-19', '1', '60', '100000000', '1666666.6666666667');

-- --------------------------------------------------------

--
-- Table structure for table `mp_generalentry`
--

CREATE TABLE `mp_generalentry` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `naration` varchar(255) CHARACTER SET latin1 NOT NULL,
  `generated_source` varchar(50) CHARACTER SET latin1 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `mp_head`
--

CREATE TABLE `mp_head` (
  `id` int(11) NOT NULL,
  `name` varchar(255) CHARACTER SET latin1 NOT NULL,
  `nature` varchar(50) CHARACTER SET latin1 NOT NULL,
  `type` varchar(50) CHARACTER SET latin1 NOT NULL,
  `expense_type` varchar(50) CHARACTER SET latin1 NOT NULL,
  `revenue_type` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `mp_head`
--

INSERT INTO `mp_head` (`id`, `name`, `nature`, `type`, `expense_type`, `revenue_type`) VALUES
(96, 'Kas Zakat', 'Assets', 'Lancar', '', ''),
(97, 'Kas Infak', 'Assets', 'Lancar', '', ''),
(98, 'Kas Amil', 'Assets', 'Lancar', '', ''),
(99, 'Piutang Qardul Hasan Amil', 'Assets', 'Lancar', '', ''),
(100, 'Piutang Qardul Hasan Non Amil', 'Assets', 'Lancar', '', ''),
(101, 'Piutang Penyaluran Dana Zakat', 'Assets', 'Lancar', '', ''),
(102, 'Piutang Saldo Zakat Operasional Amil', 'Assets', 'Lancar', '', ''),
(103, 'Piutang Saldo Infak Sedekah Operasional Amil', 'Assets', 'Lancar', '', ''),
(104, 'Piutang Lain-lain', 'Assets', 'Lancar', '', ''),
(105, 'Uang Muka Penyaluran Zakat', 'Assets', 'Lancar', '', ''),
(106, 'Uang Muka Penyaluran Infak', 'Assets', 'Lancar', '', ''),
(107, 'Uang Muka Operasional Amil', 'Assets', 'Lancar', '', ''),
(108, 'Sewa Gedung Dibayar Dimuka', 'Assets', 'Lancar', '', ''),
(109, 'Biaya Renovasi Gedung Dimuka', 'Assets', 'Lancar', '', ''),
(110, 'Asuransi Dibayar Dimuka', 'Assets', 'Lancar', '', ''),
(111, 'Biaya Dibayar Dimuka Lainnya', 'Assets', 'Lancar', '', ''),
(112, 'Tanah', 'Assets', 'Tetap', '', ''),
(113, 'Bangunan', 'Assets', 'Tetap', '', ''),
(114, 'Kendaraan', 'Assets', 'Tetap', '', ''),
(115, 'Inventaris', 'Assets', 'Tetap', '', ''),
(116, 'Akumulasi Penyusutan Bangunan', 'Assets', 'Tetap', '', ''),
(117, 'Akumulasi Penyusutan Kendaraan', 'Assets', 'Tetap', '', ''),
(118, 'Akumulasi Penyusutan Inventaris', 'Assets', 'Tetap', '', ''),
(119, 'Aset Zakat', 'Assets', 'Tetap', '', ''),
(120, 'Aset Infak', 'Assets', 'Tetap', '', ''),
(121, 'Akumulasi Penyusutan Aset Zakat', 'Assets', 'Tetap', '', ''),
(122, 'Akumulasi Penyusutan Aset Infak', 'Assets', 'Tetap', '', ''),
(123, 'Hutang Penyaluran Dana Zakat', 'Liability', 'Lancar', '', ''),
(124, 'Hutang Penyaluran Dana Infak Terikat', 'Liability', 'Lancar', '', ''),
(125, 'Hutang Penyaluran Dana Infak Tidak Terikat', 'Liability', 'Lancar', '', ''),
(126, 'Hutang Amil Pihak Ke3', 'Liability', 'Lancar', '', ''),
(127, 'Hutang Amil Dana Zakat', 'Liability', 'Lancar', '', ''),
(128, 'Hutang Amil Dana Infak', 'Liability', 'Lancar', '', ''),
(129, 'Hutang Dana Non Syariah', 'Liability', 'Lancar', '', ''),
(130, 'Hutang PPH21', 'Liability', 'Lancar', '', ''),
(131, 'Hutang Jangka Pendek Lainnya', 'Liability', 'Lancar', '', ''),
(132, 'Hutang Jangka Panjang', 'Liability', 'Tetap', '', ''),
(133, 'Hutang Jangka Panjang Lainnya', 'Liability', 'Tetap', '', ''),
(134, 'Penerimaan Zakat Mal Perusahaan', 'Revenue', 'Lancar', '', 'Penerimaan Zakat'),
(135, 'Penerimaan Zakat Mal Perorangan', 'Revenue', 'Lancar', '', 'Penerimaan Zakat'),
(136, 'Penerimaan Zakat Perdagangan', 'Revenue', 'Lancar', '', 'Penerimaan Zakat'),
(137, 'Penerimaan Zakat Fitrah', 'Revenue', 'Lancar', '', 'Penerimaan Zakat'),
(138, 'Penerimaan Bagi Hasil Rek Zakat', 'Revenue', 'Lancar', '', 'Penerimaan Zakat'),
(139, 'Penerimaan IST Qurban', 'Revenue', 'Lancar', '', 'Penerimaan Infak'),
(140, 'Penerimaan IST Dana Beasiswa', 'Revenue', 'Lancar', '', 'Penerimaan Infak'),
(141, 'Penerimaan IST Tabungan Qurban', 'Revenue', 'Lancar', '', 'Penerimaan Infak'),
(142, 'Penerimaan IST Smart Camp', 'Revenue', 'Lancar', '', 'Penerimaan Infak'),
(143, 'Penerimaan IST Fidyah', 'Revenue', 'Lancar', '', 'Penerimaan Infak'),
(144, 'Penerimaan IST Nadzar', 'Revenue', 'Lancar', '', 'Penerimaan Infak'),
(145, 'Penerimaan IST Kafarat', 'Revenue', 'Lancar', '', 'Penerimaan Infak'),
(146, 'Penerimaan IST Dana Kemanusiaan', 'Revenue', 'Lancar', '', 'Penerimaan Infak'),
(147, 'Penerimaan IST Pendidikan M Pro Institute', 'Revenue', 'Lancar', '', 'Penerimaan Infak'),
(148, 'Penerimaan Bagi Hasil Rek Infak Terikat', 'Revenue', 'Lancar', '', 'Penerimaan Infak'),
(149, 'Penerimaan Infak Umum', 'Revenue', 'Lancar', '', 'Penerimaan Infak'),
(150, 'Penerimaan Infak Tidak Terikat - Barang', 'Revenue', 'Lancar', '', 'Penerimaan Infak'),
(151, 'Penerimaan Infak Tidak Terikat - PKBL/CSR', 'Revenue', 'Lancar', '', 'Penerimaan Infak'),
(152, 'Penerimaan Bagi Hasil Rek Infak Tidak Terikat', 'Revenue', 'Lancar', '', 'Penerimaan Infak'),
(153, 'Penerimaan Infak Tidak Terikat Lainnya', 'Revenue', 'Lancar', '', 'Penerimaan Infak'),
(154, 'Bagian Amil dari Dana Zakat', 'Revenue', 'Lancar', '', 'Penerimaan Amil'),
(155, 'Bagian Amil dari Dana Infak', 'Revenue', 'Lancar', '', 'Penerimaan Amil'),
(156, 'Bagian Amil dari Bagi Hasil infak', 'Revenue', 'Lancar', '', 'Penerimaan Amil'),
(157, 'Bagian Amil dari Bagi Hasil Zakat', 'Revenue', 'Lancar', '', 'Penerimaan Amil'),
(158, 'Bagian Amil dari Dana Zakat Fitrah', 'Revenue', 'Lancar', '', 'Penerimaan Amil'),
(159, 'Penerimaan Amil dari UPZ', 'Revenue', 'Lancar', '', 'Penerimaan Amil'),
(160, 'Penerimaan Bagi Hasil Rek Amil', 'Revenue', 'Lancar', '', 'Penerimaan Amil'),
(161, 'Penyaluran Zakat Untuk Fakir - Ekonomi', 'Expense', 'Lancar', 'Pengeluaran Zakat', ''),
(162, 'Penyaluran Zakat Untuk Fakir - Pendidikan', 'Expense', 'Lancar', 'Pengeluaran Zakat', ''),
(163, 'Penyaluran Zakat Untuk Fakir - Kemanusiaan', 'Expense', 'Lancar', 'Pengeluaran Zakat', ''),
(164, 'Penyaluran Zakat Untuk Fakir - Kesehatan', 'Expense', 'Lancar', 'Pengeluaran Zakat', ''),
(165, 'Penyaluran Zakat Untuk Fakir - Sosial', 'Expense', 'Lancar', 'Pengeluaran Zakat', ''),
(166, 'Penyaluran Zakat Untuk Miskin - Ekonomi', 'Expense', 'Lancar', 'Pengeluaran Zakat', ''),
(167, 'Penyaluran Zakat Untuk Miskin - Pendidikan', 'Expense', 'Lancar', 'Pengeluaran Zakat', ''),
(168, 'Penyaluran Zakat Untuk Miskin - Kemanusiaan', 'Expense', 'Lancar', 'Pengeluaran Zakat', ''),
(169, 'Penyaluran Zakat Untuk Miskin - Kesehatan', 'Expense', 'Lancar', 'Pengeluaran Zakat', ''),
(170, 'Penyaluran Zakat Untuk Miskin - Sosial', 'Expense', 'Lancar', 'Pengeluaran Zakat', ''),
(171, 'Penyaluran IST Qurban', 'Expense', 'Lancar', 'Pengeluaran Infak', ''),
(172, 'Penyaluran IST Dana Beasiswa', 'Expense', 'Lancar', 'Pengeluaran Infak', ''),
(173, 'Penyaluran IST Tabungan Qurban', 'Expense', 'Lancar', 'Pengeluaran Infak', ''),
(174, 'Penyaluran IST Smart Camp', 'Expense', 'Lancar', 'Pengeluaran Infak', ''),
(175, 'Penyaluran IST Dana Kemanusiaan', 'Expense', 'Lancar', 'Pengeluaran Infak', ''),
(176, 'Penyaluran ISTT Untuk Fakir - Pendidikan', 'Expense', 'Lancar', 'Pengeluaran Infak', ''),
(177, 'Penyaluran ISTT Untuk Fakir - Kesehatan', 'Expense', 'Lancar', 'Pengeluaran Infak', ''),
(178, 'Penyaluran ISTT Untuk Fakir - Ekonomi', 'Expense', 'Lancar', 'Pengeluaran Infak', ''),
(179, 'Penyaluran ISTT Untuk Fakir - Kemanusiaan', 'Expense', 'Lancar', 'Pengeluaran Infak', ''),
(180, 'Penyaluran ISTT Untuk Fakir - Sosial', 'Expense', 'Lancar', 'Pengeluaran Infak', ''),
(181, 'Penyaluran ISTT Untuk Miskin - Pendidikan', 'Expense', 'Lancar', 'Pengeluaran Infak', ''),
(182, 'Penyaluran ISTT Untuk Miskin - Kesehatan', 'Expense', 'Lancar', 'Pengeluaran Infak', ''),
(183, 'Penyaluran ISTT Untuk Miskin - Ekonomi', 'Expense', 'Lancar', 'Pengeluaran Infak', ''),
(184, 'Penyaluran ISTT Untuk Miskin - Kemanusiaan', 'Expense', 'Lancar', 'Pengeluaran Infak', ''),
(185, 'Penyaluran ISTT Untuk Miskin - Sosial', 'Expense', 'Lancar', 'Pengeluaran Infak', ''),
(186, 'Beban Telpon/Pulsa/Komunikasi', 'Expense', 'Lancar', 'Pengeluaran Amil', ''),
(187, 'Beban Air, Listrik, Internet', 'Expense', 'Lancar', 'Pengeluaran Amil', ''),
(188, 'Beban Pemeliharaan Kendaraan', 'Expense', 'Lancar', 'Pengeluaran Amil', ''),
(189, 'Beban Pemeliharaan Gedung', 'Expense', 'Lancar', 'Pengeluaran Amil', ''),
(190, 'Beban Pemeliharaan Peralatan dan Inventaris', 'Expense', 'Lancar', 'Pengeluaran Amil', ''),
(191, 'Beban Sewa Gedung', 'Expense', 'Lancar', 'Pengeluaran Amil', ''),
(192, 'Beban Adm Bank', 'Expense', 'Lancar', 'Pengeluaran Amil', ''),
(193, 'Beban Pajak', 'Expense', 'Lancar', 'Pengeluaran Amil', ''),
(194, 'Beban Penyusutan Bangunan', 'Expense', 'Lancar', 'Pengeluaran Amil', ''),
(195, 'Beban Penyusutan Kendaraan', 'Expense', 'Lancar', 'Pengeluaran Amil', ''),
(196, 'Beban Penyusutan Inventaris', 'Expense', 'Lancar', 'Pengeluaran Amil', '');

-- --------------------------------------------------------

--
-- Table structure for table `mp_hutang`
--

CREATE TABLE `mp_hutang` (
  `id` int(11) NOT NULL,
  `nama_hutang` varchar(128) NOT NULL,
  `tanggal_hutang` date NOT NULL,
  `penambahan` varchar(128) NOT NULL,
  `pengurangan` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `mp_hutang`
--

INSERT INTO `mp_hutang` (`id`, `nama_hutang`, `tanggal_hutang`, `penambahan`, `pengurangan`) VALUES
(1, 'Yayasan A', '2022-08-09', '1000000', '0');

-- --------------------------------------------------------

--
-- Table structure for table `mp_piutang`
--

CREATE TABLE `mp_piutang` (
  `id` int(11) NOT NULL,
  `nama_piutang` varchar(128) NOT NULL,
  `tanggal_piutang` date NOT NULL,
  `penambahan` varchar(128) NOT NULL,
  `pengurangan` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `mp_piutang`
--

INSERT INTO `mp_piutang` (`id`, `nama_piutang`, `tanggal_piutang`, `penambahan`, `pengurangan`) VALUES
(4, 'Yayasan C', '2022-08-09', '1000000', '0'),
(5, 'Yayasan B', '2022-08-11', '0', '1000000');

-- --------------------------------------------------------

--
-- Table structure for table `mp_sub_entry`
--

CREATE TABLE `mp_sub_entry` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `accounthead` int(11) NOT NULL,
  `amount` int(128) NOT NULL,
  `type` int(1) NOT NULL,
  `journal_type` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `email` varchar(128) NOT NULL,
  `image` varchar(128) NOT NULL,
  `password` varchar(256) NOT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` int(1) NOT NULL,
  `date_created` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `name`, `email`, `image`, `password`, `role_id`, `is_active`, `date_created`) VALUES
(3, 'Admin', 'admin@gmail.com', 'AB1.png', '$2y$10$S8ampsPXwlmsB3mo.Z9rHuG3gAg2ncZmDt.bX4MDD.MDWV1kYfOVO', 1, 1, 1642982363),
(4, 'Keuangan', 'user@gmail.com', 'AB.png', '$2y$10$RCw/N3ra0GGPnybfSbVBkeqHQAnwTsYbR1wHV9wVLMz1OVNQaUnBi', 2, 1, 1642983030),
(6, 'Keuangan 2', 'user2@gmail.com', 'AB.png', '$2y$10$RCw/N3ra0GGPnybfSbVBkeqHQAnwTsYbR1wHV9wVLMz1OVNQaUnBi', 4, 1, 1642983030);

-- --------------------------------------------------------

--
-- Table structure for table `user_access_menu`
--

CREATE TABLE `user_access_menu` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user_access_menu`
--

INSERT INTO `user_access_menu` (`id`, `role_id`, `menu_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(6, 1, 3),
(13, 2, 7),
(14, 2, 8),
(15, 2, 9),
(18, 4, 8),
(19, 4, 7);

-- --------------------------------------------------------

--
-- Table structure for table `user_company`
--

CREATE TABLE `user_company` (
  `id` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `loc` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user_company`
--

INSERT INTO `user_company` (`id`, `name`, `loc`) VALUES
(1, 'PT ABC', 'Surabaya');

-- --------------------------------------------------------

--
-- Table structure for table `user_menu`
--

CREATE TABLE `user_menu` (
  `id` int(11) NOT NULL,
  `menu` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user_menu`
--

INSERT INTO `user_menu` (`id`, `menu`) VALUES
(1, 'Admin'),
(2, 'User'),
(3, 'Menu'),
(7, 'Data'),
(8, 'Akuntansi'),
(9, 'Laporan');

-- --------------------------------------------------------

--
-- Table structure for table `user_role`
--

CREATE TABLE `user_role` (
  `id` int(11) NOT NULL,
  `role` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user_role`
--

INSERT INTO `user_role` (`id`, `role`) VALUES
(1, 'Administrator'),
(2, 'Member'),
(4, 'Member 2');

-- --------------------------------------------------------

--
-- Table structure for table `user_sub_menu`
--

CREATE TABLE `user_sub_menu` (
  `id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `title` varchar(128) NOT NULL,
  `url` varchar(128) NOT NULL,
  `icon` varchar(128) NOT NULL,
  `is_active` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user_sub_menu`
--

INSERT INTO `user_sub_menu` (`id`, `menu_id`, `title`, `url`, `icon`, `is_active`) VALUES
(1, 1, 'Dashboard', 'admin', 'fas fa-fw fa-tachometer-alt', 1),
(3, 2, 'Edit Profil', 'user/edit', 'fas fa-fw fa-user-edit', 1),
(4, 3, 'Menu Manajemen', 'menu', 'fas fa-fw fa-folder', 1),
(5, 3, 'Submenu Manajemen', 'menu/submenu', 'fas fa-fw fa-folder-open', 1),
(7, 1, 'Izin Akses', 'admin/role', 'fas fa-fw fa-user-tie', 1),
(11, 7, 'Dashboard', 'dashboard', 'fas fa-fw fa-tachometer-alt', 1),
(15, 8, 'Daftar Akun', 'accounts', 'fas fa-fw fa-list-ol', 1),
(16, 8, 'Jurnal Umum', 'statements', 'fas fa-fw fa-swatchbook', 1),
(17, 8, 'Buku Besar', 'statements/leadgerAccounst', 'fas fa-fw fa-book', 1),
(18, 8, 'Neraca Saldo Awal', 'statements/trail_balance', 'fas fa-fw fa-balance-scale', 1),
(19, 9, 'Laporan Posisi Keuangan', 'statements/balancesheet', 'fas fa-fw fa-chart-pie', 1),
(21, 8, 'Neraca Saldo Akhir', 'statements/trail_balance_final', 'fas fa-fw fa-balance-scale', 1),
(22, 7, 'Daftar Hutang', 'data/hutang', 'fas fa-fw fa-book', 1),
(23, 7, 'Daftar Piutang', 'data/piutang', 'fas fa-fw fa-book', 1),
(24, 7, 'Daftar Aset Tetap', 'data', 'fas fa-fw fa-book', 1),
(26, 9, 'LPD Zakat', 'statements/zakat_statement', 'fas fa-fw fa-book', 1),
(27, 9, 'LPD Infak', 'statements/infak_statement', 'fas fa-fw fa-book', 1),
(28, 9, 'LPD Amil', 'statements/amil_statement', 'fas fa-fw fa-book', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mp_aset`
--
ALTER TABLE `mp_aset`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mp_generalentry`
--
ALTER TABLE `mp_generalentry`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mp_head`
--
ALTER TABLE `mp_head`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mp_hutang`
--
ALTER TABLE `mp_hutang`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mp_piutang`
--
ALTER TABLE `mp_piutang`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mp_sub_entry`
--
ALTER TABLE `mp_sub_entry`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sid` (`parent_id`),
  ADD KEY `accounthead` (`accounthead`),
  ADD KEY `amount` (`amount`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `user_access_menu`
--
ALTER TABLE `user_access_menu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Indexes for table `user_company`
--
ALTER TABLE `user_company`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_menu`
--
ALTER TABLE `user_menu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_role`
--
ALTER TABLE `user_role`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_sub_menu`
--
ALTER TABLE `user_sub_menu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mp_aset`
--
ALTER TABLE `mp_aset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `mp_generalentry`
--
ALTER TABLE `mp_generalentry`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=363;

--
-- AUTO_INCREMENT for table `mp_head`
--
ALTER TABLE `mp_head`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=197;

--
-- AUTO_INCREMENT for table `mp_hutang`
--
ALTER TABLE `mp_hutang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mp_piutang`
--
ALTER TABLE `mp_piutang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `mp_sub_entry`
--
ALTER TABLE `mp_sub_entry`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=290;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_access_menu`
--
ALTER TABLE `user_access_menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `user_company`
--
ALTER TABLE `user_company`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_menu`
--
ALTER TABLE `user_menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_role`
--
ALTER TABLE `user_role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_sub_menu`
--
ALTER TABLE `user_sub_menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `mp_sub_entry`
--
ALTER TABLE `mp_sub_entry`
  ADD CONSTRAINT `mp_sub_entry_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `mp_generalentry` (`id`),
  ADD CONSTRAINT `mp_sub_entry_ibfk_2` FOREIGN KEY (`accounthead`) REFERENCES `mp_head` (`id`);

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `user_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `user_role` (`id`);

--
-- Constraints for table `user_access_menu`
--
ALTER TABLE `user_access_menu`
  ADD CONSTRAINT `user_access_menu_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `user_role` (`id`),
  ADD CONSTRAINT `user_access_menu_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `user_menu` (`id`);

--
-- Constraints for table `user_sub_menu`
--
ALTER TABLE `user_sub_menu`
  ADD CONSTRAINT `user_sub_menu_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `user_menu` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
