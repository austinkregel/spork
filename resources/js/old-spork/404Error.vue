<template>
    <div class="w-full h-full flex flex-col items-center justify-center ">
        <div class="h-20 flex items-center justify-center gap-4">
            <div class="mb-8 text-5xl font-bold text-blue-600 dark:text-blue-400">404</div>
            <hr class="border-l border-gray-300 h-full" />

            <div class="flex flex-col gap-2">
                <div class="text-5xl font-bold text-gray-900 dark:text-gray-50">Page not found</div>
                <div class="text-lg text-gray-500 dark:text-gray-400">Please check the URL and try again.</div>
            </div>
        </div>
        <div class="mt-8 flex gap-4">
            <router-link to="/" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Home
            </router-link>

            
            <router-link to="/login" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-blue-600 border-blue-600 dark:text-blue-300 dark:border-blue-300 hover:text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Contact us
            </router-link>

        </div>
    </div>
</template>