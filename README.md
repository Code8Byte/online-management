# Online Management - Sistem LKH & OEE Divisi Sortir

Aplikasi web modern berbasis Single Page Application (SPA) untuk pencatatan Laporan Kerja Harian (LKH), pemantauan Overall Equipment Effectiveness (OEE), manajemen Job Order (PP), dan antrian pallet produksi.

---

## 🚀 Fitur Utama

- **Dashboard Interaktif**:
  - Kartu KPI Utama: Availability, Performance, Quality OEE, dan Quality WIP.
  - Grafik tren periode OEE & Performance dengan garis target standar.
  - Grafik Pareto Downtime (menit) & Pareto Cacat (pcs) dengan kurva kumulatif 0-100%.
  - Drill-down downtime per kode hingga rincian keterangan kejadian.
  - Analisis & Rekomendasi dinamis berbasis data terfilter.
- **Isi LKH & Input Job Order**:
  - Form multi-step (Shift & Mesin, Jam Kerja & Lembur, 5S & Persiapan, Job Order Multi-PP, Catatan Masalah, Ringkasan OEE).
  - Fitur **Request PP** langsung dari form jika Job Order belum ada di master data (berstatus `req` / Pending ACC).
- **Monitoring Progress PP**:
  - Pemantauan pencapaian sortir 3 tingkatan (S1 Normal, S2 Sortir Ulang, S3 Sortir Ulang ke-3).
- **Master Data & Konfigurasi**:
  - Pengelolaan Mesin, Produk, Job Order (PP), Kode Downtime, dan Jenis Cacat.
  - Fitur Approval (ACC) Job Order berstatus request untuk menetapkan kuota dan standar speed (JPH).
- **Pelaporan & Ekspor**:
  - Ekspor Data LKH ke Microsoft Excel (.xlsx) dan CSV.
  - Ekspor Laporan CC Departemen Lipat & Divisi Sortir dalam format landscape A4.
  - Cetak laporan formal (Print to PDF/A4) dengan media print stylesheet.
- **Keamanan & Kontrol Akses**:
  - Role-based access control (Admin, Operator, Viewer).
  - Proteksi CSRF, Session Cookie HttpOnly/SameSite, rate limiting proteksi brute-force.

---

## 🛠️ Tech Stack

- **Frontend**: Vue 3 (Composition API), Vite, Vue Router, Chart.js, Vanilla CSS.
- **Backend API**: PHP 8+ (PDO MySQL, REST JSON Response, Session Auth).
- **Database**: MySQL 8.0+ / MariaDB.
- **Reporting Engine**: OpenPyXL, SimpleXlsxWriter, ReportLab.

---

## 📦 Panduan Instalasi & Menjalankan Lokal

### 1. Kloning Repositori
```bash
git clone https://github.com/Code8Byte/online-management.git
cd online-management
```

### 2. Setup Basis Data
1. Buat database baru di MySQL (misal: `db_sortir`).
2. Impor file skema:
   ```bash
   mysql -u root -p db_sortir < database.sql
   ```
3. Akun demo bawaan:
   - **Admin**: `admin` / `admin123`
   - **Viewer**: `viewer` / `viewer123`
   - **Operator**: `operator` / `operator123`

### 3. Konfigurasi Backend API
1. Salin file template konfigurasi:
   ```bash
   cp api/config.example.php api/config.php
   ```
2. Buka `api/config.php` dan sesuaikan pengaturan koneksi:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', 'password_anda');
   define('DB_NAME', 'db_sortir');
   ```

### 4. Menjalankan Frontend
```bash
# Instal dependensi
npm install

# Jalankan server pengembangan
npm run dev

# Build untuk produksi
npm run build
```

---

## 🔒 Lisensi & Privasi

Dibuat untuk sistem manajemen operasional internal. Hak cipta &copy; 2026.
