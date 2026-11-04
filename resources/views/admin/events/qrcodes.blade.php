@extends('layouts.app', ['title' => 'Barcode Meja Acara — ' . $event->title])

@push('styles')
<style>
@media print {
    html, body {
        height: auto !important;
        overflow: visible !important;
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    body * {
        visibility: hidden;
    }
    .no-print, nav, header, footer {
        display: none !important;
    }
    #printable-card, #printable-card * {
        visibility: visible;
    }
    #printable-card {
        position: absolute !important;
        left: 50% !important;
        top: 0 !important;
        transform: translateX(-50%) !important;
        width: 120mm !important; /* Ideal for A4 single page or standing table acrylic */
        max-width: 120mm !important;
        margin: 0 !important;
        padding: 24px 24px !important;
        border: 2px solid #C5A059 !important;
        border-radius: 1.5rem !important;
        box-shadow: none !important;
        background: #FAF6F0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        page-break-after: avoid !important;
    }
    @page {
        size: portrait;
        margin: 8mm;
    }
}
</style>
@endpush

@section('content')
<div x-data="{ cardMode: 'single' }" class="min-h-screen bg-[#FAF6F0] py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Top Nav & Actions Bar -->
        <div class="no-print flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-wedding-gold/20 mb-6 gap-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.dashboard') }}"
                   class="p-2.5 rounded-xl bg-white border border-wedding-gold/20 hover:bg-wedding-linen text-wedding-dark transition-colors shadow-sm"
                   title="Kembali ke Dashboard">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                </a>
                <div>
                    <span class="text-xs uppercase tracking-widest text-wedding-gold font-bold block">Desain Kartu Meja & Barcode</span>
                    <h1 class="font-serif text-2xl font-bold text-wedding-dark">{{ $event->title }}</h1>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <button onclick="window.print()"
                        type="button"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-wedding-gold to-wedding-goldHover text-white text-xs font-semibold shadow hover:shadow-md transition-all flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.077-.32-2.19-.32-3.329 0-5.523 4.477-10 10-10s10 4.477 10 10c0 1.139-.08 2.252-.32 3.329m-19.36 0A9.97 9.97 0 013 10.5c0-5.523 4.477-10 10-10s10 4.477 10 10c0 1.139-.08 2.252-.32 3.329m-19.36 0a9.97 9.97 0 00-.32 3.329c0 5.523 4.477 10 10 10s10-4.477 10-10a9.97 9.97 0 00-.32-3.329M9 13.5l3 3m0 0l3-3m-3 3v-6"/>
                    </svg>
                    <span>Cetak Kartu Meja (Print / PDF)</span>
                </button>
            </div>
        </div>

        <!-- Mode Selector (No Print) -->
        <div class="no-print mb-6 p-3 rounded-2xl bg-white border border-wedding-gold/25 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center space-x-2">
                <span class="text-xs font-bold text-wedding-dark uppercase tracking-wider">Format Barcode:</span>
                <div class="inline-flex rounded-xl p-1 bg-wedding-linen border border-wedding-gold/20 text-xs">
                    <button @click="cardMode = 'single'"
                            type="button"
                            class="px-3.5 py-1.5 rounded-lg font-bold transition-all"
                            :class="cardMode === 'single' ? 'bg-white text-wedding-gold shadow-sm border border-wedding-gold/30' : 'text-wedding-dark/70 hover:text-wedding-dark'">
                        ✨ 1 Barcode Portal (Kamera & Galeri) — Rekomendasi
                    </button>
                    <button @click="cardMode = 'camera_only'"
                            type="button"
                            class="px-3.5 py-1.5 rounded-lg font-bold transition-all"
                            :class="cardMode === 'camera_only' ? 'bg-white text-wedding-gold shadow-sm border border-wedding-gold/30' : 'text-wedding-dark/70 hover:text-wedding-dark'">
                        📷 Kamera Saja
                    </button>
                    <button @click="cardMode = 'double'"
                            type="button"
                            class="px-3.5 py-1.5 rounded-lg font-bold transition-all"
                            :class="cardMode === 'double' ? 'bg-white text-wedding-gold shadow-sm border border-wedding-gold/30' : 'text-wedding-dark/70 hover:text-wedding-dark'">
                        2 Barcode (Terpisah)
                    </button>
                </div>
            </div>

            <div class="text-[11px] text-wedding-subtle flex items-center space-x-1.5">
                <span>📄</span>
                <span x-show="cardMode === 'single' || cardMode === 'camera_only'">Format 1 Barcode dijamin <strong>pas 1 halaman A4</strong> (tidak terpotong).</span>
                <span x-show="cardMode === 'double'">Format 2 Barcode membutuhkan kertas panjang / standing akrilik tinggi.</span>
            </div>
        </div>

        <!-- PRINTABLE STANDING CARD -->
        <div class="flex justify-center pb-12">
            <div id="printable-card"
                 class="relative w-full max-w-[420px] bg-[#FAF6F0] rounded-[2rem] border-2 border-wedding-gold/40 shadow-xl p-7 sm:p-8 text-center transition-all select-none">
                
                <!-- Inner Double Border Accent -->
                <div class="absolute inset-2.5 border border-wedding-gold/30 rounded-[1.5rem] pointer-events-none"></div>

                <!-- Top Floral Ornament -->
                <div class="relative z-10 pt-1 mb-2">
                    <div class="inline-flex items-center justify-center space-x-2 text-wedding-gold mb-1">
                        <span class="h-px w-8 bg-wedding-gold/60"></span>
                        <svg class="w-4 h-4 text-wedding-gold" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        <span class="h-px w-8 bg-wedding-gold/60"></span>
                    </div>

                    <p class="text-[10px] uppercase tracking-[0.3em] text-wedding-olive font-bold">
                        BE OUR PHOTOGRAPHER
                    </p>

                    <h2 class="font-serif text-2xl sm:text-3xl text-wedding-dark font-bold leading-tight mt-1 mb-1">
                        {{ $event->title }}
                    </h2>

                    @if($event->event_date)
                        <p class="text-[11px] text-wedding-subtle tracking-wide font-medium">
                            {{ $event->event_date->translatedFormat('l, d F Y') }}
                            @if($event->location)
                                <br><span class="text-[10px] text-wedding-subtle/80">{{ $event->location }}</span>
                            @endif
                        </p>
                    @endif
                </div>

                <!-- Introductory Instruction -->
                <div class="relative z-10 my-2 px-2">
                    <p class="text-[11px] text-wedding-dark/80 leading-relaxed font-serif italic">
                        Bantu kami mengabadikan setiap senyuman & momen hangat hari ini melalui kamera disposable digital!
                    </p>
                </div>

                <!-- 1 BARCODE MODE (REKOMENDASI: PAS 1 HALAMAN A4 TANPA TERPOTONG) -->
                <div x-show="cardMode === 'single'" class="relative z-10 my-4">
                    <div class="bg-white/95 rounded-2xl p-4 sm:p-5 border border-wedding-gold/30 shadow-sm flex flex-col items-center">
                        <div class="flex items-center space-x-1.5 text-wedding-gold mb-1">
                            <span class="text-sm">📸</span>
                            <span class="text-[10px] uppercase tracking-widest font-bold">SCAN DENGAN KAMERA HP</span>
                        </div>
                        <h3 class="font-serif text-base sm:text-lg font-bold text-wedding-dark mb-2.5">
                            Buka Kamera &amp; Galeri Acara
                        </h3>

                        <!-- Crisp SVG QR Code pointing to Landing Portal -->
                        <div class="p-2.5 bg-white rounded-xl border border-wedding-gold/20 shadow-inner mb-3">
                            <div class="w-44 h-44 sm:w-48 sm:h-48 flex items-center justify-center">
                                {!! $landingQrSvg !!}
                            </div>
                        </div>

                        <!-- 3 Clear Bullets -->
                        <div class="space-y-1 text-[11px] text-wedding-dark/90 font-medium text-left w-full max-w-[260px] mx-auto pt-1">
                            <div class="flex items-center space-x-1.5">
                                <span class="text-wedding-gold">✔</span>
                                <span>Ambil Foto Analog (Jatah {{ $event->max_shots_per_device }} Jepretan)</span>
                            </div>
                            <div class="flex items-center space-x-1.5">
                                <span class="text-wedding-gold">✔</span>
                                <span>Lihat &amp; Unduh Galeri Bersama Tamu</span>
                            </div>
                            <div class="flex items-center space-x-1.5">
                                <span class="text-wedding-gold">✔</span>
                                <span>Langsung di Browser • Tanpa Install Aplikasi</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CAMERA ONLY BARCODE MODE -->
                <div x-show="cardMode === 'camera_only'" class="relative z-10 my-4" style="display: none;">
                    <div class="bg-white/95 rounded-2xl p-4 sm:p-5 border border-wedding-gold/30 shadow-sm flex flex-col items-center">
                        <div class="flex items-center space-x-1.5 text-wedding-gold mb-1">
                            <span class="text-sm">📷</span>
                            <span class="text-[10px] uppercase tracking-widest font-bold">SCAN UNTUK MEMOTRET</span>
                        </div>
                        <h3 class="font-serif text-base sm:text-lg font-bold text-wedding-dark mb-2.5">
                            Buka Kamera Sekali Pakai
                        </h3>

                        <div class="p-2.5 bg-white rounded-xl border border-wedding-gold/20 shadow-inner mb-3">
                            <div class="w-44 h-44 sm:w-48 sm:h-48 flex items-center justify-center">
                                {!! $cameraQrSvg !!}
                            </div>
                        </div>

                        <span class="text-[11px] text-wedding-olive font-medium">Jatah {{ $event->max_shots_per_device }} foto analog per smartphone • Tanpa install</span>
                    </div>
                </div>

                <!-- 2 BARCODES MODE (UNTUK STANDING AKRILIK EKSTRA TINGGI) -->
                <div x-show="cardMode === 'double'" class="relative z-10 space-y-3.5 my-3" style="display: none;">
                    <!-- Barcode 1: Kamera -->
                    <div class="bg-white/95 rounded-2xl p-3.5 border border-wedding-gold/30 shadow-sm flex flex-col items-center">
                        <span class="text-[9px] uppercase tracking-widest font-bold text-wedding-gold mb-0.5">1. SCAN UNTUK MEMOTRET</span>
                        <h4 class="font-serif text-sm font-bold text-wedding-dark mb-1.5">Buka Kamera Disposable</h4>
                        <div class="p-1.5 bg-white rounded-xl border border-wedding-gold/20 shadow-inner mb-1.5">
                            <div class="w-32 h-32 flex items-center justify-center">
                                {!! $cameraQrSvg !!}
                            </div>
                        </div>
                        <span class="text-[10px] text-wedding-olive">Jatah {{ $event->max_shots_per_device }} foto analog</span>
                    </div>

                    <!-- Barcode 2: Galeri -->
                    <div class="bg-white/95 rounded-2xl p-3.5 border border-wedding-gold/30 shadow-sm flex flex-col items-center">
                        <span class="text-[9px] uppercase tracking-widest font-bold text-wedding-gold mb-0.5">2. SCAN UNTUK LIHAT FOTO</span>
                        <h4 class="font-serif text-sm font-bold text-wedding-dark mb-1.5">Buka Galeri Foto</h4>
                        <div class="p-1.5 bg-white rounded-xl border border-wedding-gold/20 shadow-inner mb-1.5">
                            <div class="w-32 h-32 flex items-center justify-center">
                                {!! $galleryQrSvg !!}
                            </div>
                        </div>
                        <span class="text-[10px] text-wedding-olive">Lihat &amp; unduh hasil jepretan</span>
                    </div>
                </div>

                <!-- Footer / How-to instructions -->
                <div class="relative z-10 pt-2 pb-1 border-t border-wedding-gold/20 text-center">
                    <p class="text-[10px] text-wedding-dark/80 font-medium mb-0.5">
                        Arahkan kamera smartphone ke kode di atas • Tanpa download aplikasi
                    </p>
                    <p class="text-[9px] text-wedding-subtle tracking-wider uppercase font-semibold">
                        #{{ Str::slug($event->title, '') }}
                    </p>
                </div>

            </div>
        </div>

        <!-- Direct URLs Copy Helper (No Print) -->
        <div class="no-print bg-white rounded-2xl border border-wedding-gold/20 p-6 max-w-xl mx-auto shadow-sm">
            <h3 class="text-xs uppercase tracking-wider text-wedding-subtle font-bold mb-4">Tautan Langsung Acara:</h3>
            
            <div class="space-y-3">
                <div>
                    <label class="block text-[11px] font-semibold text-wedding-dark mb-1">1. URL Portal Acara (Kamera &amp; Galeri):</label>
                    <div class="flex items-center space-x-2">
                        <input type="text" readonly value="{{ $landingUrl }}" class="w-full text-xs font-mono bg-wedding-ivory border border-wedding-gold/20 px-3 py-2 rounded-lg select-all text-wedding-dark">
                        <a href="{{ $landingUrl }}" target="_blank" class="px-3 py-2 bg-wedding-linen hover:bg-wedding-gold/20 rounded-lg text-xs font-semibold text-wedding-dark">Buka</a>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-wedding-dark mb-1">2. URL Kamera Tamu:</label>
                    <div class="flex items-center space-x-2">
                        <input type="text" readonly value="{{ $cameraUrl }}" class="w-full text-xs font-mono bg-wedding-ivory border border-wedding-gold/20 px-3 py-2 rounded-lg select-all text-wedding-dark">
                        <a href="{{ $cameraUrl }}" target="_blank" class="px-3 py-2 bg-wedding-linen hover:bg-wedding-gold/20 rounded-lg text-xs font-semibold text-wedding-dark">Buka</a>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-wedding-dark mb-1">3. URL Galeri Tamu:</label>
                    <div class="flex items-center space-x-2">
                        <input type="text" readonly value="{{ $galleryUrl }}" class="w-full text-xs font-mono bg-wedding-ivory border border-wedding-gold/20 px-3 py-2 rounded-lg select-all text-wedding-dark">
                        <a href="{{ $galleryUrl }}" target="_blank" class="px-3 py-2 bg-wedding-linen hover:bg-wedding-gold/20 rounded-lg text-xs font-semibold text-wedding-dark">Buka</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
