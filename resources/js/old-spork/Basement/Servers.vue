<template>
        <div class="w-full px-4">
            <div class="py-4 text-lg font-bold tracking-wide">
                Servers
            </div>
            <feature-required :allowMoreThanOne="true" feature="servers"></feature-required>
            <div class="flex flex-wrap -mx-4" v-if="$store.getters.features?.servers?.length > 0">
                <div class="w-1/2" v-for="server in servers">
                    <div class="shadow rounded rounded-lg bg-white dark:bg-slate-700 leading-loose mx-4">
                        <div class="p-4">
                            <div class="w-full flex justify-between align-middle items-center">
                                <span class="text-xl font-bold tracking-wide">{{ server.name }}</span>
                                <span class="font-medium px-4 rounded-lg shadow-sm" :class="{'bg-red-200 text-red-600': !server.is_powered_on, 'bg-green-200 text-green-600': server.is_powered_on }">• {{ server.is_powered_on ? 'On' : 'Off'}} </span>
                            </div>
                            <div class="w-full flex justify-between mt-4">
                                <span class="font-semibold">IP Address</span>
                                <span class="font-bold">{{ server.ip_address  || 'No IP'}} </span>
                            </div>
                            <div class="w-full flex justify-between border-t border-gray-300">
                                <span class="font-semibold">CPU</span>
                                <span class="font-bold">{{ server.threads }} Cores</span>
                            </div>
                            <div class="w-full flex justify-between border-t border-gray-300">
                                <span class="font-semibold">RAM</span>
                                <span class="font-bold">{{ server.memory }} MB</span>
                            </div>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-slate-600 p-4 flex justify-between">
                            <div class="flex flex-col leading-tight">
                                <router-link class="text-blue-500 font-semibold" :to="'/servers/' + server.id">More Information</router-link>
                                <div class="text-xs text-gray-600 dark:text-gray-300">{{ server.updated_at | date }}</div>
                            </div>
                            <div class="flex items-center">
                                <button class="focus:outline-none" @click="$store.dispatch('deleteServer', server)">
                                    <TrashIcon class="w-4 h-4 text-red-600 fill-current" />
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div v-else class="flex flex-wrap w-full">
                <div class="shadow rounded-lg bg-white dark:bg-slate-700 p-4 flex-1 italic">
                    No servers have been linked or created with this platform yet.
                    {{ $store.getters.features?.servers ?? [] }}
                </div>
            </div>
        </div>
</template>

<script>
    import { ViewBoardsIcon, LinkIcon, TrashIcon } from '@heroicons/vue/solid'
    export default {
        name: "Servers",
        components: {
            ViewBoardsIcon,
            LinkIcon,
            TrashIcon
        },
        computed: {
            servers() {
                return this.$store.getters.servers;
            }
        },
        methods: {
            deleteServer() {
                alert("We're not going to delete that yet...")
            }
        }
    }
</script>

<style scoped>
</style>
