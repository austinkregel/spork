<template>
    <ServerInfrastucture :title="`Server · ${server.name}`" :server="server">
        <div class="space-y-6">
            <GlassCard
                title="Operational snapshot"
                subtitle="Overview"
            >
                <template #actions>
                    <GlassButton variant="secondary" @click="router.visit('/-/infrastructure/servers')">
                        Back to infrastructure
                    </GlassButton>
                    <GlassButton>Trigger deploy</GlassButton>
                </template>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <GlassMetricCard
                        v-for="metric in overviewMetrics"
                        :key="metric.label"
                        :label="metric.label"
                        :value="metric.value"
                        :description="metric.caption"
                    />
                </div>
            </GlassCard>

            <GlassCard
                v-if="statsTelemetry"
                title="Live stats snapshot"
                subtitle="Agent telemetry"
            >
                <template #actions>
                    <div class="text-xs text-stone-500 dark:text-stone-400">
                        <div v-if="telemetrySampleAt">Sampled {{ telemetrySampleAt }}</div>
                        <div v-if="telemetryReceivedAt">Ingested {{ telemetryReceivedAt }}</div>
                    </div>
                </template>

                <p class="text-sm text-stone-600 dark:text-stone-300">
                    From the latest ingested <code class="font-mono text-xs">stats</code> event.
                </p>

                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <GlassMetricCard
                        :label="'Host'"
                        :value="statsTelemetry.hostname ?? server.name"
                        :description="`${statsTelemetry.platform ?? 'unknown'} ${statsTelemetry.release ?? ''} · ${statsTelemetry.arch ?? ''} · ${statsTelemetry.cpus ?? '—'} CPU(s)`"
                    />
                    <GlassMetricCard
                        :label="'Kernel'"
                        :value="statsTelemetry.kernelVersion ?? '—'"
                        :description="statsTelemetry.agentVersion ?? 'unknown agent'"
                    />
                    <GlassMetricCard
                        :label="'Uptime'"
                        :value="uptimeLabel ?? '—'"
                        :description="`Last reboot ${statsTelemetry.lastReboot ?? '—'}`"
                    />
                    <GlassMetricCard
                        :label="'Network'"
                        :value="publicIpLabel ?? 'No public IP'"
                        :description="`Internal ${internalIpLabel ?? '—'}`"
                    />
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-2">
                    <GlassSurface class="overflow-hidden">
                        <div class="border-b border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-stone-100/40 dark:bg-stone-800/40 px-4 py-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Disks</p>
                        </div>
                        <div class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
                            <div
                                v-if="diskPressureCount > 0"
                                class="border-b border-amber-300/60 bg-amber-50/60 px-4 py-2 text-xs font-semibold text-amber-800 dark:bg-amber-500/10 dark:text-amber-100"
                            >
                                High disk usage: {{ diskPressureCount }} mount(s) above 85%
                            </div>
                            <div
                                v-for="disk in diskRows"
                                :key="disk.mount"
                                :class="['flex items-center justify-between gap-4 px-4 py-2 text-sm', disk.isHighUsage ? 'bg-amber-50/40 dark:bg-amber-500/5' : '']"
                            >
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-stone-800 dark:text-stone-100">{{ disk.mount }}</p>
                                    <p class="truncate text-xs text-stone-500 dark:text-stone-400">{{ disk.fsname }} · {{ disk.fstype }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="font-semibold text-stone-800 dark:text-stone-100">{{ disk.usedLabel }}</p>
                                    <p class="text-xs text-stone-500 dark:text-stone-400">{{ disk.availLabel }} free · {{ disk.capacityLabel }}</p>
                                </div>
                            </div>
                            <div v-if="!diskRows.length" class="px-4 py-3 text-sm text-stone-500 dark:text-stone-400">
                                No disk stats reported.
                            </div>
                        </div>
                    </GlassSurface>

                    <GlassSurface class="overflow-hidden">
                        <div class="border-b border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-stone-100/40 dark:bg-stone-800/40 px-4 py-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Updates &amp; health</p>
                        </div>
                        <dl class="space-y-3 p-4 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-stone-600 dark:text-stone-300">Updates available</dt>
                                <dd class="font-semibold text-stone-800 dark:text-stone-100">{{ updatesAvailableLabel }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-stone-600 dark:text-stone-300">Security</dt>
                                <dd class="font-semibold text-stone-800 dark:text-stone-100">{{ securityPatchStatusLabel }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-stone-600 dark:text-stone-300">Service health</dt>
                                <dd class="font-semibold text-stone-800 dark:text-stone-100">{{ serviceHealthLabel }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-stone-600 dark:text-stone-300">Time sync</dt>
                                <dd class="font-semibold text-stone-800 dark:text-stone-100">{{ timeSyncLabel }}</dd>
                            </div>
                        </dl>
                    </GlassSurface>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-2">
                    <GlassSurface class="overflow-hidden">
                        <div class="border-b border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-stone-100/40 dark:bg-stone-800/40 px-4 py-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Network interfaces</p>
                        </div>
                        <div class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
                            <div
                                v-for="iface in netInterfaceRows"
                                :key="iface.key"
                                class="flex items-center justify-between gap-4 px-4 py-2 text-sm"
                            >
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-stone-800 dark:text-stone-100">
                                        {{ iface.name }}
                                        <span class="ml-2 font-mono text-xs text-stone-500 dark:text-stone-400">{{ iface.family }}</span>
                                    </p>
                                    <p class="truncate text-xs text-stone-500 dark:text-stone-400">
                                        {{ iface.address }} <span v-if="iface.cidr" class="font-mono">({{ iface.cidr }})</span>
                                    </p>
                                </div>
                                <div class="flex shrink-0 flex-wrap justify-end gap-1">
                                    <GlassPill
                                        v-for="pill in iface.pills"
                                        :key="pill"
                                        :tone="pillTone(pill)"
                                        size="sm"
                                    >
                                        {{ pill }}
                                    </GlassPill>
                                </div>
                            </div>
                            <div v-if="!netInterfaceRows.length" class="px-4 py-3 text-sm text-stone-500 dark:text-stone-400">
                                No network interfaces reported.
                            </div>
                        </div>
                    </GlassSurface>

                    <GlassSurface class="overflow-hidden">
                        <div class="border-b border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] bg-stone-100/40 dark:bg-stone-800/40 px-4 py-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Thermal sensors</p>
                        </div>
                        <div class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
                            <div
                                v-for="sensor in thermalRows"
                                :key="sensor.key"
                                class="flex items-center justify-between gap-4 px-4 py-2 text-sm"
                            >
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-stone-800 dark:text-stone-100">{{ sensor.label }}</p>
                                    <p class="truncate text-xs text-stone-500 dark:text-stone-400">{{ sensor.component }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="font-semibold text-stone-800 dark:text-stone-100">{{ sensor.tempC }}</p>
                                    <p class="text-xs text-stone-500 dark:text-stone-400">{{ sensor.thresholds }}</p>
                                </div>
                            </div>
                            <div v-if="!thermalRows.length" class="px-4 py-3 text-sm text-stone-500 dark:text-stone-400">
                                No thermal sensors reported.
                            </div>
                        </div>
                    </GlassSurface>
                </div>
            </GlassCard>

            <div class="grid gap-6 lg:grid-cols-2">
                <GlassCard
                    title="Traffic targets"
                    subtitle="Linked domains"
                >
                    <template #actions>
                        <GlassButton variant="secondary" size="sm" @click="router.visit('/-/infrastructure/servers')">
                            Manage links
                        </GlassButton>
                    </template>

                    <ul class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)]">
                        <li
                            v-for="domain in linkedDomains"
                            :key="domain.id ?? domain.name"
                            class="flex items-center justify-between py-3 text-sm"
                        >
                            <div>
                                <p class="font-medium text-stone-800 dark:text-stone-100">{{ domain.name }}</p>
                                <p class="text-xs text-stone-500 dark:text-stone-400">{{ domain.provider ?? 'Registrar unknown' }}</p>
                            </div>
                            <GlassPill size="sm">{{ domain.dns_provider ?? 'DNS TBD' }}</GlassPill>
                        </li>
                        <li v-if="!linkedDomains.length" class="py-6 text-center text-sm text-stone-500 dark:text-stone-400">
                            No domains linked yet. Use the infrastructure hub to attach traffic.
                        </li>
                    </ul>
                </GlassCard>

                <GlassCard
                    title="Observed workloads"
                    subtitle="Services &amp; agents"
                >
                    <div class="flex flex-wrap gap-2">
                        <GlassPill
                            v-for="service in services"
                            :key="service.id ?? service.service"
                            size="sm"
                        >
                            {{ service.service ?? service.name }}
                            <span v-if="service.status" class="ml-1 text-[10px] uppercase tracking-wide text-stone-400 dark:text-stone-500">
                                {{ service.status }}
                            </span>
                        </GlassPill>
                        <p v-if="!services.length" class="text-sm text-stone-500 dark:text-stone-400">
                            No agents have reported running services yet.
                        </p>
                    </div>

                    <div class="mt-4 space-y-2 rounded-lg border border-dashed border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-stone-500 dark:text-stone-400">
                            Automation hooks
                        </p>
                        <p class="text-sm text-stone-600 dark:text-stone-300">
                            Register deployment or monitoring automations here to roll out updates, rotate keys, or push config.
                        </p>
                    </div>
                </GlassCard>
            </div>

            <GlassCard title="Recent events" subtitle="Activity">
                <ol class="relative space-y-6 border-l border-[var(--color-glass-border-light)] dark:border-[var(--color-glass-border-dark)] pl-6">
                    <li v-for="event in activityTimeline" :key="event.id" class="relative">
                        <span class="absolute -left-2 top-1 h-3 w-3 rounded-full bg-indigo-500" aria-hidden="true"></span>
                        <p class="text-sm font-semibold text-stone-800 dark:text-stone-50">{{ event.title }}</p>
                        <p class="text-xs text-stone-500 dark:text-stone-400">{{ event.timestamp }}</p>
                        <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">{{ event.description }}</p>
                    </li>
                    <li v-if="!activityTimeline.length" class="text-sm text-stone-500 dark:text-stone-400">
                        No recent events recorded for this server.
                    </li>
                </ol>
            </GlassCard>
        </div>
    </ServerInfrastucture>
</template>

<script setup>
import ServerInfrastucture from "@/Layouts/ServerInfrastucture.vue";
import GlassCard from "@/Components/Glass/GlassCard.vue";
import GlassSurface from "@/Components/Glass/GlassSurface.vue";
import GlassButton from "@/Components/Glass/GlassButton.vue";
import GlassMetricCard from "@/Components/Glass/GlassMetricCard.vue";
import GlassPill from "@/Components/Glass/GlassPill.vue";
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

function pillTone(pill) {
    if (pill === 'public') return 'success';
    if (pill === 'overlay') return 'info';
    return 'neutral';
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
