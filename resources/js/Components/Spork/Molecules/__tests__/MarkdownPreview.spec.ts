import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import MarkdownPreview from '../MarkdownPreview.vue';

describe('MarkdownPreview', () => {
    it('wraps spoiler content in spoiler tag', () => {
        const wrapper = mount(MarkdownPreview, {
            props: {
                source: 'Hidden ||secret|| text\n\nSecond paragraph',
            },
        });

        const spoiler = wrapper.find('.spoiler');
        expect(spoiler.exists()).toBe(true);
        expect(spoiler.text()).toBe('secret');
    });
});

