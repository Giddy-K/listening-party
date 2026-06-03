<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>TogetherCast.io — Listen Together</title>
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
    <x-button class="absolute z-50 top-4 left-4" xs flat href="/">← Home</x-button>
    {{ $slot }}
</body>

</html>
