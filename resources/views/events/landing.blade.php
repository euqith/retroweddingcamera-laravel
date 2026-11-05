@extends('layouts.app', ['title' => $event->title])

@section('content')
<div class="min-h-screen bg-[#FAF6F0] flex flex-col justify-between px-6 py-8 sm:py-12 max-w-lg mx-auto text-center">
    <!-- Header / Floral Ornament -->
    <div class="pt-6">
        <div class="inline-flex items-center justify-center space-x-2 text-wedding-gold mb-3">
            <span class="h-px w-8 bg-wedding-gold/40"></span>
            <svg class="w-5 h-5 text-wedding-gold" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
            </svg>
            <span class="h-px w-8 bg-wedding-gold/40"></span>
        </div>

        <p class="text-xs uppercase tracking-[0.25em] text-wedding-olive font-semibold mb-2">
            @if($event->event_type === 'wedding')
                Holy Matrimony & Wedding Reception
            @elseif($event->event_type === 'church')
                Church Service & Special Event
            @else
                Special Celebration
            @endif
        </p>

        <h1 class="font-serif text-3xl sm:text-4xl text-wedding-dark leading-tight mb-3">
            {{ $event->title }}
        </h1>

        @if($event->event_date)
            <p class="text-sm text-wedding-subtle font-medium">
                {{ $event->event_date->translatedFormat('l, d F Y') }}
                @if($event->location)
                    • {{ $event->location }}
                @endif
            </p>
        @endif
    </div>

    <!-- Center Card / Disposable Camera Sleeve Preview -->
    <div class="my-8 bg-white/80 backdrop-blur-sm border border-wedding-gold/30 rounded-3xl p-6 sm:p-8 shadow-sm">
        <div class="w-16 h-16 mx-auto mb-4 bg-wedding-linen rounded-2xl flex items-center justify-center text-wedding-gold shadow-inner">
            <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
            </svg>
        </div>

        <h2 class="font-serif text-2xl text-wedding-dark mb-2">Kamera Disposable Tamu</h2>
        <p class="text-sm text-wedding-subtle leading-relaxed mb-6">
            {{ $event->description ?: 'Abadikan momen tulus dan hangat hari ini. Setiap perangkat memiliki jatah 36 jepretan film analog yang langsung terkumpul ke galeri bersama!' }}
        </p>

        <!-- Roll Status Indicator -->
        <div class="bg-wedding-ivory border border-wedding-gold/20 rounded-2xl p-4 flex items-center justify-between">
            <div class="text-left">
                <span class="text-xs uppercase tracking-wider text-wedding-subtle block">Status Roll Anda</span>
                <span class="font-mono text-lg font-bold text-wedding-dark">
                    {{ $session->shots_taken }} / {{ $session->max_shots }} Foto
                </span>
            </div>
            <div class="text-right">
                <span class="text-xs uppercase tracking-wider text-wedding-subtle block">Sisa Film</span>
                <span class="font-mono text-lg font-bold {{ $session->remaining_shots > 0 ? 'text-wedding-filmOrange' : 'text-gray-400' }}">
                    {{ $session->remaining_shots }} Jepretan
                </span>
            </div>
        </div>

        @if(isset($recentPhotos) && $recentPhotos->count() > 0)
            <!-- Community Photos Preview (Everyone's shots visible on Home) -->
            <div class="mt-6 pt-5 border-t border-wedding-gold/20 text-left">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase tracking-wider text-wedding-dark">Foto Terbaru Semua Tamu</span>
                    <a href="{{ route('event.gallery', $event->slug) }}" class="text-xs text-wedding-gold hover:underline font-medium">
                        Lihat Semua ({{ $photosCount }}) →
                    </a>
                </div>
                <div class="grid grid-cols-4 gap-2">
                    @foreach($recentPhotos as $photo)
                        <a href="{{ route('event.gallery', $event->slug) }}" class="aspect-square rounded-xl overflow-hidden bg-wedding-linen border border-wedding-gold/30 hover:opacity-90 transition-opacity block relative group">
                            <img src="{{ $photo->thumbnail_url ?: $photo->url }}"
                                 alt="Foto Acara {{ $event->title }}"
                                 class="w-full h-full object-cover">
                            <span class="absolute bottom-1 right-1 text-[8px] font-mono text-amber-300 drop-shadow opacity-0 group-hover:opacity-100 transition-opacity">
                                '{{ $photo->taken_at ? $photo->taken_at->format('y m d') : date('y m d') }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Actions -->
    <div class="space-y-3 pb-6">
        @if(!$event->isCameraOpen())
            @if($event->cameraStatus() === 'upcoming')
                <div class="p-3.5 bg-amber-50 border border-amber-200 text-amber-900 rounded-2xl text-xs text-left">
                    <span class="font-bold block mb-0.5">⏳ Kamera Belum Dibuka</span>
                    <span>Acara akan dimulai pada <strong>{{ $event->starts_at?->translatedFormat('d F Y, H:i') }} WIB</strong>. Kamera akan aktif otomatis saat acara berlangsung.</span>
                </div>
            @else
                <div class="p-3.5 bg-gray-100 border border-gray-200 text-gray-700 rounded-2xl text-xs text-left">
                    <span class="font-bold block mb-0.5">🎞️ Periode Memotret Telah Selesai</span>
                    <span>Acara telah berakhir pada <strong>{{ $event->ends_at?->translatedFormat('d F Y, H:i') }} WIB</strong>. Seluruh roll foto tamu dapat dilihat di galeri bersama!</span>
                </div>
            @endif
        @elseif($session->remaining_shots > 0)
            <a href="{{ route('event.camera', $event->slug) }}"
               class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-wedding-gold to-wedding-goldHover text-white font-medium text-base tracking-wide shadow-md hover:shadow-lg transition-all flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                </svg>
                <span>Buka Kamera Disposable</span>
            </a>
        @else
            <div class="p-3 bg-wedding-linen text-wedding-subtle rounded-xl text-xs">
                Roll film Anda telah terpakai semua (36/36 foto). Anda dapat melihat seluruh hasilnya di galeri!
            </div>
        @endif

        <a href="{{ route('event.gallery', $event->slug) }}"
           class="w-full py-3.5 px-6 rounded-2xl border border-wedding-gold/40 text-wedding-dark bg-white hover:bg-wedding-linen/50 font-medium text-sm transition-all flex items-center justify-center space-x-2">
            <svg class="w-4 h-4 text-wedding-gold" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
            </svg>
            <span>Lihat Galeri Bersama ({{ $photosCount }} Foto)</span>
        </a>

        <!-- Public Table Card & QR Code Link -->
        <a href="{{ route('event.card', $event->slug) }}"
           class="w-full py-3 px-6 rounded-2xl border border-wedding-gold/30 text-wedding-dark/80 bg-wedding-ivory/50 hover:bg-wedding-linen font-medium text-xs transition-all flex items-center justify-center space-x-2 shadow-xs">
            <span>📄</span>
            <span>Lihat &amp; Cetak Kartu Barcode Meja</span>
            <span class="text-wedding-gold">➔</span>
        </a>

        <div class="pt-4 flex items-center justify-center space-x-3 text-xs text-wedding-subtle">
            <a href="{{ route('event.card', $event->slug) }}" class="hover:text-wedding-gold transition-colors flex items-center space-x-1">
                <span>📄</span>
                <span>Kartu Meja &amp; Barcode</span>
            </a>
            <span>•</span>
            @auth
                <a href="{{ route('admin.dashboard') }}" class="hover:text-wedding-gold transition-colors">Admin Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="hover:text-wedding-gold transition-colors">Login Admin</a>
            @endauth
        </div>
    </div>
</div>
@endsection

