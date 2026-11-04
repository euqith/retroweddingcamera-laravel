<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Retro Wedding Camera') }}</title>

    <!-- Google Fonts: Cormorant Garamond, Plus Jakarta Sans, Share Tech Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (via CDN for instant standalone stability & custom classes) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        wedding: {
                            ivory: '#FAF6F0',
                            linen: '#F4ECE1',
                            card: '#FFFDF9',
                            gold: '#C5A059',
                            goldHover: '#B08C45',
                            olive: '#6B7B6E',
                            dark: '#231F1E',
                            subtle: '#6E675F',
                            filmOrange: '#FF6F00',
                        }
                    },
                    fontFamily: {
                        serif: ['"Cormorant Garamond"', 'serif'],
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"Share Tech Mono"', 'monospace'],
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    <style>
        /* Custom scrollbar and subtle film textures */
        body {
            background-color: #FAF6F0;
            color: #231F1E;
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        .film-date-stamp {
            font-family: 'Share Tech Mono', monospace;
            color: #FF7700;
            text-shadow: 0 0 4px rgba(255, 119, 0, 0.8), 0 0 8px rgba(255, 80, 0, 0.6);
            letter-spacing: 2px;
        }

        /* Skeuomorphic textured film winder */
        .winder-wheel {
            background: repeating-linear-gradient(
                90deg,
                #111,
                #111 4px,
                #2a2a2a 4px,
                #2a2a2a 8px
            );
        }

        .shutter-btn {
            background: radial-gradient(circle at 35% 35%, #555, #111);
            box-shadow: 0 6px 12px rgba(0,0,0,0.4), inset 0 2px 2px rgba(255,255,255,0.3);
        }
        .shutter-btn:active {
            transform: translateY(2px) scale(0.98);
            box-shadow: 0 2px 4px rgba(0,0,0,0.5), inset 0 1px 1px rgba(255,255,255,0.2);
        }
    </style>

    @stack('styles')
</head>
<body class="h-full antialiased selection:bg-wedding-gold selection:text-white">
    <div class="min-h-full flex flex-col">
        <!-- Main Content -->
        <main class="flex-1">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
