@extends('layouts.app', ['title' => 'Login Admin — Retro Wedding Camera'])

@section('content')
<div class="min-h-screen bg-[#FAF6F0] flex flex-col justify-center py-12 sm:px-6 lg:px-8 px-4">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <!-- Logo / Floral Crest -->
        <div class="inline-flex items-center justify-center space-x-2 text-wedding-gold mb-4">
            <span class="h-px w-8 bg-wedding-gold/40"></span>
            <div class="w-12 h-12 bg-white rounded-2xl shadow-sm border border-wedding-gold/30 flex items-center justify-center text-wedding-gold">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
                </svg>
            </div>
            <span class="h-px w-8 bg-wedding-gold/40"></span>
        </div>

        <p class="text-xs uppercase tracking-[0.25em] text-wedding-olive font-semibold mb-1">
            Event Organizer Portal
        </p>
        <h1 class="font-serif text-3xl font-bold text-wedding-dark tracking-wide">
            Retro Wedding Camera
        </h1>
        <p class="mt-2 text-xs text-wedding-subtle">
            Masuk untuk mengelola acara pernikahan, cetak barcode meja, dan galeri foto
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-6 shadow-sm border border-wedding-gold/30 rounded-3xl sm:px-10">
            @if(session('success'))
                <div class="mb-5 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-semibold text-wedding-dark uppercase tracking-wider mb-1">
                        Email Panitia / Admin
                    </label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email', 'admin@retrocamera.test') }}"
                           required
                           autofocus
                           class="w-full px-4 py-3 rounded-xl border border-wedding-gold/40 bg-wedding-ivory/40 text-wedding-dark text-sm focus:outline-none focus:ring-2 focus:ring-wedding-gold/50 focus:border-wedding-gold transition-all"
                           placeholder="admin@retrocamera.test">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="password" class="block text-xs font-semibold text-wedding-dark uppercase tracking-wider">
                            Kata Sandi
                        </label>
                        <span class="text-[11px] text-wedding-subtle">default: password</span>
                    </div>
                    <input type="password"
                           id="password"
                           name="password"
                           value="password"
                           required
                           class="w-full px-4 py-3 rounded-xl border border-wedding-gold/40 bg-wedding-ivory/40 text-wedding-dark text-sm focus:outline-none focus:ring-2 focus:ring-wedding-gold/50 focus:border-wedding-gold transition-all">
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center text-xs text-wedding-dark cursor-pointer">
                        <input type="checkbox"
                               name="remember"
                               checked
                               class="rounded border-wedding-gold/40 text-wedding-gold focus:ring-wedding-gold">
                        <span class="ml-2">Ingat saya</span>
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit"
                            class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-wedding-gold to-wedding-goldHover hover:opacity-95 text-white font-medium text-sm tracking-wide shadow-md transition-all flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                        </svg>
                        <span>Masuk ke Dashboard</span>
                    </button>
                </div>
            </form>

            <!-- 1-Click Fast Login Button -->
            <div class="mt-6 pt-5 border-t border-wedding-gold/20 text-center">
                <form action="{{ route('login.quick') }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="w-full py-2.5 px-4 rounded-xl border border-wedding-gold/40 bg-wedding-linen hover:bg-wedding-gold/10 text-wedding-dark text-xs font-semibold transition-colors flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4 text-wedding-gold" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                        </svg>
                        <span>1-Klik Masuk Otomatis (Demo/Admin)</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Direct links to public events if available -->
        @php
            $activeEvents = \App\Models\Event::where('is_active', true)->take(3)->get();
        @endphp
        @if($activeEvents->count() > 0)
            <div class="mt-6 text-center">
                <span class="text-xs uppercase tracking-wider text-wedding-subtle block mb-2">Atau Buka Kamera Acara:</span>
                <div class="flex flex-wrap justify-center gap-2">
                    @foreach($activeEvents as $ev)
                        <a href="{{ route('event.camera', $ev->slug) }}"
                           class="inline-flex items-center space-x-1 px-3 py-1.5 bg-white border border-wedding-gold/30 rounded-full text-xs text-wedding-dark hover:border-wedding-gold transition-colors">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span>{{ $ev->title }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
