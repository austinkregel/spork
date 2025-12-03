import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import SidebarThreadItem from '../SidebarThreadItem.vue';

const baseThread = {
    id: 1,
    name: 'Thread Alpha',
    latest_message_preview: 'Most recent',
    latest_message_at: '2024-01-01T00:00:00Z',
    participants: [
        { id: 1, name: 'Alice' },
        { id: 2, name: 'Bob' },
        { id: 3, name: 'Carol' },
    ],
};

describe('SidebarThreadItem', () => {
    it('renders thread details and truncates participants to max', () => {
        const wrapper = mount(SidebarThreadItem, {
            props: {
                thread: baseThread,
                active: false,
                formatRelative: vi.fn(() => '1m ago'),
                maxParticipants: 2,
            },
        });

        expect(wrapper.text()).toContain('Thread Alpha');
        expect(wrapper.text()).toContain('Most recent');
        expect(wrapper.text()).toContain('1m ago');
        expect(wrapper.text()).toContain('Alice');
        expect(wrapper.text()).toContain('Bob');
        expect(wrapper.text()).not.toContain('Carol');
    });

    it('emits select event when clicked', async () => {
        const wrapper = mount(SidebarThreadItem, {
            props: {
                thread: baseThread,
                active: true,
                formatRelative: vi.fn(() => 'now'),
            },
        });

        await wrapper.find('button').trigger('click');

        expect(wrapper.emitted('select')).toBeTruthy();
        expect(wrapper.emitted('select')?.[0]?.[0]).toEqual(baseThread);
    });
});

