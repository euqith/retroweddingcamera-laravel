@extends('layouts.app', ['title' => 'QR Code — ' . $event->title])

@push('styles')
<style>
@media print {
    body {
        background: white !important;
    }
    .no-print {
        display: none !important;
    }
    .print-card {
        border: 2px solid #C5A059 !important;
        box-shadow: none !important;
        page-break-inside: avoid;
    }
}
</style>
@endpush

@section('content')
<div class="min-h-screen bg-[#FAF6F0] py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
        <!-- Top Nav (No print) -->
        <div class="no-print flex items-center justify-between pb-6 border-b border-wedding-gold/20 mb-8">
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.dashboard') }}" class="p-2 rounded-xl bg-white border border-wedding-gold/20 hover:bg-wedding-linen text-wedding-dark transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                </a>
                <div>
                    <span class="text-xs uppercase tracking-widest text-wedding-gold font-bold block">QR Code Generator</span>
                    <h1 class="font-serif text-2xl font-bold text-wedding-dark">{{ $event->title }}</h1>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <button onclick="window.print()"
                        type="button"
                        class="px-4 py-2.5 rounded-xl bg-wedding-dark hover:bg-black text-white text-xs font-semibold shadow transition-all flex items-center space-x-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.077-.32-2.19-.32-3.329 0-5.523 4.477-10 10-10s10 4.477 10 10c0 1.139-.08 2.252-.32 3.329m-19.36 0A9.97 9.97 0 013 10.5c0-5.523 4.477-10 10-10s10 4.477 10 10c0 1.139-.08 2.252-.32 3.329m-19.36 0a9.97 9.97 0 00-.32 3.329c0 5.523 4.477 10 10 10s10-4.477 10-10a9.97 9.97 0 00-.32-3.329M9 13.5l3 3m0 0l3-3m-3 3v-6"/>
                    </svg>
                    <span>Cetak Kartu Meja</span>
                </button>
            </div>
        </div>

        <!-- Printable Table Tent Card (Wedding & Church Theme) -->
        <div class="print-card bg-white rounded-3xl border-2 border-wedding-gold/40 p-8 sm:p-12 shadow-sm text-center mb-8">
            <!-- Ornamental Border & Title -->
            <div class="max-w-md mx-auto mb-8">
                <div class="inline-flex items-center justify-center space-x-2 text-wedding-gold mb-2">
                    <span class="h-px w-10 bg-wedding-gold"></span>
                    <span class="font-serif text-lg tracking-widest uppercase">Disposable Camera</span>
                    <span class="h-px w-10 bg-wedding-gold"></span>
                </div>

                <h2 class="font-serif text-3xl sm:text-4xl text-wedding-dark font-bold mb-2">
                    {{ $event->title }}
                </h2>

                <p class="text-xs text-wedding-subtle tracking-wide">
                    Pindai QR Code di bawah menggunakan kamera smartphone Anda untuk mulai mengambil foto atau melihat galeri bersama!
                </p>
            </div>

            <!-- Twin QR Codes Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 max-w-2xl mx-auto">
                <!-- QR Code 1: Buka Kamera -->
                <div class="bg-wedding-ivory border border-wedding-gold/30 rounded-2xl p-6 flex flex-col items-center">
                    <span class="text-xs uppercase tracking-widest text-wedding-gold font-bold mb-1">Pilihan 1</span>
                    <h3 class="font-serif text-xl font-bold text-wedding-dark mb-4">Buka Kamera Disposable</h3>

                    <div class="bg-white p-3 rounded-2xl shadow-inner border border-wedding-gold/20 mb-4">
                        <div class="w-48 h-48 flex items-center justify-center">
                            {!! $cameraQrSvg !!}
                        </div>
                    </div>

                    <p class="text-[11px] text-wedding-subtle mb-3">Scan untuk mengambil hingga 36 foto analog</p>
                    <span class="font-mono text-[10px] text-wedding-dark/60 bg-white px-2 py-1 rounded border border-wedding-gold/20 break-all select-all">
                        {{ $cameraUrl }}
                    </span>
                </div>

                <!-- QR Code 2: Buka Galeri -->
                <div class="bg-wedding-ivory border border-wedding-gold/30 rounded-2xl p-6 flex flex-col items-center">
                    <span class="text-xs uppercase tracking-widest text-wedding-gold font-bold mb-1">Pilihan 2</span>
                    <h3 class="font-serif text-xl font-bold text-wedding-dark mb-4">Buka Galeri Foto</h3>

                    <div class="bg-white p-3 rounded-2xl shadow-inner border border-wedding-gold/20 mb-4">
                        <div class="w-48 h-48 flex items-center justify-center">
                            {!! $galleryQrSvg !!}
                        </div>
                    </div>

                    <p class="text-[11px] text-wedding-subtle mb-3">Scan untuk melihat seluruh foto acara hari ini</p>
                    <span class="font-mono text-[10px] text-wedding-dark/60 bg-white px-2 py-1 rounded border border-wedding-gold/20 break-all select-all">
                        {{ $galleryUrl }}
                    </span>
                </div>
            </div>

            <!-- Footer instructions -->
            <div class="mt-8 pt-6 border-t border-wedding-gold/20 text-xs text-wedding-subtle">
                Tanpa perlu install aplikasi • Cukup buka kamera smartphone lalu scan QR di atas
            </div>
        </div>
    </div>
</div>
@endsection
