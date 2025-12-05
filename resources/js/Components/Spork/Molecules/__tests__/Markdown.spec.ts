import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import Markdown from '../Markdown.vue';

describe('Markdown', () => {
    it('renders spoiler spans for double pipe syntax', () => {
        const wrapper = mount(Markdown, {
            props: {
                source: 'Hidden ||secret|| text',
            },
        });

        const spoiler = wrapper.find('.spoiler');
        expect(spoiler.exists()).toBe(true);
        expect(spoiler.text()).toBe('secret');
    });

    it('does not treat exclamation marks as spoilers', () => {
        const wrapper = mount(Markdown, {
            props: {
                source: 'Hidden !!secret!! text',
            },
        });

        expect(wrapper.find('.spoiler').exists()).toBe(false);
        expect(wrapper.text()).toContain('!!secret!!');
    });
});

