import { ref } from 'vue';

export type MessageToastPerson = {
    id?: number | string | null;
    name?: string | null;
    photo_url?: string | null;
};

export type MessageToastItem = {
    id: string;
    thread_id: number | string;
    thread_name?: string | null;
    message_event_id?: string | null;
    from_person?: MessageToastPerson | null;
    preview?: string | null;
    reply_to?: {
        from_person?: MessageToastPerson | null;
        preview?: string | null;
    } | null;
    created_at_ms: number;
};

export type ThreadToastGroup = {
    id: string;
    thread_id: number | string;
    thread_name?: string | null;
    total_count: number;
    messages: MessageToastItem[];
    latest_at_ms: number;
};

const timeouts_by_group_id = new Map<string, number>();
const groups = ref<ThreadToastGroup[]>([]);

function ensureStringId(id: unknown): string {
    if (typeof id === 'string' && id.length > 0) {
        return id;
    }

    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        // @ts-ignore - randomUUID exists in modern browsers
        return crypto.randomUUID();
    }

    return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

function scheduleAutoDismiss(groupId: string, duration_ms: number) {
    if (timeouts_by_group_id.has(groupId)) {
        clearTimeout(timeouts_by_group_id.get(groupId));
    }

    const handle = window.setTimeout(() => dismiss(groupId), duration_ms);
    timeouts_by_group_id.set(groupId, handle);
}

function groupIdForThread(thread_id: number | string): string {
    return `thread:${String(thread_id)}`;
}

export function pushMessageToast(
    item: Omit<MessageToastItem, 'id' | 'created_at_ms'> & { id?: string },
    options?: {
        duration_ms?: number;
        max_groups?: number;
        max_messages_per_thread?: number;
    }
): string {
    const duration_ms = options?.duration_ms ?? 12_000;
    const max_groups = options?.max_groups ?? 3;
    const max_messages_per_thread = options?.max_messages_per_thread ?? 5;

    const now = Date.now();
    const id = ensureStringId(item.id);

    const message: MessageToastItem = {
        ...item,
        id,
        created_at_ms: now,
    };

    const groupId = groupIdForThread(message.thread_id);
    const existing = groups.value.find((g) => g.id === groupId) ?? null;

    if (!existing) {
        groups.value = [
            {
                id: groupId,
                thread_id: message.thread_id,
                thread_name: message.thread_name ?? null,
                total_count: 1,
                messages: [message],
                latest_at_ms: now,
            },
            ...groups.value,
        ].slice(0, max_groups);
    } else {
        const messages = [...existing.messages, message]
            .sort((a, b) => a.created_at_ms - b.created_at_ms)
            .slice(-max_messages_per_thread);

        const updated: ThreadToastGroup = {
            ...existing,
            thread_name: existing.thread_name ?? message.thread_name ?? null,
            total_count: existing.total_count + 1,
            messages,
            latest_at_ms: now,
        };

        groups.value = [updated, ...groups.value.filter((g) => g.id !== groupId)].slice(0, max_groups);
    }

    scheduleAutoDismiss(groupId, duration_ms);

    return groupId;
}

export function dismiss(groupId: string): void {
    if (timeouts_by_group_id.has(groupId)) {
        clearTimeout(timeouts_by_group_id.get(groupId));
        timeouts_by_group_id.delete(groupId);
    }

    groups.value = groups.value.filter((group) => group.id !== groupId);
}

export function useMessageToasts() {
    return {
        groups,
        pushMessageToast,
        dismiss,
    };
}

export function __resetMessageToastsForTests() {
    for (const handle of timeouts_by_group_id.values()) {
        clearTimeout(handle);
    }
    timeouts_by_group_id.clear();
    groups.value = [];
}


