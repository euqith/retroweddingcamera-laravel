# Retro Wedding Camera — Digital Disposable Camera for Wedding & Church Events

Aplikasi web berbasis **Laravel** & **MySQL** yang mengemulasi pengalaman menggunakan **kamera analog disposable (sekali pakai)** secara digital melalui browser smartphone. Dirancang khusus dengan estetika hangat dan sakral untuk **acara pernikahan (Wedding)** maupun **acara gereja (Church Events)**.

Tamu cukup memindai **QR Code** di lokasi acara untuk langsung membuka kamera disposable digital, mengambil foto maupun selfie hingga **36 jepretan (1 roll film)** per perangkat dengan hasil warna khas film analog, dan seluruh foto otomatis terintegrasi ke **Gallery Event** terkait.

---

## Fitur Utama

1. **Web-Based Disposable Camera (Tanpa Install Aplikasi)**
   - Akses langsung melalui browser smartphone (iOS Safari & Android Chrome) menggunakan WebRTC API (`getUserMedia`).
   - Antarmuka kamera skeuomorphic bergaya kamera analog sekali pakai (Fujifilm QuickSnap / Kodak FunSaver) dengan balutan desain khusus bertema *Wedding* & *Church*.
   - Pengalaman autentik tanpa fitur *retake* sebelum memotret, lengkap dengan animasi & efek suara kokang film (*film wind*) serta lampu kilat (*flash*).
2. **Batas 36 Foto & Mode Selfie per Perangkat**
   - Setiap perangkat (*device*) mendapatkan jatah **1 roll film (maksimal 36 eksposur)** per event.
   - Mendukung kamera belakang (*rear*) dan kamera depan (*selfie* dengan koreksi *mirror*).
   - Pelacakan kuota berbasis kombinasi `device_token` (`localStorage` + Cookie) dan pencatatan sesi di database MySQL (`camera_sessions`).
3. **Pemrosesan Tone Warna Kamera Disposable (Retro Film Filter)**
   - Emulasi warna khas film analog ISO 400/800 (seperti contoh referensi di folder [`attachment/`](./attachment)):
     - *Warm golden highlights* & *faded matte shadows* (lifted blacks).
     - Karakter lensa plastik *disposable*: *vignette* natural, sedikit *chromatic aberration* di tepi, *soft highlight bloom/halation*, dan *35mm film grain*.
     - Opsional *quartz date stamp* klasik di sudut foto.
4. **Manajemen Multi-Event (Wedding & Acara Gereja)**
   - Admin/Organizer dapat membuat dan mengelola banyak event (`events`), mengatur tema warna/bingkai kamera, tanggal acara, serta status publikasi galeri.
   - Setiap foto (`photos`) dan sesi perangkat (`camera_sessions`) terintegrasi penuh dengan masing-masing event.
5. **Generator QR Code Otomatis (Kamera & Galeri)**
   - Menghasilkan 2 jenis QR Code untuk setiap event yang dapat diunduh (PNG/SVG/Kartu Cetak):
     - **QR Code Kamera (`/e/{slug}/camera`)**: Dipasang di meja tamu / buku acara gereja agar tamu langsung memotret.
     - **QR Code Galeri (`/e/{slug}/gallery`)**: Untuk melihat dan mengunduh seluruh hasil foto dari event tersebut.
6. **Galeri Event Interaktif**
   - Seluruh foto yang diambil tamu otomatis masuk ke galeri event terkait.
   - Dilengkapi tampilan *Masonry / Polaroid Grid*, *Lightbox*, fitur unduh foto, dan mode *Live Slideshow* untuk ditampilkan di layar proyektor acara.
7. **Fitur Reset Roll Kamera (Khusus Admin / Testing Development)**
   - Admin dapat melakukan **Reset Roll (kembali ke 0/36)** untuk perangkat tertentu maupun seluruh perangkat pada suatu event melalui Dashboard Admin atau tombol cepat saat mode *development* (`APP_ENV=local` / Admin).

---

## Dokumen Perencanaan & Referensi

- 📋 **Dokumen Perencanaan Lengkap**: Lihat [`PLANNING.md`](./PLANNING.md) untuk arsitektur sistem, skema database MySQL, alur kerja pemrosesan gambar, daftar endpoint, dan roadmap pengerjaan.
- 🖼️ **Referensi Tone Gambar**: Simpan contoh foto referensi tone *disposable camera* di dalam folder [`attachment/`](./attachment).
- 🎬 **Referensi UX**: [Instagram Reel Reference](https://www.instagram.com/reel/DdvkrcRpy9G/?psln=MTF4NTFnenEzMW96ag%3D%3D)

---

## Tech Stack

- **Backend**: Laravel 11.x / 12.x (PHP 8.2+)
- **Database**: MySQL 8.0+
- **Frontend**: Blade, Tailwind CSS, Alpine.js, HTML5 Canvas & WebRTC MediaDevices API
- **Image Processing**: HTML5 Canvas (LUT/Shader Filter) + Intervention Image v3 (Server-side storage & thumbnail)
- **QR Code**: `simplesoftwareio/simple-qrcode`
