export type NewMessageBroadcastEvent = {
    model?: Record<string, unknown> | null;
    thread_id?: number | string | null;
    thread_name?: string | null;
    event_id?: string | null;
    from_person?: { id?: number | string | null; name?: string | null } | number | string | null;
    is_user?: boolean | null;
    subject?: string | null;
    preview?: string | null;
    from_email?: string | null;
    reply_to?: {
        event_id?: string | null;
        from_person?: { id?: number | string | null; name?: string | null; photo_url?: string | null } | number | string | null;
        preview?: string | null;
    } | null;
};

export type InertiaRouterLike = {
    visit: (url: string, options?: Record<string, unknown>) => void;
};

export type ToastNewMessageDeps = {
    pathname: string;
    user_person_id: number | string | null;
    route: (name: string, ...args: any[]) => string;
    router: InertiaRouterLike;
};

export function shouldToastNewMessage(args: {
    pathname: string;
    thread_id: number | string | null;
    is_user: boolean;
}): boolean {
    if (!args.thread_id) {
        return false;
    }

    if (args.is_user) {
        return false;
    }

    const pathname = args.pathname.endsWith('/') && args.pathname.length > 1 ? args.pathname.slice(0, -1) : args.pathname;

    // Suppress when already viewing this exact thread.
    if (pathname === `/-/communication/chat/${args.thread_id}`) {
        return false;
    }

    return true;
}

function resolveIsUser(event: NewMessageBroadcastEvent, user_person_id: number | string | null): boolean {
    if (event.is_user === true) {
        return true;
    }

    if (!user_person_id) {
        return false;
    }

    const from_person = event.from_person;

    if (from_person && typeof from_person === 'object') {
        return String(from_person.id ?? '') === String(user_person_id);
    }

    if (typeof from_person === 'string' || typeof from_person === 'number') {
        return String(from_person) === String(user_person_id);
    }

    return false;
}

export function normalizeNewMessageEvent(event: NewMessageBroadcastEvent): NewMessageBroadcastEvent {
    const payload = event?.model && typeof event.model === 'object' ? (event.model as NewMessageBroadcastEvent) : event;
    return payload;
}

export function buildMessageToastItemFromEvent(
    event: NewMessageBroadcastEvent,
    deps: Pick<ToastNewMessageDeps, 'pathname' | 'user_person_id'>
):
    | {
          thread_id: number | string;
          thread_name: string | null;
          message_event_id: string | null;
          from_person: { id?: number | string | null; name?: string | null; photo_url?: string | null } | null;
          preview: string | null;
          reply_to: { from_person: { id?: number | string | null; name?: string | null; photo_url?: string | null } | null; preview: string | null } | null;
      }
    | null {
    const payload = normalizeNewMessageEvent(event);

    const thread_id = payload.thread_id ?? null;
    const is_user = resolveIsUser(payload, deps.user_person_id);

    if (!shouldToastNewMessage({ pathname: deps.pathname, thread_id, is_user })) {
        return null;
    }

    if (!thread_id) {
        return null;
    }

    const from_person =
        payload.from_person && typeof payload.from_person === 'object'
            ? {
                  id: payload.from_person.id ?? null,
                  name: payload.from_person.name ?? null,
                  // photo_url is only present on the newer payload; keep null otherwise
                  photo_url: (payload.from_person as any).photo_url ?? null,
              }
            : null;

    const reply_to =
        payload.reply_to && typeof payload.reply_to === 'object'
            ? {
                  from_person:
                      payload.reply_to.from_person && typeof payload.reply_to.from_person === 'object'
                          ? {
                                id: payload.reply_to.from_person.id ?? null,
                                name: payload.reply_to.from_person.name ?? null,
                                photo_url: payload.reply_to.from_person.photo_url ?? null,
                            }
                          : null,
                  preview: payload.reply_to.preview ?? null,
              }
            : null;

    return {
        thread_id,
        thread_name: payload.thread_name ?? null,
        message_event_id: payload.event_id ?? null,
        from_person,
        preview: payload.preview ?? payload.subject ?? null,
        reply_to,
    };
}

