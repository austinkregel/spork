<template>
    <div class="relative bg-white overflow-hidden" :style="{ backgroundImage: 'url(' + background + ')' }">
        <div class="max-w-7xl mx-auto">
            <div class="relative z-10 pb-8 bg-white sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
                <div class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
                    <div class="text-center">
                        <h2 class="text-4xl tracking-tight font-extrabold text-stone-900 sm:text-5xl md:text-6xl">
                            <span class="block xl:inline">{{ title }}</span>
                        </h2>
                        <p class="mt-3 max-w-md mx-auto text-base text-stone-500 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                            {{ description }}
                        </p>
                        <div class="mt-5 max-w-md mx-auto sm:flex sm:justify-center md:mt-8">
                            <div class="rounded-md shadow">
                                <a :href="ctaLink" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-slate-600 hover:bg-slate-700 md:py-4 md:text-lg md:px-10">
                                    {{ ctaText }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'Hero',
    props: {
        background: {
            type: String,
            required: false,
            default: ''
        },
        title: {
            type: String,
            required: true,
            default: 'Hello Hero'
        },
        description: {
            type: String,
            required: false,
            default: 'With a highly uniform component library you can build your own hero section.'
        },
        ctaText: {
            type: String,
            required: false,
            default: 'Call to action'
        },
        ctaLink: {
            type: String,
            required: false,
            default: '#'
        }
    }
}
</script>
