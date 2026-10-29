

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE login_attempts (
    ip VARCHAR(45) PRIMARY KEY,
    attempts INT DEFAULT 0,
    last_attempt DATETIME
);

CREATE TABLE mesin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(50) NOT NULL,
    aktif BOOLEAN DEFAULT TRUE
);

CREATE TABLE produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    famili VARCHAR(50) NOT NULL,
    std_jph DECIMAL(10,2) NOT NULL,
    aktif BOOLEAN DEFAULT TRUE
);

CREATE TABLE pp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_pp VARCHAR(50) UNIQUE NOT NULL,
    produk_id INT NOT NULL,
    plan_total INT NOT NULL,
    status ENUM('aktif', 'selesai') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produk_id) REFERENCES produk(id)
);

CREATE TABLE jenis_cacat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    aktif BOOLEAN DEFAULT TRUE,
    urutan INT DEFAULT 0
);

CREATE TABLE kode_downtime (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(20) NOT NULL,
    arti VARCHAR(100) NOT NULL,
    kategori VARCHAR(50) NOT NULL,
    jenis_waste VARCHAR(50) NOT NULL,
    aktif BOOLEAN DEFAULT TRUE
);

CREATE TABLE shift_jadwal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shift INT NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL
);

CREATE TABLE lkh (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pp_id INT NOT NULL,
    tanggal DATE NOT NULL,
    shift INT NOT NULL,
    mesin_id INT NOT NULL,
    operator VARCHAR(100) NOT NULL,
    start DATETIME NOT NULL,
    finish DATETIME NOT NULL,
    good INT DEFAULT 0,
    reject INT DEFAULT 0,
    setup_reject INT DEFAULT 0,
    total_layar INT DEFAULT 0,
    setting_menit INT DEFAULT 0,
    problem TEXT,
    root_cause TEXT,
    tindakan_sementara TEXT,
    tindakan_perbaikan TEXT,
    pic VARCHAR(100),
    total_proses INT DEFAULT 0,
    waktu_prod_jam DECIMAL(10,4) DEFAULT 0,
    total_dt_menit INT DEFAULT 0,
    jam_jalan DECIMAL(10,4) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pp_id) REFERENCES pp(id),
    FOREIGN KEY (mesin_id) REFERENCES mesin(id),
    INDEX (tanggal),
    INDEX (shift),
    INDEX (mesin_id),
    INDEX (pp_id)
);

CREATE TABLE lkh_downtime (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lkh_id INT NOT NULL,
    kode_id INT NOT NULL,
    menit INT NOT NULL,
    keterangan TEXT,
    FOREIGN KEY (lkh_id) REFERENCES lkh(id) ON DELETE CASCADE,
    FOREIGN KEY (kode_id) REFERENCES kode_downtime(id)
);

CREATE TABLE lkh_cacat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lkh_id INT NOT NULL,
    jenis_cacat_id INT NOT NULL,
    kategori ENUM('reject', 'setup') NOT NULL,
    qty INT NOT NULL,
    FOREIGN KEY (lkh_id) REFERENCES lkh(id) ON DELETE CASCADE,
    FOREIGN KEY (jenis_cacat_id) REFERENCES jenis_cacat(id)
);

-- Seed default users (Demo)
-- Admin: admin / admin123
-- Viewer: viewer / viewer123
-- Operator: operator / operator123
INSERT INTO users (username, password, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('viewer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer'),
('operator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operator');

-- Seed shift_jadwal
INSERT INTO shift_jadwal (shift, jam_mulai, jam_selesai) VALUES
(1, '06:30:00', '14:30:00'),
(2, '14:30:00', '22:30:00'),
(3, '22:30:00', '06:30:00');

-- Seed jenis_cacat awal
INSERT INTO jenis_cacat (nama, urutan) VALUES
('Baret', 1), ('Outmax', 2), ('Outmin', 3), ('Brontok', 4), ('Blobor', 5),
('Stell', 6), ('Noda', 7), ('Misprint', 8), ('Bintik Putih', 9), ('UV Garis', 10),
('Pon', 11), ('Garis Putih', 12), ('Noda Kuning', 13);

-- Seed kode_downtime awal
INSERT INTO kode_downtime (kode, arti, kategori, jenis_waste) VALUES
('BLT', 'Briefing/Lapor Tanggung Jawab', 'Man', 'Non-utilized talent'),
('FDR', 'Feeding/Roll change', 'Mesin', 'Waiting'),
('CAM', 'Camera Setting/Adjustment', 'Mesin', 'Waiting'),
('DLV', 'Delivery/Unloading', 'Material', 'Transportation'),
('MRK', 'Marking/Sortir manual', 'Metode', 'Extra-processing'),
('CLN', 'Cleaning', 'Lingkungan', 'Waiting'),
('MDL', 'Ganti Model/PP', 'Metode', 'Waiting'),
('DOC', 'Dokumentasi/LKH', 'Metode', 'Motion'),
('SMP', 'Sampling/QC', 'Metode', 'Extra-processing'),
('PKG', 'Packaging', 'Material', 'Transportation'),
('WIP', 'Cari WIP/Bahan', 'Material', 'Motion'),
('PLT', 'Pindah Palet', 'Material', 'Transportation'),
('QCH', 'Tunggu QC Hold/Release', 'Metode', 'Waiting'),
('OPR', 'Operator Istirahat/Izin', 'Man', 'Waiting'),
('MTG', 'Meeting', 'Man', 'Non-utilized talent'),
('5S', 'Bersih-bersih/5S', 'Lingkungan', 'Waiting'),
('PWR', 'Listrik Padam', 'Lingkungan', 'Waiting');
