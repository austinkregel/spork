import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

const mockRouter = vi.hoisted(() => ({
    visit: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    router: mockRouter,
}));

vi.mock('@/Layouts/ServerInfrastucture.vue', () => ({
    default: {
        name: 'ServerInfrastucture',
        props: ['title', 'server'],
        template: '<div><slot /></div>',
    },
}));

vi.mock('@/Components/Glass/GlassButton.vue', () => ({
    default: {
        name: 'GlassButton',
        template: '<button><slot /></button>',
        props: ['variant', 'size', 'href', 'disabled', 'block', 'iconLeft', 'iconRight', 'type'],
    },
}));

import Show from '../Show.vue';

describe('Infrastructure/Show', () => {
    it('does not render CPU metric when telemetry is missing', () => {
        const wrapper = mount(Show, {
            props: {
                server: {
                    name: 'test',
                    domains: [],
                    services: [],
                    projects: [],
                    telemetry: null,
                },
            },
        });

        expect(wrapper.text()).not.toContain('CPU');
    });

    it('renders stats telemetry metrics when event_type=stats', () => {
        const wrapper = mount(Show, {
            props: {
                server: {
                    name: 'node-de8f262',
                    domains: [],
                    services: [],
                    projects: [],
                    telemetry: {
                        event_type: 'stats',
                        received_at: '2026-01-02T13:01:02.367Z',
                        payload: {
                            clientId: 'node-de8f262',
                            data: {
                                agentVersion: 'v0.0.21',
                                cpu: 0.2377,
                                mem: { used: 243597312, total: 1928617984 },
                                load: { '1m': 0.02, '5m': 0.02, '15m': 0 },
                                disk: [{ mount: '/', fsname: '/dev/vda1', fstype: 'ext4', used: 9000, avail: 1000, capacity: 90 }],
                                netIfaces: [{ name: 'eth0', family: 'IPv4', address: '162.243.54.67', cidr: '162.243.54.0/24', internal: false }],
                                thermal: [{ sensorKey: 'cpu_thermal', component: 'CPU', name: 'Cpu thermal', temperature: 42.3 }],
                                updates: { available: 1, restartRequired: false },
                                hostname: 'node-b37beb',
                                timeSyncStatus: 'synced',
                                uptimeSec: 120,
                                lastReboot: '2026-01-02T12:53:10Z',
                            },
                        },
                    },
                },
            },
        });

        expect(wrapper.text()).toContain('CPU');
        expect(wrapper.text()).toContain('Memory');
        expect(wrapper.text()).toContain('Disk /');
        expect(wrapper.text()).toContain('Updates');

        // sanity: CPU rounds to 0%
        expect(wrapper.text()).toContain('0%');
        expect(wrapper.text()).toContain('v0.0.21');
        expect(wrapper.text()).toContain('Time sync: synced');

        expect(wrapper.text()).toContain('Network interfaces');
        expect(wrapper.text()).toContain('Thermal sensors');
        expect(wrapper.text()).toContain('High disk usage');
    });
});


