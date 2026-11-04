@extends('layouts.app', ['title' => 'Barcode Meja Acara — ' . $event->title])

@push('styles')
<style>
@media print {
    /* Hide everything except the print card */
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
        position: absolute;
        left: 50%;
        top: 20px;
        transform: translateX(-50%);
        width: 105mm !important; /* Standard 10x24 cm / 4x9 inch standing table card */
        max-width: 105mm !important;
        margin: 0 !important;
        padding: 24px 20px !important;
        border: 2px solid #C5A059 !important;
        box-shadow: none !important;
        background: #FAF6F0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        page-break-inside: avoid;
    }
    @page {
        size: portrait;
        margin: 10mm;
    }
}
</style>
@endpush

@section('content')
<div class="min-h-screen bg-[#FAF6F0] py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Top Nav & Actions Bar -->
        <div class="no-print flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-wedding-gold/20 mb-8 gap-4">
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

        <!-- Layout Info Notice -->
        <div class="no-print mb-6 p-4 rounded-2xl bg-white border border-wedding-gold/30 flex items-center justify-between text-xs text-wedding-dark shadow-sm">
            <div class="flex items-center space-x-3">
                <span class="text-xl">✨</span>
                <div>
                    <span class="font-semibold block text-wedding-dark">Format Kartu Meja Memanjang (Standing Acrylic 10 x 24 cm)</span>
                    <span class="text-wedding-subtle">Sesuai referensi Instagram: Berisi 2 barcode vertikal (Kamera & Galeri) untuk dipajang di atas meja tamu acara.</span>
                </div>
            </div>
            <button onclick="window.print()" class="text-wedding-gold font-semibold hover:underline hidden sm:block">
                Cetak Sekarang →
            </button>
        </div>

        <!-- PRINTABLE STANDING CARD (DESAIN MEMANJANG VERTIKAL) -->
        <div class="flex justify-center pb-12">
            <div id="printable-card"
                 class="relative w-full max-w-[420px] bg-[#FAF6F0] rounded-[2rem] border-2 border-wedding-gold/40 shadow-xl p-8 sm:p-10 text-center transition-all select-none">
                
                <!-- Inner Double Border Accent -->
                <div class="absolute inset-3 border border-wedding-gold/30 rounded-[1.5rem] pointer-events-none"></div>

                <!-- Top Floral Ornament -->
                <div class="relative z-10 pt-2 mb-4">
                    <div class="inline-flex items-center justify-center space-x-2 text-wedding-gold mb-2">
                        <span class="h-px w-8 bg-wedding-gold/60"></span>
                        <svg class="w-5 h-5 text-wedding-gold" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        <span class="h-px w-8 bg-wedding-gold/60"></span>
                    </div>

                    <p class="text-[11px] uppercase tracking-[0.3em] text-wedding-olive font-bold">
                        BE OUR PHOTOGRAPHER
                    </p>

                    <h2 class="font-serif text-3xl sm:text-4xl text-wedding-dark font-bold leading-tight mt-1 mb-1">
                        {{ $event->title }}
                    </h2>

                    @if($event->event_date)
                        <p class="text-xs text-wedding-subtle tracking-wide font-medium">
                            {{ $event->event_date->translatedFormat('l, d F Y') }}
                            @if($event->location)
                                <br><span class="text-[11px] text-wedding-subtle/80">{{ $event->location }}</span>
                            @endif
                        </p>
                    @endif
                </div>

                <!-- Introductory Instruction -->
                <div class="relative z-10 my-4 px-3">
                    <p class="text-xs text-wedding-dark/80 leading-relaxed font-serif italic">
                        Bantu kami mengabadikan setiap senyuman & momen hangat hari ini melalui kamera disposable digital!
                    </p>
                </div>

                <!-- TWIN BARCODE VERTICAL STACK (MEMANJANG KE BAWAH) -->
                <div class="relative z-10 space-y-5 my-6">
                    
                    <!-- 1. BARCODE KAMERA DISPOSABLE -->
                    <div class="bg-white/95 rounded-2xl p-5 border border-wedding-gold/30 shadow-sm flex flex-col items-center">
                        <div class="flex items-center space-x-1.5 text-wedding-gold mb-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
                            </svg>
                            <span class="text-[10px] uppercase tracking-widest font-bold">1. SCAN UNTUK MEMOTRET</span>
                        </div>
                        <h3 class="font-serif text-lg font-bold text-wedding-dark mb-3">Buka Kamera Disposable</h3>

                        <!-- Crisp SVG QR Code -->
                        <div class="p-2.5 bg-white rounded-xl border border-wedding-gold/20 shadow-inner mb-3">
                            <div class="w-40 h-40 sm:w-44 sm:h-44 flex items-center justify-center">
                                {!! $cameraQrSvg !!}
                            </div>
                        </div>

                        <span class="text-[11px] text-wedding-olive font-medium">Jatah 36 foto analog per smartphone</span>
                    </div>

                    <!-- Elegant Divider -->
                    <div class="flex items-center justify-center space-x-3 text-wedding-gold/60 py-1">
                        <span class="h-px w-16 bg-wedding-gold/30"></span>
                        <span class="text-xs font-serif uppercase tracking-widest text-wedding-gold font-semibold">&amp;</span>
                        <span class="h-px w-16 bg-wedding-gold/30"></span>
                    </div>

                    <!-- 2. BARCODE GALERI BERSAMA -->
                    <div class="bg-white/95 rounded-2xl p-5 border border-wedding-gold/30 shadow-sm flex flex-col items-center">
                        <div class="flex items-center space-x-1.5 text-wedding-gold mb-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                            </svg>
                            <span class="text-[10px] uppercase tracking-widest font-bold">2. SCAN UNTUK LIHAT FOTO</span>
                        </div>
                        <h3 class="font-serif text-lg font-bold text-wedding-dark mb-3">Buka Galeri Foto</h3>

                        <!-- Crisp SVG QR Code -->
                        <div class="p-2.5 bg-white rounded-xl border border-wedding-gold/20 shadow-inner mb-3">
                            <div class="w-40 h-40 sm:w-44 sm:h-44 flex items-center justify-center">
                                {!! $galleryQrSvg !!}
                            </div>
                        </div>

                        <span class="text-[11px] text-wedding-olive font-medium">Lihat & unduh semua hasil jepretan tamu</span>
                    </div>

                </div>

                <!-- Footer / How-to instructions -->
                <div class="relative z-10 pt-3 pb-2 border-t border-wedding-gold/20 text-center">
                    <p class="text-[11px] text-wedding-dark/80 font-medium mb-1">
                        Arahkan kamera HP ke salah satu kode di atas • Tanpa download aplikasi
                    </p>
                    <p class="text-[10px] text-wedding-subtle tracking-wider uppercase font-semibold">
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
                    <label class="block text-[11px] font-semibold text-wedding-dark mb-1">1. URL Kamera Tamu:</label>
                    <div class="flex items-center space-x-2">
                        <input type="text" readonly value="{{ $cameraUrl }}" class="w-full text-xs font-mono bg-wedding-ivory border border-wedding-gold/20 px-3 py-2 rounded-lg select-all text-wedding-dark">
                        <a href="{{ $cameraUrl }}" target="_blank" class="px-3 py-2 bg-wedding-linen hover:bg-wedding-gold/20 rounded-lg text-xs font-semibold text-wedding-dark">Buka</a>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-wedding-dark mb-1">2. URL Galeri Tamu:</label>
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
