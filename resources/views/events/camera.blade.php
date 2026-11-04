@extends('layouts.app', ['title' => 'Kamera — ' . $event->title])

@section('content')
<div x-data="disposableCamera({
        slug: '{{ $event->slug }}',
        csrf: '{{ csrf_token() }}',
        initialShots: {{ $session->shots_taken }},
        maxShots: {{ $session->max_shots }},
        eventTitle: '{{ addslashes($event->title) }}',
        filterPreset: '{{ $event->filter_preset }}',
        captureUrl: '{{ route('event.camera.capture', $event->slug) }}',
        resetUrl: '{{ route('event.session.reset', $event->slug) }}',
        galleryUrl: '{{ route('event.gallery', $event->slug) }}',
        landingUrl: '{{ route('event.landing', $event->slug) }}',
        initialMyPhotos: {{ json_encode($myPhotos) }}
    })"
    x-init="initCamera()"
    class="relative min-h-screen bg-[#1c1917] flex flex-col items-center justify-between overflow-hidden select-none">

    <!-- Screen Flash Overlay -->
    <div x-show="isFlashing"
         x-transition:enter="transition-opacity duration-75"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-white z-50 pointer-events-none"
         style="display: none;"></div>

    <!-- Hidden Native Camera File Input (Always works on all phones & HTTP/HTTPS) -->
    <input type="file"
           x-ref="nativeFileInput"
           accept="image/*"
           :capture="facingMode === 'user' ? 'user' : 'environment'"
           class="hidden"
           @change="handleNativePhoto($event)">

    <!-- Top Status Bar & Event Branding -->
    <header class="w-full max-w-md pt-4 px-4 flex items-center justify-between text-white/80 z-20">
        <a :href="landingUrl" class="p-2 -ml-2 rounded-full hover:bg-white/10 transition-colors" title="Kembali ke Info Acara">
            <svg class="w-5 h-5 text-white/70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
        </a>

        <div class="text-center">
            <span class="text-[10px] uppercase tracking-widest text-wedding-gold font-semibold block">Disposable Camera</span>
            <h1 class="font-serif text-sm tracking-wide text-white/90 truncate max-w-[200px]">{{ $event->title }}</h1>
        </div>

        <!-- Open My Photos Roll Button -->
        <button @click="showMyGallery = true"
                type="button"
                class="relative p-2 -mr-2 rounded-full hover:bg-white/10 text-wedding-gold transition-colors"
                title="Lihat Roll Foto Saya">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
            </svg>
            <span class="absolute top-1 right-1 bg-wedding-filmOrange text-white text-[9px] font-bold px-1 rounded-full font-mono shadow"
                  x-show="myPhotos.length > 0"
                  x-text="myPhotos.length"></span>
        </button>
    </header>

    <!-- Skeuomorphic Disposable Camera Body -->
    <div class="relative w-full max-w-sm my-auto px-4 flex flex-col items-center">
        <div class="w-full bg-[#FAF6F0] rounded-[2.5rem] p-5 shadow-[0_20px_50px_rgba(0,0,0,0.8)] border-4 border-[#231F1E] relative overflow-hidden">
            <!-- Vintage Paper Sleeve Texture & Wedding Gold Foil Header -->
            <div class="text-center pb-3 border-b border-wedding-gold/30">
                <p class="font-serif text-lg tracking-wide text-[#231F1E] font-bold">
                    {{ $event->title }}
                </p>
                <div class="flex items-center justify-center space-x-2 text-[10px] tracking-wider text-wedding-olive uppercase font-semibold">
                    <span>36 Exposures</span>
                    <span>•</span>
                    <span>ISO 800 Grain</span>
                </div>
            </div>

            <!-- Viewfinder Area -->
            <div class="relative w-full aspect-[4/3] bg-black rounded-2xl overflow-hidden my-4 border-4 border-[#18181b] shadow-inner">
                <!-- Video Stream for Live WebRTC -->
                <video x-ref="videoElement"
                       autoplay
                       playsinline
                       muted
                       class="w-full h-full object-cover transition-transform duration-200"
                       :class="facingMode === 'user' ? 'scale-x-[-1]' : ''"></video>

                <!-- Hidden Canvas for Tone Processing -->
                <canvas x-ref="captureCanvas" class="hidden"></canvas>

                <!-- Viewfinder Mask & Optical Crosshair -->
                <div x-show="!cameraError && !isRollEmpty"
                     class="absolute inset-0 border border-white/20 pointer-events-none rounded-xl m-2 flex items-center justify-center">
                    <div class="w-8 h-8 border border-white/30 rounded-full flex items-center justify-center">
                        <div class="w-1.5 h-1.5 bg-white/40 rounded-full"></div>
                    </div>
                </div>

                <!-- Live Quartz Date Stamp Overlay Preview -->
                <div class="absolute bottom-3 right-3 film-date-stamp text-xs pointer-events-none select-none">
                    '{{ date('y m d') }}
                </div>

                <!-- Camera Error / Native Mode Overlay -->
                <div x-show="cameraError && !isRollEmpty"
                     class="absolute inset-0 bg-[#231F1E]/95 backdrop-blur-sm flex flex-col items-center justify-center text-center p-5 text-white z-20"
                     style="display: none;">
                    <div class="w-10 h-10 bg-amber-500/20 text-wedding-gold rounded-full flex items-center justify-center mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                        </svg>
                    </div>
                    <p class="text-xs font-semibold text-wedding-ivory mb-1">Kamera Smartphone Siap</p>
                    <p class="text-[11px] text-white/70 mb-3 px-2" x-text="cameraError"></p>

                    <button @click="triggerNativeCamera()"
                            type="button"
                            class="py-2.5 px-4 rounded-xl bg-gradient-to-r from-wedding-gold to-wedding-goldHover text-white text-xs font-semibold shadow hover:shadow-md transition-all flex items-center space-x-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                        </svg>
                        <span>Jepret dengan Kamera HP</span>
                    </button>
                    <button @click="startStream()" type="button" class="mt-2 text-[10px] text-white/40 hover:text-white underline">
                        Coba Muat Ulang Live Kamera
                    </button>
                </div>

                <!-- Empty Roll Overlay (36/36 reached) -->
                <div x-show="isRollEmpty"
                     class="absolute inset-0 bg-[#231F1E]/95 backdrop-blur-sm flex flex-col items-center justify-center text-center p-6 text-white z-30"
                     style="display: none;">
                    <div class="w-12 h-12 bg-wedding-filmOrange/20 text-wedding-filmOrange rounded-full flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008h-.008v-.008z"/>
                        </svg>
                    </div>
                    <h3 class="font-serif text-xl font-bold text-wedding-ivory mb-1">Roll Film Habis (36/36)</h3>
                    <p class="text-xs text-white/70 mb-4">Seluruh foto Anda sudah tersimpan aman di roll ini.</p>
                    <button @click="showMyGallery = true"
                            type="button"
                            class="w-full py-2.5 px-4 bg-gradient-to-r from-wedding-gold to-wedding-goldHover text-white rounded-xl text-xs font-semibold shadow">
                        Lihat Roll Foto Saya
                    </button>
                    <button @click="resetRoll()" type="button" class="mt-2 text-[11px] text-white/50 hover:text-white underline">
                        Reset Roll (Khusus Testing Dev)
                    </button>
                </div>

                <!-- Processing Spinner -->
                <div x-show="isProcessing"
                     class="absolute inset-0 bg-black/60 backdrop-blur-[2px] flex flex-col items-center justify-center text-white z-20"
                     style="display: none;">
                    <div class="w-8 h-8 border-2 border-wedding-gold border-t-transparent rounded-full animate-spin mb-2"></div>
                    <span class="text-[11px] tracking-widest uppercase font-mono text-wedding-gold">Developing Film...</span>
                </div>
            </div>

            <!-- Controls Panel: Shutter, Film Counter, Flash & My Photos (Winder Removed) -->
            <div class="flex items-center justify-between pt-1">
                <!-- Mechanical Film Counter Window -->
                <div class="flex flex-col items-center">
                    <div class="bg-[#18181b] border-2 border-[#3f3f46] rounded-lg px-2.5 py-1 text-center shadow-inner">
                        <span class="font-mono text-sm font-bold text-wedding-filmOrange tracking-wider"
                              x-text="String(remainingShots).padStart(2, '0')"></span>
                    </div>
                    <span class="text-[9px] uppercase tracking-wider text-wedding-dark/60 font-semibold mt-1">Sisa Film</span>
                </div>

                <!-- My Shots Roll Button (Replaces confusing Winder) -->
                <div class="flex flex-col items-center">
                    <button @click="showMyGallery = true"
                            type="button"
                            class="relative w-11 h-11 rounded-xl overflow-hidden border-2 border-wedding-gold/40 bg-wedding-linen shadow flex items-center justify-center hover:border-wedding-gold transition-colors">
                        <template x-if="myPhotos.length > 0">
                            <img :src="myPhotos[0].thumbnail_url" class="w-full h-full object-cover">
                        </template>
                        <template x-if="myPhotos.length === 0">
                            <svg class="w-5 h-5 text-wedding-gold" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                            </svg>
                        </template>
                        <span class="absolute -top-1 -right-1 bg-wedding-filmOrange text-white text-[8px] font-bold px-1 rounded-full font-mono shadow"
                              x-show="myPhotos.length > 0"
                              x-text="myPhotos.length"></span>
                    </button>
                    <span class="text-[9px] uppercase tracking-wider text-wedding-dark/60 font-semibold mt-1">Roll Saya</span>
                </div>

                <!-- Big Shutter Button -->
                <div class="flex flex-col items-center">
                    <button @click="takePhoto()"
                            :disabled="isRollEmpty || isProcessing"
                            class="w-16 h-16 rounded-full shutter-btn border-4 border-[#3f3f46] flex items-center justify-center transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                        <div class="w-10 h-10 rounded-full border border-white/20"></div>
                    </button>
                    <span class="text-[9px] uppercase tracking-wider text-wedding-dark/60 font-semibold mt-1">Shutter</span>
                </div>

                <!-- Flash Toggle -->
                <div class="flex flex-col items-center">
                    <button @click="toggleFlash()"
                            type="button"
                            class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors border shadow-sm"
                            :class="flashEnabled ? 'bg-wedding-filmOrange text-white border-amber-600 shadow-md ring-2 ring-amber-400' : 'bg-wedding-linen text-wedding-dark/60 border-wedding-gold/20'">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M7 2v11h3v9l7-12h-4l4-8z"/>
                        </svg>
                    </button>
                    <span class="text-[9px] uppercase tracking-wider text-wedding-dark/60 font-semibold mt-1"
                          :class="flashEnabled ? 'text-wedding-filmOrange font-bold' : ''"
                          x-text="flashEnabled ? '⚡ Flash ON' : 'Flash OFF'"></span>
                </div>

                <!-- Flip Camera Button (Selfie vs Rear) -->
                <div class="flex flex-col items-center">
                    <button @click="flipCamera()"
                            type="button"
                            class="w-10 h-10 rounded-xl bg-wedding-linen text-wedding-dark/70 border border-wedding-gold/20 flex items-center justify-center hover:bg-wedding-gold/20 transition-colors shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                        </svg>
                    </button>
                    <span class="text-[9px] uppercase tracking-wider text-wedding-dark/60 font-semibold mt-1"
                          x-text="facingMode === 'user' ? 'Selfie' : 'Kamera'"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Info & Dev Reset -->
    <footer class="w-full max-w-md pb-6 px-4 flex flex-col items-center text-center z-20 space-y-2">
        <p class="text-xs text-white/60 font-mono">
            Tersisa: <span class="text-wedding-filmOrange font-bold" x-text="remainingShots"></span> / <span x-text="maxShots"></span> Foto
        </p>

        <!-- Dev Reset Button (Testing Mode) -->
        <button @click="resetRoll()"
                type="button"
                class="inline-flex items-center space-x-1.5 px-3 py-1 bg-white/10 hover:bg-white/20 text-white/70 hover:text-white rounded-full text-[11px] transition-colors border border-white/10">
            <svg class="w-3.5 h-3.5 text-wedding-gold" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
            </svg>
            <span>[Dev] Reset Roll (0/36)</span>
        </button>
    </footer>

    <!-- PRIVATE "ROLL SAYA" SLIDE-OVER DRAWER -->
    <div x-show="showMyGallery"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-full"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-full"
         class="fixed inset-0 z-40 bg-black/80 backdrop-blur-md flex flex-col justify-end"
         style="display: none;"
         @keydown.escape.window="showMyGallery = false">

        <div class="w-full max-w-lg mx-auto bg-[#FAF6F0] rounded-t-[2.5rem] p-5 max-h-[85vh] flex flex-col shadow-2xl">
            <!-- Drawer Header -->
            <div class="flex items-center justify-between pb-3 border-b border-wedding-gold/20">
                <div>
                    <span class="text-[10px] uppercase tracking-wider text-wedding-olive font-bold block">Koleksi Pribadi Perangkat Ini</span>
                    <h2 class="font-serif text-lg font-bold text-wedding-dark">
                        Roll Foto Saya (<span x-text="myPhotos.length"></span>/36 Foto)
                    </h2>
                </div>
                <button @click="showMyGallery = false"
                        type="button"
                        class="p-2 rounded-full bg-wedding-linen hover:bg-wedding-gold/20 text-wedding-dark transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Photos Grid -->
            <div class="flex-1 overflow-y-auto py-4">
                <template x-if="myPhotos.length > 0">
                    <div class="grid grid-cols-2 gap-3">
                        <template x-for="photo in myPhotos" :key="photo.id">
                            <div @click="previewPhoto = photo"
                                 class="bg-white p-2 rounded-xl border border-wedding-gold/20 shadow-sm cursor-pointer group">
                                <div class="relative w-full aspect-[4/3] rounded-lg overflow-hidden bg-black/5">
                                    <img :src="photo.thumbnail_url"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                    <div class="absolute bottom-1 right-1 film-date-stamp text-[9px] pointer-events-none select-none">
                                        '{{ date('y m d') }}
                                    </div>
                                </div>
                                <div class="mt-1.5 px-1 flex items-center justify-between text-[10px] text-wedding-subtle">
                                    <span class="font-mono font-bold text-wedding-dark" x-text="'#' + photo.shot_number"></span>
                                    <span x-text="photo.taken_at"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="myPhotos.length === 0">
                    <div class="text-center py-12 text-wedding-subtle text-xs">
                        <div class="w-12 h-12 mx-auto mb-2 bg-wedding-linen text-wedding-gold rounded-full flex items-center justify-center">
                            📷
                        </div>
                        <p class="font-semibold text-wedding-dark">Belum ada foto yang diambil.</p>
                        <p class="text-[11px] mt-1">Arahkan kamera lalu tekan tombol Shutter untuk mulai mengisi roll ini!</p>
                    </div>
                </template>
            </div>

            <!-- Drawer Footer Banner -->
            <div class="pt-3 border-t border-wedding-gold/20 flex flex-col space-y-2 text-center">
                <p class="text-[11px] text-wedding-subtle">
                    Halaman ini hanya menampilkan jepretan dari perangkat Anda.
                </p>
                <a :href="landingUrl"
                   class="w-full py-2.5 px-4 rounded-xl bg-wedding-linen hover:bg-wedding-gold/20 text-wedding-dark font-semibold text-xs border border-wedding-gold/30 transition-colors flex items-center justify-center space-x-1.5">
                    <span>Lihat Galeri Bersama Seluruh Tamu di Halaman Event</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Full Photo Lightbox (from My Shots) -->
    <div x-show="previewPhoto"
         x-transition
         class="fixed inset-0 bg-black/95 z-50 flex items-center justify-center p-4"
         style="display: none;"
         @keydown.escape.window="previewPhoto = null">
        <div class="relative max-w-lg w-full bg-[#FAF6F0] rounded-2xl p-4 flex flex-col items-center"
             @click.away="previewPhoto = null">
            <button @click="previewPhoto = null"
                    type="button"
                    class="absolute top-3 right-3 p-1.5 bg-black/10 rounded-full text-wedding-dark hover:bg-black/20">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <div class="w-full aspect-[4/3] rounded-xl overflow-hidden bg-black my-2">
                <img :src="previewPhoto ? previewPhoto.url : ''" class="w-full h-full object-contain">
            </div>
            <div class="w-full flex items-center justify-between text-xs mt-2 px-1">
                <span class="font-mono font-bold text-wedding-dark" x-text="'Shot #' + (previewPhoto ? previewPhoto.shot_number : '')"></span>
                <a :href="previewPhoto ? previewPhoto.url : '#'"
                   download
                   class="py-1.5 px-3 rounded-lg bg-wedding-dark text-white text-[11px] font-semibold hover:bg-black transition-colors">
                    Unduh Foto
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function disposableCamera(config) {
    return {
        slug: config.slug,
        csrf: config.csrf,
        shotsTaken: config.initialShots,
        maxShots: config.maxShots,
        captureUrl: config.captureUrl,
        resetUrl: config.resetUrl,
        galleryUrl: config.galleryUrl,
        landingUrl: config.landingUrl,
        myPhotos: config.initialMyPhotos || [],
        showMyGallery: false,
        previewPhoto: null,
        facingMode: 'environment', // environment or user (selfie)
        flashEnabled: false,
        isFlashing: false,
        isProcessing: false,
        cameraError: null,
        stream: null,
        audioCtx: null,

        get remainingShots() {
            return Math.max(0, this.maxShots - this.shotsTaken);
        },

        get isRollEmpty() {
            return this.shotsTaken >= this.maxShots;
        },

        async initCamera() {
            this.initAudio();
            await this.startStream();
        },

        initAudio() {
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (AudioContext) {
                    this.audioCtx = new AudioContext();
                }
            } catch (e) {
                console.warn('AudioContext not supported', e);
            }
        },

        // Play synthetic realistic mechanical shutter click
        playShutterSound() {
            if (!this.audioCtx) return;
            try {
                if (this.audioCtx.state === 'suspended') {
                    this.audioCtx.resume();
                }
                const osc = this.audioCtx.createOscillator();
                const gain = this.audioCtx.createGain();
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(850, this.audioCtx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(80, this.audioCtx.currentTime + 0.05);
                gain.gain.setValueAtTime(0.85, this.audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, this.audioCtx.currentTime + 0.05);
                osc.connect(gain);
                gain.connect(this.audioCtx.destination);
                osc.start();
                osc.stop(this.audioCtx.currentTime + 0.06);

                setTimeout(() => {
                    if (!this.audioCtx) return;
                    const osc2 = this.audioCtx.createOscillator();
                    const gain2 = this.audioCtx.createGain();
                    osc2.type = 'square';
                    osc2.frequency.setValueAtTime(650, this.audioCtx.currentTime);
                    osc2.frequency.exponentialRampToValueAtTime(60, this.audioCtx.currentTime + 0.06);
                    gain2.gain.setValueAtTime(0.75, this.audioCtx.currentTime);
                    gain2.gain.exponentialRampToValueAtTime(0.01, this.audioCtx.currentTime + 0.06);
                    osc2.connect(gain2);
                    gain2.connect(this.audioCtx.destination);
                    osc2.start();
                    osc2.stop(this.audioCtx.currentTime + 0.07);
                }, 60);
            } catch (err) {
                console.warn('Audio error', err);
            }
        },

        async startStream() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }

            this.cameraError = null;

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                this.cameraError = 'Live kamera browser memerlukan koneksi HTTPS di HP. Tekan tombol di bawah untuk memotret langsung!';
                return;
            }

            try {
                const constraints = {
                    audio: false,
                    video: {
                        facingMode: { ideal: this.facingMode },
                        width: { ideal: 1920 }
                    }
                };
                this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            } catch (err1) {
                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({
                        audio: false,
                        video: { facingMode: this.facingMode }
                    });
                } catch (err2) {
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                    } catch (err3) {
                        console.error('Kamera gagal diakses:', err3);
                        this.cameraError = 'Live kamera tidak dapat dinyalakan. Gunakan tombol kamera langsung di bawah!';
                        return;
                    }
                }
            }

            if (this.stream && this.$refs.videoElement) {
                this.$refs.videoElement.srcObject = this.stream;
                this.$refs.videoElement.onloadedmetadata = () => {
                    this.$refs.videoElement.play().catch(e => console.warn('Autoplay prevented:', e));
                };
            }
        },

        async flipCamera() {
            this.facingMode = this.facingMode === 'environment' ? 'user' : 'environment';
            if (this.stream) {
                await this.startStream();
            }
        },

        async toggleFlash() {
            this.flashEnabled = !this.flashEnabled;

            // Test if hardware torch is available
            if (this.stream && this.facingMode === 'environment') {
                const track = this.stream.getVideoTracks()[0];
                if (track && track.getCapabilities && track.getCapabilities().torch) {
                    try {
                        await track.applyConstraints({
                            advanced: [{ torch: this.flashEnabled }]
                        });
                    } catch (e) {
                        console.warn('Hardware torch toggle notice:', e);
                    }
                }
            }
        },

        triggerNativeCamera() {
            this.$refs.nativeFileInput.click();
        },

        async takePhoto() {
            if (this.isRollEmpty || this.isProcessing) return;

            if (!this.stream || this.cameraError) {
                this.triggerNativeCamera();
                return;
            }

            this.isProcessing = true;
            this.playShutterSound();

            // Hardware Torch pulse if available
            if (this.flashEnabled && this.stream) {
                const track = this.stream.getVideoTracks()[0];
                if (track && track.getCapabilities && track.getCapabilities().torch) {
                    try {
                        await track.applyConstraints({ advanced: [{ torch: true }] });
                    } catch (e) {}
                }
            }

            // Screen Flash Burst
            if (this.flashEnabled) {
                this.isFlashing = true;
                await new Promise(r => setTimeout(r, 120));
            }

            if (navigator.vibrate) {
                navigator.vibrate(50);
            }

            const video = this.$refs.videoElement;
            const canvas = this.$refs.captureCanvas;
            const ctx = canvas.getContext('2d');

            const width = video.videoWidth || 1280;
            const height = video.videoHeight || 960;
            canvas.width = width;
            canvas.height = height;

            ctx.save();
            if (this.facingMode === 'user') {
                ctx.translate(width, 0);
                ctx.scale(-1, 1);
            }
            ctx.drawImage(video, 0, 0, width, height);
            ctx.restore();

            // Turn off screen flash
            if (this.flashEnabled) {
                this.isFlashing = false;
                if (this.stream) {
                    const track = this.stream.getVideoTracks()[0];
                    if (track && track.getCapabilities && track.getCapabilities().torch) {
                        try {
                            await track.applyConstraints({ advanced: [{ torch: false }] });
                        } catch (e) {}
                    }
                }
            }

            // Apply Punchy Disposable Film Tone with High Whites & ISO 800 Grain
            this.applyDisposableFilter(ctx, width, height, this.flashEnabled);

            const imageData = canvas.toDataURL('image/jpeg', 0.90);
            await this.uploadPhotoData(imageData);
        },

        async handleNativePhoto(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.isProcessing = true;
            this.playShutterSound();

            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = async () => {
                    const canvas = this.$refs.captureCanvas;
                    const ctx = canvas.getContext('2d');

                    let width = img.width;
                    let height = img.height;
                    const maxDim = 1920;
                    if (width > maxDim || height > maxDim) {
                        if (width > height) {
                            height = Math.round((height * maxDim) / width);
                            width = maxDim;
                        } else {
                            width = Math.round((width * maxDim) / height);
                            height = maxDim;
                        }
                    }

                    canvas.width = width;
                    canvas.height = height;
                    ctx.drawImage(img, 0, 0, width, height);

                    // Apply Punchy Disposable Film Tone with High Whites & ISO 800 Grain
                    this.applyDisposableFilter(ctx, width, height, this.flashEnabled);

                    const imageData = canvas.toDataURL('image/jpeg', 0.90);
                    await this.uploadPhotoData(imageData);
                    event.target.value = '';
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        async uploadPhotoData(imageData) {
            try {
                const response = await fetch(this.captureUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        image: imageData,
                        camera_facing: this.facingMode,
                        flash_enabled: this.flashEnabled
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.shotsTaken = data.shots_taken;

                    // Add new photo to local myPhotos roll
                    if (data.photo) {
                        this.myPhotos.unshift({
                            id: data.photo.id,
                            shot_number: data.photo.shot_number,
                            url: data.photo.url,
                            thumbnail_url: data.photo.thumbnail_url,
                            taken_at: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                        });
                    }

                    setTimeout(() => {
                        this.isProcessing = false;
                    }, 600);
                } else {
                    alert(data.message || 'Gagal menyimpan foto.');
                    this.isProcessing = false;
                }
            } catch (error) {
                console.error('Upload foto error:', error);
                alert('Terjadi kesalahan saat memproses foto.');
                this.isProcessing = false;
            }
        },

        // Color grading replicating the disposable camera look:
        // - High punchy contrast & high whites
        // - ISO 800 organic gritty grain
        // - Natural corner-only vignette (no middle circle blob)
        // - Direct flash exposure pop
        applyDisposableFilter(ctx, width, height, flashOn) {
            const imgData = ctx.getImageData(0, 0, width, height);
            const data = imgData.data;

            const contrast = 1.26; // High punchy contrast
            const midShift = 4;

            for (let i = 0; i < data.length; i += 4) {
                const pixelIndex = i / 4;
                const px = pixelIndex % width;
                const py = Math.floor(pixelIndex / width);

                let r = data[i];
                let g = data[i + 1];
                let b = data[i + 2];

                // Direct Flash Simulation: Center-weighted bright exposure burst
                if (flashOn) {
                    const nx = (px - width / 2) / (width / 2);
                    const ny = (py - height / 2) / (height / 2);
                    const centerDist = Math.hypot(nx, ny);
                    const flashFactor = Math.max(0, 1 - centerDist * 0.78);
                    r += flashFactor * 46;
                    g += flashFactor * 44;
                    b += flashFactor * 38;
                }

                // S-Curve Contrast: Deep rich darks, intense pop highlights
                let nr = r / 255;
                let ng = g / 255;
                let nb = b / 255;

                // Lift whites aggressively (Disposable Camera High Whites)
                nr = Math.pow(nr, 0.90);
                ng = Math.pow(ng, 0.90);
                nb = Math.pow(nb, 0.94);

                nr = (nr - 0.5) * contrast + 0.5;
                ng = (ng - 0.5) * contrast + 0.5;
                nb = (nb - 0.5) * contrast + 0.5;

                r = nr * 255 + midShift;
                g = ng * 255 + midShift;
                b = nb * 255 + midShift;

                // High Whites / Creamy highlights
                if (r > 165) {
                    r = Math.min(255, r * 1.08 + 10);
                    g = Math.min(255, g * 1.06 + 8);
                }

                // Film Negative Shadow Character (Deep blacks with subtle tint)
                if (r < 70) {
                    r = Math.max(5, r * 0.95);
                    g = Math.max(8, g * 0.98 + 4);
                    b = Math.max(5, b * 0.92);
                }

                // Coarse ISO 800 Film Grain Simulation
                const luma = 0.299 * r + 0.587 * g + 0.114 * b;
                const grainLumaFactor = (1 - Math.abs(luma - 128) / 140) * 0.9 + 0.3;
                const grain = (Math.random() - 0.5) * 36 * grainLumaFactor;

                data[i] = Math.max(0, Math.min(255, r + grain));
                data[i + 1] = Math.max(0, Math.min(255, g + grain * 0.94));
                data[i + 2] = Math.max(0, Math.min(255, b + grain * 0.88));
            }
            ctx.putImageData(imgData, 0, 0);

            // 2. CORNER-ONLY NATURAL LENS SHADING (FIXED: NO CIRCLE IN CENTER)
            const maxRadius = Math.hypot(width, height) / 2;
            const gradient = ctx.createRadialGradient(
                width / 2, height / 2, maxRadius * 0.68,
                width / 2, height / 2, maxRadius * 1.05
            );
            gradient.addColorStop(0, 'rgba(0,0,0,0)');
            gradient.addColorStop(0.55, 'rgba(0,0,0,0)');
            gradient.addColorStop(0.85, 'rgba(12,8,4,0.14)');
            gradient.addColorStop(1, 'rgba(6,4,2,0.32)');

            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, width, height);

            // 3. Vintage Orange Quartz Date Stamp ('YY MM DD) in bottom-right corner
            const dateStr = `'${String(new Date().getFullYear()).slice(-2)} ${String(new Date().getMonth() + 1).padStart(2, '0')} ${String(new Date().getDate()).padStart(2, '0')}`;
            const fontSize = Math.max(22, Math.floor(width * 0.028));

            ctx.save();
            ctx.font = `bold ${fontSize}px "Share Tech Mono", monospace`;
            ctx.textAlign = 'right';
            ctx.textBaseline = 'bottom';
            ctx.shadowColor = '#FF7700';
            ctx.shadowBlur = fontSize * 0.4;
            ctx.fillStyle = '#FFAA33';
            ctx.fillText(dateStr, width - (fontSize * 1.2), height - (fontSize * 1.2));
            ctx.restore();
        },

        async resetRoll() {
            if (!confirm('Reset jatah roll kamera perangkat ini kembali ke 0/36?')) return;

            try {
                const response = await fetch(this.resetUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ clear_photos: false })
                });

                const data = await response.json();
                if (data.success) {
                    this.shotsTaken = 0;
                    this.myPhotos = [];
                    alert('Roll kamera berhasil di-reset ke 0/36! Anda dapat memotret kembali.');
                }
            } catch (err) {
                console.error(err);
                alert('Gagal me-reset roll kamera.');
            }
        }
    };
}
</script>
@endpush
