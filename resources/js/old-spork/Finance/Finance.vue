<template>
    <dual-menu-panel
        name="Finance"
        :navigation="routes"
        redirect="/finance"
        store-query-action="queryFinance"
    />
</template>

<script>
    import DualMenuPanel from "../../components/DualMenuPanel";
    import {HomeIcon, TagIcon, DocumentAddIcon, CogIcon } from "@heroicons/vue/outline";

    export default {
        name: "Finance",
        components: {
            DualMenuPanel,
        },

        computed: {
            routes() {
                return [
                    {
                        name: 'Dashboard',
                        href: '/finance/dashboard',
                        icon: HomeIcon,
                        current: false,
                    },
                    {
                        name: 'Bills',
                        href: '/finance/bills',
                        icon: DocumentAddIcon,
                        current: false,
                    },
                    {
                        name: 'Groups',
                        href: '/finance/groups',
                        icon: TagIcon,
                        current: false,
                    },
                    {
                        name: 'Settings',
                        href: '/finance/settings',
                        icon: CogIcon,
                        current: false,
                    },
                ]
            }
        },
        created() {
        }
    }

</script>

<style scoped>

</style>
