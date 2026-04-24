export type LinkPreviewData = {
    url: string;
    host: string;
    title: string;
    description?: string;
    image?: string | null;
};

const urlPattern = /https?:\/\/[^\s)]+/gi;
const imageExtensions = /\.(png|jpe?g|gif|bmp|webp|svg|avif|ico)$/i;
const gifvPattern = /\.gifv(?:\?.*)?$/i;

export type InlineMediaResult = {
    url: string;
    kind: 'image' | 'video';
    provider?: 'tenor' | 'giphy' | 'imgur' | 'gfycat' | 'direct' | 'attachment';
    source?: string;
    thumbnailUrl?: string | null;
    mp4Url?: string | null;
    needsHydration?: boolean;
};

export function extractLinks(text: string | null | undefined): string[] {
    if (!text) {
        return [];
    }

    return Array.from(text.match(urlPattern) ?? []);
}

export function isLikelyImageUrl(url: string | null | undefined): boolean {
    if (!url) {
        return false;
    }

    return imageExtensions.test(url);
}

export function buildPreviewFromUrl(url: string): LinkPreviewData | null {
    try {
        const parsed = new URL(url);
        const host = parsed.hostname;

        return {
            url,
            host,
            title: host,
            description: '',
            image: null,
        };
    } catch (_) {
        return null;
    }
}

export function derivePreviewFromSettings(settings: Record<string, unknown> | null | undefined): LinkPreviewData | null {
    if (!settings) {
        return null;
    }

    const candidate =
        // support future shapes without needing TODOs
        (settings.link_preview as Record<string, unknown> | undefined) ??
        (settings.preview as Record<string, unknown> | undefined) ??
        null;

    if (!candidate || typeof candidate.url !== 'string') {
        return null;
    }

    return {
        url: candidate.url,
        host: typeof candidate.host === 'string' ? candidate.host : new URL(candidate.url).hostname,
        title: typeof candidate.title === 'string' ? candidate.title : candidate.url,
        description: typeof candidate.description === 'string' ? candidate.description : '',
        image: typeof candidate.image === 'string' ? candidate.image : null,
    };
}

const extractGiphyId = (pathname: string): string | null => {
    const parts = pathname.split('/').filter(Boolean);
    if (parts.length === 0) {
        return null;
    }

    const last = parts[parts.length - 1];

    if (pathname.includes('/media/')) {
        return last;
    }

    const slugPieces = last.split('-');
    return slugPieces.length ? slugPieces[slugPieces.length - 1] : null;
};

const extractImgurId = (pathname: string): string | null => {
    const parts = pathname.split('/').filter(Boolean);
    const last = parts[parts.length - 1] ?? '';
    if (!last) {
        return null;
    }

    const cleaned = last.replace(/\.(gif|jpg|jpeg|png|gifv)$/i, '');
    return cleaned || null;
};

const extractGfycatSlug = (pathname: string): string | null => {
    const parts = pathname.split('/').filter(Boolean);
    return parts[0] ?? null;
};

export function resolveMediaFromLink(link: string | null | undefined): InlineMediaResult | null {
    if (!link) {
        return null;
    }

    const trimmed = link.trim();

    if (!trimmed) {
        return null;
    }

    if (isLikelyImageUrl(trimmed)) {
        return {
            url: trimmed,
            kind: 'image',
            provider: 'direct',
            source: trimmed,
        };
    }

    try {
        const parsed = new URL(trimmed);
        const host = parsed.hostname.toLowerCase();
        const pathname = parsed.pathname;

        if (gifvPattern.test(pathname)) {
            return {
                url: trimmed.replace(gifvPattern, '.mp4'),
                kind: 'video',
                provider: host.includes('imgur') ? 'imgur' : undefined,
                source: trimmed,
            };
        }

        if (host.endsWith('giphy.com')) {
            if (host.includes('media.giphy.com')) {
                return {
                    url: trimmed,
                    kind: 'image',
                    provider: 'giphy',
                    source: trimmed,
                };
            }

            return {
                url: trimmed,
                kind: 'image',
                provider: 'giphy',
                source: trimmed,
                needsHydration: true,
            };
        }

        if (host.includes('tenor.com') || host.includes('tenor.co')) {
            return {
                url: trimmed,
                kind: 'image',
                provider: 'tenor',
                source: trimmed,
                needsHydration: true,
            };
        }

        if (host.endsWith('gfycat.com')) {
            const slug = extractGfycatSlug(pathname);

            if (slug) {
                return {
                    url: `https://thumbs.gfycat.com/${slug}-size_restricted.gif`,
                    kind: 'image',
                    provider: 'gfycat',
                    source: trimmed,
                };
            }
        }

        if (host.endsWith('imgur.com')) {
            const id = extractImgurId(pathname);

            if (id) {
                return {
                    url: `https://i.imgur.com/${id}.gif`,
                    kind: 'image',
                    provider: 'imgur',
                    source: trimmed,
                };
            }
        }
    } catch {
        return null;
    }

    return null;
}

const mediaHydrationCache = new Map<string, Promise<InlineMediaResult | null>>();

export function hydrateMediaViaProxy(link: string): Promise<InlineMediaResult | null> {
    if (!link || typeof fetch !== 'function') {
        return Promise.resolve(null);
    }

    if (!mediaHydrationCache.has(link)) {
        mediaHydrationCache.set(
            link,
            fetch(`/api/media/unfurl?url=${encodeURIComponent(link)}`, {
                headers: {
                    Accept: 'application/json',
                },
            })
                .then(async (response) => {
                    if (!response.ok) {
                        return null;
                    }

                    const payload = await response.json();

                    if (!payload?.url) {
                        return null;
                    }

                    return {
                        url: payload.url,
                        kind: payload.type === 'video' ? 'video' : 'image',
                        provider: payload.provider,
                        source: payload.source ?? link,
                        thumbnailUrl: payload.thumbnail_url ?? null,
                        mp4Url: payload.mp4_url ?? null,
                    } satisfies InlineMediaResult;
                })
                .catch(() => null)
        );
    }

    return mediaHydrationCache.get(link)!;
}

