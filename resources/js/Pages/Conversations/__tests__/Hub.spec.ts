import { describe, expect, it, beforeEach, afterEach, vi } from 'vitest';
import { nextTick } from 'vue';
import { mount } from '@vue/test-utils';
import Hub from '../Hub.vue';
import MessageContentRenderer from '@/Components/Spork/Molecules/Conversations/MessageContentRenderer.vue';

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

const flushPromises = () => new Promise((resolve) => setTimeout(resolve));
const originalFetch = global.fetch;
const proxyFetch = vi.fn();

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
        proxyFetch.mockImplementation((request: RequestInfo | URL) => {
            const target = typeof request === 'string' ? request : request.toString();
            const decoded = decodeURIComponent(target);

            if (decoded.includes('tenor.com')) {
                return Promise.resolve({
                    ok: true,
                    json: () =>
                        Promise.resolve({
                            provider: 'tenor',
                            type: 'image',
                            url: 'https://media.tenor.com/resolved.gif',
                        }),
                } as Response);
            }

            return Promise.resolve({
                ok: true,
                json: () =>
                    Promise.resolve({
                        provider: 'giphy',
                        type: 'image',
                        url: 'https://media.giphy.com/media/3o6Zt6ML6BklcajjsA/giphy.gif',
                    }),
            } as Response);
        });
        global.fetch = proxyFetch as unknown as typeof fetch;

        if (typeof navigator !== 'undefined') {
            Object.assign(navigator, {
                clipboard: {
                    writeText: vi.fn(),
                },
            });
        }
    });

    afterEach(() => {
        global.fetch = originalFetch;
    });

    it('renders empty state when no active thread is present', () => {
        const wrapper = mount(Hub);

        expect(wrapper.get('[data-testid="conversation-empty"]').exists()).toBe(true);
        expect(wrapper.text()).toContain('No conversation selected');
    });

    it('renders active thread messages through message bubbles', async () => {
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
                    {
                        id: 'm3',
                        message: 'https://example.com/cat.png',
                        originated_at: '2024-01-01T00:02:00Z',
                        from_person: { id: 2, name: 'Bob' },
                    },
                    {
                        id: 'm4',
                        message: 'https://example.com/article',
                        originated_at: '2024-01-01T00:03:00Z',
                        from_person: { id: 1, name: 'Alice' },
                    },
                    {
                        id: 'm5',
                        message: 'https://tenor.com/view/tf2-heavy-meet-the-heavy-bullet-outsmart-bullet-gif-20206045',
                        originated_at: '2024-01-01T00:04:00Z',
                        from_person: { id: 1, name: 'Alice' },
                    },
                    {
                        id: 'm6',
                        message: 'https://giphy.com/gifs/theoffice-funny-3o6Zt6ML6BklcajjsA',
                        originated_at: '2024-01-01T00:05:00Z',
                        from_person: { id: 2, name: 'Bob' },
                    },
                ],
            },
        };

        const wrapper = mount(Hub);
        await flushPromises();

        expect(wrapper.find('[data-testid="conversation-empty"]').exists()).toBe(false);
        expect(wrapper.findAll('[data-testid="message-bubble"]')).toHaveLength(6);
        expect(wrapper.find('img[src="https://example.com/cat.png"]').exists()).toBe(true);
        expect(wrapper.find('img[src="https://media.tenor.com/resolved.gif"]').exists()).toBe(true);
        expect(wrapper.find('img[src="https://media.giphy.com/media/3o6Zt6ML6BklcajjsA/giphy.gif"]').exists()).toBe(true);
        expect(wrapper.find('a[href="https://example.com/article"]').exists()).toBe(true);
        expect(wrapper.text()).toContain('Thread 10');
    });

    it('shows participant avatars when density is comfortable', async () => {
        mockPage.props = {
            ...defaultPageProps(),
            threads: {
                data: [
                    {
                        id: 42,
                        name: 'Avatar Thread',
                        latest_message_preview: 'Hello',
                        latest_message_at: '2024-01-01T00:00:00Z',
                        participants: [],
                    },
                ],
                total: 1,
            },
            activeThread: {
                id: 42,
                name: 'Avatar Thread',
                participants: [
                    { id: 1, name: 'Alice', photo_url: 'https://example.com/alice.jpg' },
                    { id: 2, name: 'Bob', photo_url: 'https://example.com/bob.jpg' },
                ],
                messages: [
                    {
                        id: 'avatar-1',
                        message: 'Simple hello',
                        originated_at: '2024-01-01T00:00:00Z',
                        from_person: { id: 1, name: 'Alice', photo_url: 'https://example.com/alice.jpg' },
                    },
                    {
                        id: 'avatar-2',
                        message: 'Reply hello',
                        originated_at: '2024-01-01T00:01:00Z',
                        from_person: 2,
                    },
                ],
            },
        };

        const wrapper = mount(Hub);
        await flushPromises();

        const avatars = wrapper.findAll('[data-testid="message-avatar"]');

        expect(avatars).toHaveLength(2);
        expect(avatars[0]?.attributes('src')).toBe('https://example.com/alice.jpg');
        expect(avatars[1]?.attributes('src')).toBe('https://example.com/bob.jpg');

        const rows = wrapper.findAll('[data-testid="message-row"]');
        expect(rows).toHaveLength(2);
        expect(rows[0]?.classes()).not.toContain('flex-row-reverse');
        expect(rows[1]?.classes()).not.toContain('flex-row-reverse');

        const bubbles = wrapper.findAll('[data-testid="message-bubble"]');
        expect(bubbles[0]?.classes()).toContain('items-start');
    });

    it('hides avatars when density is not comfortable', async () => {
        mockPage.props = {
            ...defaultPageProps(),
            activeThread: {
                id: 84,
                name: 'Avatar Thread',
                participants: [{ id: 1, name: 'Alice', photo_url: 'https://example.com/alice.jpg' }],
                messages: [
                    {
                        id: 'avatar-1',
                        message: 'Simple hello',
                        originated_at: '2024-01-01T00:00:00Z',
                        from_person: 1,
                    },
                ],
            },
        };

        const wrapper = mount(Hub);
        await flushPromises();

        expect(wrapper.findAll('[data-testid="message-avatar"]')).toHaveLength(1);

        await wrapper.find('select').setValue('text-base');
        await flushPromises();

        expect(wrapper.find('[data-testid="message-avatar"]').exists()).toBe(false);

        const outboundRow = wrapper.find('[data-testid="message-row"]');
        expect(outboundRow.classes()).toContain('flex-row-reverse');

        const outboundBubble = wrapper.find('[data-testid="message-bubble"]');
        expect(outboundBubble.classes()).toContain('items-end');
    });

    it('pins the viewport to the newest message when inline media finishes loading', async () => {
        mockPage.props = {
            ...defaultPageProps(),
            activeThread: {
                id: 91,
                name: 'Media Thread',
                participants: [{ id: 1, name: 'Alice' }],
                messages: [
                    {
                        id: 'media-1',
                        message: 'https://example.com/cat.gif',
                        originated_at: '2024-01-01T00:00:00Z',
                        from_person: { id: 1, name: 'Alice' },
                    },
                ],
            },
        };

        const wrapper = mount(Hub);
        await flushPromises();

        const pane = wrapper.find('main').element as HTMLElement;
        Object.defineProperty(pane, 'scrollHeight', { configurable: true, value: 2000 });
        Object.defineProperty(pane, 'clientHeight', { configurable: true, value: 600 });
        pane.scrollTop = 1400;

        const renderer = wrapper.findComponent(MessageContentRenderer);
        renderer.vm.$emit('media-loaded');
        await nextTick();

        expect(pane.scrollTop).toBe(2000);
    });

    it('does not snap to bottom when the user intentionally scrolls up', async () => {
        mockPage.props = {
            ...defaultPageProps(),
            activeThread: {
                id: 92,
                name: 'Media Thread',
                participants: [{ id: 1, name: 'Alice' }],
                messages: [
                    {
                        id: 'media-1',
                        message: 'https://example.com/cat.gif',
                        originated_at: '2024-01-01T00:00:00Z',
                        from_person: { id: 1, name: 'Alice' },
                    },
                ],
            },
        };

        const wrapper = mount(Hub);
        await flushPromises();

        const pane = wrapper.find('main').element as HTMLElement;
        Object.defineProperty(pane, 'scrollHeight', { configurable: true, value: 2000 });
        Object.defineProperty(pane, 'clientHeight', { configurable: true, value: 400 });
        pane.scrollTop = 1200;
        await wrapper.find('main').trigger('scroll');

        pane.scrollTop = 1200;

        const renderer = wrapper.findComponent(MessageContentRenderer);
        renderer.vm.$emit('media-loaded');
        await nextTick();

        expect(pane.scrollTop).toBe(1200);
    });
});

