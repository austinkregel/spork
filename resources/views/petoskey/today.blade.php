
<!DOCTYPE html>
<html class="dark">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex" />
    <title>Today, in Petoskey Michigan</title>
    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js'])
</head>
<body class="antialiased">
<div class="px-8 bg-gradient-to-r from-blue-700 to-indigo-600 relative flex items-top justify-center min-h-screen  sm:items-center sm:pt-0 flex-col">
    <div class="my-8 max-w-7xl w-full bg-gradient-to-r dark:from-slate-700 dark:to-stone-600 dark:text-white rounded p-4 flex flex-wrap rounded-lg shadow-lg">
        <div id="weather" class="flex flex-wrap items-center gap-4 w-full">

            <div class="w-1/5 justify-center items-center flex py-8">
                <div class="text-7xl flex align-top">
                    <span id="temp" class="text-4xl lg:text-7xl ">76</span>
                    <span class="text-lg pt-0  lg:pt-2 pl-2">&deg; f</span>
                </div>
            </div>
            <div class="h-16 w-px border-l"></div>
            <div class="text-sm md:text-base py-10 sm:pl-4 md:pl-8 lg:pl-10">
                Monday, July 12, 2021<br />
                <span class="text-xs md:text-sm flex flex-wrap items-center gap-1">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" data-darkreader-inline-fill="">
                      <path clip-rule="evenodd" fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z"></path>
                    </svg>
                    Petoskey, MI
                </span>
                <span class="hidden md:block md:text-sm">Cloudy</span>
            </div>
            <div class="text-5xl md:text-7xl px-8 text-right flex-grow">
                <div class="flex flex-col gap-2">
                    <!-- Emoji based on weather -->
                    <span>🌤</span>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-1/2 p-4">
            <div class="font-bold text-lg">News</div>
            <ul role="list" class="divide-y divide-gray-100">
                <li class="flex items-center justify-between gap-x-6 py-5">
                    <div class="min-w-0">
                        <div class="flex items-start gap-x-3">
                            <p class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-50">GraphQL API</p>
                            <p class="rounded-md whitespace-nowrap mt-0.5 px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset text-green-700 bg-green-50 ring-green-600/20">Complete</p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                            <p class="whitespace-nowrap">Due on <time datetime="2023-03-17T00:00Z">March 17, 2023</time></p>
                            <svg viewBox="0 0 2 2" class="h-0.5 w-0.5 fill-current">
                                <circle cx="1" cy="1" r="1" />
                            </svg>
                            <p class="truncate">Created by Leslie Alexander</p>
                        </div>
                    </div>
                    <div class="flex flex-none items-center gap-x-4">
                        <a href="#" class="hidden rounded-md bg-white dark:bg-gray-800 px-2.5 py-1.5 text-sm font-semibold text-gray-900 dark:text-gray-50 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 sm:block">View project<span class="sr-only">, GraphQL API</span></a>
                        <div class="relative flex-none">
                            <button type="button" class="-m-2.5 block p-2.5 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:text-gray-50" id="options-menu-0-button" aria-expanded="false" aria-haspopup="true">
                                <span class="sr-only">Open options</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM10 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM11.5 15.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </li>
                <li class="flex items-center justify-between gap-x-6 py-5">
                    <div class="min-w-0">
                        <div class="flex items-start gap-x-3">
                            <p class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-50">New benefits plan</p>
                            <p class="rounded-md whitespace-nowrap mt-0.5 px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset text-gray-600 bg-gray-50 ring-gray-500/10">In progress</p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                            <p class="whitespace-nowrap">Due on <time datetime="2023-05-05T00:00Z">May 5, 2023</time></p>
                            <svg viewBox="0 0 2 2" class="h-0.5 w-0.5 fill-current">
                                <circle cx="1" cy="1" r="1" />
                            </svg>
                            <p class="truncate">Created by Leslie Alexander</p>
                        </div>
                    </div>
                    <div class="flex flex-none items-center gap-x-4">
                        <a href="#" class="hidden rounded-md bg-white dark:bg-gray-800 px-2.5 py-1.5 text-sm font-semibold text-gray-900 dark:text-gray-50 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 sm:block">View project<span class="sr-only">, New benefits plan</span></a>
                        <div class="relative flex-none">
                            <button type="button" class="-m-2.5 block p-2.5 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:text-gray-50" id="options-menu-1-button" aria-expanded="false" aria-haspopup="true">
                                <span class="sr-only">Open options</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM10 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM11.5 15.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0z" />
                                </svg>
                            </button>

                        </div>
                    </div>
                </li>
                <li class="flex items-center justify-between gap-x-6 py-5">
                    <div class="min-w-0">
                        <div class="flex items-start gap-x-3">
                            <p class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-50">Onboarding emails</p>
                            <p class="rounded-md whitespace-nowrap mt-0.5 px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset text-gray-600 bg-gray-50 ring-gray-500/10">In progress</p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                            <p class="whitespace-nowrap">Due on <time datetime="2023-05-25T00:00Z">May 25, 2023</time></p>
                            <svg viewBox="0 0 2 2" class="h-0.5 w-0.5 fill-current">
                                <circle cx="1" cy="1" r="1" />
                            </svg>
                            <p class="truncate">Created by Courtney Henry</p>
                        </div>
                    </div>
                    <div class="flex flex-none items-center gap-x-4">
                        <a href="#" class="hidden rounded-md bg-white dark:bg-gray-800 px-2.5 py-1.5 text-sm font-semibold text-gray-900 dark:text-gray-50 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 sm:block">View project<span class="sr-only">, Onboarding emails</span></a>
                        <div class="relative flex-none">
                            <button type="button" class="-m-2.5 block p-2.5 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:text-gray-50" id="options-menu-2-button" aria-expanded="false" aria-haspopup="true">
                                <span class="sr-only">Open options</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM10 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM11.5 15.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </li>
                <li class="flex items-center justify-between gap-x-6 py-5">
                    <div class="min-w-0">
                        <div class="flex items-start gap-x-3">
                            <p class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-50">iOS app</p>
                            <p class="rounded-md whitespace-nowrap mt-0.5 px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset text-gray-600 bg-gray-50 ring-gray-500/10">In progress</p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                            <p class="whitespace-nowrap">Due on <time datetime="2023-06-07T00:00Z">June 7, 2023</time></p>
                            <svg viewBox="0 0 2 2" class="h-0.5 w-0.5 fill-current">
                                <circle cx="1" cy="1" r="1" />
                            </svg>
                            <p class="truncate">Created by Leonard Krasner</p>
                        </div>
                    </div>
                    <div class="flex flex-none items-center gap-x-4">
                        <a href="#" class="hidden rounded-md bg-white dark:bg-gray-800 px-2.5 py-1.5 text-sm font-semibold text-gray-900 dark:text-gray-50 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 sm:block">View project<span class="sr-only">, iOS app</span></a>
                        <div class="relative flex-none">
                            <button type="button" class="-m-2.5 block p-2.5 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:text-gray-50" id="options-menu-3-button" aria-expanded="false" aria-haspopup="true">
                                <span class="sr-only">Open options</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM10 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM11.5 15.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </li>
                <li class="flex items-center justify-between gap-x-6 py-5">
                    <div class="min-w-0">
                        <div class="flex items-start gap-x-3">
                            <p class="text-sm font-semibold leading-6 text-gray-900 dark:text-gray-50">Marketing site redesign</p>
                            <p class="rounded-md whitespace-nowrap mt-0.5 px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset text-yellow-800 bg-yellow-50 ring-yellow-600/20">Archived</p>
                        </div>
                        <div class="mt-1 flex items-center gap-x-2 text-xs leading-5 text-gray-500 dark:text-gray-400">
                            <p class="whitespace-nowrap">Due on <time datetime="2023-06-10T00:00Z">June 10, 2023</time></p>
                            <svg viewBox="0 0 2 2" class="h-0.5 w-0.5 fill-current">
                                <circle cx="1" cy="1" r="1" />
                            </svg>
                            <p class="truncate">Created by Courtney Henry</p>
                        </div>
                    </div>
                    <div class="flex flex-none items-center gap-x-4">
                        <a href="#" class="hidden rounded-md bg-white dark:bg-gray-800 px-2.5 py-1.5 text-sm font-semibold text-gray-900 dark:text-gray-50 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 sm:block">View project<span class="sr-only">, Marketing site redesign</span></a>
                        <div class="relative flex-none">
                            <button type="button" class="-m-2.5 block p-2.5 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:text-gray-50" id="options-menu-4-button" aria-expanded="false" aria-haspopup="true">
                                <span class="sr-only">Open options</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M10 3a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM10 8.5a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM11.5 15.5a1.5 1.5 0 10-3 0 1.5 1.5 0 003 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </li>
            </ul>

        </div>
        <div class="w-full lg:w-1/2 p-4 flex flex-col">
            <div class="font-bold text-lg">Local Events</div>
            <ul role="list" class="divide-y divide-gray-100 overflow-y-scroll shadow" style="max-height:435px">
                @foreach ($articles as $article)
                <li class="py-4">
                    <div class="flex items-center gap-x-3">
                        <h3 class="flex-auto truncate text-sm font-semibold leading-6 text-gray-900 dark:text-gray-50">
                            {{$article->content}}
                        </h3>
                        <time datetime="{{ $article->last_modified }}" class="flex-none text-xs text-gray-500 dark:text-gray-400">
                            {{$article->last_modified->shortRelativeToNowDiffForHumans(true)}}</time>
                    </div>
                    <a href="{{ $article->url }}" target="_blank" class="flex items-center gap-x-2 text-xs leading-5 text-gray-500 dark:text-gray-300">
                        <span class="truncate">{{ $article->url }}</span>
                    </a>
                </li>
                @endforeach
            </ul>
            <div class="flex flex-wrap justify-between w-full mt-6">
                {{ $articles->links('vendor.pagination.tailwind') }}

            </div>
        </div>
    </div>
</div>
</body>
</html>
