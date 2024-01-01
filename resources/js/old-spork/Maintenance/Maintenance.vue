<template>
    <dual-menu-panel
        name="Maintenance"
        :navigation="routes"
        redirect="/Maintenance"
        store-query-action="queryStore"
        feature="Maintenance"
    />
</template>

<script>
import DualMenuPanel from "../../components/DualMenuPanel";
import { HomeIcon, PencilIcon, TruckIcon } from "@heroicons/vue/outline";

export default {
    name: "Maintenance",
    components: {
        DualMenuPanel,
    },

    computed: {
        routes() {
            return [
                {
                    name: 'Properties',
                    href: '/maintenance/properties',
                    icon: HomeIcon,
                    current: false,
                },
                {
                    name: 'Garage',
                    href: '/maintenance/garage',
                    icon: TruckIcon,
                    current: false,
                }
            ]
        }
    },
    mounted() {
    }
}
</script>

<style scoped>

</style>
