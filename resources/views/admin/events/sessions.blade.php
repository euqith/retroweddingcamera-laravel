@extends('layouts.app', ['title' => 'Device Sessions — ' . $event->title])

@section('content')
<div class="min-h-screen bg-[#FAF6F0] py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
        <!-- Top Nav -->
        <div class="flex items-center justify-between pb-6 border-b border-wedding-gold/20 mb-8">
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.dashboard') }}" class="p-2 rounded-xl bg-white border border-wedding-gold/20 hover:bg-wedding-linen text-wedding-dark transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                    </svg>
                </a>
                <div>
                    <span class="text-xs uppercase tracking-widest text-wedding-gold font-bold block">Device Sessions Monitor</span>
                    <h1 class="font-serif text-2xl font-bold text-wedding-dark">{{ $event->title }}</h1>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <a href="{{ route('admin.events.gallery', $event) }}" class="px-3.5 py-2 rounded-xl bg-white border border-wedding-gold/20 hover:bg-wedding-linen text-wedding-dark text-xs font-semibold shadow-xs transition-colors">
                    Galeri Event
                </a>
                <a href="{{ route('event.card', $event->slug) }}" target="_blank" class="px-3.5 py-2 rounded-xl bg-wedding-dark hover:bg-black text-white text-xs font-semibold shadow transition-colors">
                    Kartu Barcode
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <!-- Sessions Table Card -->
        <div class="bg-white rounded-3xl border border-wedding-gold/20 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-wedding-gold/10 flex items-center justify-between">
                <div>
                    <h2 class="font-serif text-lg font-bold text-wedding-dark">Daftar Device & Status Kuota Roll</h2>
                    <p class="text-xs text-wedding-subtle">Setiap device dibatasi {{ $event->max_shots_per_device }} foto per roll.</p>
                </div>
                <span class="text-xs font-mono text-wedding-dark font-semibold">{{ $sessions->total() }} Device</span>
            </div>

            @if($sessions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-wedding-ivory text-wedding-dark uppercase tracking-wider font-semibold border-b border-wedding-gold/10">
                            <tr>
                                <th class="px-6 py-3">Device Token / Info</th>
                                <th class="px-6 py-3">Roll Progress</th>
                                <th class="px-6 py-3">Foto Tersimpan</th>
                                <th class="px-6 py-3">Jepretan Terakhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-wedding-gold/10 text-wedding-dark">
                            @foreach($sessions as $session)
                                <tr class="hover:bg-wedding-linen/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-mono font-bold text-sm text-wedding-dark">{{ $session->device_token }}</div>
                                        <div class="text-[10px] text-wedding-subtle truncate max-w-xs" title="{{ $session->user_agent }}">
                                            {{ $session->ip_address }} • {{ Str::limit($session->user_agent, 40) }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-2">
                                            <span class="font-mono font-bold {{ $session->isRollEmpty() ? 'text-red-600' : 'text-wedding-dark' }}">
                                                {{ $session->shots_taken }} / {{ $session->max_shots }}
                                            </span>
                                            @if($session->isRollEmpty())
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-red-100 text-red-700">
                                                    Roll Habis
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-700">
                                                    Aktif
                                                </span>
                                            @endif
                                        </div>
                                        <!-- Progress Bar -->
                                        <div class="w-32 h-1.5 bg-gray-200 rounded-full mt-1.5 overflow-hidden">
                                            <div class="h-full bg-wedding-gold rounded-full transition-all"
                                                 style="width: {{ min(100, ($session->shots_taken / $session->max_shots) * 100) }}%"></div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 font-mono font-semibold">
                                        {{ $session->photos_count }} foto
                                    </td>

                                    <td class="px-6 py-4 text-wedding-subtle">
                                        {{ $session->last_shot_at ? $session->last_shot_at->diffForHumans() : 'Belum memotret' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-wedding-gold/10">
                    {{ $sessions->links() }}
                </div>
            @else
                <div class="text-center py-12 text-wedding-subtle text-xs">
                    Belum ada perangkat yang terhubung ke event ini.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

