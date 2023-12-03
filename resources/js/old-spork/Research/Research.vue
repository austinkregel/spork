<template>
    <dual-menu-panel
        name="Research"
        :navigation="routes"
        redirect="/research"
        store-query-action="queryStore"
        feature="research"
    />
</template>

<script>
import DualMenuPanel from "../../components/DualMenuPanel";
import {CreditCardIcon, HomeIcon, PencilIcon, ShoppingCartIcon} from "@heroicons/vue/outline";

export default {
    name: "Research",
    components: {
        DualMenuPanel,
    },

    computed: {
        routes() {
            return [
                {
                    name: 'Home',
                    href: '/research',
                    icon: HomeIcon,
                    current: false,
                },
                {
                    name: 'Topics',
                    href: '#',
                    icon: PencilIcon,
                    current: false,
                    children: this.$store.getters?.features?.research?.map(list => ({
                        name: list.name,
                        href: '/research/' + list.id,
                        current: false,
                    })) ?? []
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
