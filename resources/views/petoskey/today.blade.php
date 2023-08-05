@php use App\Models\Forecast; @endphp
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
</head>
<body class="antialiased">
<div
    class="px-8 bg-gradient-to-r from-blue-700 to-indigo-600 relative flex items-top justify-center min-h-screen  sm:items-center sm:pt-0 flex-col">
    <div
        class="my-8 max-w-7xl w-full bg-gradient-to-r dark:from-slate-700 dark:to-stone-600 dark:text-white rounded p-4 flex flex-wrap rounded-lg shadow-lg">
        <div id="weather" class="flex flex-wrap items-center gap-4 w-full">

            <div class="w-1/5 justify-center items-center flex flex-col py-8">
                <div class="text-7xl flex align-top">
                    <span id="temp" class="text-4xl lg:text-7xl ">{{round($weather->temperature)}}</span>
                    <span class="text-lg pt-0  lg:pt-2 pl-2">&deg; f</span>
                </div>
            </div>
            <div class="h-16 w-px border-l"></div>
            <div class="text-sm md:text-base py-10 sm:pl-4 md:pl-8 lg:pl-10">
                {{now()->format('l, F j, Y')}}<br/>
                <span class="text-xs md:text-sm flex flex-wrap items-center gap-1">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                         aria-hidden="true" data-darkreader-inline-fill="">
                      <path clip-rule="evenodd" fill-rule="evenodd"
                            d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z"></path>
                    </svg>
                    Petoskey, MI
                </span>
                <span class="hidden md:block md:text-sm">
                    {{ \Illuminate\Support\Str::title($weather->condition) }}
                </span>
            </div>
            <div class="text-3xl md:text-5xl xl:text-7xl px-8 text-right flex-grow">
                <div class="flex flex-col gap-2">
                    <!-- Emoji based on weather -->
                    <span>{{ $weather->condition_image }}</span>
                    {{--                    <span class="text-xs">{{ $weather->pressure }} hpa {{ $weather->humidity }}% humidity</span>--}}
                </div>
            </div>
        </div>
        <div class="w-full lg:w-1/2 p-4">
            <div class="font-bold text-lg">News</div>
            <ul role="list" class="divide-y divide-gray-100 overflow-y-scroll shadow" style="max-height:435px">
                @foreach($news as $story)
                    <li class="flex items-center justify-between gap-x-6 py-5">
                        <div class="min-w-0 w-full pr-4">
                            <div class="flex items-start justify-between  w-full gap-x-3">
                                <p class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-50">{{$story->content}}</p>
                                <time datetime="{{ $story->last_modified }}"
                                      class="flex-none text-xs text-gray-500 dark:text-gray-400">
                                    {{$story->last_modified->shortRelativeToNowDiffForHumans(true)}}</time>
                            </div>
                            <div
                                class="mt-1 flex items-center gap-x-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                <span class="truncate">{{ $story->author->name }}</span>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="flex flex-wrap justify-between w-full mt-6">
                {{ $news->appends('articles', request()->get('articles', 1))->links('vendor.pagination.tailwind') }}
            </div>
        </div>
        <div class="w-full lg:w-1/2 p-4 flex flex-col">
            <div class="font-bold text-lg">Local Events</div>
            <ul role="list" class="divide-y divide-gray-100 overflow-y-scroll shadow" style="max-height:435px">
                @foreach ($articles as $article)
                    <li class="py-4">
                        <div class="flex items-center gap-x-3 pr-4">
                            <h3 class="flex-auto truncate text-sm font-semibold leading-6 text-gray-900 dark:text-gray-50">
                                {{$article->content}}
                            </h3>
                            <time datetime="{{ $article->last_modified }}"
                                  class="flex-none text-xs text-gray-500 dark:text-gray-400">
                                {{$article->last_modified->shortRelativeToNowDiffForHumans(true)}}
                            </time>
                        </div>
                        <a href="{{ $article->url }}" target="_blank"
                           class="flex items-center gap-x-2 text-xs leading-5 text-gray-500 dark:text-gray-300">
                            <span class="truncate">{{ $article->author->name }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="flex flex-wrap justify-between w-full mt-6">
                {{ $articles->appends('news', request()->get('news', 1))->links('vendor.pagination.tailwind') }}
            </div>
        </div>
    </div>
</div>
</body>
</html>
