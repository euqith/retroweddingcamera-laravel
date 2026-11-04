@extends('layouts.app', ['title' => 'Buat Event Baru — Retro Wedding Camera'])

@section('content')
<div class="min-h-screen bg-[#FAF6F0] py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center space-x-3 mb-8">
            <a href="{{ route('admin.dashboard') }}" class="p-2 rounded-xl bg-white border border-wedding-gold/20 hover:bg-wedding-linen text-wedding-dark transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <span class="text-xs uppercase tracking-widest text-wedding-gold font-bold block">Event Baru</span>
                <h1 class="font-serif text-2xl font-bold text-wedding-dark">Buat Acara Wedding / Gereja</h1>
            </div>
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

        <form action="{{ route('admin.events.store') }}" method="POST" class="bg-white rounded-3xl border border-wedding-gold/20 p-6 sm:p-8 shadow-sm space-y-6">
            @csrf

            <!-- Title -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-wedding-dark mb-2">Nama Acara *</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       placeholder="Contoh: The Wedding of Daniel & Clara / Youth Christmas Service"
                       class="w-full px-4 py-3 rounded-xl border border-wedding-gold/30 focus:border-wedding-gold focus:ring-1 focus:ring-wedding-gold text-sm bg-wedding-ivory/30">
            </div>

            <!-- Slug -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-wedding-dark mb-2">Custom URL Slug (Opsional)</label>
                <div class="flex items-center">
                    <span class="px-3 py-3 rounded-l-xl bg-wedding-linen border border-r-0 border-wedding-gold/30 text-xs text-wedding-subtle font-mono">/e/</span>
                    <input type="text" name="slug" value="{{ old('slug') }}"
                           placeholder="daniel-clara"
                           class="w-full px-4 py-3 rounded-r-xl border border-wedding-gold/30 focus:border-wedding-gold focus:ring-1 focus:ring-wedding-gold text-sm bg-wedding-ivory/30">
                </div>
            </div>

            <!-- Event Type & Date -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-wedding-dark mb-2">Jenis Acara *</label>
                    <select name="event_type" required class="w-full px-4 py-3 rounded-xl border border-wedding-gold/30 focus:border-wedding-gold focus:ring-1 focus:ring-wedding-gold text-sm bg-wedding-ivory/30">
                        <option value="wedding" {{ old('event_type') === 'wedding' ? 'selected' : '' }}>Pernikahan (Wedding)</option>
                        <option value="church" {{ old('event_type') === 'church' ? 'selected' : '' }}>Acara Gereja (Church Event)</option>
                        <option value="general" {{ old('event_type') === 'general' ? 'selected' : '' }}>Acara Lainnya</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-wedding-dark mb-2">Tanggal Acara</label>
                    <input type="date" name="event_date" value="{{ old('event_date') }}"
                           class="w-full px-4 py-3 rounded-xl border border-wedding-gold/30 focus:border-wedding-gold focus:ring-1 focus:ring-wedding-gold text-sm bg-wedding-ivory/30">
                </div>
            </div>

            <!-- Location -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-wedding-dark mb-2">Lokasi Acara</label>
                <input type="text" name="location" value="{{ old('location') }}"
                       placeholder="Contoh: St. Mary Cathedral & Grand Ballroom"
                       class="w-full px-4 py-3 rounded-xl border border-wedding-gold/30 focus:border-wedding-gold focus:ring-1 focus:ring-wedding-gold text-sm bg-wedding-ivory/30">
            </div>

            <!-- Theme & Filter Presets -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-wedding-dark mb-2">Tema Tampilan Kamera *</label>
                    <select name="theme_preset" required class="w-full px-4 py-3 rounded-xl border border-wedding-gold/30 text-sm bg-wedding-ivory/30">
                        <option value="ivory_gold" {{ old('theme_preset') === 'ivory_gold' ? 'selected' : '' }}>Ivory & Gold (Wedding Classic)</option>
                        <option value="sacred_olive" {{ old('theme_preset') === 'sacred_olive' ? 'selected' : '' }}>Sacred Olive (Church / Botanical)</option>
                        <option value="classic_film" {{ old('theme_preset') === 'classic_film' ? 'selected' : '' }}>Classic 35mm Analog</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-wedding-dark mb-2">Preset Tone Film *</label>
                    <select name="filter_preset" required class="w-full px-4 py-3 rounded-xl border border-wedding-gold/30 text-sm bg-wedding-ivory/30">
                        <option value="fuji_quicksnap" {{ old('filter_preset') === 'fuji_quicksnap' ? 'selected' : '' }}>Fuji QuickSnap 400 (Warm + Green Shadow)</option>
                        <option value="kodak_funsaver" {{ old('filter_preset') === 'kodak_funsaver' ? 'selected' : '' }}>Kodak FunSaver (Warm Golden Grain)</option>
                        <option value="warm_portra" {{ old('filter_preset') === 'warm_portra' ? 'selected' : '' }}>Portra 400 (Soft Warm Skin Tones)</option>
                    </select>
                </div>
            </div>

            <!-- Max shots per device -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-wedding-dark mb-2">Jatah Foto per Device (1 Roll)</label>
                <input type="number" name="max_shots_per_device" value="{{ old('max_shots_per_device', 36) }}" min="1" max="100" required
                       class="w-full px-4 py-3 rounded-xl border border-wedding-gold/30 focus:border-wedding-gold text-sm bg-wedding-ivory/30 font-mono">
                <p class="text-[11px] text-wedding-subtle mt-1">Standar kamera sekali pakai analog adalah 36 jepretan.</p>
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-wedding-dark mb-2">Pesan Sambutan Tamu</label>
                <textarea name="description" rows="3"
                          placeholder="Pesan hangat untuk tamu saat membuka kamera disposable..."
                          class="w-full px-4 py-3 rounded-xl border border-wedding-gold/30 text-sm bg-wedding-ivory/30">{{ old('description') }}</textarea>
            </div>

            <!-- Options -->
            <div class="space-y-3 pt-2">
                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                           class="w-4 h-4 text-wedding-gold rounded border-wedding-gold/40 focus:ring-wedding-gold">
                    <span class="text-xs text-wedding-dark font-medium">Aktifkan Event (Tamu dapat mengakses kamera)</span>
                </label>

                <label class="flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox" name="is_gallery_public" value="1" {{ old('is_gallery_public', true) ? 'checked' : '' }}
                           class="w-4 h-4 text-wedding-gold rounded border-wedding-gold/40 focus:ring-wedding-gold">
                    <span class="text-xs text-wedding-dark font-medium">Buka Galeri untuk Publik (Tamu dapat melihat galeri bersama)</span>
                </label>
            </div>

            <!-- Submit -->
            <div class="pt-4 border-t border-wedding-gold/20 flex items-center justify-end space-x-3">
                <a href="{{ route('admin.dashboard') }}" class="px-5 py-2.5 rounded-xl border border-wedding-gold/30 text-xs font-semibold text-wedding-dark hover:bg-wedding-linen transition-colors">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-wedding-gold to-wedding-goldHover text-white text-xs font-semibold shadow hover:shadow-md transition-all">
                    Simpan & Buat QR Code
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
