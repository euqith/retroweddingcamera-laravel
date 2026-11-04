@extends('layouts.app', ['title' => 'Kamera — ' . $event->title])

@php
    $theme = $event->getThemeStyles();
    $cameraStatus = $cameraStatus ?? $event->cameraStatus();
@endphp

@section('content')
<div x-data="disposableCamera({
        slug: '{{ $event->slug }}',
        csrf: '{{ csrf_token() }}',
        initialShots: {{ $session->shots_taken }},
        maxShots: {{ $session->max_shots }},
        eventTitle: '{{ addslashes($event->title) }}',
        filterPreset: '{{ $event->filter_preset }}',
        captureUrl: '{{ route('event.camera.capture', $event->slug) }}',
        galleryUrl: '{{ route('event.gallery', $event->slug) }}',
        landingUrl: '{{ route('event.landing', $event->slug) }}',
        cameraStatus: '{{ $cameraStatus }}',
        initialMyPhotos: {{ json_encode($myPhotos) }}
    })"
    x-init="initCamera()"
    class="relative min-h-screen {{ $theme['page_bg'] }} flex flex-col items-center justify-between overflow-hidden select-none transition-colors duration-300">

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
            <span class="text-[10px] uppercase tracking-widest {{ $theme['accent_color'] }} font-bold block">
                {{ $theme['decal_icon'] }} {{ $theme['name'] }}
            </span>
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

    @if($cameraStatus !== 'open')
        <!-- EVENT DURATION GATE (LOCKED / CLOSED CAMERA) -->
        <div class="relative w-full max-w-sm my-auto px-4 flex flex-col items-center z-20">
            <div class="w-full {{ $theme['body_bg'] }} rounded-[2.5rem] p-6 shadow-[0_20px_50px_rgba(0,0,0,0.8)] border-4 {{ $theme['body_border'] }} text-center relative overflow-hidden transition-all">
                @if($event->theme_preset === 'classic_film')
                    <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-red-600 via-amber-400 to-black"></div>
                @elseif($event->theme_preset === 'sacred_olive')
                    <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-[#D4AF37] via-[#8FBC8F] to-[#D4AF37]"></div>
                @endif

                <!-- Vintage Lock Status Icon -->
                <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-black/10 border-2 {{ $theme['header_border'] }} flex items-center justify-center text-3xl shadow-inner mt-2">
                    {{ $cameraStatus === 'upcoming' ? '⏳' : '🎞️' }}
                </div>

                <span class="text-[10px] font-mono font-bold uppercase tracking-widest {{ $theme['accent_color'] }} block mb-1">
                    {{ $cameraStatus === 'upcoming' ? 'Kamera Belum Dibuka' : 'Periode Foto Telah Selesai' }}
                </span>

                <h2 class="font-serif text-2xl font-bold {{ $theme['title_color'] }} mb-2">
                    {{ $event->title }}
                </h2>

                <div class="p-4 rounded-2xl bg-black/10 border {{ $theme['header_border'] }} mb-4 text-xs text-left">
                    @if($cameraStatus === 'upcoming')
                        <span class="text-[10px] uppercase tracking-wider {{ $theme['sub_color'] }} font-semibold block mb-1">Jadwal Kamera Dibuka:</span>
                        <span class="font-bold {{ $theme['title_color'] }} text-sm block">
                            {{ $event->starts_at ? $event->starts_at->translatedFormat('l, d F Y - H:i') . ' WIB' : 'Jadwal Segera Dimulai' }}
                        </span>
                        <p class="text-[11px] {{ $theme['sub_color'] }} mt-2 leading-relaxed">
                            Kamera disposable digital untuk acara ini akan aktif otomatis saat acara dimulai. Siapkan senyum terbaik Anda!
                        </p>
                    @else
                        <span class="text-[10px] uppercase tracking-wider {{ $theme['sub_color'] }} font-semibold block mb-1">Acara Selesai Pada:</span>
                        <span class="font-bold {{ $theme['title_color'] }} text-sm block">
                            {{ $event->ends_at ? $event->ends_at->translatedFormat('l, d F Y - H:i') . ' WIB' : 'Acara Telah Berakhir' }}
                        </span>
                        <p class="text-[11px] {{ $theme['sub_color'] }} mt-2 leading-relaxed">
                            Periode pengambilan foto tamu untuk acara ini telah ditutup. Seluruh foto yang berhasil diabadikan tersimpan aman dan dapat dinikmati di galeri bersama!
                        </p>
                    @endif
                </div>

                <div class="space-y-2">
                    <a href="{{ route('event.gallery', $event->slug) }}"
                       class="w-full py-3.5 px-4 rounded-xl bg-wedding-dark text-white font-semibold text-xs shadow hover:bg-black transition-colors flex items-center justify-center space-x-2">
                        <span>Buka Galeri Foto Bersama Tamu</span>
                        <span>➔</span>
                    </a>

                    <a href="{{ route('event.landing', $event->slug) }}"
                       class="w-full py-2.5 px-4 rounded-xl bg-black/10 hover:bg-black/20 {{ $theme['title_color'] }} font-semibold text-xs transition-colors block">
                        Kembali ke Info Acara
                    </a>
                </div>
            </div>
        </div>
    @else
        <!-- Skeuomorphic Disposable Camera Body -->
        <div class="relative w-full max-w-sm my-auto px-4 flex flex-col items-center">
            <div class="w-full {{ $theme['body_bg'] }} rounded-[2.5rem] p-5 shadow-[0_20px_50px_rgba(0,0,0,0.8)] border-4 {{ $theme['body_border'] }} relative overflow-hidden transition-all duration-300">
                <!-- Theme Specific Decals -->
                @if($event->theme_preset === 'classic_film')
                    <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-red-600 via-amber-400 to-black"></div>
                    <div class="absolute -top-6 -right-6 w-16 h-16 bg-black/10 rounded-full pointer-events-none"></div>
                @elseif($event->theme_preset === 'sacred_olive')
                    <div class="absolute top-0 left-0 right-0 h-2 bg-gradient-to-r from-[#D4AF37] via-[#8FBC8F] to-[#D4AF37]"></div>
                    <div class="absolute top-3 left-4 text-xs opacity-40 select-none pointer-events-none">🌿</div>
                    <div class="absolute top-3 right-4 text-xs opacity-40 select-none pointer-events-none">🌿</div>
                @else
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-transparent via-wedding-gold/40 to-transparent"></div>
                @endif

                <!-- Vintage Paper Sleeve Texture & Header -->
                <div class="text-center pb-3 border-b {{ $theme['header_border'] }}">
                    <div class="text-[9px] uppercase tracking-widest font-mono font-bold {{ $theme['accent_color'] }} mb-0.5">
                        {{ $theme['decal_icon'] }} {{ $theme['tag'] }}
                    </div>
                    <p class="font-serif text-lg tracking-wide {{ $theme['title_color'] }} font-bold truncate">
                        {{ $event->title }}
                    </p>
                    <div class="flex items-center justify-center space-x-2 text-[10px] tracking-wider {{ $theme['sub_color'] }} uppercase font-semibold">
                        <span>{{ $event->max_shots_per_device }} Exposures</span>
                        <span>•</span>
                        <!-- Film Simulation Switcher Badge -->
                        <button @click="showFilmModal = true"
                                type="button"
                                class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full {{ $theme['badge_style'] }} font-mono text-[9px] font-bold tracking-wider transition-colors shadow-xs"
                                title="Ganti Simulasi Film">
                            <span>🎞️</span>
                            <span x-text="getActiveFilm().badge"></span>
                            <span class="text-[8px] opacity-70">▼</span>
                        </button>
                    </div>
                </div>

            <!-- Viewfinder Area -->
            <div class="relative w-full aspect-[4/3] bg-black rounded-2xl overflow-hidden my-4 border-4 border-[#18181b] shadow-inner">
                <!-- Video Stream for Live WebRTC with Real-Time Film Simulation Preview -->
                <video x-ref="videoElement"
                       autoplay
                       playsinline
                       muted
                       class="w-full h-full object-cover"
                       :style="getVideoFilterStyle()"></video>

                <!-- Hidden Canvas for Tone Processing -->
                <canvas x-ref="captureCanvas" class="hidden"></canvas>

                <!-- Live Film Grain Overlay on Viewfinder -->
                <div class="absolute inset-0 pointer-events-none mix-blend-overlay opacity-25"
                     style="background-image: url('data:image/svg+xml,%3Csvg viewBox=\'0 0 200 200\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cfilter id=\'noiseFilter\'%3E%3CfeTurbulence type=\'fractalNoise\' baseFrequency=\'0.8\' numOctaves=\'3\' stitchTiles=\'stitch\'/%3E%3C/filter%3E%3Crect width=\'100%25\' height=\'100%25\' filter=\'url(%23noiseFilter)\' opacity=\'0.5\'/%3E%3C/svg%3E'); background-repeat: repeat;">
                </div>

                <!-- Live Corner Vignette Overlay on Viewfinder -->
                <div class="absolute inset-0 pointer-events-none transition-opacity duration-300"
                     :style="{
                         background: 'radial-gradient(circle, rgba(0,0,0,0) 55%, rgba(12,8,4,' + (getVignetteOpacity() * 0.45) + ') 85%, rgba(6,4,2,' + getVignetteOpacity() + ') 100%)'
                     }">
                </div>

                <!-- Viewfinder Mask & Optical Crosshair -->
                <div x-show="!cameraError && !isRollEmpty"
                     class="absolute inset-0 border border-white/20 pointer-events-none rounded-xl m-2 flex items-center justify-center">
                    <div class="w-8 h-8 border border-white/30 rounded-full flex items-center justify-center">
                        <div class="w-1.5 h-1.5 bg-white/40 rounded-full"></div>
                    </div>
                </div>

                <!-- Live Quartz Date Stamp Overlay Preview -->
                <div class="absolute bottom-3 right-3 film-date-stamp text-xs pointer-events-none select-none transition-all duration-300"
                     :style="{
                         color: getDateColor(),
                         textShadow: '0 0 6px ' + getDateColor()
                     }">
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
                </div>

                <!-- Optical Mechanical Shutter Blink Effect -->
                <div x-show="isShutterBlinking"
                     class="absolute inset-0 bg-white z-30 pointer-events-none transition-opacity duration-100"
                     style="display: none;"></div>

                <!-- Non-blocking Vintage Developing Indicator -->
                <div x-show="isDeveloping"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                     class="absolute top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-black/85 backdrop-blur-md border border-wedding-gold/60 text-wedding-gold text-[10px] font-mono font-bold flex items-center space-x-2 shadow-xl z-20 pointer-events-none"
                     style="display: none;">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                    <span x-text="statusMessage || '🎞️ Developing Film...'"></span>
                </div>
            </div>

            <!-- QUICK HORIZONTAL FILM STOCK CAROUSEL / SELECTOR -->
            <div class="my-2 pb-1">
                <div class="flex items-center justify-between px-0.5 mb-1.5">
                    <div class="flex items-center space-x-1.5">
                        <span class="text-[10px] uppercase font-bold tracking-wider text-wedding-dark/80">🎞️ Roll Film:</span>
                        <span class="text-[11px] font-mono font-bold text-wedding-filmOrange" x-text="getActiveFilm().name"></span>
                    </div>
                    <button @click="showFilmModal = true"
                            type="button"
                            class="text-[10px] text-wedding-gold hover:text-wedding-goldHover font-bold tracking-wider uppercase underline">
                        Semua (7) ➔
                    </button>
                </div>
                <div class="flex items-center space-x-2 overflow-x-auto py-1 px-0.5 no-scrollbar">
                    <template x-for="preset in filmPresets" :key="preset.id">
                        <button @click="setFilm(preset.id)"
                                type="button"
                                class="flex-shrink-0 px-3 py-1.5 rounded-xl border text-xs font-mono font-bold transition-all shadow-xs flex items-center space-x-1.5"
                                :class="currentPreset === preset.id 
                                    ? 'bg-[#231F1E] text-wedding-gold border-wedding-gold ring-2 ring-wedding-gold/40 scale-105 shadow' 
                                    : 'bg-white/90 text-wedding-dark/70 border-wedding-gold/30 hover:bg-white hover:border-wedding-gold'">
                            <span class="w-1.5 h-1.5 rounded-full"
                                  :class="currentPreset === preset.id ? 'bg-wedding-gold' : 'bg-gray-300'"></span>
                            <span x-text="preset.badge"></span>
                        </button>
                    </template>
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
                            :disabled="isRollEmpty || isShutterCooldown"
                            class="w-16 h-16 rounded-full shutter-btn border-4 border-[#3f3f46] flex items-center justify-center transition-all disabled:opacity-40 disabled:cursor-not-allowed active:scale-95 active:shadow-inner"
                            :class="isShutterCooldown ? 'opacity-70 scale-95' : ''">
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

    <!-- Bottom Info -->
    <footer class="w-full max-w-md pb-6 px-4 flex flex-col items-center text-center z-20 space-y-1">
        <p class="text-xs text-white/60 font-mono">
            Tersisa: <span class="text-wedding-filmOrange font-bold" x-text="remainingShots"></span> / <span x-text="maxShots"></span> Foto
        </p>
    </footer>
    @endif

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

    <!-- FILM PRESET SELECTOR MODAL / BOTTOM SHEET -->
    <div x-show="showFilmModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
         style="display: none;"
         @keydown.escape.window="showFilmModal = false">

        <div class="w-full max-w-md bg-[#FAF6F0] rounded-t-[2.5rem] sm:rounded-3xl p-6 shadow-2xl border-t-2 sm:border-2 border-wedding-gold/40 max-h-[85vh] flex flex-col"
             @click.away="showFilmModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-wedding-gold/20 mb-3">
                <div>
                    <span class="text-[10px] uppercase tracking-widest text-wedding-gold font-bold block">Pilih Karakter Film</span>
                    <h3 class="font-serif text-xl font-bold text-wedding-dark">Simulasi Roll Film 35mm</h3>
                </div>
                <button @click="showFilmModal = false" class="p-2 text-wedding-dark/60 hover:text-wedding-dark rounded-full text-base">
                    ✕
                </button>
            </div>

            <div class="overflow-y-auto space-y-2.5 pr-1 flex-1">
                <template x-for="preset in filmPresets" :key="preset.id">
                    <button @click="setFilm(preset.id); showFilmModal = false;"
                            type="button"
                            class="w-full text-left p-3.5 rounded-2xl border transition-all flex items-center justify-between"
                            :class="currentPreset === preset.id ? 'bg-wedding-linen border-wedding-gold shadow-sm ring-1 ring-wedding-gold' : 'bg-white border-wedding-gold/20 hover:border-wedding-gold/50'">
                        <div class="pr-2">
                            <div class="flex items-center space-x-2 mb-1">
                                <span class="font-mono text-[10px] font-bold px-2 py-0.5 rounded bg-[#231F1E] text-wedding-gold" x-text="preset.badge"></span>
                                <span class="font-serif text-sm font-bold text-wedding-dark" x-text="preset.name"></span>
                                <span x-show="preset.id === defaultPreset" class="text-[9px] px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 font-semibold border border-amber-300">Default Acara</span>
                            </div>
                            <span class="text-[11px] text-wedding-olive font-semibold block" x-text="preset.tag"></span>
                            <span class="text-[10px] text-wedding-subtle block mt-0.5 leading-snug" x-text="preset.desc"></span>
                        </div>
                        <div class="w-5 h-5 rounded-full border flex items-center justify-center flex-shrink-0"
                             :class="currentPreset === preset.id ? 'border-wedding-gold bg-wedding-gold text-white' : 'border-gray-300'">
                            <span x-show="currentPreset === preset.id" class="text-xs">✓</span>
                        </div>
                    </button>
                </template>
            </div>

            <div class="pt-3 border-t border-wedding-gold/20 text-center mt-3">
                <p class="text-[10px] text-wedding-subtle">
                    Setiap foto akan diproses dengan karakter kurva tone, saturasi warna, dan grain dari roll film yang dipilih. Pilihan ini hanya berlaku untuk perangkat ini.
                </p>
                <button x-show="currentPreset !== defaultPreset"
                        @click="setFilm(defaultPreset); showFilmModal = false;"
                        type="button"
                        class="mt-2 text-[11px] text-wedding-gold hover:text-wedding-goldHover underline font-semibold block mx-auto">
                    🔄 Kembalikan ke Preset Default Acara
                </button>
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
        galleryUrl: config.galleryUrl,
        landingUrl: config.landingUrl,
        myPhotos: config.initialMyPhotos || [],
        showMyGallery: false,
        previewPhoto: null,
        showFilmModal: false,
        defaultPreset: config.filterPreset || 'fuji_quicksnap',
        currentPreset: (function() {
            try {
                return localStorage.getItem('retro_film_' + config.slug) || config.filterPreset || 'fuji_quicksnap';
            } catch (e) {
                return config.filterPreset || 'fuji_quicksnap';
            }
        })(),

        // 7 Rich Authentic Vintage Analog Film Stock Recipes
        filmPresets: [
            { id: 'fuji_quicksnap', name: 'Fuji QuickSnap 400', badge: 'FUJI 400', tag: 'Natural & Emerald Shadows', desc: 'Tone sejuk jernih, shadow emerald & highlight putih pop khas kamera sekali pakai legendaris' },
            { id: 'kodak_funsaver', name: 'Kodak FunSaver 800', badge: 'KODAK 800', tag: 'Warm Amber & Coarse Grain', desc: 'Nuansa emas hangat, warna merah-kuning kaya, dan butiran grain kasar ISO 800' },
            { id: 'warm_portra', name: 'Kodak Portra 400', badge: 'PORTRA 400', tag: 'Soft Pastel Skin Tones', desc: 'Tone warna kulit lembut & creamy, kontras halus sangat cocok untuk momen wedding' },
            { id: 'ilford_bw', name: 'Ilford HP5 Plus 400', badge: 'ILFORD B&W', tag: 'Classic Silver Monochrome', desc: 'Hitam putih analog kontras tinggi dengan gradasi perak dramatis & grain klasik' },
            { id: 'cinestill_800t', name: 'CineStill 800T Cinema', badge: 'CINE 800T', tag: 'Tungsten Blues & Red Halation', desc: 'Nuansa film layar lebar malam hari, bayangan biru dingin dan kilau hangat pada lampu' },
            { id: 'agfa_vista', name: 'Agfa Vista 200', badge: 'AGFA 200', tag: 'Vibrant 90s Nostalgia', desc: 'Warna-warna primer cerah, saturasi merah-biru menyala ala snapshot pesta era 90-an' },
            { id: 'lomography_color', name: 'Lomography 400', badge: 'LOMO 400', tag: 'Toy Camera & Vignette', desc: 'Saturasi tinggi eksentrik, cross-process magenta-teal dan efek vignetting sudut pekat' }
        ],

        getActiveFilm() {
            return this.filmPresets.find(p => p.id === this.currentPreset) || this.filmPresets[0];
        },

        setFilm(presetId) {
            this.currentPreset = presetId;
            try {
                localStorage.setItem('retro_film_' + this.slug, presetId);
            } catch (e) {}
        },

        // Single Source of Truth for Film Simulation Curves
        getFilterString(preset = this.currentPreset) {
            switch (preset) {
                case 'ilford_bw':
                    return 'grayscale(100%) contrast(1.35) brightness(0.96)';
                case 'kodak_funsaver':
                    // Warm golden amber, rich tone rolloff
                    return 'sepia(0.24) saturate(1.28) contrast(1.24) brightness(1.02) hue-rotate(-10deg)';
                case 'warm_portra':
                    // Soft pastel wedding skin tones, gentle creamy rolloff
                    return 'sepia(0.12) saturate(1.10) contrast(1.12) brightness(1.04) hue-rotate(-4deg)';
                case 'cinestill_800t':
                    // Cinema tungsten cool shadows & amber highlights
                    return 'contrast(1.24) saturate(1.22) brightness(0.97) hue-rotate(14deg)';
                case 'agfa_vista':
                    // Vibrant 90s primary colors & punchy saturation
                    return 'contrast(1.26) saturate(1.38) brightness(1.02)';
                case 'lomography_color':
                    // Toy camera cross-process & deep shadows
                    return 'contrast(1.32) saturate(1.32) brightness(0.95) hue-rotate(-14deg)';
                default: // fuji_quicksnap
                    // Clean, emerald shadows & high whites pop
                    return 'contrast(1.24) brightness(1.04) saturate(1.12) hue-rotate(5deg)';
            }
        },

        // Real-Time Viewfinder Video Filter Simulation (GPU Accelerated)
        getVideoFilterStyle() {
            const mirror = this.facingMode === 'user' ? 'scaleX(-1)' : 'scaleX(1)';
            return {
                filter: this.getFilterString(this.currentPreset),
                transform: mirror,
                transition: 'filter 0.35s ease, transform 0.2s ease'
            };
        },

        getDateColor() {
            switch (this.currentPreset) {
                case 'ilford_bw': return '#EAEAEA';
                case 'kodak_funsaver': return '#FF7700';
                case 'warm_portra': return '#FFB866';
                case 'cinestill_800t': return '#FF4422';
                case 'agfa_vista': return '#FF8800';
                case 'lomography_color': return '#FF5500';
                default: return '#FFAA33';
            }
        },

        getVignetteOpacity() {
            return this.currentPreset === 'lomography_color' ? 0.46 : 0.26;
        },

        facingMode: 'environment', // environment or user (selfie)
        flashEnabled: false,
        isFlashing: false,
        isProcessing: false,
        isDeveloping: false,
        statusMessage: '',
        isShutterBlinking: false,
        isShutterCooldown: false,
        cameraError: null,
        stream: null,
        audioCtx: null,
        _noiseCanvas: null,

        // Authentic analog film grain tile centered at neutral mid-gray (128)
        // In overlay blend mode, 128 is 100% transparent and preserves rich contrast & saturation without milky haze
        getNoisePattern(ctx) {
            if (!this._noiseCanvas) {
                const nc = document.createElement('canvas');
                nc.width = 128;
                nc.height = 128;
                const nctx = nc.getContext('2d');
                const imgData = nctx.createImageData(128, 128);
                const data = imgData.data;
                for (let i = 0; i < data.length; i += 4) {
                    const v = (128 + (Math.random() - 0.5) * 64) | 0;
                    data[i] = v;
                    data[i + 1] = v;
                    data[i + 2] = v;
                    data[i + 3] = 255;
                }
                nctx.putImageData(imgData, 0, 0);
                this._noiseCanvas = nc;
            }
            return ctx.createPattern(this._noiseCanvas, 'repeat');
        },

        get remainingShots() {
            return Math.max(0, this.maxShots - this.shotsTaken);
        },

        get isRollEmpty() {
            return this.shotsTaken >= this.maxShots;
        },

        cameraStatus: config.cameraStatus || 'open',

        async initCamera() {
            if (this.cameraStatus !== 'open') return;
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

        toggleFlash() {
            // Only toggle the flash mode state. Do NOT turn on the hardware torch continuously.
            this.flashEnabled = !this.flashEnabled;
        },

        triggerNativeCamera() {
            this.$refs.nativeFileInput.click();
        },

        async takePhoto() {
            if (this.isRollEmpty || this.isShutterCooldown) return;

            if (!this.stream || this.cameraError) {
                this.triggerNativeCamera();
                return;
            }

            // Quick 400ms shutter cooldown (feels mechanical, prevents accidental double taps)
            this.isShutterCooldown = true;
            setTimeout(() => { this.isShutterCooldown = false; }, 400);

            // Optical shutter mechanical blink (90ms)
            this.isShutterBlinking = true;
            setTimeout(() => { this.isShutterBlinking = false; }, 90);

            try {
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
                    await new Promise(r => setTimeout(r, 100));
                }

                if (navigator.vibrate) {
                    navigator.vibrate(50);
                }

                // Non-blocking developing badge indicator
                this.isDeveloping = true;
                this.statusMessage = '🎞️ Developing Film...';

                // Optimistic film counter advance (mechanical roll feel!)
                const pendingShotNumber = this.shotsTaken + 1;
                this.shotsTaken = pendingShotNumber;

                const video = this.$refs.videoElement;
                const canvas = this.$refs.captureCanvas;
                const ctx = canvas.getContext('2d');

                const videoW = video.videoWidth || 1280;
                const videoH = video.videoHeight || 960;

                // STRICT 4:3 LANDSCAPE CROP (MATCHES VIEWFINDER PIXEL-PERFECT)
                const targetAspect = 4 / 3;
                let cropX = 0, cropY = 0, cropW = videoW, cropH = videoH;

                if (videoW / videoH > targetAspect) {
                    cropH = videoH;
                    cropW = Math.round(videoH * targetAspect);
                    cropX = Math.round((videoW - cropW) / 2);
                } else {
                    cropW = videoW;
                    cropH = Math.round(videoW / targetAspect);
                    cropY = Math.round((videoH - cropH) / 2);
                }

                // High-performance 1200x900 (crisp 4:3 vintage frame, fast processing & lightweight upload)
                const outputWidth = 1200;
                const outputHeight = 900;

                canvas.width = outputWidth;
                canvas.height = outputHeight;

                // 1. Capture exact 4:3 crop to temporary offscreen buffer
                const offscreen = document.createElement('canvas');
                offscreen.width = outputWidth;
                offscreen.height = outputHeight;
                const offCtx = offscreen.getContext('2d');

                offCtx.save();
                if (this.facingMode === 'user') {
                    offCtx.translate(outputWidth, 0);
                    offCtx.scale(-1, 1);
                }
                offCtx.drawImage(video, cropX, cropY, cropW, cropH, 0, 0, outputWidth, outputHeight);
                offCtx.restore();

                // 2. Render from offscreen to main canvas
                const filterStr = this.getFilterString(this.currentPreset);

                try {
                    ctx.save();
                    ctx.filter = filterStr;
                    ctx.drawImage(offscreen, 0, 0);
                    ctx.restore();
                } catch (err) {
                    ctx.drawImage(offscreen, 0, 0);
                }

                // Turn off screen flash & hardware torch immediately
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

                // 3. Apply authentic analog finishing (Flash, grain, lens vignette, quartz date stamp) in <10ms
                this.applyAnalogFinishing(ctx, outputWidth, outputHeight, this.flashEnabled, this.currentPreset);

                const imageData = canvas.toDataURL('image/jpeg', 0.85);
                await this.uploadPhotoData(imageData, pendingShotNumber);
            } catch (err) {
                console.error('takePhoto error:', err);
                alert('Gagal mengambil foto: ' + (err.message || 'Terjadi kesalahan sistem'));
                this.isDeveloping = false;
                this.isProcessing = false;
            }
        },

        async handleNativePhoto(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.isDeveloping = true;
            this.statusMessage = '🎞️ Memproses Foto...';
            this.playShutterSound();

            const reader = new FileReader();
            reader.onerror = () => {
                this.isDeveloping = false;
                alert('Gagal membaca file foto dari galeri/kamera HP.');
                event.target.value = '';
            };

            reader.onload = (e) => {
                const img = new Image();
                img.onerror = () => {
                    this.isDeveloping = false;
                    alert('Format gambar tidak dapat diproses.');
                    event.target.value = '';
                };

                img.onload = async () => {
                    try {
                        const pendingShotNumber = this.shotsTaken + 1;
                        this.shotsTaken = pendingShotNumber;

                        const canvas = this.$refs.captureCanvas;
                        const ctx = canvas.getContext('2d');

                        const imgW = img.width;
                        const imgH = img.height;

                        // STRICT 4:3 LANDSCAPE CROP (MATCHES VIEWFINDER PIXEL-PERFECT)
                        const targetAspect = 4 / 3;
                        let cropX = 0, cropY = 0, cropW = imgW, cropH = imgH;

                        if (imgW / imgH > targetAspect) {
                            cropH = imgH;
                            cropW = Math.round(imgH * targetAspect);
                            cropX = Math.round((imgW - cropW) / 2);
                        } else {
                            cropW = imgW;
                            cropH = Math.round(imgW / targetAspect);
                            cropY = Math.round((imgH - cropH) / 2);
                        }

                        const outputWidth = 1200;
                        const outputHeight = 900;

                        // Offscreen raw crop
                        const offscreen = document.createElement('canvas');
                        offscreen.width = outputWidth;
                        offscreen.height = outputHeight;
                        const offCtx = offscreen.getContext('2d');
                        offCtx.drawImage(img, cropX, cropY, cropW, cropH, 0, 0, outputWidth, outputHeight);

                        // Main canvas with identical viewfinder film filter
                        canvas.width = outputWidth;
                        canvas.height = outputHeight;

                        const filterStr = this.getFilterString(this.currentPreset);

                        try {
                            ctx.save();
                            ctx.filter = filterStr;
                            ctx.drawImage(offscreen, 0, 0);
                            ctx.restore();
                        } catch (err) {
                            ctx.drawImage(offscreen, 0, 0);
                        }

                        // Apply authentic analog finishing in <10ms
                        this.applyAnalogFinishing(ctx, outputWidth, outputHeight, false, this.currentPreset);

                        const imageData = canvas.toDataURL('image/jpeg', 0.85);
                        await this.uploadPhotoData(imageData, pendingShotNumber);
                    } catch (err) {
                        console.error('handleNativePhoto error:', err);
                        alert('Gagal memproses foto.');
                        this.isDeveloping = false;
                    } finally {
                        event.target.value = '';
                    }
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        async uploadPhotoData(imageData, targetShotNumber) {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 20000); // 20s safety network timeout

            try {
                const response = await fetch(this.captureUrl, {
                    method: 'POST',
                    signal: controller.signal,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        image: imageData,
                        camera_facing: this.facingMode,
                        flash_enabled: this.flashEnabled,
                        filter_applied: this.currentPreset
                    })
                });

                clearTimeout(timeoutId);

                if (!response.ok) {
                    let errorMsg = 'Gagal menyimpan foto (' + response.status + ').';
                    try {
                        const errData = await response.json();
                        if (errData && errData.message) errorMsg = errData.message;
                    } catch (e) {}
                    throw new Error(errorMsg);
                }

                const data = await response.json();

                if (data.success) {
                    this.shotsTaken = data.shots_taken;
                    this.statusMessage = '✓ Foto #' + data.shots_taken + ' Tersimpan!';

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
                        this.isDeveloping = false;
                    }, 1400);
                } else {
                    throw new Error(data.message || 'Gagal menyimpan foto.');
                }
            } catch (error) {
                clearTimeout(timeoutId);
                console.error('Upload foto error:', error);
                
                // Revert optimistic shot count if needed
                if (targetShotNumber && this.shotsTaken >= targetShotNumber) {
                    this.shotsTaken--;
                }
                this.isDeveloping = false;

                if (error.name === 'AbortError') {
                    alert('Waktu koneksi habis saat mengirim foto ke server. Periksa koneksi internet Anda dan coba lagi.');
                } else {
                    alert(error.message || 'Terjadi kesalahan saat memproses foto. Silakan coba lagi.');
                }
            } finally {
                this.isProcessing = false;
            }
        },

        // Ultra-Fast GPU-Accelerated Analog Finishing: Flash, Film Grain, Vignette & Quartz Date Stamp (<10ms)
        applyAnalogFinishing(ctx, width, height, flashOn, preset = this.currentPreset) {
            // 1. Direct Flash Exposure Burst (Center-weighted soft radial gradient via Screen blend)
            if (flashOn) {
                ctx.save();
                ctx.globalCompositeOperation = 'screen';
                const maxDim = Math.hypot(width, height) / 2;
                const flashGrad = ctx.createRadialGradient(
                    width / 2, height / 2, 0,
                    width / 2, height / 2, maxDim * 0.95
                );
                flashGrad.addColorStop(0, 'rgba(255, 248, 235, 0.36)');
                flashGrad.addColorStop(0.45, 'rgba(255, 242, 215, 0.16)');
                flashGrad.addColorStop(1, 'rgba(0, 0, 0, 0)');
                ctx.fillStyle = flashGrad;
                ctx.fillRect(0, 0, width, height);
                ctx.restore();
            }

            // 2. Subtle Authentic 35mm Analog Film Grain (Centered at 128 mid-gray, no milky wash)
            let grainAlpha = 0.09;
            switch (preset) {
                case 'kodak_funsaver': grainAlpha = 0.12; break;
                case 'warm_portra': grainAlpha = 0.07; break;
                case 'ilford_bw': grainAlpha = 0.14; break;
                case 'cinestill_800t': grainAlpha = 0.10; break;
                case 'agfa_vista': grainAlpha = 0.09; break;
                case 'lomography_color': grainAlpha = 0.11; break;
                default: grainAlpha = 0.09; break;
            }

            try {
                const pattern = this.getNoisePattern(ctx);
                if (pattern) {
                    ctx.save();
                    ctx.globalCompositeOperation = 'overlay';
                    ctx.globalAlpha = grainAlpha;
                    ctx.fillStyle = pattern;
                    ctx.fillRect(0, 0, width, height);
                    ctx.restore();
                }
            } catch (e) {
                console.warn('Grain pattern blend skipped:', e);
            }

            // 3. Corner Lens Vignette
            const vignetteStrength = this.getVignetteOpacity();
            const maxRadius = Math.hypot(width, height) / 2;
            const vignetteGrad = ctx.createRadialGradient(
                width / 2, height / 2, maxRadius * 0.65,
                width / 2, height / 2, maxRadius * 1.05
            );
            vignetteGrad.addColorStop(0, 'rgba(0,0,0,0)');
            vignetteGrad.addColorStop(0.60, 'rgba(0,0,0,0)');
            vignetteGrad.addColorStop(0.85, `rgba(12,8,4,${vignetteStrength * 0.45})`);
            vignetteGrad.addColorStop(1, `rgba(6,4,2,${vignetteStrength})`);

            ctx.save();
            ctx.fillStyle = vignetteGrad;
            ctx.fillRect(0, 0, width, height);
            ctx.restore();

            // 4. Vintage Quartz Date Stamp ('YY MM DD) in bottom-right corner
            const dateColor = this.getDateColor();
            const dateStr = `'${String(new Date().getFullYear()).slice(-2)} ${String(new Date().getMonth() + 1).padStart(2, '0')} ${String(new Date().getDate()).padStart(2, '0')}`;
            const fontSize = Math.max(22, Math.floor(width * 0.026));

            ctx.save();
            ctx.font = `bold ${fontSize}px "Share Tech Mono", monospace`;
            ctx.textAlign = 'right';
            ctx.textBaseline = 'bottom';
            ctx.shadowColor = dateColor;
            ctx.shadowBlur = fontSize * 0.4;
            ctx.fillStyle = dateColor;
            ctx.fillText(dateStr, width - (fontSize * 1.2), height - (fontSize * 1.2));
            ctx.restore();
        }
    };
}
</script>
@endpush
