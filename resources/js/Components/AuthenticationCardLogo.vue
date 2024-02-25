<script setup>
import { Link } from '@inertiajs/vue3';
import {CpuChipIcon} from "@heroicons/vue/24/outline";
</script>

<template>
    <Link :href="'/'">
        <CpuChipIcon class="h-16 w-16 text-slate-500" />
    </Link>
</template>
