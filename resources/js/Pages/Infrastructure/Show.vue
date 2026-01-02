<template>
    <ServerInfrastucture :title="`Server · ${server.name}`" :server="server">
        <div class="space-y-6">
            <section class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 p-6 shadow-sm space-y-4">
                <header class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400 font-semibold">
                            Overview
                        </p>
                        <h2 class="text-2xl font-semibold text-stone-900 dark:text-white">
                            Operational snapshot
                        </h2>
                    </div>
                    <div class="flex gap-2">
                        <SporkButton secondary @click="() => router.visit('/-/servers')">
                            Back to infrastructure
                        </SporkButton>
                        <SporkButton primary>
                            Trigger deploy
                        </SporkButton>
                    </div>
                </header>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div v-for="metric in overviewMetrics" :key="metric.label" class="p-4 rounded-lg border border-stone-200 dark:border-stone-800 bg-stone-50 dark:bg-stone-800/50">
                        <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                            {{ metric.label }}
                        </p>
                        <p class="mt-2 text-2xl font-semibold text-stone-900 dark:text-white">
                            {{ metric.value }}
                        </p>
                        <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                            {{ metric.caption }}
                        </p>
                    </div>
                </div>
            </section>

            <section
                v-if="statsTelemetry"
                class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 p-6 shadow-sm space-y-4"
            >
                <header class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400 font-semibold">
                            Agent telemetry
                        </p>
                        <h3 class="text-lg font-semibold text-stone-900 dark:text-white">
                            Live stats snapshot
                        </h3>
                        <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">
                            From the latest ingested <code class="font-mono text-xs">stats</code> event.
                        </p>
                    </div>
                    <div class="text-xs text-stone-500 dark:text-stone-400">
                        <div v-if="telemetrySampleAt">Sampled {{ telemetrySampleAt }}</div>
                        <div v-if="telemetryReceivedAt">Ingested {{ telemetryReceivedAt }}</div>
                    </div>
                </header>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="p-4 rounded-lg border border-stone-200 dark:border-stone-800 bg-stone-50 dark:bg-stone-800/50">
                        <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">Host</p>
                        <p class="mt-2 text-base font-semibold text-stone-900 dark:text-white">
                            {{ statsTelemetry.hostname ?? server.name }}
                        </p>
                        <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                            {{ statsTelemetry.platform ?? 'unknown' }} {{ statsTelemetry.release ?? '' }} · {{ statsTelemetry.arch ?? '' }} · {{ statsTelemetry.cpus ?? '—' }} CPU(s)
                        </p>
                    </div>

                    <div class="p-4 rounded-lg border border-stone-200 dark:border-stone-800 bg-stone-50 dark:bg-stone-800/50">
                        <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">Kernel</p>
                        <p class="mt-2 text-base font-semibold text-stone-900 dark:text-white">
                            {{ statsTelemetry.kernelVersion ?? '—' }}
                        </p>
                        <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                            {{ statsTelemetry.agentVersion ?? 'unknown agent' }}
                        </p>
                    </div>

                    <div class="p-4 rounded-lg border border-stone-200 dark:border-stone-800 bg-stone-50 dark:bg-stone-800/50">
                        <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">Uptime</p>
                        <p class="mt-2 text-base font-semibold text-stone-900 dark:text-white">
                            {{ uptimeLabel ?? '—' }}
                        </p>
                        <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                            Last reboot {{ statsTelemetry.lastReboot ?? '—' }}
                        </p>
                    </div>

                    <div class="p-4 rounded-lg border border-stone-200 dark:border-stone-800 bg-stone-50 dark:bg-stone-800/50">
                        <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">Network</p>
                        <p class="mt-2 text-base font-semibold text-stone-900 dark:text-white">
                            {{ publicIpLabel ?? 'No public IP' }}
                        </p>
                        <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                            Internal {{ internalIpLabel ?? '—' }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="border border-stone-200 dark:border-stone-800 rounded-lg overflow-hidden">
                        <div class="px-4 py-2 bg-stone-50 dark:bg-stone-800/50 border-b border-stone-200 dark:border-stone-800">
                            <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                                Disks
                            </p>
                        </div>
                        <div class="divide-y divide-stone-200 dark:divide-stone-800">
                            <div
                                v-if="diskPressureCount > 0"
                                class="px-4 py-2 text-xs font-semibold bg-amber-50 text-amber-800 border-b border-amber-200 dark:bg-amber-900/30 dark:text-amber-100 dark:border-amber-800"
                            >
                                High disk usage: {{ diskPressureCount }} mount(s) above 85%
                            </div>
                            <div
                                v-for="disk in diskRows"
                                :key="disk.mount"
                                class="px-4 py-2 text-sm flex items-center justify-between gap-4"
                                :class="disk.isHighUsage ? 'bg-amber-50/60 dark:bg-amber-900/20' : ''"
                            >
                                <div class="min-w-0">
                                    <p class="font-medium text-stone-800 dark:text-stone-100 truncate">
                                        {{ disk.mount }}
                                    </p>
                                    <p class="text-xs text-stone-500 dark:text-stone-400 truncate">
                                        {{ disk.fsname }} · {{ disk.fstype }}
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="font-semibold text-stone-800 dark:text-stone-100">
                                        {{ disk.usedLabel }}
                                    </p>
                                    <p class="text-xs text-stone-500 dark:text-stone-400">
                                        {{ disk.availLabel }} free · {{ disk.capacityLabel }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="!diskRows.length" class="px-4 py-3 text-sm text-stone-500 dark:text-stone-400">
                                No disk stats reported.
                            </div>
                        </div>
                    </div>

                    <div class="border border-stone-200 dark:border-stone-800 rounded-lg overflow-hidden">
                        <div class="px-4 py-2 bg-stone-50 dark:bg-stone-800/50 border-b border-stone-200 dark:border-stone-800">
                            <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                                Updates & health
                            </p>
                        </div>
                        <div class="p-4 space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-stone-600 dark:text-stone-300">Updates available</span>
                                <span class="font-semibold text-stone-800 dark:text-stone-100">{{ updatesAvailableLabel }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-stone-600 dark:text-stone-300">Security</span>
                                <span class="font-semibold text-stone-800 dark:text-stone-100">{{ securityPatchStatusLabel }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-stone-600 dark:text-stone-300">Service health</span>
                                <span class="font-semibold text-stone-800 dark:text-stone-100">{{ serviceHealthLabel }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-stone-600 dark:text-stone-300">Time sync</span>
                                <span class="font-semibold text-stone-800 dark:text-stone-100">{{ timeSyncLabel }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="border border-stone-200 dark:border-stone-800 rounded-lg overflow-hidden">
                        <div class="px-4 py-2 bg-stone-50 dark:bg-stone-800/50 border-b border-stone-200 dark:border-stone-800">
                            <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                                Network interfaces
                            </p>
                        </div>
                        <div class="divide-y divide-stone-200 dark:divide-stone-800">
                            <div
                                v-for="iface in netInterfaceRows"
                                :key="iface.key"
                                class="px-4 py-2 text-sm flex items-center justify-between gap-4"
                            >
                                <div class="min-w-0">
                                    <p class="font-medium text-stone-800 dark:text-stone-100 truncate">
                                        {{ iface.name }}
                                        <span class="ml-2 text-xs font-mono text-stone-500 dark:text-stone-400">{{ iface.family }}</span>
                                    </p>
                                    <p class="text-xs text-stone-500 dark:text-stone-400 truncate">
                                        {{ iface.address }} <span v-if="iface.cidr" class="font-mono">({{ iface.cidr }})</span>
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-1 justify-end shrink-0">
                                    <span
                                        v-for="pill in iface.pills"
                                        :key="pill"
                                        class="px-2 py-0.5 text-[10px] font-semibold rounded-full border"
                                        :class="pillClass(pill)"
                                    >
                                        {{ pill }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="!netInterfaceRows.length" class="px-4 py-3 text-sm text-stone-500 dark:text-stone-400">
                                No network interfaces reported.
                            </div>
                        </div>
                    </div>

                    <div class="border border-stone-200 dark:border-stone-800 rounded-lg overflow-hidden">
                        <div class="px-4 py-2 bg-stone-50 dark:bg-stone-800/50 border-b border-stone-200 dark:border-stone-800">
                            <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                                Thermal sensors
                            </p>
                        </div>
                        <div class="divide-y divide-stone-200 dark:divide-stone-800">
                            <div
                                v-for="sensor in thermalRows"
                                :key="sensor.key"
                                class="px-4 py-2 text-sm flex items-center justify-between gap-4"
                            >
                                <div class="min-w-0">
                                    <p class="font-medium text-stone-800 dark:text-stone-100 truncate">
                                        {{ sensor.label }}
                                    </p>
                                    <p class="text-xs text-stone-500 dark:text-stone-400 truncate">
                                        {{ sensor.component }}
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="font-semibold text-stone-800 dark:text-stone-100">
                                        {{ sensor.tempC }}
                                    </p>
                                    <p class="text-xs text-stone-500 dark:text-stone-400">
                                        {{ sensor.thresholds }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="!thermalRows.length" class="px-4 py-3 text-sm text-stone-500 dark:text-stone-400">
                                No thermal sensors reported.
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <div class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 p-6 shadow-sm">
                    <header class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-xs uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400 font-semibold">
                                Linked domains
                            </p>
                            <h3 class="text-lg font-semibold text-stone-900 dark:text-white">
                                Traffic targets
                            </h3>
                        </div>
                        <SporkButton secondary xsmall @click="() => router.visit('/-/servers')">
                            Manage links
                        </SporkButton>
                    </header>

                    <ul class="divide-y divide-stone-200 dark:divide-stone-800">
                        <li
                            v-for="domain in linkedDomains"
                            :key="domain.id ?? domain.name"
                            class="py-3 flex items-center justify-between text-sm"
                        >
                            <div>
                                <p class="font-medium text-stone-800 dark:text-stone-100">{{ domain.name }}</p>
                                <p class="text-xs text-stone-500 dark:text-stone-400">
                                    {{ domain.provider ?? 'Registrar unknown' }}
                                </p>
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-200">
                                {{ domain.dns_provider ?? 'DNS TBD' }}
                            </span>
                        </li>
                        <li v-if="!linkedDomains.length" class="py-6 text-sm text-stone-500 dark:text-stone-400 text-center">
                            No domains linked yet. Use the infrastructure hub to attach traffic.
                        </li>
                    </ul>
                </div>

                <div class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 p-6 shadow-sm space-y-4">
                    <header>
                        <p class="text-xs uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400 font-semibold">
                            Services & agents
                        </p>
                        <h3 class="text-lg font-semibold text-stone-900 dark:text-white">
                            Observed workloads
                        </h3>
                    </header>

                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="service in services"
                            :key="service.id ?? service.service"
                            class="px-3 py-1.5 text-xs rounded-full border border-stone-200 dark:border-stone-700 text-stone-700 dark:text-stone-200 bg-stone-50 dark:bg-stone-800/60"
                        >
                            {{ service.service ?? service.name }}
                            <span v-if="service.status" class="ml-1 text-[10px] uppercase tracking-wide text-stone-400 dark:text-stone-500">
                                {{ service.status }}
                            </span>
                        </span>
                        <p v-if="!services.length" class="text-sm text-stone-500 dark:text-stone-400">
                            No agents have reported running services yet.
                        </p>
                    </div>

                    <div class="border border-dashed border-stone-300 dark:border-stone-700 rounded-lg p-4 space-y-2">
                        <p class="text-xs uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400 font-semibold">
                            Automation hooks
                        </p>
                        <p class="text-sm text-stone-600 dark:text-stone-300">
                            Register deployment or monitoring automations here to roll out updates, rotate keys, or push config.
                        </p>
                    </div>
                </div>
            </section>

            <section class="border border-stone-200 dark:border-stone-800 rounded-lg bg-white dark:bg-stone-900 p-6 shadow-sm">
                <header class="mb-4">
                    <p class="text-xs uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400 font-semibold">
                        Activity
                    </p>
                    <h3 class="text-lg font-semibold text-stone-900 dark:text-white">
                        Recent events
                    </h3>
                </header>

                <ol class="relative border-l border-stone-200 dark:border-stone-800 pl-6 space-y-6">
                    <li v-for="event in activityTimeline" :key="event.id" class="relative">
                        <span class="absolute -left-2 top-1 w-3 h-3 rounded-full bg-indigo-500"></span>
                        <p class="text-sm font-semibold text-stone-800 dark:text-white">
                            {{ event.title }}
                        </p>
                        <p class="text-xs text-stone-500 dark:text-stone-400">
                            {{ event.timestamp }}
                        </p>
                        <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">
                            {{ event.description }}
                        </p>
                    </li>
                    <li v-if="!activityTimeline.length" class="text-sm text-stone-500 dark:text-stone-400">
                        No recent events recorded for this server.
                    </li>
                </ol>
            </section>
        </div>
    </ServerInfrastucture>
</template>

<script setup>
import ServerInfrastucture from "@/Layouts/ServerInfrastucture.vue";
import SporkButton from "@/Components/Spork/SporkButton.vue";
import { computed } from "vue";
import dayjs from "dayjs";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    server: {
        type: Object,
        required: true,
    },
});

const linkedDomains = computed(() => props.server.domains ?? []);
const services = computed(() => props.server.services ?? []);

function formatPercent(value) {
    const num = Number(value);
    if (!Number.isFinite(num)) {
        return null;
    }

    return `${Math.round(num)}%`;
}

function formatBytes(bytes) {
    const num = Number(bytes);
    if (!Number.isFinite(num) || num < 0) {
        return null;
    }

    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let value = num;
    let i = 0;
    while (value >= 1024 && i < units.length - 1) {
        value /= 1024;
        i += 1;
    }

    const digits = i === 0 ? 0 : (value >= 100 ? 0 : (value >= 10 ? 1 : 2));
    return `${value.toFixed(digits)} ${units[i]}`;
}

function formatRatioPercent(numerator, denominator) {
    const n = Number(numerator);
    const d = Number(denominator);
    if (!Number.isFinite(n) || !Number.isFinite(d) || d <= 0) {
        return null;
    }

    return `${Math.round((n / d) * 100)}%`;
}

function formatSeconds(seconds) {
    const s = Number(seconds);
    if (!Number.isFinite(s) || s < 0) {
        return null;
    }

    const days = Math.floor(s / 86400);
    const hours = Math.floor((s % 86400) / 3600);
    const minutes = Math.floor((s % 3600) / 60);

    if (days > 0) {
        return `${days}d ${hours}h`;
    }

    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }

    return `${Math.max(minutes, 0)}m`;
}

const statsTelemetry = computed(() => {
    const telemetry = props.server?.telemetry ?? null;
    if (!telemetry || telemetry.event_type !== 'stats') {
        return null;
    }

    const data = telemetry?.payload?.data ?? null;
    return data && typeof data === 'object' ? data : null;
});

const telemetryReceivedAt = computed(() => {
    const telemetry = props.server?.telemetry ?? null;
    if (!telemetry || telemetry.event_type !== 'stats') {
        return null;
    }

    const ts = telemetry.received_at ?? null;
    if (!ts) {
        return null;
    }

    try {
        return dayjs(ts).format('MMM D, h:mm A');
    } catch {
        return String(ts);
    }
});

const telemetrySampleAt = computed(() => {
    if (!statsTelemetry.value?.ts) {
        return null;
    }

    try {
        return dayjs(statsTelemetry.value.ts).format('MMM D, h:mm A');
    } catch {
        return String(statsTelemetry.value.ts);
    }
});

const uptimeLabel = computed(() => formatSeconds(statsTelemetry.value?.uptimeSec));

const publicIpLabel = computed(() => {
    const serverIp = props.server?.ip_address ?? null;
    if (typeof serverIp === 'string' && serverIp.trim()) {
        return serverIp;
    }

    const ifaces = Array.isArray(statsTelemetry.value?.netIfaces) ? statsTelemetry.value.netIfaces : [];
    const publicIPv4 = ifaces.find((i) => i && i.internal === false && i.family === 'IPv4' && typeof i.address === 'string');
    return publicIPv4?.address ?? null;
});

const internalIpLabel = computed(() => {
    const serverIp = props.server?.internal_ip_address ?? null;
    if (typeof serverIp === 'string' && serverIp.trim()) {
        return serverIp;
    }

    const ifaces = Array.isArray(statsTelemetry.value?.netIfaces) ? statsTelemetry.value.netIfaces : [];
    const internalIPv4 = ifaces.find((i) => i && i.internal === true && i.family === 'IPv4' && typeof i.address === 'string');
    return internalIPv4?.address ?? null;
});

const diskRows = computed(() => {
    const disks = Array.isArray(statsTelemetry.value?.disk) ? statsTelemetry.value.disk : [];

    return disks
        .filter((d) => d && typeof d.mount === 'string' && d.mount)
        .map((d) => {
            const used = Number(d.used);
            const avail = Number(d.avail);
            const total = Number.isFinite(used) && Number.isFinite(avail) ? used + avail : null;
            const usedLabel = total != null ? `${formatBytes(used) ?? '—'} / ${formatBytes(total) ?? '—'}` : (formatBytes(used) ?? '—');
            const availLabel = formatBytes(avail) ?? '—';
            const cap = Number(d.capacity);
            const capacityLabel = Number.isFinite(cap) ? `${Math.round(cap)}%` : '—';

            return {
                mount: d.mount,
                fsname: typeof d.fsname === 'string' ? d.fsname : '—',
                fstype: typeof d.fstype === 'string' ? d.fstype : '—',
                usedLabel,
                availLabel,
                capacityLabel,
                isHighUsage: Number.isFinite(cap) && cap >= 85,
            };
        });
});

const diskPressureCount = computed(() => diskRows.value.filter((d) => d.isHighUsage).length);

const rootDiskRow = computed(() => diskRows.value.find((d) => d.mount === '/') ?? null);

const memoryPercentLabel = computed(() => {
    const mem = statsTelemetry.value?.mem ?? null;
    if (!mem) return null;
    return formatRatioPercent(mem.used, mem.total);
});

const memoryBytesLabel = computed(() => {
    const mem = statsTelemetry.value?.mem ?? null;
    if (!mem) return null;
    const used = formatBytes(mem.used);
    const total = formatBytes(mem.total);
    if (!used || !total) return null;
    return `${used} / ${total}`;
});

function isOverlayInterfaceName(name) {
    return typeof name === 'string' && /^(zt|wg|tailscale|docker|br-|cni|flannel)/.test(name);
}

function pillClass(pill) {
    if (pill === 'public') {
        return 'border-green-300 text-green-700 bg-green-50 dark:border-green-700 dark:text-green-200 dark:bg-green-900/30';
    }
    if (pill === 'internal') {
        return 'border-stone-300 text-stone-700 bg-stone-50 dark:border-stone-700 dark:text-stone-200 dark:bg-stone-800/60';
    }
    if (pill === 'overlay') {
        return 'border-indigo-300 text-indigo-700 bg-indigo-50 dark:border-indigo-700 dark:text-indigo-200 dark:bg-indigo-900/30';
    }
    return 'border-stone-300 text-stone-700 bg-stone-50 dark:border-stone-700 dark:text-stone-200 dark:bg-stone-800/60';
}

const netInterfaceRows = computed(() => {
    const ifaces = Array.isArray(statsTelemetry.value?.netIfaces) ? statsTelemetry.value.netIfaces : [];
    return ifaces
        .filter((i) => i && typeof i.name === 'string' && typeof i.family === 'string' && typeof i.address === 'string')
        .map((i) => {
            const pills = [];
            if (i.internal === false) pills.push('public');
            if (i.internal === true) pills.push('internal');
            if (isOverlayInterfaceName(i.name)) pills.push('overlay');

            return {
                key: `${i.name}-${i.family}-${i.address}`,
                name: i.name,
                family: i.family,
                address: i.address,
                cidr: typeof i.cidr === 'string' ? i.cidr : null,
                pills,
            };
        });
});

const thermalRows = computed(() => {
    const sensors = Array.isArray(statsTelemetry.value?.thermal) ? statsTelemetry.value.thermal : [];
    return sensors
        .filter((s) => s && (typeof s.temperature === 'number' || typeof s.tempC === 'number'))
        .map((s) => {
            const temp = typeof s.tempC === 'number' ? s.tempC : s.temperature;
            const high = typeof s.sensorHigh === 'number' ? s.sensorHigh : (typeof s.high === 'number' ? s.high : null);
            const critical = typeof s.sensorCritical === 'number' ? s.sensorCritical : (typeof s.critical === 'number' ? s.critical : null);

            const label = (typeof s.name === 'string' && s.name.trim())
                ? s.name
                : (typeof s.sensorKey === 'string' ? s.sensorKey : 'sensor');

            const component = (typeof s.component === 'string' && s.component.trim())
                ? s.component
                : 'Unknown';

            const thresholds = [
                Number.isFinite(high) ? `high ${Math.round(high)}°C` : null,
                Number.isFinite(critical) ? `critical ${Math.round(critical)}°C` : null,
            ].filter(Boolean).join(' · ');

            return {
                key: String(s.sensorKey ?? label),
                label,
                component,
                tempC: `${Math.round(Number(temp))}°C`,
                thresholds: thresholds || '—',
            };
        });
});

const updatesAvailableLabel = computed(() => {
    const u = statsTelemetry.value?.updates ?? null;
    if (!u || u.available == null) return '—';
    const n = Number(u.available);
    if (!Number.isFinite(n)) return '—';
    const suffix = u.restartRequired ? ' (restart)' : '';
    return `${n}${suffix}`;
});

const securityPatchStatusLabel = computed(() => {
    const s = statsTelemetry.value?.securityPatchStatus ?? null;
    return typeof s === 'string' && s.trim() ? s : '—';
});

const serviceHealthLabel = computed(() => {
    const h = statsTelemetry.value?.serviceHealth ?? null;
    if (!h) return '—';
    const total = Number(h.total);
    const running = Number(h.running);
    const failed = Number(h.failed);
    if (![total, running, failed].every((n) => Number.isFinite(n))) return '—';
    return `${running}/${total} running · ${failed} failed`;
});

const timeSyncLabel = computed(() => {
    const s = statsTelemetry.value?.timeSyncStatus ?? null;
    return typeof s === 'string' && s.trim() ? s : '—';
});

const overviewMetrics = computed(() => {
    // When we have stats telemetry, prefer it as the canonical “operational snapshot”.
    // Fall back to older/provider-derived fields when telemetry is missing.
    if (statsTelemetry.value) {
        return [
            {
                label: 'Uptime',
                value: uptimeLabel.value ?? '—',
                caption: statsTelemetry.value.lastReboot
                    ? `Last reboot ${statsTelemetry.value.lastReboot}`
                    : (telemetrySampleAt.value ? `Sampled ${telemetrySampleAt.value}` : 'No uptime data'),
            },
            {
                label: 'Heartbeat',
                value: props.server.last_ping_at ? `${Math.max(dayjs().diff(dayjs(props.server.last_ping_at), 'minute'), 1)}m ago` : 'Never',
                caption: telemetryReceivedAt.value
                    ? `Ingested ${telemetryReceivedAt.value}`
                    : (props.server.last_ping_at ? dayjs(props.server.last_ping_at).format('MMM D, h:mm A') : 'No heartbeat yet'),
            },
            {
                label: 'CPU',
                value: formatPercent(statsTelemetry.value.cpu) ?? '—',
                caption: `Load ${statsTelemetry.value.load?.['1m'] ?? '—'} · Agent ${statsTelemetry.value.agentVersion ?? 'unknown'}`,
            },
            {
                label: 'Memory',
                value: memoryPercentLabel.value ?? '—',
                caption: memoryBytesLabel.value ?? 'No memory data',
            },
            {
                label: 'Disk /',
                value: rootDiskRow.value?.capacityLabel ?? '—',
                caption: rootDiskRow.value?.usedLabel ?? 'No disk data',
            },
            {
                label: 'Updates',
                value: updatesAvailableLabel.value,
                caption: [
                    serviceHealthLabel.value !== '—' ? serviceHealthLabel.value : null,
                    timeSyncLabel.value !== '—' ? `Time sync: ${timeSyncLabel.value}` : null,
                ].filter(Boolean).join(' · ') || '—',
            },
            {
                label: 'Monthly cost',
                value: props.server.cost_per_hour ? `$${(Number(props.server.cost_per_hour) * 24 * 30).toFixed(0)}` : '—',
                caption: props.server.cost_per_hour ? `$${Number(props.server.cost_per_hour).toFixed(2)}/hr` : 'Unknown rate',
            },
            {
                label: 'Projects linked',
                value: props.server.projects?.length ?? 0,
                caption: 'Deployments referencing this host',
            },
        ];
    }

    return [
        {
            label: 'Uptime',
            value: props.server.booted_at ? `${dayjs().diff(dayjs(props.server.booted_at), 'hour')}h` : 'Unknown',
            caption: props.server.booted_at ? `Booted ${dayjs(props.server.booted_at).format('MMM D, YYYY')}` : 'No boot record yet',
        },
        {
            label: 'Agent heartbeat',
            value: props.server.last_ping_at ? `${Math.max(dayjs().diff(dayjs(props.server.last_ping_at), 'minute'), 1)}m ago` : 'Never',
            caption: props.server.last_ping_at ? dayjs(props.server.last_ping_at).format('MMM D, h:mm A') : 'No heartbeat yet',
        },
        {
            label: 'Monthly cost',
            value: props.server.cost_per_hour ? `$${(Number(props.server.cost_per_hour) * 24 * 30).toFixed(0)}` : '—',
            caption: props.server.cost_per_hour ? `$${Number(props.server.cost_per_hour).toFixed(2)}/hr` : 'Unknown rate',
        },
        {
            label: 'Projects linked',
            value: props.server.projects?.length ?? 0,
            caption: 'Deployments referencing this host',
        },
    ];
});

const activityTimeline = computed(() => {
    if (props.server.activity) {
        return props.server.activity.map((event) => ({
            id: event.id ?? event.created_at,
            title: event.title ?? event.description ?? 'Activity',
            description: event.description ?? event.meta ?? '',
            timestamp: dayjs(event.created_at ?? event.timestamp).format('MMM D, YYYY h:mm A'),
        }));
    }

    const fallback = [];

    if (props.server.created_at) {
        fallback.push({
            id: 'created',
            title: 'Server linked to Spork',
            description: 'Initial metadata synced from provider.',
            timestamp: dayjs(props.server.created_at).format('MMM D, YYYY h:mm A'),
        });
    }

    if (props.server.updated_at) {
        fallback.push({
            id: 'updated',
            title: 'Metadata refreshed',
            description: 'Latest status pulled from automation agent.',
            timestamp: dayjs(props.server.updated_at).format('MMM D, YYYY h:mm A'),
        });
    }

    return fallback;
});
</script>
