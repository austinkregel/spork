import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import TagPill from '../TagPill.vue';

describe('TagPill', () => {
    it('renders tag.name.en when present', () => {
        const wrapper = mount(TagPill, {
            props: {
                tag: { id: 1, name: { en: 'Groceries' } },
            },
        });

        expect(wrapper.text()).toContain('Groceries');
    });

    it('renders tag.name when it is a string', () => {
        const wrapper = mount(TagPill, {
            props: {
                tag: { id: 1, name: 'Bills' },
            },
        });

        expect(wrapper.text()).toContain('Bills');
    });

    it('renders raw string tags', () => {
        const wrapper = mount(TagPill, {
            props: {
                tag: 'Dining',
            },
        });

        expect(wrapper.text()).toContain('Dining');
    });
});





