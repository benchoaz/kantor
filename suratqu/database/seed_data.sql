-- Seed Data for SuratQu
USE suratqu_db;

-- 1. Insert Jabatan (Hierarchy)
INSERT INTO jabatan (nama_jabatan, level_hierarki, parent_id, can_buat_surat, can_disposisi, can_verifikasi, can_tanda_tangan) VALUES
('Camat', 1, NULL, 1, 1, 1, 1),
('Sekretaris Camat', 2, 1, 1, 1, 1, 1),
('Kasi Pemerintahan', 3, 2, 1, 1, 1, 0),
('Kasi PMD', 3, 2, 1, 1, 1, 0),
('Staf Administrasi', 4, 3, 1, 0, 0, 0);

-- 2. Insert Users (Password: admin123)
-- Hash: $2y$10$8.N.M1F./kUvKq5S5.U5.O0U5.O0U5.O0U5.O0U5.O0U5.O0U5. (Actually bcrypt for 'admin123')
INSERT INTO users (id_jabatan, username, password, nama_lengkap, role, telegram_id) VALUES
(1, 'camat', '$2y$10$TVh5M0KgUZ5saq6Y.zs0xerxFi82UdJAgJQlm9uC/Nr714ByEfysG', 'Drs. H. Ahmad Fauzi, M.Si', 'user', NULL),
(2, 'sekcam', '$2y$10$TVh5M0KgUZ5saq6Y.zs0xerxFi82UdJAgJQlm9uC/Nr714ByEfysG', 'Budi Santoso, S.Sos', 'user', NULL),
(5, 'admin', '$2y$10$TVh5M0KgUZ5saq6Y.zs0xerxFi82UdJAgJQlm9uC/Nr714ByEfysG', 'Administrator Sistem', 'admin', NULL);

-- 3. Insert Kop Surat Default
INSERT INTO kop_surat (nama_instansi, alamat, kontak, logo_path, is_active) VALUES
('PEMERINTAH KABUPATEN PROBOLINGGO', 'Jl. Raya Panglima Sudirman No. 1, Kraksaan', 'Telp: (0335) 123456 | Email: info@probolinggokab.go.id', 'assets/img/logo_kab.png', 1);
