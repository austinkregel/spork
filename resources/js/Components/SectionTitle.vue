<template>
    <div class="md:col-span-1 flex justify-between">
        <div class="px-4 sm:px-0">
            <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                <slot name="title" />
            </h3>

            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                <slot name="description" />
            </p>
        </div>

        <div class="px-4 sm:px-0">
            <slot name="aside" />
        </div>
    </div>
</template>
