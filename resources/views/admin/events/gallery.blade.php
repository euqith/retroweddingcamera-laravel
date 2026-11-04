@extends('layouts.app', ['title' => 'Galeri ' . $event->title . ' — Admin CMS'])

@section('content')
<div class="min-h-screen bg-[#FAF6F0] py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        <!-- Top Nav -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-wedding-gold/20 mb-8 gap-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.dashboard') }}" class="p-2 rounded-xl bg-white border border-wedding-gold/20 hover:bg-wedding-linen text-wedding-dark transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                </a>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider bg-wedding-gold/15 text-wedding-goldHover">
                            Galeri Event Khusus
                        </span>
                        <span class="text-xs text-wedding-subtle font-medium">{{ $event->event_type }}</span>
                    </div>
                    <h1 class="font-serif text-2xl font-bold text-wedding-dark">{{ $event->title }}</h1>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if($photos->total() > 0)
                    <form action="{{ route('admin.events.gallery.clear', $event) }}" method="POST"
                          onsubmit="return confirm('⚠️ HAPUS SEMUA FOTO EVENT INI?\n\nApakah Anda yakin ingin menghapus seluruh {{ $photos->total() }} foto pada event \'{{ addslashes($event->title) }}\'?\n\nSemua file foto fisik di server akan dibersihkan permanen!');">
                        @csrf
                        <button type="submit"
                                class="px-3.5 py-2 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 text-xs font-semibold rounded-xl transition-colors flex items-center space-x-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                            </svg>
                            <span>Hapus Semua Foto ({{ $photos->total() }})</span>
                        </button>
                    </form>
                @endif

                <a href="{{ route('event.gallery', $event->slug) }}" target="_blank"
                   class="px-3.5 py-2 bg-wedding-linen hover:bg-wedding-gold/20 text-wedding-dark text-xs font-semibold rounded-xl transition-colors flex items-center space-x-1.5 border border-wedding-gold/20">
                    <svg class="w-4 h-4 text-wedding-gold" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                    </svg>
                    <span>Buka Tampilan Tamu</span>
                </a>

                <a href="{{ route('admin.events.qrcodes', $event) }}"
                   class="px-3.5 py-2 bg-wedding-dark hover:bg-black text-white text-xs font-semibold rounded-xl transition-colors flex items-center space-x-1.5 shadow">
                    <span>QR Code</span>
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <!-- Photos Section -->
        <div class="bg-white rounded-3xl border border-wedding-gold/20 shadow-sm p-6 sm:p-8">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-wedding-gold/10">
                <div>
                    <h2 class="font-serif text-xl font-bold text-wedding-dark">Semua Foto Event Ini</h2>
                    <p class="text-xs text-wedding-subtle">Hanya menampilkan foto-foto yang diambil untuk event: <strong>{{ $event->title }}</strong></p>
                </div>
                <span class="text-xs font-mono font-bold text-wedding-dark bg-wedding-linen px-3 py-1.5 rounded-lg border border-wedding-gold/20">
                    Total: {{ $photos->total() }} Foto
                </span>
            </div>

            @if($photos->count() > 0)
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    @foreach($photos as $photo)
                        <div class="group bg-wedding-ivory rounded-2xl overflow-hidden border border-wedding-gold/20 shadow-sm hover:shadow-md transition-all flex flex-col justify-between p-2">
                            <div class="relative w-full aspect-[4/3] rounded-xl overflow-hidden bg-black/5">
                                <img src="{{ $photo->thumbnail_url }}"
                                     alt="Shot #{{ $photo->shot_number }}"
                                     loading="lazy"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">

                                @if($event->show_date_stamp)
                                    <div class="absolute bottom-2 right-2 film-date-stamp text-[9px] pointer-events-none select-none">
                                        '{{ $photo->taken_at->format('y m d') }}
                                    </div>
                                @endif

                                @if($photo->camera_facing === 'user')
                                    <span class="absolute top-2 left-2 bg-black/60 text-white text-[9px] px-1.5 py-0.5 rounded font-mono">
                                        Selfie
                                    </span>
                                @endif
                            </div>

                            <div class="pt-2 px-1 flex items-center justify-between text-[11px] text-wedding-subtle">
                                <div>
                                    <span class="font-mono font-bold text-wedding-dark">#{{ $photo->shot_number }}</span>
                                    <span>• {{ $photo->taken_at->format('H:i') }}</span>
                                </div>

                                <form action="{{ route('admin.photos.destroy', $photo) }}" method="POST"
                                      onsubmit="return confirm('⚠️ Hapus foto #{{ $photo->shot_number }} ini permanen dari server?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center space-x-1 px-2 py-1 rounded-lg bg-red-50 hover:bg-red-600 text-red-600 hover:text-white text-[10px] font-semibold transition-all shadow-xs"
                                            title="Hapus Foto">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                        </svg>
                                        <span>Hapus</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6 pt-4 border-t border-wedding-gold/10">
                    {{ $photos->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-xs text-wedding-subtle mb-3">Belum ada foto yang masuk untuk event ini.</p>
                    <a href="{{ route('event.camera', $event->slug) }}" target="_blank"
                       class="inline-flex items-center space-x-1.5 px-4 py-2 bg-wedding-gold text-white text-xs font-semibold rounded-xl">
                        <span>Coba Ambil Foto Sekarang</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

