@extends('layouts.app', ['title' => 'Galeri ' . $event->title . ' — Admin CMS'])

@section('content')
<div class="min-h-screen bg-[#FAF6F0] py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        <!-- Top Nav -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-wedding-gold/20 mb-8 gap-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.dashboard') }}" class="p-2 rounded-xl bg-white border border-wedding-gold/20 hover:bg-wedding-linen text-wedding-dark transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                </a>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider bg-wedding-gold/15 text-wedding-goldHover">
                            Galeri Event Khusus
                        </span>
                        <span class="text-xs text-wedding-subtle font-medium">{{ $event->event_type }}</span>
                    </div>
                    <h1 class="font-serif text-2xl font-bold text-wedding-dark">{{ $event->title }}</h1>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if($photos->total() > 0)
                    <a href="{{ route('admin.events.download-zip', $event) }}"
                       class="px-3.5 py-2 bg-wedding-gold hover:bg-wedding-goldHover text-white text-xs font-semibold rounded-xl transition-colors flex items-center space-x-1.5 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                        </svg>
                        <span>Unduh Semua Foto (ZIP)</span>
                    </a>

                    <form action="{{ route('admin.events.gallery.clear', $event) }}" method="POST"
                          onsubmit="return confirm('⚠️ HAPUS SEMUA FOTO EVENT INI?\n\nApakah Anda yakin ingin menghapus seluruh {{ $photos->total() }} foto pada event \'{{ addslashes($event->title) }}\'?\n\nSemua file foto fisik di server akan dibersihkan permanen!');">
                        @csrf
                        <button type="submit"
                                class="px-3.5 py-2 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 text-xs font-semibold rounded-xl transition-colors flex items-center space-x-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                            </svg>
                            <span>Hapus Semua Foto</span>
                        </button>
                    </form>
                @endif

                <a href="{{ route('event.gallery', $event->slug) }}" target="_blank"
                   class="px-3.5 py-2 bg-wedding-linen hover:bg-wedding-gold/20 text-wedding-dark text-xs font-semibold rounded-xl transition-colors flex items-center space-x-1.5 border border-wedding-gold/20">
                    <svg class="w-4 h-4 text-wedding-gold" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                    </svg>
                    <span>Buka Tampilan Tamu</span>
                </a>

                <a href="{{ route('event.card', $event->slug) }}" target="_blank"
                   class="px-3.5 py-2 bg-wedding-dark hover:bg-black text-white text-xs font-semibold rounded-xl transition-colors flex items-center space-x-1.5 shadow">
                    <span>Kartu Barcode</span>
                </a>
            </div>
        </div>

        <!-- Flash Alerts -->
        @if(session('success'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-start space-x-3">
                <span class="text-lg leading-none">✅</span>
                <div class="font-medium">{{ session('success') }}</div>
            </div>
        @endif
        @if(session('info'))
            <div class="mb-6 p-4 rounded-2xl bg-sky-50 border border-sky-200 text-sky-800 text-sm flex items-start space-x-3">
                <span class="text-lg leading-none">ℹ️</span>
                <div>{{ session('info') }}</div>
            </div>
        @endif
        @if(session('warning'))
            <div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 text-sm flex items-start space-x-3">
                <span class="text-lg leading-none">⚠️</span>
                <div>{{ session('warning') }}</div>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-start space-x-3">
                <span class="text-lg leading-none">❌</span>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        <!-- Google Drive Backup & Auto-Sync Card -->
        <div class="bg-white rounded-3xl border border-wedding-gold/20 shadow-sm p-6 sm:p-7 mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-wedding-gold/10">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-[#E8F0FE] border border-[#D2E3FC] flex items-center justify-center shrink-0 shadow-xs p-2">
                        <svg class="w-6 h-6" viewBox="0 0 87.3 78" xmlns="http://www.w3.org/2000/svg">
                            <path d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8h-27.5c0 1.55.4 3.1 1.2 4.5z" fill="#0066da"/>
                            <path d="m43.65 25-13.75-23.8c-1.35.8-2.5 1.9-3.3 3.3l-25.4 44a9.06 9.06 0 0 0 -1.2 4.5h27.5z" fill="#00ac47"/>
                            <path d="m73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5h-27.502l5.852 11.5z" fill="#ea4335"/>
                            <path d="m43.65 25 13.75-23.8c-1.35-.8-2.9-1.2-4.5-1.2h-18.5c-1.6 0-3.15.45-4.5 1.2z" fill="#00832d"/>
                            <path d="m59.8 53h-32.3l-13.75 23.8c1.35.8 2.9 1.2 4.5 1.2h50.8c1.6 0 3.15-.45 4.5-1.2z" fill="#2684fc"/>
                            <path d="m73.4 26.5-12.7-22c-.8-1.4-1.95-2.5-3.3-3.3l-13.75 23.8 16.15 28h27.45c0-1.55-.4-3.1-1.2-4.5z" fill="#ffba00"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="font-serif text-lg font-bold text-wedding-dark">Google Drive Backup & Auto-Sync</h3>
                            @if(!empty($event->google_drive_folder_id))
                                @if($event->google_drive_sync_status === 'completed')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center space-x-1">
                                        <span>●</span><span>Tersinkronkan ({{ $event->google_drive_synced_count }} Foto)</span>
                                    </span>
                                @elseif($event->google_drive_sync_status === 'syncing')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-800 border border-blue-200 animate-pulse flex items-center space-x-1">
                                        <span>🔄</span><span>Sedang Menyalin...</span>
                                    </span>
                                @elseif($event->google_drive_sync_status === 'credentials_needed')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800 border border-amber-200 flex items-center space-x-1">
                                        <span>⚠️</span><span>Link Terdaftar (Perlu Kredensial)</span>
                                    </span>
                                @elseif($event->google_drive_sync_status === 'failed')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 text-rose-800 border border-rose-200 flex items-center space-x-1">
                                        <span>❌</span><span>Gagal Salin</span>
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                                        Terhubung
                                    </span>
                                @endif
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                                    Belum Terdaftar
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-wedding-subtle">
                            Daftarkan link Google Drive publik untuk mencadangkan seluruh foto tamu secara otomatis ke folder cloud Anda.
                        </p>
                    </div>
                </div>

                @if(!empty($event->google_drive_folder_id))
                    <div class="flex items-center space-x-2">
                        <a href="{{ $event->google_drive_url }}" target="_blank"
                           class="px-3 py-1.5 rounded-xl bg-white border border-wedding-gold/30 hover:bg-wedding-linen text-wedding-dark text-xs font-semibold flex items-center space-x-1 transition-colors shadow-xs">
                            <span>Buka Folder Drive</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                            </svg>
                        </a>
                    </div>
                @endif
            </div>

            <!-- Content Area -->
            <div class="pt-5" x-data="{ editing: {{ empty($event->google_drive_folder_id) ? 'true' : 'false' }} }">
                @if(!empty($event->google_drive_folder_id))
                    @php
                        $syncedPhotosCount = $event->photos()->whereNotNull('google_drive_file_id')->count();
                        $pendingPhotosCount = $event->photos()->whereNull('google_drive_file_id')->count();
                    @endphp
                    <div x-show="!editing" class="space-y-4">
                        <div class="p-4 rounded-2xl bg-wedding-ivory/60 border border-wedding-gold/20 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="min-w-0">
                                <span class="text-[10px] font-semibold uppercase tracking-wider text-wedding-subtle block">Folder URL Terdaftar</span>
                                <a href="{{ $event->google_drive_url }}" target="_blank" class="text-xs font-mono font-medium text-wedding-dark hover:underline truncate block max-w-xl">
                                    {{ $event->google_drive_url }}
                                </a>
                                <div class="flex flex-wrap items-center gap-2 mt-1.5 text-[11px] text-wedding-subtle">
                                    <span>Folder ID: <strong class="font-mono text-wedding-dark">{{ $event->google_drive_folder_id }}</strong></span>
                                    <span>•</span>
                                    <span class="inline-flex items-center space-x-1 font-medium text-wedding-dark">
                                        <span>Status:</span>
                                        <strong class="text-emerald-700">{{ $syncedPhotosCount }} Foto Tersinkron</strong>
                                        @if($pendingPhotosCount > 0)
                                            <span class="text-amber-700 font-bold">({{ $pendingPhotosCount }} foto baru belum disalin)</span>
                                        @endif
                                    </span>
                                    @if($event->google_drive_synced_at)
                                        <span>• Terakhir disinkron: {{ $event->google_drive_synced_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center space-x-2 shrink-0">
                                <button type="button" @click="editing = true"
                                        class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-white border border-wedding-gold/20 hover:bg-wedding-linen text-wedding-dark transition-colors">
                                    Ganti Link
                                </button>
                                <form action="{{ route('admin.events.google-drive.disconnect', $event) }}" method="POST"
                                      onsubmit="return confirm('Putus tautan Google Drive dari event ini?')">
                                    @csrf
                                    <button type="submit"
                                            class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200 transition-colors">
                                        Putus Tautan
                                    </button>
                                </form>
                            </div>
                        </div>

                        @if($event->google_drive_last_error)
                            <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs">
                                <strong>Catatan Sinkronisasi:</strong> {{ $event->google_drive_last_error }}
                            </div>
                        @endif

                        <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                            <form action="{{ route('admin.events.google-drive.sync', $event) }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="px-4 py-2 bg-wedding-dark hover:bg-black text-white text-xs font-semibold rounded-xl shadow-xs flex items-center space-x-2 transition-colors">
                                    <svg class="w-4 h-4 text-wedding-gold" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                                    </svg>
                                    @if($pendingPhotosCount > 0)
                                        <span>Salin {{ $pendingPhotosCount }} Foto Baru ke Google Drive</span>
                                    @else
                                        <span>Sinkronkan Foto ke Google Drive</span>
                                    @endif
                                </button>
                            </form>

                            @if($photos->total() > 0)
                                <a href="{{ route('admin.events.download-zip', $event) }}" class="text-xs text-wedding-subtle hover:text-wedding-dark font-medium underline flex items-center space-x-1">
                                    <span>Atau unduh arsip ZIP langsung ({{ $photos->total() }} Foto)</span>
                                    <span>↓</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Registration / Edit Form -->
                <div x-show="editing" class="space-y-3">
                    <form action="{{ route('admin.events.google-drive.update', $event) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-wedding-dark mb-1.5">
                                Link Folder Google Drive (Public / Shared) *
                            </label>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <input type="url" name="google_drive_url" required
                                       value="{{ old('google_drive_url', $event->google_drive_url) }}"
                                       placeholder="https://drive.google.com/drive/folders/1abcXYZ..."
                                       class="flex-1 px-4 py-2.5 rounded-xl border border-wedding-gold/30 focus:border-wedding-gold text-xs sm:text-sm bg-wedding-ivory/30">
                                <div class="flex items-center space-x-2">
                                    <button type="submit"
                                            class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-wedding-dark hover:bg-black text-white text-xs font-semibold shadow-xs transition-colors flex items-center justify-center space-x-1.5">
                                        <svg class="w-4 h-4 text-wedding-gold" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z"/>
                                        </svg>
                                        <span>Daftarkan & Salin Semua Foto</span>
                                    </button>
                                    @if(!empty($event->google_drive_folder_id))
                                        <button type="button" @click="editing = false"
                                                class="px-3.5 py-2.5 rounded-xl bg-white border border-wedding-gold/20 hover:bg-wedding-linen text-wedding-dark text-xs font-semibold">
                                            Batal
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <span class="text-[11px] text-wedding-subtle mt-1.5 block">
                                Contoh format: <code class="bg-black/5 px-1.5 py-0.5 rounded font-mono text-[10px]">https://drive.google.com/drive/folders/1AbC2dEf...</code>
                            </span>
                        </div>
                    </form>
                </div>

                <!-- Guidance Accordion / Notes -->
                <div class="mt-5 pt-4 border-t border-wedding-gold/10" x-data="{ guideTab: 'appscript', copied: false }">
                    <details class="group text-xs text-wedding-subtle cursor-pointer" {{ empty($driveConfigured) ? 'open' : '' }}>
                        <summary class="font-semibold text-wedding-dark flex items-center justify-between list-none select-none">
                            <span class="flex items-center space-x-1.5">
                                <svg class="w-4 h-4 text-wedding-gold" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>
                                </svg>
                                <span>Petunjuk Mengaktifkan Sinkronisasi Otomatis Google Drive</span>
                            </span>
                            <span class="text-xs transition-transform group-open:rotate-180">▾</span>
                        </summary>

                        <div class="mt-4 pt-3 border-t border-wedding-gold/10 space-y-4">
                            <!-- Tab Switcher -->
                            <div class="flex items-center space-x-2 border-b border-wedding-gold/20 pb-2">
                                <button type="button" @click="guideTab = 'appscript'"
                                        :class="guideTab === 'appscript' ? 'bg-wedding-dark text-white shadow-xs' : 'bg-wedding-ivory text-wedding-subtle hover:text-wedding-dark'"
                                        class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all">
                                    ⚡ Opsi 1: Google Apps Script (2 Menit, Termudah)
                                </button>
                                <button type="button" @click="guideTab = 'gcp'"
                                        :class="guideTab === 'gcp' ? 'bg-wedding-dark text-white shadow-xs' : 'bg-wedding-ivory text-wedding-subtle hover:text-wedding-dark'"
                                        class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all">
                                    🏢 Opsi 2: GCP Service Account
                                </button>
                            </div>

                            <!-- Opsi 1: Google Apps Script -->
                            <div x-show="guideTab === 'appscript'" class="space-y-3 text-[11px] leading-relaxed text-wedding-subtle">
                                <p class="text-wedding-dark font-medium">
                                    Gunakan akun Google biasa (Gmail / Google Workspace). Tidak memerlukan Google Cloud Console, kartu kredit, ataupun konfigurasi rumit.
                                </p>
                                <ol class="list-decimal list-inside space-y-2 pl-1">
                                    <li>Buka <a href="https://script.new" target="_blank" class="text-blue-600 font-semibold underline hover:text-blue-800">script.new ↗</a> (Google Apps Script).</li>
                                    <li>Hapus isi default yang ada di editor, lalu paste kode berikut:</li>
                                </ol>

                                <div class="relative bg-neutral-900 text-neutral-100 rounded-xl p-3 font-mono text-[10px] overflow-x-auto">
                                    <button type="button"
                                            @click="navigator.clipboard.writeText(document.getElementById('apps-script-code').innerText); copied = true; setTimeout(() => copied = false, 2000)"
                                            class="absolute top-2.5 right-2.5 px-2 py-1 rounded bg-white/20 hover:bg-white/30 text-white text-[10px] font-sans font-semibold transition-colors">
                                        <span x-text="copied ? '✓ Tersalin!' : 'Salin Kode'"></span>
                                    </button>
                                    <pre id="apps-script-code">function doPost(e) {
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
}</pre>
                                </div>

                                <ol start="3" class="list-decimal list-inside space-y-2 pl-1">
                                    <li>Klik tombol <strong>Deploy</strong> (biru di kanan atas) &gt; pilih <strong>New deployment</strong>.</li>
                                    <li>Klik ikon gerigi ⚙️ di samping <em>Select type</em> &gt; pilih <strong>Web app</strong>.</li>
                                    <li>Atur pengaturan berikut:
                                        <ul class="list-disc list-inside pl-4 mt-1 space-y-0.5 text-wedding-dark">
                                            <li><strong>Execute as:</strong> <code>Me (email Anda)</code></li>
                                            <li><strong>Who has access:</strong> <code>Anyone</code> (Siapa saja)</li>
                                        </ul>
                                    </li>
                                    <li>Klik <strong>Deploy</strong> &gt; izinkan akses (Authorize Access) akun Google Anda.</li>
                                    <li>Salin <strong>Web app URL</strong> yang berakhiran <code>/exec</code>.</li>
                                    <li>Buka file <code>.env</code> di server Anda dan tambahkan baris:
                                        <div class="mt-1 bg-black/5 p-2 rounded-lg font-mono text-wedding-dark text-[10px]">
                                            GOOGLE_DRIVE_WEBHOOK_URL="https://script.google.com/macros/s/XXXXX.../exec"
                                        </div>
                                    </li>
                                </ol>
                                <p class="text-emerald-700 font-medium">
                                    ✓ Setelah baris di atas disimpan di <code>.env</code>, klik tombol <strong>"Salin / Sinkronkan Ulang Semua Foto ke Drive"</strong> dan semua foto akan langsung tersalin ke folder Google Drive Anda!
                                </p>
                            </div>

                            <!-- Opsi 2: GCP Service Account -->
                            <div x-show="guideTab === 'gcp'" class="space-y-3 text-[11px] leading-relaxed text-wedding-subtle">
                                <p class="text-wedding-dark font-medium">
                                    Gunakan Google Cloud IAM Service Account untuk sinkronisasi resmi skala korporat melalui Google Drive v3 API.
                                </p>
                                @if(!empty($serviceAccountEmail))
                                    <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800">
                                        <strong>Google Service Account Aktif Terdeteksi:</strong><br>
                                        Bagikan (Share) folder Google Drive Anda ke email berikut dengan akses <strong>Editor</strong>:<br>
                                        <code class="font-mono font-bold select-all bg-white px-2 py-0.5 rounded text-emerald-950 mt-1.5 inline-block">{{ $serviceAccountEmail }}</code>
                                    </div>
                                @else
                                    <ol class="list-decimal list-inside space-y-2 pl-1">
                                        <li>Buka <a href="https://console.cloud.google.com" target="_blank" class="text-blue-600 underline">Google Cloud Console ↗</a> dan aktifkan <strong>Google Drive API</strong>.</li>
                                        <li>Buat <strong>Service Account</strong> di menu IAM &amp; Admin &gt; Service Accounts.</li>
                                        <li>Buka tab <strong>Keys</strong> &gt; <strong>Add Key</strong> &gt; <strong>Create new key</strong> (JSON) lalu unduh filenya.</li>
                                        <li>Letakkan file JSON tersebut di server pada path:<br>
                                            <code class="bg-black/5 px-1.5 py-0.5 rounded font-mono text-[10px]">storage/app/google-service-account.json</code>
                                        </li>
                                        <li>Bagikan folder Google Drive Anda ke email Service Account tersebut sebagai <strong>Editor</strong>.</li>
                                    </ol>
                                @endif
                            </div>

                            <div class="p-3 rounded-xl bg-wedding-linen border border-wedding-gold/20 flex items-center justify-between">
                                <span>Butuh backup instan tanpa setup API sekarang?</span>
                                @if($photos->total() > 0)
                                    <a href="{{ route('admin.events.download-zip', $event) }}" class="px-3 py-1.5 rounded-lg bg-wedding-gold text-white font-semibold text-[10px] hover:bg-wedding-goldHover transition-colors">
                                        Unduh File ZIP Sekarang
                                    </a>
                                @endif
                            </div>
                        </div>
                    </details>
                </div>
            </div>
        </div>

        <!-- Photos Section -->
        <div class="bg-white rounded-3xl border border-wedding-gold/20 shadow-sm p-6 sm:p-8">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-wedding-gold/10">
                <div>
                    <h2 class="font-serif text-xl font-bold text-wedding-dark">Semua Foto Event Ini</h2>
                    <p class="text-xs text-wedding-subtle">Hanya menampilkan foto-foto yang diambil untuk event: <strong>{{ $event->title }}</strong></p>
                </div>
                <span class="text-xs font-mono font-bold text-wedding-dark bg-wedding-linen px-3 py-1.5 rounded-lg border border-wedding-gold/20">
                    Total: {{ $photos->total() }} Foto
                </span>
            </div>

            @if($photos->count() > 0)
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    @foreach($photos as $photo)
                        <div class="group bg-wedding-ivory rounded-2xl overflow-hidden border border-wedding-gold/20 shadow-sm hover:shadow-md transition-all flex flex-col justify-between p-2">
                            <div class="relative w-full aspect-[4/3] rounded-xl overflow-hidden bg-black/5">
                                <img src="{{ $photo->thumbnail_url }}"
                                     alt="Shot #{{ $photo->shot_number }}"
                                     loading="lazy"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">

                                @if($event->show_date_stamp)
                                    <div class="absolute bottom-2 right-2 film-date-stamp text-[9px] pointer-events-none select-none">
                                        '{{ $photo->taken_at->format('y m d') }}
                                    </div>
                                @endif

                                @if($photo->camera_facing === 'user')
                                    <span class="absolute top-2 left-2 bg-black/60 text-white text-[9px] px-1.5 py-0.5 rounded font-mono">
                                        Selfie
                                    </span>
                                @endif
                            </div>

                            <div class="pt-2 px-1 flex items-center justify-between text-[11px] text-wedding-subtle">
                                <div>
                                    <span class="font-mono font-bold text-wedding-dark">#{{ $photo->shot_number }}</span>
                                    <span>• {{ $photo->taken_at->format('H:i') }}</span>
                                </div>

                                <form action="{{ route('admin.photos.destroy', $photo) }}" method="POST"
                                      onsubmit="return confirm('⚠️ Hapus foto #{{ $photo->shot_number }} ini permanen dari server?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center space-x-1 px-2 py-1 rounded-lg bg-red-50 hover:bg-red-600 text-red-600 hover:text-white text-[10px] font-semibold transition-all shadow-xs"
                                            title="Hapus Foto">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                        </svg>
                                        <span>Hapus</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 pt-4 border-t border-wedding-gold/10">
                    {{ $photos->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-xs text-wedding-subtle mb-3">Belum ada foto yang masuk untuk event ini.</p>
                    <a href="{{ route('event.camera', $event->slug) }}" target="_blank"
                       class="inline-flex items-center space-x-1.5 px-4 py-2 bg-wedding-gold text-white text-xs font-semibold rounded-xl">
                        <span>Coba Ambil Foto Sekarang</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

