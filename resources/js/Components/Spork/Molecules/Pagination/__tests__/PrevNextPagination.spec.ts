import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

const visit = vi.hoisted(() => vi.fn());

vi.mock('@inertiajs/vue3', () => ({
    router: {
        visit,
    },
}));

import PrevNextPagination from '../PrevNextPagination.vue';

const findButtonByText = (wrapper, text) => {
    return wrapper.findAll('button').find((b) => b.text().trim() === text) ?? null;
};

describe('PrevNextPagination', () => {
    it('hides both buttons when no urls exist', () => {
        const wrapper = mount(PrevNextPagination, {
            props: {
                paginator: {},
            },
        });

        expect(wrapper.text()).not.toContain('Previous');
        expect(wrapper.text()).not.toContain('Next');
    });

    it('visits previous and next urls when clicked', async () => {
        visit.mockClear();

        const wrapper = mount(PrevNextPagination, {
            props: {
                paginator: {
                    prev_page_url: '/prev',
                    next_page_url: '/next',
                },
            },
        });

        const prev = findButtonByText(wrapper, 'Previous');
        const next = findButtonByText(wrapper, 'Next');

        expect(prev).not.toBeNull();
        expect(next).not.toBeNull();

        await prev.trigger('click');
        await next.trigger('click');

        expect(visit).toHaveBeenCalledWith('/prev', expect.objectContaining({ preserveScroll: true, preserveState: true }));
        expect(visit).toHaveBeenCalledWith('/next', expect.objectContaining({ preserveScroll: true, preserveState: true }));
    });
});


