<template>
    <dual-menu-panel
        name="Servers"
        :navigation="routes"
        redirect="/servers"
        store-query-action="queryServers"
    />
</template>

<script>
    import DualMenuPanel from "@components/DualMenuPanel";
    import {ServerIcon} from "@heroicons/vue/outline";

    export default {
        name: "Servers",
        components: {
            DualMenuPanel,
        },

        computed: {
            routes() {
                return [
                    {
                        name: 'Dashboard',
                        href: '/servers/dashboard',
                        icon: ServerIcon,
                        current: false,
                    },
                    {
                        name: 'Services',
                        href: '/servers/services',
                        icon: ServerIcon,
                        current: false,
                    },
                ]
            }
        },
        mounted() {
        }
    }

</script>

<style scoped>

</style>
