-- Database Initialization for SuratQu
-- Compatible with PHP 8.1+ and MySQL/MariaDB

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

-- 1. TABEL JABATAN (Hirarki Fleksibel)
CREATE TABLE `jabatan` (
  `id_jabatan` int(11) NOT NULL AUTO_INCREMENT,
  `nama_jabatan` varchar(100) NOT NULL,
  `level_hierarki` int(11) NOT NULL COMMENT '1: Camat, 2: Sekcam, 3: Kasi/Kasubbag, 4: Staf',
  `parent_id` int(11) DEFAULT NULL,
  `can_buat_surat` tinyint(1) DEFAULT 0,
  `can_disposisi` tinyint(1) DEFAULT 0,
  `can_verifikasi` tinyint(1) DEFAULT 0,
  `can_tanda_tangan` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_jabatan`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `jabatan_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `jabatan` (`id_jabatan`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. TABEL USERS
CREATE TABLE `users` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `id_jabatan` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `telegram_id` varchar(50) DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_user`),
  KEY `id_jabatan` (`id_jabatan`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`id_jabatan`) REFERENCES `jabatan` (`id_jabatan`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. TABEL KOP SURAT
CREATE TABLE `kop_surat` (
  `id_kop` int(11) NOT NULL AUTO_INCREMENT,
  `nama_instansi` varchar(255) NOT NULL,
  `alamat` text NOT NULL,
  `kontak` varchar(100) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_kop`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. TABEL SURAT MASUK
CREATE TABLE `surat_masuk` (
  `id_sm` int(11) NOT NULL AUTO_INCREMENT,
  `no_agenda` varchar(50) NOT NULL UNIQUE,
  `no_surat` varchar(100) NOT NULL,
  `asal_surat` varchar(200) NOT NULL,
  `tgl_surat` date NOT NULL,
  `tgl_diterima` datetime DEFAULT current_timestamp(),
  `perihal` text NOT NULL,
  `klasifikasi` varchar(50) DEFAULT NULL,
  `id_kop` int(11) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('baru','disposisi','proses','selesai') DEFAULT 'baru',
  PRIMARY KEY (`id_sm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. TABEL DISPOSISI
CREATE TABLE `disposisi` (
  `id_disposisi` int(11) NOT NULL AUTO_INCREMENT,
  `id_sm` int(11) NOT NULL,
  `pengirim_id` int(11) NOT NULL,
  `penerima_id` int(11) NOT NULL,
  `instruksi` text DEFAULT NULL,
  `batas_waktu` date DEFAULT NULL,
  `catatan_tindak_lanjut` text DEFAULT NULL,
  `status_tindak_lanjut` enum('pending','proses','selesai') DEFAULT 'pending',
  `tgl_disposisi` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_disposisi`),
  KEY `id_sm` (`id_sm`),
  KEY `pengirim_id` (`pengirim_id`),
  KEY `penerima_id` (`penerima_id`),
  CONSTRAINT `disposisi_ibfk_1` FOREIGN KEY (`id_sm`) REFERENCES `surat_masuk` (`id_sm`) ON DELETE CASCADE,
  CONSTRAINT `disposisi_ibfk_2` FOREIGN KEY (`pengirim_id`) REFERENCES `users` (`id_user`),
  CONSTRAINT `disposisi_ibfk_3` FOREIGN KEY (`penerima_id`) REFERENCES `users` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. TABEL SURAT KELUAR
CREATE TABLE `surat_keluar` (
  `id_sk` int(11) NOT NULL AUTO_INCREMENT,
  `no_surat` varchar(100) DEFAULT NULL,
  `tgl_surat` date NOT NULL,
  `tujuan` varchar(200) NOT NULL,
  `perihal` text NOT NULL,
  `id_user_pembuat` int(11) NOT NULL,
  `status` enum('draft','verifikasi','disetujui','terkirim') DEFAULT 'draft',
  `file_path` varchar(255) DEFAULT NULL,
  `klasifikasi` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_sk`),
  KEY `id_user_pembuat` (`id_user_pembuat`),
  CONSTRAINT `surat_keluar_ibfk_1` FOREIGN KEY (`id_user_pembuat`) REFERENCES `users` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. TABEL TEMPLATE SURAT
CREATE TABLE `template_surat` (
  `id_template` int(11) NOT NULL AUTO_INCREMENT,
  `nama_template` varchar(100) NOT NULL,
  `konten_html` longtext NOT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_template`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. TABEL LOG AKTIVITAS
CREATE TABLE `log_aktivitas` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) DEFAULT NULL,
  `aksi` varchar(255) NOT NULL,
  `tabel_terkait` varchar(50) DEFAULT NULL,
  `id_data_terkait` int(11) DEFAULT NULL,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_log`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. TABEL LOG NOTIFIKASI
CREATE TABLE `log_notifikasi` (
  `id_notif` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) DEFAULT NULL,
  `pesan` text NOT NULL,
  `channel` enum('telegram','email') DEFAULT 'telegram',
  `status` enum('sent','failed','retry') DEFAULT 'sent',
  `waktu` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_notif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
