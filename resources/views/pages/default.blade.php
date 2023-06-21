
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex" />

    <link rel="stylesheet" href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" />

    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet" />

    <style>
        body {
            font-family: 'Nunito';
        }
    </style>
</head>
<body class="antialiased">
<div class="relative flex items-top justify-center min-h-screen bg-gray-100 dark:bg-gray-900 sm:items-center sm:pt-0">
    <main>
        <nav class="flex ml-8 mb-5 pt-4 md:pt-0" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-4">
                <li>
                    <div>
                        <a href="https://forge.laravel.com/servers" target="_blank" class="text-gray-400 hover:text-gray-500">

                            <svg class="flex-shrink-0 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                            </svg>

                            <span class="sr-only">Servers</span>
                        </a>
                    </div>
                </li>

                <li>
                    <div class="flex items-center">
                        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                        <a href="http://{{$page['domain']}}" target="_blank" aria-current="page" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">{{$page['domain']}}</a>
                    </div>
                </li>
            </ol>
        </nav>

        <div class="">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="px-6 sm:px-2 bg-white border-b border-gray-200">
                        <div>
                            <div class="max-w-7xl pt-6 pb-12 px-2 sm:px-2 lg:px-6">
                                <div class="max-w-3xl ">
                                    <p class="mt-4 font-medium text-lg text-gray-800">We've finished preparing your site for you. Here are the next steps:</p>
                                </div>

                                <dl class="mt-10 space-y-10 sm:space-y-0 sm:grid sm:grid-cols-1 sm:gap-x-6 sm:gap-y-12 lg:grid-cols-3 lg:gap-x-8">
                                    <div class="flex">
                                        <!-- Heroicon name: check -->
                                        <svg class="flex-shrink-0 h-6 w-6 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>

                                        <div class="ml-3">
                                            <dt class="text-lg leading-6 font-medium text-gray-900">
                                                Install your project
                                            </dt>
                                            <dd class="mt-2 text-base text-gray-500">
                                                Install the project that contains your application's code.
                                            </dd>
                                        </div>
                                    </div>

                                    <div class="flex">
                                        <!-- Heroicon name: check -->
                                        <svg class="flex-shrink-0 h-6 w-6 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <div class="ml-3">
                                            <dt class="text-lg leading-6 font-medium text-gray-900">
                                                Setup your domain
                                            </dt>
                                            <dd class="mt-2 text-base text-gray-500">
                                                Rename the site's domain to the domain you plan to actually use.
                                            </dd>
                                        </div>
                                    </div>


                                    <div class="flex">
                                        <!-- Heroicon name: check -->
                                        <svg class="flex-shrink-0 h-6 w-6 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <div class="ml-3">
                                            <dt class="text-lg leading-6 font-medium text-gray-900">
                                                Configure your DNS records
                                            </dt>
                                            <dd class="mt-2 text-base text-gray-500">
                                                Configure the DNS records for this site at your domain registrar.
                                            </dd>
                                        </div>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
