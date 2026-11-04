@extends('layouts.app', ['title' => 'Galeri — ' . $event->title])

@section('content')
<div x-data="{
        selectedPhoto: null,
        openLightbox(photo) {
            this.selectedPhoto = photo;
        },
        closeLightbox() {
            this.selectedPhoto = null;
        }
    }"
    class="min-h-screen bg-[#FAF6F0] pb-24">

    @auth
        <!-- Admin Bar Overlay -->
        <div class="bg-wedding-dark text-white text-xs py-2 px-4 flex items-center justify-between border-b border-wedding-gold/30">
            <div class="flex items-center space-x-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-white/90">Mode Admin Aktif ({{ Auth::user()->name }}). Anda dapat menghapus foto langsung dari galeri ini.</span>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="text-wedding-gold hover:underline font-semibold text-[11px] flex items-center space-x-1">
                <span>Dashboard Admin</span>
                <span>→</span>
            </a>
        </div>
    @endauth

    <!-- Top Sticky Header -->
    <header class="sticky top-0 bg-[#FAF6F0]/95 backdrop-blur-md border-b border-wedding-gold/20 z-30 px-4 py-3 sm:py-4">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <a href="{{ route('event.landing', $event->slug) }}" class="p-2 -ml-2 rounded-full hover:bg-wedding-linen text-wedding-dark transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
            </a>

            <div class="text-center">
                <span class="text-[10px] uppercase tracking-[0.2em] text-wedding-olive font-semibold block">Public Event Gallery</span>
                <h1 class="font-serif text-base sm:text-lg text-wedding-dark font-bold truncate max-w-[240px] sm:max-w-sm">
                    {{ $event->title }}
                </h1>
            </div>

            <a href="{{ route('event.camera', $event->slug) }}"
               class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-full bg-gradient-to-r from-wedding-gold to-wedding-goldHover text-white text-xs font-semibold shadow hover:shadow-md transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                </svg>
                <span class="hidden sm:inline">Ambil Foto</span>
                <span class="sm:hidden font-mono text-[10px]">({{ $session->remaining_shots }})</span>
            </a>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-4 pt-6">
        <!-- Event Summary Stats -->
        <div class="text-center mb-6">
            <p class="text-xs text-wedding-subtle tracking-wide">
                {{ $totalPhotos }} Foto Candid Kamera Disposable • {{ $guestCount }} Perangkat Tamu
            </p>

            <!-- Filter Pills -->
            <div class="flex items-center justify-center space-x-2 mt-4 overflow-x-auto py-1">
                <a href="{{ route('event.gallery', ['slug' => $event->slug, 'filter' => 'all']) }}"
                   class="px-4 py-1.5 rounded-full text-xs font-medium transition-all {{ $filter === 'all' ? 'bg-wedding-dark text-white shadow' : 'bg-white border border-wedding-gold/20 text-wedding-dark hover:bg-wedding-linen' }}">
                    Semua Foto ({{ $totalPhotos }})
                </a>
                <a href="{{ route('event.gallery', ['slug' => $event->slug, 'filter' => 'my_shots']) }}"
                   class="px-4 py-1.5 rounded-full text-xs font-medium transition-all {{ $filter === 'my_shots' ? 'bg-wedding-dark text-white shadow' : 'bg-white border border-wedding-gold/20 text-wedding-dark hover:bg-wedding-linen' }}">
                    Kamera Saya ({{ $session->shots_taken }}/{{ $session->max_shots }})
                </a>
                <a href="{{ route('event.gallery', ['slug' => $event->slug, 'filter' => 'selfies']) }}"
                   class="px-4 py-1.5 rounded-full text-xs font-medium transition-all {{ $filter === 'selfies' ? 'bg-wedding-dark text-white shadow' : 'bg-white border border-wedding-gold/20 text-wedding-dark hover:bg-wedding-linen' }}">
                    Selfie
                </a>
            </div>
        </div>

        <!-- Photos Grid (Polaroid / 35mm Analog Print Style) -->
        @if($photos->count() > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-6">
                @foreach($photos as $photo)
                    <div @click="openLightbox({
                            id: {{ $photo->id }},
                            shotNumber: {{ $photo->shot_number }},
                            url: '{{ $photo->url }}',
                            takenAt: '{{ $photo->taken_at->format('d M Y, H:i') }}',
                            facing: '{{ $photo->camera_facing }}',
                            flash: {{ $photo->flash_enabled ? 'true' : 'false' }}
                        })"
                        class="bg-white p-2 sm:p-3 rounded-2xl shadow-sm border border-wedding-gold/20 hover:shadow-md transition-all cursor-pointer group flex flex-col justify-between">
                        <!-- Image Container with Aspect Ratio -->
                        <div class="relative w-full aspect-[4/3] rounded-xl overflow-hidden bg-wedding-linen">
                            <img src="{{ $photo->thumbnail_url }}"
                                 alt="Photo #{{ $photo->shot_number }}"
                                 loading="lazy"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">

                            <!-- Date Stamp on thumbnail -->
                            <div class="absolute bottom-2 right-2 film-date-stamp text-[10px] pointer-events-none select-none">
                                '{{ $photo->taken_at->format('y m d') }}
                            </div>

                            @if($photo->camera_facing === 'user')
                                <span class="absolute top-2 left-2 bg-black/50 backdrop-blur-sm text-white text-[9px] px-1.5 py-0.5 rounded-md font-mono">
                                    Selfie
                                </span>
                            @endif
                        </div>

                        <!-- Card Footer -->
                        <div class="mt-2 px-1 flex items-center justify-between text-[11px] text-wedding-subtle">
                            <span class="font-mono font-semibold text-wedding-dark">#{{ $photo->shot_number }}</span>
                            <div class="flex items-center space-x-1.5">
                                <span>{{ $photo->taken_at->format('H:i') }}</span>
                                @auth
                                    <form action="{{ route('admin.photos.destroy', $photo) }}" method="POST"
                                          onclick="event.stopPropagation();"
                                          onsubmit="event.stopPropagation(); return confirm('⚠️ Hapus foto #{{ $photo->shot_number }} ini permanen dari server?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="p-1 rounded bg-red-50 hover:bg-red-600 text-red-500 hover:text-white transition-colors"
                                                title="Hapus Foto (Admin)">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endauth
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $photos->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-16 bg-white rounded-3xl border border-wedding-gold/20 p-8 shadow-sm">
                <div class="w-16 h-16 mx-auto mb-4 bg-wedding-linen text-wedding-gold rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                    </svg>
                </div>
                <h3 class="font-serif text-xl font-bold text-wedding-dark mb-1">Belum Ada Foto</h3>
                <p class="text-xs text-wedding-subtle mb-6">Jadilah yang pertama mengabadikan momen dengan kamera disposable!</p>
                <a href="{{ route('event.camera', $event->slug) }}" class="inline-flex items-center space-x-2 py-3 px-6 bg-gradient-to-r from-wedding-gold to-wedding-goldHover text-white rounded-xl text-sm font-semibold shadow">
                    <span>Buka Kamera Sekarang</span>
                </a>
            </div>
        @endif
    </div>

    <!-- Lightbox Modal -->
    <div x-show="selectedPhoto"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/90 backdrop-blur-md z-50 flex items-center justify-center p-4"
         style="display: none;"
         @keydown.escape.window="closeLightbox()">

        <div class="relative w-full max-w-xl bg-[#FAF6F0] rounded-3xl p-4 sm:p-6 shadow-2xl flex flex-col items-center"
             @click.away="closeLightbox()">
            <!-- Close Button -->
            <button @click="closeLightbox()"
                    type="button"
                    class="absolute top-4 right-4 p-2 bg-black/10 hover:bg-black/20 text-wedding-dark rounded-full transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <!-- Full Image Preview -->
            <div class="relative w-full aspect-[4/3] rounded-2xl overflow-hidden bg-black shadow-inner my-2">
                <img :src="selectedPhoto ? selectedPhoto.url : ''"
                     class="w-full h-full object-contain">

                <div class="absolute bottom-3 right-3 film-date-stamp text-xs pointer-events-none select-none">
                    '{{ date('y m d') }}
                </div>
            </div>

            <!-- Details & Actions -->
            <div class="w-full mt-3 flex items-center justify-between text-xs text-wedding-dark">
                <div>
                    <span class="font-mono font-bold text-sm block" x-text="'Shot #' + (selectedPhoto ? selectedPhoto.shotNumber : '')"></span>
                    <span class="text-wedding-subtle" x-text="selectedPhoto ? selectedPhoto.takenAt : ''"></span>
                </div>

                <div class="flex items-center space-x-2">
                    @auth
                        <form :action="'/admin/photos/' + (selectedPhoto ? selectedPhoto.id : '')" method="POST"
                              onsubmit="return confirm('⚠️ Hapus foto ini permanen dari galeri dan server?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="py-2 px-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-medium text-xs flex items-center space-x-1.5 transition-colors shadow">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                </svg>
                                <span>Hapus Foto (Admin)</span>
                            </button>
                        </form>
                    @endauth

                    <a :href="selectedPhoto ? selectedPhoto.url : '#'"
                       download
                       class="py-2 px-4 rounded-xl bg-wedding-dark hover:bg-wedding-dark/90 text-white font-medium text-xs flex items-center space-x-1.5 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                        </svg>
                        <span>Unduh Foto</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Bottom Action: Buka Kamera -->
    <div class="fixed bottom-6 inset-x-0 flex justify-center z-20 pointer-events-none">
        <a href="{{ route('event.camera', $event->slug) }}"
           class="pointer-events-auto px-6 py-3.5 rounded-full bg-wedding-dark text-white font-semibold text-sm shadow-xl hover:bg-black transition-all flex items-center space-x-2 border border-wedding-gold/40">
            <svg class="w-5 h-5 text-wedding-gold" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
            </svg>
            <span>Buka Kamera (Sisa: {{ $session->remaining_shots }})</span>
        </a>
    </div>
</div>
@endsection

