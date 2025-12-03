import { describe, expect, it, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import Hub from '../Hub.vue';

function defaultPageProps() {
    return {
        threads: {
            data: [],
            total: 0,
        },
        activeThread: null,
        labels: {
            title: 'Unified Chat',
        },
        composer: {
            emoji: [],
        },
        auth: {
            user: {
                person: { id: 1 },
            },
        },
    };
}

const mockRouter = vi.hoisted(() => ({
    visit: vi.fn(),
    reload: vi.fn(),
}));

const mockPage = vi.hoisted(() => ({
    props: defaultPageProps(),
}));

vi.mock('@inertiajs/vue3', () => ({
    router: mockRouter,
    usePage: () => mockPage,
}));

vi.mock('@/Layouts/AppLayout.vue', () => ({
    default: {
        name: 'AppLayout',
        props: ['title'],
        template: '<div><slot /></div>',
    },
}));

vi.mock('vue3-markdown-it', () => ({
    default: {
        name: 'Markdown',
        props: ['source'],
        template: '<div class="markdown" v-html="source"></div>',
    },
}));

vi.mock('axios', () => ({
    default: {
        post: vi.fn(() => Promise.resolve()),
        delete: vi.fn(() => Promise.resolve()),
    },
}));

describe('Hub.vue', () => {
    beforeEach(() => {
        mockPage.props = defaultPageProps();
        mockRouter.visit.mockReset();
        mockRouter.reload.mockReset();
        global.route = vi.fn((name, id) => `${name}.${id ?? ''}`);

        if (typeof navigator !== 'undefined') {
            Object.assign(navigator, {
                clipboard: {
                    writeText: vi.fn(),
                },
            });
        }
    });

    it('renders empty state when no active thread is present', () => {
        const wrapper = mount(Hub);

        expect(wrapper.get('[data-testid="conversation-empty"]').exists()).toBe(true);
        expect(wrapper.text()).toContain('No conversation selected');
    });

    it('renders active thread messages through message bubbles', () => {
        mockPage.props = {
            ...defaultPageProps(),
            threads: {
                data: [
                    {
                        id: 10,
                        name: 'Thread 10',
                        latest_message_preview: 'hi',
                        latest_message_at: '2024-01-01T00:00:00Z',
                        participants: [],
                    },
                ],
                total: 1,
            },
            activeThread: {
                id: 10,
                name: 'Thread 10',
                participants: [
                    { id: 1, name: 'Alice' },
                    { id: 2, name: 'Bob' },
                ],
                messages: [
                    {
                        id: 'm1',
                        message: 'Hello there',
                        originated_at: '2024-01-01T00:00:00Z',
                        from_person: { id: 1, name: 'Alice' },
                    },
                    {
                        id: 'm2',
                        message: 'Reply body',
                        originated_at: '2024-01-01T00:01:00Z',
                        from_person: { id: 2, name: 'Bob' },
                        reply_to: {
                            from_person: { name: 'Alice' },
                            preview: 'Hello there',
                        },
                    },
                ],
            },
        };

        const wrapper = mount(Hub);

        expect(wrapper.find('[data-testid="conversation-empty"]').exists()).toBe(false);
        expect(wrapper.findAll('[data-testid="message-bubble"]')).toHaveLength(2);
        expect(wrapper.text()).toContain('Thread 10');
    });
});

