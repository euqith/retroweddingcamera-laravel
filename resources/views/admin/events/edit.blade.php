@extends('layouts.app', ['title' => 'Edit Event — ' . $event->title])

@section('content')
<div class="min-h-screen bg-[#FAF6F0] py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.dashboard') }}" class="p-2 rounded-xl bg-white border border-wedding-gold/20 hover:bg-wedding-linen text-wedding-dark transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                </a>
                <div>
                    <span class="text-xs uppercase tracking-widest text-wedding-gold font-bold block">Edit Event</span>
                    <h1 class="font-serif text-2xl font-bold text-wedding-dark">{{ $event->title }}</h1>
                </div>
            </div>

            <!-- Delete Event Form -->
            <form action="{{ route('admin.events.destroy', $event) }}" method="POST"
                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus event ini beserta seluruh fotonya?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded-xl transition-colors text-xs font-semibold flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                    </svg>
                    <span>Hapus Event</span>
                </button>
            </form>
        </div>

        @if($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.events.update', $event) }}" method="POST" class="bg-white rounded-3xl border border-wedding-gold/20 p-6 sm:p-8 shadow-sm space-y-6">
            @csrf
            @method('PUT')

            <!-- Title -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-wedding-dark mb-2">Nama Acara *</label>
                <input type="text" name="title" value="{{ old('title', $event->title) }}" required
                       class="w-full px-4 py-3 rounded-xl border border-wedding-gold/30 focus:border-wedding-gold text-sm bg-wedding-ivory/30">
            </div>

            <!-- Slug -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-wedding-dark mb-2">Custom URL Slug *</label>
                <div class="flex items-center">
                    <span class="px-3 py-3 rounded-l-xl bg-wedding-linen border border-r-0 border-wedding-gold/30 text-xs text-wedding-subtle font-mono">/e/</span>
                    <input type="text" name="slug" value="{{ old('slug', $event->slug) }}" required
                           class="w-full px-4 py-3 rounded-r-xl border border-wedding-gold/30 focus:border-wedding-gold text-sm bg-wedding-ivory/30">
                </div>
            </div>

            <!-- Event Type & Date -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-wedding-dark mb-2">Jenis Acara *</label>
                    <select name="event_type" required class="w-full px-4 py-3 rounded-xl border border-wedding-gold/30 text-sm bg-wedding-ivory/30">
                        <option value="wedding" {{ old('event_type', $event->event_type) === 'wedding' ? 'selected' : '' }}>Pernikahan (Wedding)</option>
                        <option value="church" {{ old('event_type', $event->event_type) === 'church' ? 'selected' : '' }}>Acara Gereja (Church Event)</option>
                        <option value="general" {{ old('event_type', $event->event_type) === 'general' ? 'selected' : '' }}>Acara Lainnya</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-wedding-dark mb-2">Tanggal Acara</label>
                    <input type="date" name="event_date" value="{{ old('event_date', $event->event_date?->format('Y-m-d')) }}"
                           class="w-full px-4 py-3 rounded-xl border border-wedding-gold/30 text-sm bg-wedding-ivory/30">
                </div>
            </div>

            <!-- Event Duration: Starts At & Ends At (Camera Active Window) -->
            <div class="p-4 rounded-2xl bg-wedding-linen/60 border border-wedding-gold/30">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center space-x-2">
                        <span class="text-base">⏱️</span>
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-wedding-dark">Jadwal Durasi Acara & Kunci Kamera</h4>
                            <p class="text-[11px] text-wedding-subtle">Kamera disposable hanya dapat digunakan memotret selama rentang waktu ini (Zona Waktu: <strong>WIB / Asia/Jakarta</strong>).</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-mono px-2 py-1 rounded bg-wedding-gold/15 text-wedding-goldHover font-bold border border-wedding-gold/30">
                        Jam Sekarang: {{ now('Asia/Jakarta')->format('H:i') }} WIB
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-wedding-dark mb-1">Waktu Mulai Acara (WIB)</label>
                        <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $event->starts_at?->timezone('Asia/Jakarta')->format('Y-m-d\TH:i')) }}"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-wedding-gold/30 focus:border-wedding-gold text-sm bg-white">
                        <span class="text-[10px] text-wedding-subtle block mt-1">Kosongkan jika ingin kamera langsung aktif kapan saja.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-wedding-dark mb-1">Waktu Selesai Acara (WIB)</label>
                        <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $event->ends_at?->timezone('Asia/Jakarta')->format('Y-m-d\TH:i')) }}"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-wedding-gold/30 focus:border-wedding-gold text-sm bg-white">
                        <span class="text-[10px] text-wedding-subtle block mt-1">Setelah waktu ini, kamera otomatis ditutup & diarahkan ke galeri.</span>
                    </div>
                </div>
            </div>

            <!-- Location -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-wedding-dark mb-2">Lokasi Acara</label>
                <input type="text" name="location" value="{{ old('location', $event->location) }}"
                       class="w-full px-4 py-3 rounded-xl border border-wedding-gold/30 text-sm bg-wedding-ivory/30">
            </div>

            <!-- Theme & Filter Presets -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-wedding-dark mb-2">Tema Tampilan Kamera *</label>
                    <select name="theme_preset" required class="w-full px-4 py-3 rounded-xl border border-wedding-gold/30 text-sm bg-wedding-ivory/30">
                        <option value="ivory_gold" {{ old('theme_preset', $event->theme_preset) === 'ivory_gold' ? 'selected' : '' }}>Ivory & Gold (Wedding Classic)</option>
                        <option value="sacred_olive" {{ old('theme_preset', $event->theme_preset) === 'sacred_olive' ? 'selected' : '' }}>Sacred Olive (Church / Botanical)</option>
                        <option value="classic_film" {{ old('theme_preset', $event->theme_preset) === 'classic_film' ? 'selected' : '' }}>Classic 35mm Analog</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-wedding-dark mb-2">Simulasi Tone Film *</label>
                    <select name="filter_preset" required class="w-full px-4 py-3 rounded-xl border border-wedding-gold/30 text-sm bg-wedding-ivory/30">
                        <option value="fuji_quicksnap" {{ old('filter_preset', $event->filter_preset) === 'fuji_quicksnap' ? 'selected' : '' }}>Fuji QuickSnap 400 (Clean, Emerald Shadows, High Whites)</option>
                        <option value="summer_gold" {{ old('filter_preset', $event->filter_preset) === 'summer_gold' ? 'selected' : '' }}>Kodak Gold 200 (Summer Riviera, Pastel Teal Sky & Warm Sand)</option>
                        <option value="kodak_funsaver" {{ old('filter_preset', $event->filter_preset) === 'kodak_funsaver' ? 'selected' : '' }}>Kodak FunSaver 800 (Golden Warm Amber, Gritty Coarse Grain)</option>
                        <option value="warm_portra" {{ old('filter_preset', $event->filter_preset) === 'warm_portra' ? 'selected' : '' }}>Kodak Portra 400 (Soft Pastel Skin Tones, Fine Art Wedding)</option>
                        <option value="ilford_bw" {{ old('filter_preset', $event->filter_preset) === 'ilford_bw' ? 'selected' : '' }}>Ilford HP5 Plus 400 (Classic Monochrome Black & White)</option>
                        <option value="cinestill_800t" {{ old('filter_preset', $event->filter_preset) === 'cinestill_800t' ? 'selected' : '' }}>CineStill 800T (Cinematic Night Glow & Tungsten Blues)</option>
                        <option value="agfa_vista" {{ old('filter_preset', $event->filter_preset) === 'agfa_vista' ? 'selected' : '' }}>Agfa Vista 200 (Vibrant 90s Nostalgia, Punchy Colors)</option>
                        <option value="lomography_color" {{ old('filter_preset', $event->filter_preset) === 'lomography_color' ? 'selected' : '' }}>Lomography Color 400 (Toy Camera Vignette & Cross-Process)</option>
                    </select>
                </div>
            </div>

            <!-- Max shots per device -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-wedding-dark mb-2">Jatah Foto per Device (1 Roll)</label>
                <input type="number" name="max_shots_per_device" value="{{ old('max_shots_per_device', $event->max_shots_per_device) }}" min="1" max="100" required
                       class="w-full px-4 py-3 rounded-xl border border-wedding-gold/30 text-sm bg-wedding-ivory/30 font-mono">
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-wedding-dark mb-2">Pesan Sambutan Tamu</label>
                <textarea name="description" rows="3"
                          class="w-full px-4 py-3 rounded-xl border border-wedding-gold/30 text-sm bg-wedding-ivory/30">{{ old('description', $event->description) }}</textarea>
            </div>

            <!-- Google Drive Folder Link & Sync Schedule -->
            <div class="p-4 rounded-2xl bg-wedding-linen/40 border border-wedding-gold/30">
                <div class="flex items-center space-x-2 mb-1.5">
                    <span class="text-base">📁</span>
                    <label class="text-xs font-bold uppercase tracking-wider text-wedding-dark">
                        Link Folder Google Drive (Opsional)
                    </label>
                </div>
                <input type="url" name="google_drive_url" value="{{ old('google_drive_url', $event->google_drive_url) }}"
                       placeholder="https://drive.google.com/drive/folders/1abcXYZ..."
                       class="w-full px-4 py-2.5 rounded-xl border border-wedding-gold/30 focus:border-wedding-gold text-xs sm:text-sm bg-white">
                <p class="text-[11px] text-wedding-subtle mt-1.5">
                    Folder Google Drive publik untuk mencadangkan seluruh foto acara. Anda juga dapat mengelola sinkronisasi langsung di Galeri Event.
                </p>

                <div class="mt-3 pt-3 border-t border-wedding-gold/20">
                    <label class="block text-xs font-semibold text-wedding-dark mb-1">
                        Jadwal Otomatis Sinkronisasi (Scheduler)
                    </label>
                    <select name="google_drive_sync_frequency" class="w-full px-3.5 py-2.5 rounded-xl border border-wedding-gold/30 text-xs sm:text-sm bg-white text-wedding-dark">
                        <option value="daily" {{ old('google_drive_sync_frequency', $event->google_drive_sync_frequency ?? 'daily') === 'daily' ? 'selected' : '' }}>1 Hari Sekali (Setiap Hari - Rekomendasi)</option>
                        <option value="twice_daily" {{ old('google_drive_sync_frequency', $event->google_drive_sync_frequency) === 'twice_daily' ? 'selected' : '' }}>2 Kali Sehari (Setiap 12 Jam)</option>
                        <option value="every_6_hours" {{ old('google_drive_sync_frequency', $event->google_drive_sync_frequency) === 'every_6_hours' ? 'selected' : '' }}>4 Kali Sehari (Setiap 6 Jam)</option>
                        <option value="hourly" {{ old('google_drive_sync_frequency', $event->google_drive_sync_frequency) === 'hourly' ? 'selected' : '' }}>Setiap Jam (1 Jam Sekali)</option>
                        <option value="every_2_days" {{ old('google_drive_sync_frequency', $event->google_drive_sync_frequency) === 'every_2_days' ? 'selected' : '' }}>2 Hari Sekali</option>
                        <option value="every_3_days" {{ old('google_drive_sync_frequency', $event->google_drive_sync_frequency) === 'every_3_days' ? 'selected' : '' }}>3 Hari Sekali</option>
                        <option value="every_7_days" {{ old('google_drive_sync_frequency', $event->google_drive_sync_frequency) === 'every_7_days' ? 'selected' : '' }}>1 Minggu Sekali</option>
                        <option value="manual" {{ old('google_drive_sync_frequency', $event->google_drive_sync_frequency) === 'manual' ? 'selected' : '' }}>Nonaktif (Hanya Sinkronisasi Manual)</option>
                    </select>
                    <span class="text-[10px] text-wedding-subtle block mt-1">
                        Server otomatis memeriksa dan menyalin foto baru serta memulihkan foto terhapus sesuai interval ini.
                    </span>
                </div>
            </div>

            <!-- Options -->
            <div class="space-y-3 pt-2">
                <div class="p-3.5 rounded-xl bg-wedding-ivory/60 border border-wedding-gold/25">
                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox" name="show_date_stamp" value="1" {{ old('show_date_stamp', $event->show_date_stamp) ? 'checked' : '' }}
                               class="w-4 h-4 mt-0.5 text-wedding-gold rounded border-wedding-gold/40 focus:ring-wedding-gold">
                        <div>
                            <span class="text-xs text-wedding-dark font-bold block">Tampilkan Cap Tanggal Analog (Date Stamp)</span>
                            <span class="text-[11px] text-wedding-subtle block mt-0.5">
                                Mencetak watermark cap tanggal vintage ('YY MM DD) berwarna oranye/emas khas kamera analog di pojok foto. Tamu juga dapat menyalakan/mematikan tombol tanggal langsung di kamera.
                            </span>
                        </div>
                    </label>
                </div>

                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $event->is_active) ? 'checked' : '' }}
                           class="w-4 h-4 text-wedding-gold rounded border-wedding-gold/40 focus:ring-wedding-gold">
                    <span class="text-xs text-wedding-dark font-medium">Aktifkan Event (Tamu dapat mengakses kamera)</span>
                </label>

                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox" name="is_gallery_public" value="1" {{ old('is_gallery_public', $event->is_gallery_public) ? 'checked' : '' }}
                           class="w-4 h-4 text-wedding-gold rounded border-wedding-gold/40 focus:ring-wedding-gold">
                    <span class="text-xs text-wedding-dark font-medium">Buka Galeri untuk Publik (Tamu dapat melihat galeri bersama)</span>
                </label>
            </div>

            <!-- Submit & Actions -->
            <div class="pt-4 border-t border-wedding-gold/20 flex items-center justify-between">
                <div>
                    <!-- Delete Button inside Edit Form Area triggers external form below -->
                    <button type="button"
                            onclick="if(confirm('⚠️ HAPUS EVENT & SELURUH FOTO?\n\nApakah Anda yakin ingin menghapus event \'{{ addslashes($event->title) }}\'?\n\nSeluruh file foto di server (folder photos/{{ $event->slug }}) dan database akan otomatis dihapus permanen!')) { document.getElementById('delete-event-form').submit(); }"
                            class="px-4 py-2.5 rounded-xl border border-red-300 text-red-600 hover:bg-red-50 text-xs font-semibold transition-colors flex items-center space-x-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                        </svg>
                        <span>Hapus Event &amp; Seluruh Foto</span>
                    </button>
                </div>

                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.dashboard') }}" class="px-5 py-2.5 rounded-xl border border-wedding-gold/30 text-xs font-semibold text-wedding-dark hover:bg-wedding-linen transition-colors">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-wedding-gold to-wedding-goldHover text-white text-xs font-semibold shadow hover:shadow-md transition-all">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>

        <!-- Hidden Delete Form -->
        <form id="delete-event-form" action="{{ route('admin.events.destroy', $event) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>
@endsection

