<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TogetherCast.io - Listen Together</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="icon" type="image/png" sizes="64x64" href="/favicon-64.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon-180.png">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600|aleo:300,500,700|annie-use-your-telescope:400&display=swap" rel="stylesheet" />
    <style>[x-cloak]{display:none!important}</style>
    <wireui:scripts />

    <script>
    window._tcConfig = {
        pusherKey: '{{ env('PUSHER_APP_KEY') }}',
        pusherCluster: '{{ env('PUSHER_APP_CLUSTER', 'mt1') }}',
    };
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-emerald-50">

    {{-- Nav --}}
    <nav class="flex items-center justify-between px-6 py-4 max-w-5xl mx-auto">
        <span class="font-cursive text-xl text-slate-800 flex items-center gap-2">
            <img src="/logo.png" class="h-8 w-8 shrink-0 object-contain aspect-square">
            TogetherCast.io
        </span>
        <div class="flex items-center gap-3">
            @auth
                <a href="{{ route('dashboard') }}" x-data="{ loading: false }" @click="loading = true" :class="loading ? 'opacity-75 cursor-wait' : ''" class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700 hover:text-emerald-900">
                    <svg x-show="loading" class="animate-spin h-4 w-4" width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span>Dashboard</span>
                </a>
            @else
                <a href="{{ route('login') }}" x-data="{ loading: false }" @click="loading = true" :class="loading ? 'opacity-75 cursor-wait' : ''" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 hover:text-slate-900">
                    <svg x-show="loading" class="animate-spin h-4 w-4" width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span>Log in</span>
                </a>
                <a href="{{ route('register') }}" x-data="{ loading: false }" @click="loading = true" :class="loading ? 'opacity-75 cursor-wait' : ''" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors">
                    <svg x-show="loading" class="animate-spin h-4 w-4" width="16" height="16" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span>Get started</span>
                </a>
            @endauth
        </div>
    </nav>

    {{-- Hero --}}
    <section class="max-w-3xl mx-auto px-6 pt-20 pb-24 text-center">
        <div class="flex items-center justify-center mb-6">
            <div class="relative flex items-center justify-center w-24 h-24">
                <span class="absolute inline-flex rounded-full opacity-40 size-28 bg-emerald-400 animate-ping"></span>
                <img src="/logo.png" class="relative w-24 h-24 object-contain drop-shadow-xl">
            </div>
        </div>
        <h1 class="font-serif text-5xl font-bold text-slate-900 leading-tight">
            Listen to podcasts<br>together, in sync.
        </h1>
        <p class="mt-6 text-lg text-slate-600 max-w-xl mx-auto">
            TogetherCast.io lets you create a listening party for any podcast episode. Share a link, start at the same moment, and react together in real time.
        </p>
        <div class="mt-10 flex items-center justify-center gap-4">
            @auth
                <a href="{{ route('dashboard') }}" x-data="{ loading: false }" @click="loading = true" :class="loading ? 'opacity-75 cursor-wait' : ''" class="inline-flex items-center gap-2 px-6 py-3 text-base font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
                    <svg x-show="loading" class="animate-spin h-5 w-5" width="20" height="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span>Go to Dashboard</span>
                </a>
            @else
                <a href="{{ route('register') }}" x-data="{ loading: false }" @click="loading = true" :class="loading ? 'opacity-75 cursor-wait' : ''" class="inline-flex items-center gap-2 px-6 py-3 text-base font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
                    <svg x-show="loading" class="animate-spin h-5 w-5" width="20" height="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span>Create a listening party</span>
                </a>
                <a href="{{ route('login') }}" x-data="{ loading: false }" @click="loading = true" :class="loading ? 'opacity-75 cursor-wait' : ''" class="inline-flex items-center gap-2 px-6 py-3 text-base font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors shadow-sm">
                    <svg x-show="loading" class="animate-spin h-5 w-5 text-slate-500" width="20" height="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span>Log in</span>
                </a>
            @endauth
        </div>
    </section>

    {{-- Features --}}
    <section class="max-w-5xl mx-auto px-6 pb-24">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl p-6 shadow-sm border border-emerald-100">
                <div class="mb-4 text-emerald-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8" width="32" height="32"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                </div>
                <h3 class="font-serif font-semibold text-slate-900 mb-2">Perfectly in sync</h3>
                <p class="text-sm text-slate-600">Everyone hears the same moment at the same time. No manually pressing play - the party syncs automatically from a shared start time.</p>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-emerald-100">
                <div class="mb-4 text-emerald-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8" width="32" height="32"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" /></svg>
                </div>
                <h3 class="font-serif font-semibold text-slate-900 mb-2">Live chat</h3>
                <p class="text-sm text-slate-600">React and chat as the episode unfolds. Messages appear in real time so the conversation follows the audio beat for beat.</p>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-emerald-100">
                <div class="mb-4 text-emerald-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8" width="32" height="32"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" /></svg>
                </div>
                <h3 class="font-serif font-semibold text-slate-900 mb-2">Any podcast, instantly</h3>
                <p class="text-sm text-slate-600">Paste a podcast RSS feed URL. TogetherCast.io fetches the latest episode automatically - no manual uploads or file sharing needed.</p>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="max-w-3xl mx-auto px-6 pb-32">
        <h2 class="font-serif text-2xl font-bold text-center text-slate-900 mb-10">How it works</h2>
        <div class="space-y-6">
            <div class="flex items-start gap-4">
                <span class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-500 text-white text-sm font-bold flex items-center justify-center">1</span>
                <div>
                    <p class="font-semibold text-slate-900">Paste a podcast RSS feed URL</p>
                    <p class="text-sm text-slate-600 mt-0.5">TogetherCast.io pulls the latest episode title, artwork, and audio automatically.</p>
                </div>
            </div>
            <div class="flex items-start gap-4">
                <span class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-500 text-white text-sm font-bold flex items-center justify-center">2</span>
                <div>
                    <p class="font-semibold text-slate-900">Set a start time and share the link</p>
                    <p class="text-sm text-slate-600 mt-0.5">Schedule the party and send the URL to your friends. They'll see a countdown.</p>
                </div>
            </div>
            <div class="flex items-start gap-4">
                <span class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-500 text-white text-sm font-bold flex items-center justify-center">3</span>
                <div>
                    <p class="font-semibold text-slate-900">Listen, chat, and react together</p>
                    <p class="text-sm text-slate-600 mt-0.5">When the timer hits zero, everyone's audio starts at the same point. Chat and emoji reactions flow in real time.</p>
                </div>
            </div>
        </div>

        <div class="mt-12 text-center">
            <a href="{{ route('register') }}" x-data="{ loading: false }" @click="loading = true" :class="loading ? 'opacity-75 cursor-wait' : ''" class="inline-flex items-center gap-2 px-8 py-3 text-base font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
                <svg x-show="loading" class="animate-spin h-5 w-5" width="20" height="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                <span x-show="!loading">Start listening together →</span>
                <span x-show="loading" x-cloak>Loading...</span>
            </a>
        </div>
    </section>

    <footer class="border-t border-emerald-100 py-6 text-center text-xs text-slate-400">
        TogetherCast.io - listen together
    </footer>

</body>
</html>
