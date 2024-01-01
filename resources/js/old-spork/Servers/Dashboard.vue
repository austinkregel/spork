<template>
    <div class="flex flex-wrap mt-4" v-if="shouldShow">
        <div class="w-full py-2 px-4 text-2xl font-bold text-stone-800">Service Dashboard</div>
        <div class="w-full pb-8 px-4 text-base font-base text-stone-500">Welcome Back, {{$store.getters.user.name}}!</div>

        <div class="grid grid-cols-3 w-full gap-4 mx-4">
            <div v-for="service in $store.getters.services" :key="service.fqdn" class="bg-white p-4 shadow rounded">

                <div class="flex flex-col gap-2">
                    <div class="flex justify-between items-center">
                        <div class="font-bold">{{ service.name }}</div>
                        <div class="text-stone-600 dark:text-stone-300 text-xs">{{ service.fqdn }}</div>
                    </div>
                    <div class="text-stone-500 text-xs tracking-wide">{{ service.addresses.filter(addr => addr.match(/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/gm)).map(d => d+':'+service.port).join(', ') }}</div>
                </div>
                <div v-if="service.subtypes.concat(service.type).includes('http')">
                    <a :href="'http://'+service.fqdn+':'+service.port" target="_blank" class="text-blue-500 hover:text-blue-700">{{ service.fqdn }}</a>
                </div>
                <div class="flex flex-wrap items-center mt-4 w-full gap-2">
                    <div class="text-sm rounded-full flex gap-1 items-center px-2" :class="[service.is_online ? 'bg-green-50 border border-green-400' : 'bg-red-50 border border-red-400']">
                        <div class="w-2 h-2 rounded-full shadow" :class="[service.is_online ? 'bg-green-500' : 'bg-red-500']"></div>
                        <span :class="[service.is_online ? 'text-green-600' : 'text-red-600']" v-if="service.is_online">Online</span>
                        <span :class="[service.is_online ? 'text-green-600' : 'text-red-600']" v-else>Offline</span>
                    </div>
                    <div  class="text-sm rounded-full flex border border-blue-500 bg-blue-50 text-blue-600 items-center px-2" v-for="tag in service.subtypes.concat(service.type).filter(e => e)" :key="tag">
                        {{ tag }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { CheckCircleIcon, ChevronRightIcon, MailIcon } from '@heroicons/vue/solid'
import CategoryIcon from "@components/CategoryIcon";

export default {
    components: {
        CheckCircleIcon,
        ChevronRightIcon,
        MailIcon,
        CategoryIcon,
    },
    setup() {
        return {
            dayjs
        }
    },
    computed: {
        shouldShow() {
            return import.meta.env.MIX_ADD_service_SUPPORT === 'true'
        }
    },
    mounted() {
        if (!this.shouldShow) {
            return;
        }

        this.$store.dispatch('fetchservices');
    }
}
</script>
