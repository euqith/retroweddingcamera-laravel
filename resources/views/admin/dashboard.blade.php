@extends('layouts.app', ['title' => 'Admin Dashboard — Retro Wedding Camera'])

@section('content')
<div class="min-h-screen bg-[#FAF6F0] py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
        <!-- Top Navbar -->
        <div class="flex items-center justify-between pb-6 border-b border-wedding-gold/20 mb-8">
            <div class="flex items-center space-x-3">
                <span class="w-10 h-10 rounded-xl bg-wedding-dark text-wedding-gold flex items-center justify-center font-bold text-lg shadow">
                    📷
                </span>
                <div>
                    <h1 class="font-serif text-2xl font-bold text-wedding-dark">Admin Dashboard</h1>
                    <p class="text-xs text-wedding-subtle">Manajemen Event, QR Code, dan Reset Roll Kamera</p>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <a href="{{ route('admin.events.create') }}"
                   class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-wedding-gold to-wedding-goldHover text-white text-sm font-semibold shadow hover:shadow-md transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    <span>Buat Event Baru</span>
                </a>

                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            title="Keluar dari Admin"
                            class="p-2.5 rounded-xl border border-wedding-gold/20 bg-white hover:bg-wedding-linen text-wedding-subtle hover:text-wedding-dark transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between">
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="bg-white p-5 rounded-2xl border border-wedding-gold/20 shadow-sm">
                <span class="text-xs font-semibold uppercase tracking-wider text-wedding-subtle block">Total Foto Masuk</span>
                <span class="font-serif text-3xl font-bold text-wedding-dark mt-1 block">{{ $totalPhotos }}</span>
                <span class="text-[11px] text-wedding-olive font-medium mt-1 block">Terkumpul dari semua event</span>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-wedding-gold/20 shadow-sm">
                <span class="text-xs font-semibold uppercase tracking-wider text-wedding-subtle block">Perangkat Terhubung</span>
                <span class="font-serif text-3xl font-bold text-wedding-dark mt-1 block">{{ $totalSessions }}</span>
                <span class="text-[11px] text-wedding-olive font-medium mt-1 block">Sesi roll disposable tamu</span>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-wedding-gold/20 shadow-sm">
                <span class="text-xs font-semibold uppercase tracking-wider text-wedding-subtle block">Event Aktif</span>
                <span class="font-serif text-3xl font-bold text-wedding-gold mt-1 block">{{ $activeEventsCount }}</span>
                <span class="text-[11px] text-wedding-subtle font-medium mt-1 block">Wedding & Acara Gereja</span>
            </div>
        </div>

        <!-- Event List Section -->
        <div class="bg-white rounded-3xl border border-wedding-gold/20 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-wedding-gold/10 flex items-center justify-between">
                <h2 class="font-serif text-xl font-bold text-wedding-dark">Daftar Event</h2>
                <span class="text-xs text-wedding-subtle">{{ $events->total() }} Event Terdaftar</span>
            </div>

            @if($events->count() > 0)
                <div class="divide-y divide-wedding-gold/10">
                    @foreach($events as $event)
                        <div class="p-6 hover:bg-wedding-ivory/50 transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center space-x-2 mb-1.5">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider
                                        {{ $event->event_type === 'wedding' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">
                                        {{ $event->event_type }}
                                    </span>
                                    <span class="text-xs text-wedding-subtle font-medium">
                                        {{ $event->event_date ? $event->event_date->format('d M Y') : 'Tanpa Tanggal' }}
                                    </span>
                                </div>

                                <h3 class="font-serif text-lg font-bold text-wedding-dark mb-1">
                                    {{ $event->title }}
                                </h3>

                                <p class="text-xs text-wedding-subtle mb-2">
                                    {{ $event->location ?: 'Lokasi belum diatur' }} •
                                    <span class="font-mono text-wedding-dark">{{ $event->photos_count }} Foto</span> •
                                    <span class="font-mono text-wedding-dark">{{ $event->camera_sessions_count }} Device</span> •
                                    Maks: <span class="font-mono">{{ $event->max_shots_per_device }} Foto/Device</span>
                                </p>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('event.camera', $event->slug) }}" target="_blank"
                                   class="px-3 py-1.5 bg-wedding-linen hover:bg-wedding-gold/20 text-wedding-dark text-xs font-semibold rounded-lg transition-colors flex items-center space-x-1">
                                    <span>Buka Kamera</span>
                                </a>

                                <a href="{{ route('admin.events.gallery', $event) }}"
                                   class="px-3 py-1.5 bg-wedding-linen hover:bg-wedding-gold/20 text-wedding-dark text-xs font-semibold rounded-lg transition-colors flex items-center space-x-1">
                                    <span>Galeri Event ({{ $event->photos_count }})</span>
                                </a>

                                <a href="{{ route('admin.events.qrcodes', $event) }}"
                                   class="px-3 py-1.5 bg-wedding-gold/15 hover:bg-wedding-gold/30 text-wedding-goldHover text-xs font-semibold rounded-lg transition-colors flex items-center space-x-1">
                                    <span>QR Codes</span>
                                </a>

                                <a href="{{ route('admin.events.sessions', $event) }}"
                                   class="px-3 py-1.5 bg-wedding-dark text-white hover:bg-black text-xs font-semibold rounded-lg transition-colors flex items-center space-x-1">
                                    <span>Device & Reset Roll</span>
                                </a>

                                <a href="{{ route('admin.events.edit', $event) }}"
                                   class="p-1.5 text-wedding-subtle hover:text-wedding-dark hover:bg-wedding-gold/10 rounded-lg transition-colors" title="Edit Event">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                    </svg>
                                </a>

                                <form action="{{ route('admin.events.destroy', $event) }}"
                                      method="POST"
                                      onsubmit="return confirm('⚠️ HAPUS EVENT & SELURUH FOTO?\n\nApakah Anda yakin ingin menghapus event \'{{ addslashes($event->title) }}\'?\n\nSeluruh file foto di server (folder photos/{{ $event->slug }}) dan database akan otomatis dihapus permanen dan tidak bisa dikembalikan!');"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Hapus Event & Seluruh Foto di Server">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="p-4 border-t border-wedding-gold/10">
                    {{ $events->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-sm text-wedding-subtle mb-4">Belum ada event yang dibuat.</p>
                    <a href="{{ route('admin.events.create') }}" class="px-4 py-2 bg-wedding-gold text-white text-xs font-semibold rounded-xl">
                        Buat Event Sekarang
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

