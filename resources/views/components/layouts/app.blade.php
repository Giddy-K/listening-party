<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>TogetherCast.io - Listen Together</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="icon" type="image/png" sizes="64x64" href="/favicon-64.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon-180.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link
        href="https://fonts.bunny.net/css?family=figtree:400,600|aleo:300,500,700|annie-use-your-telescope:400&display=swap"
        rel="stylesheet" />

    <wireui:scripts />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-emerald-50">
    <nav class="flex items-center justify-between px-6 py-3 bg-white border-b border-emerald-100 shadow-sm">
        <a href="{{ route('home') }}" class="flex items-center gap-2 font-cursive text-lg text-slate-800 hover:opacity-80 transition-opacity">
            <img src="/logo.png" class="h-7 w-7 shrink-0 object-contain aspect-square" width="28" height="28">
            TogetherCast.io
        </a>
        @auth
        <div class="flex items-center gap-4">
            <span class="text-sm text-slate-500">{{ Auth::user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm font-medium text-slate-500 hover:text-red-600 transition-colors">
                    Log out
                </button>
            </form>
        </div>
        @endauth
    </nav>
    {{ $slot }}
</body>

</html>
