import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import MessageContentRenderer from '../MessageContentRenderer.vue';

vi.mock('vue3-markdown-it', () => ({
    default: {
        name: 'Markdown',
        props: ['source'],
        template: '<div class="markdown">{{ source }}</div>',
    },
}));

const baseMessage = () => ({
    message: 'Hello world',
    html_message: null,
    thumbnail_url: null,
    settings: null,
    from_person: { name: 'Tester' },
});

const mockFetch = vi.fn();
const originalFetch = global.fetch;
const flushPromises = () => new Promise((resolve) => setTimeout(resolve));

beforeEach(() => {
    mockFetch.mockReset();
    global.fetch = mockFetch as unknown as typeof fetch;
});

afterEach(() => {
    global.fetch = originalFetch;
});

describe('MessageContentRenderer', () => {
    it('renders markdown content by default', () => {
        const wrapper = mount(MessageContentRenderer, {
            props: {
                message: baseMessage(),
            },
        });

        expect(wrapper.text()).toContain('Hello world');
    });

    it('renders inline images when message contains image url', () => {
        const wrapper = mount(MessageContentRenderer, {
            props: {
                message: {
                    ...baseMessage(),
                    message: 'https://example.com/cat.png',
                },
            },
        });

        expect(wrapper.find('img').exists()).toBe(true);
    });

    it('emits media-loaded when inline images finish loading', async () => {
        const wrapper = mount(MessageContentRenderer, {
            props: {
                message: {
                    ...baseMessage(),
                    message: 'https://example.com/cat.png',
                },
            },
        });

        const img = wrapper.find('img');
        await img.trigger('load');

        expect(wrapper.emitted('media-loaded')).toBeTruthy();
    });

    it('shows link preview cards for non-image links', () => {
        const wrapper = mount(MessageContentRenderer, {
            props: {
                message: {
                    ...baseMessage(),
                    message: 'https://example.com/article',
                },
            },
        });

        expect(wrapper.text()).toContain('example.com');
    });

    it('renders tenor share links as inline gifs', async () => {
        mockFetch.mockResolvedValueOnce({
            ok: true,
            json: () =>
                Promise.resolve({
                    provider: 'tenor',
                    type: 'image',
                    url: 'https://media.tenor.com/resolved.gif',
                }),
        } as Response);

        const wrapper = mount(MessageContentRenderer, {
            props: {
                message: {
                    ...baseMessage(),
                    message: 'https://tenor.com/view/tf2-heavy-meet-the-heavy-bullet-outsmart-bullet-gif-20206045',
                },
            },
        });

        await flushPromises();
        expect(wrapper.find('img').attributes('src')).toBe('https://media.tenor.com/resolved.gif');
    });

    it('hydrates giphy share links via proxy', async () => {
        mockFetch.mockResolvedValueOnce({
            ok: true,
            json: () =>
                Promise.resolve({
                    provider: 'giphy',
                    type: 'image',
                    url: 'https://media.giphy.com/media/3o6Zt6ML6BklcajjsA/giphy.gif',
                }),
        } as Response);

        const wrapper = mount(MessageContentRenderer, {
            props: {
                message: {
                    ...baseMessage(),
                    message: 'https://giphy.com/gifs/theoffice-funny-3o6Zt6ML6BklcajjsA',
                },
            },
        });

        await flushPromises();
        const img = wrapper.find('img');
        expect(img.exists()).toBe(true);
        expect(img.attributes('src')).toBe('https://media.giphy.com/media/3o6Zt6ML6BklcajjsA/giphy.gif');
    });

    it('emits media-loaded when inline videos report metadata', async () => {
        const wrapper = mount(MessageContentRenderer, {
            props: {
                message: {
                    ...baseMessage(),
                    message: 'https://i.imgur.com/funny.gifv',
                },
            },
        });

        await flushPromises();

        const video = wrapper.find('video');
        expect(video.exists()).toBe(true);

        await video.trigger('loadedmetadata');

        expect(wrapper.emitted('media-loaded')).toBeTruthy();
    });
});

