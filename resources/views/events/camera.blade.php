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
        galleryUrl: '{{ route('event.gallery', $event->slug) }}'
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
        <a href="{{ route('event.landing', $event->slug) }}" class="p-2 -ml-2 rounded-full hover:bg-white/10 transition-colors">
            <svg class="w-5 h-5 text-white/70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
        </a>

        <div class="text-center">
            <span class="text-[10px] uppercase tracking-widest text-wedding-gold font-semibold block">Disposable Camera</span>
            <h1 class="font-serif text-sm tracking-wide text-white/90 truncate max-w-[200px]">{{ $event->title }}</h1>
        </div>

        <a href="{{ route('event.gallery', $event->slug) }}" class="p-2 -mr-2 rounded-full hover:bg-white/10 text-white/80 transition-colors" title="Buka Galeri">
            <svg class="w-5 h-5 text-wedding-gold" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
            </svg>
        </a>
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
                    <span>ISO 400 Film</span>
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
                    <p class="text-xs font-semibold text-wedding-ivory mb-1">Mode Kamera Smartphone</p>
                    <p class="text-[11px] text-white/70 mb-3 px-2" x-text="cameraError"></p>

                    <button @click="triggerNativeCamera()"
                            type="button"
                            class="py-2 px-4 rounded-xl bg-gradient-to-r from-wedding-gold to-wedding-goldHover text-white text-xs font-semibold shadow hover:shadow-md transition-all flex items-center space-x-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                        </svg>
                        <span>Buka Kamera HP Langsung</span>
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
                    <h3 class="font-serif text-xl font-bold text-wedding-ivory mb-1">Roll Film Habis!</h3>
                    <p class="text-xs text-white/70 mb-4">Anda telah menggunakan seluruh jatah 36 foto. Semua foto sudah masuk ke galeri acara.</p>
                    <a :href="galleryUrl" class="w-full py-2.5 px-4 bg-gradient-to-r from-wedding-gold to-wedding-goldHover text-white rounded-xl text-xs font-semibold shadow">
                        Buka Galeri Event
                    </a>
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

            <!-- Controls Panel: Shutter, Film Counter, Flash & Winder Wheel -->
            <div class="flex items-center justify-between pt-1">
                <!-- Mechanical Film Counter Window -->
                <div class="flex flex-col items-center">
                    <div class="bg-[#18181b] border-2 border-[#3f3f46] rounded-lg px-2.5 py-1 text-center shadow-inner">
                        <span class="font-mono text-sm font-bold text-wedding-filmOrange tracking-wider"
                              x-text="String(remainingShots).padStart(2, '0')"></span>
                    </div>
                    <span class="text-[9px] uppercase tracking-wider text-wedding-dark/60 font-semibold mt-1">Sisa Film</span>
                </div>

                <!-- Mechanical Film Winder Wheel -->
                <div class="flex flex-col items-center">
                    <div class="w-9 h-14 winder-wheel rounded-md border border-black/60 shadow-inner"
                         :class="isWinding ? 'animate-pulse' : ''"></div>
                    <span class="text-[9px] uppercase tracking-wider text-wedding-dark/60 font-semibold mt-1">Winder</span>
                </div>

                <!-- Shutter Button -->
                <div class="flex flex-col items-center">
                    <button @click="takePhoto()"
                            :disabled="isRollEmpty || isProcessing || isWinding"
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
                            :class="flashEnabled ? 'bg-wedding-filmOrange text-white border-amber-600' : 'bg-wedding-linen text-wedding-dark/60 border-wedding-gold/20'">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M7 2v11h3v9l7-12h-4l4-8z"/>
                        </svg>
                    </button>
                    <span class="text-[9px] uppercase tracking-wider text-wedding-dark/60 font-semibold mt-1"
                          x-text="flashEnabled ? 'Flash ON' : 'Flash OFF'"></span>
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

    <!-- Bottom Controls & Dev Testing Reset -->
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
            <span>[Dev] Reset Roll (Kembali ke 0/36)</span>
        </button>
    </footer>
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
        facingMode: 'environment', // environment or user (selfie)
        flashEnabled: false,
        isFlashing: false,
        isProcessing: false,
        isWinding: false,
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
                osc.frequency.setValueAtTime(800, this.audioCtx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(100, this.audioCtx.currentTime + 0.04);
                gain.gain.setValueAtTime(0.8, this.audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, this.audioCtx.currentTime + 0.04);
                osc.connect(gain);
                gain.connect(this.audioCtx.destination);
                osc.start();
                osc.stop(this.audioCtx.currentTime + 0.05);

                setTimeout(() => {
                    if (!this.audioCtx) return;
                    const osc2 = this.audioCtx.createOscillator();
                    const gain2 = this.audioCtx.createGain();
                    osc2.type = 'square';
                    osc2.frequency.setValueAtTime(600, this.audioCtx.currentTime);
                    osc2.frequency.exponentialRampToValueAtTime(80, this.audioCtx.currentTime + 0.06);
                    gain2.gain.setValueAtTime(0.7, this.audioCtx.currentTime);
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

        // Play mechanical film winding sound
        playWindingSound() {
            if (!this.audioCtx) return;
            try {
                for (let i = 0; i < 4; i++) {
                    setTimeout(() => {
                        if (!this.audioCtx) return;
                        const osc = this.audioCtx.createOscillator();
                        const gain = this.audioCtx.createGain();
                        osc.type = 'sawtooth';
                        osc.frequency.setValueAtTime(1200 - (i * 100), this.audioCtx.currentTime);
                        osc.frequency.exponentialRampToValueAtTime(300, this.audioCtx.currentTime + 0.03);
                        gain.gain.setValueAtTime(0.3, this.audioCtx.currentTime);
                        gain.gain.exponentialRampToValueAtTime(0.01, this.audioCtx.currentTime + 0.03);
                        osc.connect(gain);
                        gain.connect(this.audioCtx.destination);
                        osc.start();
                        osc.stop(this.audioCtx.currentTime + 0.04);
                    }, i * 70);
                }
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

            // Security context check
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                this.cameraError = 'Live kamera browser memerlukan koneksi HTTPS di HP. Tekan tombol di bawah untuk memotret langsung!';
                return;
            }

            try {
                // Attempt 1: Ideal facingMode
                const constraints = {
                    audio: false,
                    video: {
                        facingMode: { ideal: this.facingMode },
                        width: { ideal: 1280 }
                    }
                };

                this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            } catch (err1) {
                try {
                    // Attempt 2: Basic facingMode
                    this.stream = await navigator.mediaDevices.getUserMedia({
                        audio: false,
                        video: { facingMode: this.facingMode }
                    });
                } catch (err2) {
                    try {
                        // Attempt 3: Any video device
                        this.stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                    } catch (err3) {
                        console.error('Kamera gagal diakses:', err3);
                        if (err3.name === 'NotAllowedError' || err3.name === 'PermissionDeniedError') {
                            this.cameraError = 'Izin kamera ditolak. Silakan izinkan akses kamera di pengaturan browser.';
                        } else {
                            this.cameraError = 'Live kamera tidak dapat dinyalakan. Gunakan tombol kamera langsung di bawah!';
                        }
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

        toggleFlash() {
            this.flashEnabled = !this.flashEnabled;
        },

        triggerNativeCamera() {
            this.$refs.nativeFileInput.click();
        },

        // Triggered when user snaps photo
        async takePhoto() {
            if (this.isRollEmpty || this.isProcessing || this.isWinding) return;

            // If stream is not active or error, use native camera capture
            if (!this.stream || this.cameraError) {
                this.triggerNativeCamera();
                return;
            }

            this.isProcessing = true;
            this.playShutterSound();

            if (this.flashEnabled) {
                this.isFlashing = true;
                setTimeout(() => { this.isFlashing = false; }, 180);
            }

            if (navigator.vibrate) {
                navigator.vibrate(40);
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

            // Apply Disposable Retro Film Tone
            this.applyDisposableFilter(ctx, width, height, this.flashEnabled);

            const imageData = canvas.toDataURL('image/jpeg', 0.88);
            await this.uploadPhotoData(imageData);
        },

        // Handles photo taken via native device camera input
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

                    // Limit max resolution for faster mobile upload
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

                    // Apply Disposable Retro Film Tone
                    this.applyDisposableFilter(ctx, width, height, this.flashEnabled);

                    const imageData = canvas.toDataURL('image/jpeg', 0.88);
                    await this.uploadPhotoData(imageData);
                    // Reset input
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

                    // Play winding animation and audio
                    this.isWinding = true;
                    this.playWindingSound();
                    setTimeout(() => {
                        this.isWinding = false;
                        this.isProcessing = false;
                    }, 1200);
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

        // Color grading replicating the disposable camera samples in attachment/
        applyDisposableFilter(ctx, width, height, flashOn) {
            const imgData = ctx.getImageData(0, 0, width, height);
            const data = imgData.data;

            // 1. Color Curve & Shadow Matte Green Tint
            for (let i = 0; i < data.length; i += 4) {
                let r = data[i];
                let g = data[i + 1];
                let b = data[i + 2];

                // Lift shadows (matte black effect)
                r = r * 0.92 + 18;
                g = g * 0.94 + 19;
                b = b * 0.88 + 14;

                // Warm golden highlights
                if (r > 160) r = Math.min(255, r * 1.05);
                if (g > 160) g = Math.min(255, g * 1.03);
                if (b > 160) b = Math.min(255, b * 0.95);

                // Subtle organic color film grain
                const grain = (Math.random() - 0.5) * 16;
                data[i] = Math.max(0, Math.min(255, r + grain));
                data[i + 1] = Math.max(0, Math.min(255, g + grain * 0.9));
                data[i + 2] = Math.max(0, Math.min(255, b + grain * 0.8));
            }
            ctx.putImageData(imgData, 0, 0);

            // 2. Plastic Lens Radial Vignette (Stronger if Flash is ON)
            const gradient = ctx.createRadialGradient(
                width / 2, height / 2, width * (flashOn ? 0.25 : 0.4),
                width / 2, height / 2, width * 0.75
            );
            gradient.addColorStop(0, 'rgba(0,0,0,0)');
            gradient.addColorStop(0.8, 'rgba(10,8,6,0.18)');
            gradient.addColorStop(1, flashOn ? 'rgba(0,0,0,0.55)' : 'rgba(0,0,0,0.38)');

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
            ctx.shadowBlur = fontSize * 0.35;
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
