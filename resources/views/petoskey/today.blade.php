@php use App\Forecast; @endphp
@php
    /**
    * @var Forecast $weather
     */
@endphp<!DOCTYPE html>
<html class="dark">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="robots" content="noindex"/>
    <title>Today, in Petoskey Michigan</title>
    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js'])
    <style>
        iframe, img, image, video, svg, object, embed, canvas, audio, picture, source {
            max-width: 400px;
        }
    </style>
</head>
<body class="antialiased">
<div
    class="px-8 bg-gradient-to-r from-blue-700 to-indigo-600 relative flex items-top justify-center min-h-screen  sm:items-center sm:pt-0 flex-col">

</div>
</body>
</html>
