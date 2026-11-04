# Retro Wedding Camera — Digital Disposable Camera for Wedding & Church Events

Aplikasi web berbasis **Laravel** & **MySQL** yang mengemulasi pengalaman menggunakan **kamera analog disposable (sekali pakai)** secara digital melalui browser smartphone. Dirancang khusus dengan estetika hangat dan sakral untuk **acara pernikahan (Wedding)** maupun **acara gereja (Church Events)**.

Tamu cukup memindai **QR Code** di lokasi acara untuk langsung membuka kamera disposable digital, mengambil foto maupun selfie hingga **36 jepretan (1 roll film)** per perangkat dengan hasil warna khas film analog, dan seluruh foto otomatis terintegrasi ke **Gallery Event** terkait.

---

## Fitur Utama

1. **Web-Based Disposable Camera (Tanpa Install Aplikasi)**
   - Akses langsung melalui browser smartphone (iOS Safari & Android Chrome) menggunakan WebRTC API (`getUserMedia`).
   - Antarmuka kamera skeuomorphic bergaya kamera analog sekali pakai (Fujifilm QuickSnap / Kodak FunSaver) dengan balutan desain khusus bertema *Wedding* & *Church*.
   - Dilengkapi animasi & efek suara jepretan shutter dan putaran roda film (*mechanical winding sound*), lampu kilat (*flash*), serta jendela bidik (*viewfinder*).
2. **Batas 36 Foto & Mode Selfie per Perangkat**
   - Setiap perangkat (*device*) mendapatkan jatah **1 roll film (maksimal 36 eksposur)** per event.
   - Mendukung kamera belakang (*rear*) dan kamera depan (*selfie* dengan koreksi *mirror*).
   - Pelacakan kuota berbasis kombinasi `device_token` (`localStorage` + Cookie) dan pencatatan sesi di database MySQL (`camera_sessions`).
3. **Pemrosesan Tone Warna Kamera Disposable (Retro Film Filter)**
   - Emulasi warna khas film analog ISO 400/800 sesuai sampel referensi di folder [`attachment/`](./attachment):
     - *Warm golden highlights* & *faded matte shadows* (lifted blacks dengan sentuhan cyan/green halus).
     - Karakter lensa plastik *disposable*: *radial vignette* natural, *subtle edge softness*, dan *35mm organic film grain*.
     - *Vintage quartz date stamp* berwarna oranye menyala di sudut kanan bawah (`'YY MM DD`).
4. **Manajemen Multi-Event (Wedding & Acara Gereja)**
   - Admin/Organizer dapat membuat dan mengelola banyak event (`events`), mengatur tema warna/bingkai kamera, tanggal acara, serta status publikasi galeri.
   - Setiap foto (`photos`) dan sesi perangkat (`camera_sessions`) terintegrasi penuh dengan masing-masing event.
5. **Generator QR Code Otomatis (Kamera & Galeri)**
   - Menghasilkan 2 jenis QR Code untuk setiap event yang dapat diunduh (PNG/SVG) serta tampilan *Printable Table Tent Card*:
     - **QR Code Kamera (`/e/{slug}/camera`)**: Dipasang di meja tamu / buku acara gereja agar tamu langsung memotret.
     - **QR Code Galeri (`/e/{slug}/gallery`)**: Untuk melihat dan mengunduh seluruh hasil foto dari event tersebut.
6. **Galeri Event Interaktif**
   - Seluruh foto yang diambil tamu otomatis masuk ke galeri event terkait.
   - Dilengkapi tampilan *Analog Print / Polaroid Grid*, *Lightbox* pratinjau penuh, filter (*Semua Foto*, *Kamera Saya*, *Selfie*), dan tombol unduh foto.
7. **Fitur Reset Roll Kamera (Khusus Admin / Testing Development)**
   - Admin dapat melakukan **Reset Roll (kembali ke 0/36)** untuk perangkat tertentu maupun seluruh perangkat pada suatu event melalui menu **Device Sessions & Testing Reset**.
   - Tombol cepat `[Dev] Reset Roll` langsung tersedia di layar kamera untuk mempermudah pengujian saat masa development.

---

## Panduan Instalasi & Menjalankan Aplikasi

### 1. Kebutuhan Sistem
- **PHP** >= 8.2 (dengan ekstensi `pdo_mysql`, `gd` / `imagick`)
- **Composer** >= 2.x
- **MySQL** >= 8.0
- **Node.js** >= 18.x & NPM

### 2. Langkah Setup

1. **Clone Repository & Masuk ke Direktori**:
   ```bash
   git clone https://github.com/euqith/retroweddingcamera-laravel.git
   cd retroweddingcamera-laravel
   ```

2. **Pasang Dependensi**:
   ```bash
   composer install
   npm install
   npm run build
   ```

3. **Konfigurasi Lingkungan (`.env`)**:
   Salin `.env.example` ke `.env` dan sesuaikan koneksi database MySQL:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=retroweddingcamera
   DB_USERNAME=root
   DB_PASSWORD=
   FILESYSTEM_DISK=public
   ```

4. **Jalankan Migrasi & Database Seeder**:
   ```bash
   php artisan migrate
   php artisan storage:link
   php artisan db:seed
   ```
   *Seeder akan membuat akun admin, 2 contoh event (Wedding "The Wedding of Daniel & Clara" dan Church Event "Youth Christmas Celebration"), serta memuat sampel foto analog dari folder `attachment/` ke dalam galeri.*

5. **Jalankan Local Server**:
   ```bash
   php artisan serve
   ```
   Akses aplikasi di browser:
   - **Halaman Sambutan Event Wedding**: `http://localhost:8000/e/daniel-clara`
   - **Kamera Disposable Web**: `http://localhost:8000/e/daniel-clara/camera`
   - **Galeri Foto Bersama**: `http://localhost:8000/e/daniel-clara/gallery`
   - **Admin Dashboard & Reset Roll**: `http://localhost:8000/admin`
   - **QR Code & Kartu Cetak**: `http://localhost:8000/admin/events/1/qrcodes`
   - **Manajemen Device & Reset Roll**: `http://localhost:8000/admin/events/1/sessions`

---

## Pengujian Otomatis (Automated Testing)

Jalankan test suite menggunakan PHPUnit:
```bash
php artisan test
```
Semua skenario pengujian utama (akses kamera, jatah 36 foto per device, pencegahan pengambilan foto saat roll habis, fitur reset roll, galeri, dan QR code) telah teruji dan lulus 100%.

---

## Referensi Desain & Dokumentasi

- 📋 [PLANNING.md](./PLANNING.md): Dokumen perencanaan arsitektur dan skema database lengkap.
- 🎨 [Contoh Desain UI Mockup](./docs/ui/):
  - [01-camera-view.jpg](./docs/ui/01-camera-view.jpg): Tampilan Kamera Disposable, Mode Selfie, dan Roll Habis (36/36).
  - [02-gallery-and-qrcode.jpg](./docs/ui/02-gallery-and-qrcode.jpg): Galeri Foto Event & Dual QR Code.
  - [03-admin-reset-dashboard.jpg](./docs/ui/03-admin-reset-dashboard.jpg): Dashboard Admin & Manajer Reset Roll Device.
