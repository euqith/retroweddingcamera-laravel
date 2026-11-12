# Panduan Konfigurasi Sinkronisasi Otomatis Google Drive

Fitur ini memungkinkan seluruh foto candid tamu pada event Retro Camera disalin secara otomatis ke folder Google Drive publik yang telah Anda daftarkan.

Google Drive tidak mengizinkan pengunggahan file secara anonim ke folder publik tanpa otorisasi. Tersedia 2 metode konfigurasi:

---

## ⚡ Opsi 1: Google Apps Script Webhook (Sangat Mudah — Rekomendasi 2 Menit)
*Cocok untuk:* Akun Google biasa (Gmail / Google Workspace). **100% Gratis, tanpa perlu Google Cloud Console, tanpa kartu kredit, dan tanpa izin rumit.**

### Langkah-langkah:
1. Buka browser dan buka: **[https://script.new](https://script.new)**
2. Beri nama proyek di kiri atas, misal: `RetroCamera Google Drive Uploader`
3. Hapus seluruh kode default di editor, lalu paste kode JavaScript di bawah ini:

```javascript
function doPost(e) {
  try {
    var data = JSON.parse(e.postData.contents);
    var folder = DriveApp.getFolderById(data.folderId);
    
    // Cegah duplikasi: jika file dengan nama sama sudah ada di folder, jangan buat ulang
    var existingFiles = folder.getFilesByName(data.filename);
    if (existingFiles.hasNext()) {
      var existing = existingFiles.next();
      return ContentService.createTextOutput(JSON.stringify({
        status: 'success',
        id: existing.getId(),
        name: existing.getName(),
        already_exists: true
      })).setMimeType(ContentService.MimeType.JSON);
    }

    var decoded = Utilities.base64Decode(data.base64);
    var blob = Utilities.newBlob(decoded, data.mimeType || 'image/jpeg', data.filename);
    var file = folder.createFile(blob);
    return ContentService.createTextOutput(JSON.stringify({
      status: 'success',
      id: file.getId(),
      name: file.getName()
    })).setMimeType(ContentService.MimeType.JSON);
  } catch (err) {
    return ContentService.createTextOutput(JSON.stringify({
      status: 'error',
      message: err.toString()
    })).setMimeType(ContentService.MimeType.JSON);
  }
}
```

4. Klik tombol **Deploy** (warna biru di kanan atas) &gt; pilih **New deployment**.
5. Klik ikon gerigi ⚙️ di samping *Select type* &gt; pilih **Web app**.
6. Isi konfigurasi:
   - **Description**: `Retro Camera Webhook`
   - **Execute as**: `Me (email google Anda)`
   - **Who has access**: `Anyone` *(Penting: pilih Anyone agar server dapat mengirim foto ke script)*
7. Klik **Deploy**.
8. Klik **Authorize access** &gt; pilih akun Google Anda &gt; klik *Advanced* &gt; klik *Go to RetroCamera Google Drive Uploader (unsafe)* &gt; klik **Allow**.
9. Salin URL **Web app** yang muncul (berakhiran `/exec`), contoh:
   ```text
   https://script.google.com/macros/s/AKfycbx.../exec
   ```
10. Buka file `.env` di proyek Laravel Anda dan tambahkan baris berikut:
    ```env
    GOOGLE_DRIVE_WEBHOOK_URL="https://script.google.com/macros/s/AKfycbx.../exec"
    ```
11. Buka halaman Galeri Event di Admin CMS (`/admin/events/{event}/gallery`) lalu klik **"Salin / Sinkronkan Ulang Semua Foto ke Drive"**. Semua foto akan langsung tersalin ke folder Google Drive Anda!

---

## 🏢 Opsi 2: Google Cloud IAM Service Account (Official Google Drive v3 API)
*Cocok untuk:* Akun Enterprise / Developer Google Cloud Platform.

### Langkah-langkah:
1. Masuk ke **[Google Cloud Console](https://console.cloud.google.com/)**.
2. Buat atau pilih Project.
3. Buka menu **APIs & Services** &gt; **Library**, cari **Google Drive API** dan klik **Enable**.
4. Buka menu **IAM & Admin** &gt; **Service Accounts** &gt; klik **Create Service Account**.
   - Berikan nama, misal `retrocamera-drive`.
   - Klik *Create and Continue* lalu *Done*.
5. Klik pada Service Account yang baru dibuat &gt; buka tab **Keys** &gt; klik **Add Key** &gt; **Create new key** &gt; pilih format **JSON**.
6. File JSON akan terunduh ke komputer Anda.
7. Letakkan file tersebut di server pada salah satu lokasi berikut:
   ```text
   storage/app/google-service-account.json
   ```
   *(Atau isi string JSON ke dalam `.env` pada variabel `GOOGLE_SERVICE_ACCOUNT_JSON`)*.
8. Buka Google Drive Anda, klik kanan pada folder acara yang ingin digunakan &gt; klik **Share / Bagikan** &gt; masukkan email Service Account Anda (misal `retrocamera-drive@project.iam.gserviceaccount.com`) dengan hak akses **Editor**.
9. Klik tombol **"Salin / Sinkronkan Ulang Semua Foto ke Drive"** di Admin CMS.

---

## 📦 Opsi Instan (Tanpa Setup API)
Jika Anda belum sempat melakukan konfigurasi di atas, Anda tetap dapat mengunduh seluruh foto event kapan saja:
1. Buka Galeri Event di Admin CMS.
2. Klik tombol **"Unduh Semua Foto (ZIP)"** di toolbar atas.
3. Seluruh foto beresolusi penuh akan terunduh dalam satu file `.zip`.
4. Buka folder Google Drive Anda di browser dan drag-and-drop seluruh foto dari ZIP tersebut.

