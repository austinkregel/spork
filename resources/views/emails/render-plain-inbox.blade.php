<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        @vite(['resources/css/app.css'])
    </head>
    <body class="font-sans antialiased bg-white dark:bg-stone-900 relative text-black dark:text-white">
        <div class="w-full justify-center items-center flex">
            <button class="py-1 px-2 rounded-lg border border-stone-300 dark:border-stone-600 dark:text-stone-200" onclick="document.querySelectorAll('[data-src]').forEach(e => { e.setAttribute('src', e.getAttribute('data-src')); })">Load images</button>
        </div>
        <pre class="max-w-7xl mx-auto px-8 py-4">{{ $body }}</pre>
    </body>
</html>

