import { describe, expect, it, beforeEach, afterEach, vi } from 'vitest';
import { buildPreviewFromUrl, extractLinks, isLikelyImageUrl, resolveMediaFromLink, hydrateMediaViaProxy } from '../link-utils';

describe('extractLinks', () => {
    it('returns http links from text', () => {
        const text = 'Check https://example.com and http://foo.bar/img.png';
        expect(extractLinks(text)).toEqual(['https://example.com', 'http://foo.bar/img.png']);
    });

    it('returns empty array for null', () => {
        expect(extractLinks(null)).toEqual([]);
    });
});

describe('isLikelyImageUrl', () => {
    it('detects image file extensions', () => {
        expect(isLikelyImageUrl('https://site.com/photo.jpg')).toBe(true);
        expect(isLikelyImageUrl('https://site.com/photo.PNG')).toBe(true);
    });

    it('ignores non-image urls', () => {
        expect(isLikelyImageUrl('https://site.com/page')).toBe(false);
    });
});

describe('buildPreviewFromUrl', () => {
    it('builds preview metadata for valid urls', () => {
        const preview = buildPreviewFromUrl('https://example.com/path');
        expect(preview).toMatchObject({
            url: 'https://example.com/path',
            host: 'example.com',
            title: 'example.com',
        });
    });

    it('returns null for invalid urls', () => {
        expect(buildPreviewFromUrl('not a url')).toBeNull();
    });
});

describe('resolveMediaFromLink', () => {
    it('marks giphy share links for hydration', () => {
        const result = resolveMediaFromLink('https://giphy.com/gifs/theoffice-funny-3o6Zt6ML6BklcajjsA');
        expect(result?.needsHydration).toBe(true);
        expect(result?.provider).toBe('giphy');
    });

    it('treats tenor share links as inline images', () => {
        const result = resolveMediaFromLink('https://tenor.com/view/tf2-heavy-meet-the-heavy-bullet-outsmart-bullet-gif-20206045');
        expect(result?.url).toBe('https://tenor.com/view/tf2-heavy-meet-the-heavy-bullet-outsmart-bullet-gif-20206045');
        expect(result?.provider).toBe('tenor');
    });

    it('converts imgur gifv to mp4 video', () => {
        const result = resolveMediaFromLink('https://i.imgur.com/abcd123.gifv');
        expect(result?.url).toBe('https://i.imgur.com/abcd123.mp4');
        expect(result?.kind).toBe('video');
    });
});

describe('hydrateMediaViaProxy', () => {
    const originalFetch = global.fetch;
    const mockFetch = vi.fn();

    beforeEach(() => {
        mockFetch.mockReset();
        global.fetch = mockFetch as unknown as typeof fetch;
    });

    afterEach(() => {
        global.fetch = originalFetch;
    });

    it('fetches the proxy endpoint and returns inline media result', async () => {
        mockFetch.mockResolvedValueOnce({
            ok: true,
            json: () =>
                Promise.resolve({
                    provider: 'tenor',
                    type: 'image',
                    url: 'https://media.tenor.com/example.gif',
                }),
        } as Response);

        const media = await hydrateMediaViaProxy('https://tenor.com/view/example');
        expect(mockFetch).toHaveBeenCalledWith('/api/media/unfurl?url=https%3A%2F%2Ftenor.com%2Fview%2Fexample', expect.any(Object));
        expect(media?.url).toBe('https://media.tenor.com/example.gif');
        expect(media?.provider).toBe('tenor');
    });
});

