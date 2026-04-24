import { describe, expect, it } from 'vitest';
import { buildMessageToastItemFromEvent, shouldToastNewMessage } from '../message-toast';

describe('message-toast', () => {
    it('builds a toast item for inbound messages when not on the active thread', () => {
        const toast = buildMessageToastItemFromEvent(
            {
                thread_id: 10,
                from_person: { id: 2, name: 'Alice' },
                subject: 'Hello',
            },
            {
                pathname: '/-/communication/chat/99',
                user_person_id: 1,
            }
        );

        expect(toast).not.toBeNull();
        expect(toast?.thread_id).toBe(10);
        expect(toast?.preview).toBe('Hello');
    });

    it('accepts legacy { model: {...} } payload shape', () => {
        const toast = buildMessageToastItemFromEvent(
            {
                model: {
                    thread_id: 55,
                    from_person: { id: 2, name: 'Bob' },
                    subject: 'Legacy payload',
                },
            },
            {
                pathname: '/-/communication/chat/99',
                user_person_id: 1,
            }
        );

        expect(toast).not.toBeNull();
        expect(toast?.thread_id).toBe(55);
    });

    it('suppresses the toast when already viewing the same thread', () => {
        expect(
            shouldToastNewMessage({
                pathname: '/-/communication/chat/10',
                thread_id: 10,
                is_user: false,
            })
        ).toBe(false);

        expect(
            shouldToastNewMessage({
                pathname: '/-/communication/chat/10/',
                thread_id: 10,
                is_user: false,
            })
        ).toBe(false);
    });

    it('suppresses the toast for outbound (user-authored) messages', () => {
        const toast = buildMessageToastItemFromEvent(
            { thread_id: 10, from_person: 1 },
            { pathname: '/-/communication/chat/99', user_person_id: 1 }
        );

        expect(toast).toBeNull();
    });

    it('includes reply_to preview when present', () => {
        const toast = buildMessageToastItemFromEvent(
            {
                thread_id: 10,
                from_person: { id: 2, name: 'Alice', photo_url: 'https://example.com/alice.jpg' } as any,
                preview: 'New content',
                reply_to: {
                    from_person: { id: 3, name: 'Bob', photo_url: 'https://example.com/bob.jpg' },
                    preview: 'Prior message preview',
                },
            },
            { pathname: '/-/communication/chat/99', user_person_id: 1 }
        );

        expect(toast?.reply_to?.preview).toBe('Prior message preview');
    });

    it('passes through thread_name when present', () => {
        const toast = buildMessageToastItemFromEvent(
            {
                thread_id: 10,
                thread_name: 'Project Alpha',
                from_person: { id: 2, name: 'Alice' },
                subject: 'Hello',
            },
            {
                pathname: '/-/communication/chat/99',
                user_person_id: 1,
            }
        );

        expect(toast?.thread_name).toBe('Project Alpha');
    });
});

