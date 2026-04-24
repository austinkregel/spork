import { beforeEach, describe, expect, it, vi } from 'vitest';
import { __resetMessageToastsForTests, pushMessageToast, useMessageToasts } from '../message-toast-store';

describe('message-toast-store', () => {
    beforeEach(() => {
        __resetMessageToastsForTests();
        vi.useFakeTimers();
    });

    it('groups by thread_id and keeps messages in chronological order', () => {
        const { groups } = useMessageToasts();

        vi.setSystemTime(new Date('2026-01-01T00:00:00Z'));
        pushMessageToast({ thread_id: 10, message_event_id: 'a', from_person: { name: 'Alice' }, preview: 'first' });

        vi.setSystemTime(new Date('2026-01-01T00:00:01Z'));
        pushMessageToast({ thread_id: 10, message_event_id: 'b', from_person: { name: 'Alice' }, preview: 'second' });

        expect(groups.value).toHaveLength(1);
        expect(groups.value[0].total_count).toBe(2);
        expect(groups.value[0].messages.map((m) => m.message_event_id)).toEqual(['a', 'b']);
    });

    it('caps messages per thread but tracks total_count', () => {
        const { groups } = useMessageToasts();

        for (let i = 0; i < 8; i += 1) {
            pushMessageToast(
                { thread_id: 10, message_event_id: `evt-${i}`, from_person: { name: 'Alice' }, preview: String(i) },
                { max_messages_per_thread: 5 }
            );
        }

        expect(groups.value).toHaveLength(1);
        expect(groups.value[0].total_count).toBe(8);
        expect(groups.value[0].messages).toHaveLength(5);
        expect(groups.value[0].messages.map((m) => m.message_event_id)).toEqual(['evt-3', 'evt-4', 'evt-5', 'evt-6', 'evt-7']);
    });

    it('caps the number of visible thread groups', () => {
        const { groups } = useMessageToasts();

        pushMessageToast({ thread_id: 1, preview: 't1' }, { max_groups: 2 });
        pushMessageToast({ thread_id: 2, preview: 't2' }, { max_groups: 2 });
        pushMessageToast({ thread_id: 3, preview: 't3' }, { max_groups: 2 });

        expect(groups.value).toHaveLength(2);
        expect(groups.value.map((g) => g.thread_id)).toEqual([3, 2]);
    });
});





