import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import NewMessageToastCenter from '../NewMessageToastCenter.vue';
import { __resetMessageToastsForTests, pushMessageToast } from '@/Conversations/message-toast-store';

const mockAxios = vi.hoisted(() => ({
    post: vi.fn(() => Promise.resolve()),
}));

const mockRouter = vi.hoisted(() => ({
    visit: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    router: mockRouter,
}));

vi.mock('axios', () => ({
    default: mockAxios,
}));

describe('NewMessageToastCenter', () => {
    beforeEach(() => {
        __resetMessageToastsForTests();
        mockAxios.post.mockReset();
        mockRouter.visit.mockReset();
        // Component calls window.route(...)
        (window as any).route = vi.fn((name, id) => `${name}.${id}`);
        sessionStorage.clear();
    });

    it('groups multiple messages by thread and wires View thread + inline Reply', async () => {
        pushMessageToast({
            thread_id: 10,
            thread_name: 'Demo thread',
            message_event_id: 'evt-1',
            from_person: { name: 'Alice', photo_url: 'https://example.com/alice.jpg' },
            preview: 'Hello world',
            reply_to: {
                from_person: { name: 'Bob' },
                preview: 'Earlier message',
            },
        });

        pushMessageToast({
            thread_id: 10,
            message_event_id: 'evt-2',
            from_person: { name: 'Alice', photo_url: 'https://example.com/alice.jpg' },
            preview: 'Second message',
        });

        const wrapper = mount(NewMessageToastCenter);

        expect(wrapper.findAll('[data-testid="new-message-toast"]')).toHaveLength(1);
        expect(wrapper.text()).toContain('2 new messages in Demo thread');
        expect(wrapper.text()).toContain('Alice');
        expect(wrapper.text()).toContain('Replying to Bob');
        expect(wrapper.text()).toContain('Earlier message');
        expect(wrapper.text()).toContain('Hello world');
        expect(wrapper.text()).toContain('Second message');

        const buttons = wrapper.findAll('button');
        const viewButton = buttons.find((b) => b.text().includes('View thread'));
        const replyButton = buttons.find((b) => b.text().includes('Reply'));

        expect(viewButton).toBeTruthy();
        expect(replyButton).toBeTruthy();

        await viewButton.trigger('click');
        expect((window as any).route).toHaveBeenCalledWith('chat.show', 10);
        expect(mockRouter.visit).toHaveBeenCalledWith('chat.show.10', expect.any(Object));

        mockRouter.visit.mockReset();

        await replyButton.trigger('click');
        expect(wrapper.text()).toContain('Press Enter to send');

        const textarea = wrapper.find('textarea');
        expect(textarea.exists()).toBe(true);
        await textarea.setValue('Quick reply');

        const sendButton = wrapper.findAll('button').find((b) => b.attributes('title') === 'Send reply');
        expect(sendButton).toBeTruthy();
        await sendButton.trigger('click');

        expect(mockAxios.post).toHaveBeenCalledWith('/api/message/reply', {
            message: 'Quick reply',
            thread_id: 10,
            reply_to_event_id: 'evt-2',
        });
    });
});


