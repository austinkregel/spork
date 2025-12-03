import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import MessageBubble from '../MessageBubble.vue';

const baseProps = {
    fromLabel: 'Alice',
    timestampLabel: 'just now',
};

describe('MessageBubble', () => {
    it('renders the provided message content', () => {
        const wrapper = mount(MessageBubble, {
            props: baseProps,
            slots: {
                default: '<p>Hello world</p>',
            },
        });

        expect(wrapper.text()).toContain('Hello world');
        expect(wrapper.text()).toContain('Alice');
        expect(wrapper.text()).toContain('just now');
    });

    it('applies outbound alignment when outbound is true', () => {
        const wrapper = mount(MessageBubble, {
            props: {
                ...baseProps,
                outbound: true,
            },
            slots: {
                default: '<p>Outbound</p>',
            },
        });

        expect(wrapper.classes()).toContain('items-end');
    });

    it('renders reply preview when provided', () => {
        const wrapper = mount(MessageBubble, {
            props: {
                ...baseProps,
                reply: {
                    title: 'Bob',
                    body: 'Preview content',
                },
            },
            slots: {
                default: '<p>Message</p>',
            },
        });

        expect(wrapper.text()).toContain('Bob');
        expect(wrapper.text()).toContain('Preview content');
    });
});

