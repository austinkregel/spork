<script setup>

import {defineComponent, ref} from "vue";
import SporkDynamicInput from "@/Components/Spork/SporkDynamicInput.vue";

export default defineComponent({
    components: {SporkDynamicInput}
})

const value = ref(0)
</script>

<template>
    <div>
        <SporkDynamicInput
             v-model="value"

        />
    </div>
</template>

<style scoped>

</style>
