<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <meta name="color-scheme" content="dark">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>API Watcher</title>
    
    {{-- In development, use Vite server. In production, use compiled assets --}}
    @if(app()->environment('local') && file_exists(public_path('hot')))
        @vite(['resources/js/app.js', 'resources/css/app.css'])
    @else
        {{-- Load from vendor publish path using manifest --}}
        <link rel="stylesheet" href="{{ \Taoshan98\LaravelApiWatcher\Support\AssetManager::asset('resources/css/app.css') }}">
        <script type="module" src="{{ \Taoshan98\LaravelApiWatcher\Support\AssetManager::asset('resources/js/app.js') }}"></script>
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="h-full bg-black text-[#f0f0f0] antialiased">
    <div id="api-watcher-app"></div>
</body>
</html>
