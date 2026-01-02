import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const mockIo = vi.hoisted(() => vi.fn());

vi.mock('socket.io-client', () => ({
    io: mockIo,
}));

function deferred<T>() {
    let resolve!: (value: T) => void;
    let reject!: (reason?: unknown) => void;

    const promise = new Promise<T>((res, rej) => {
        resolve = res;
        reject = rej;
    });

    return { promise, resolve, reject };
}

describe('connectMonitorBridge', () => {
    const originalFetch = global.fetch;

    beforeEach(() => {
        mockIo.mockReset();
        vi.resetModules();
    });

    afterEach(() => {
        global.fetch = originalFetch;
    });

    it('returns a stable singleton socket across concurrent calls', async () => {
        const fetchDeferred = deferred<any>();
        global.fetch = vi.fn(() => fetchDeferred.promise) as unknown as typeof fetch;

        const fakeSocket = {
            connected: false,
            id: 'sock-1',
            on: vi.fn(),
            off: vi.fn(),
            emit: vi.fn(),
        };

        mockIo.mockReturnValue(fakeSocket);

        const { connectMonitorBridge } = await import('@/composables/useMonitorBridge');

        const p1 = connectMonitorBridge();
        const p2 = connectMonitorBridge();

        expect(global.fetch).toHaveBeenCalledTimes(1);

        fetchDeferred.resolve({
            ok: true,
            json: () => Promise.resolve({ token: 'test-token' }),
        });

        const s1 = await p1;
        const s2 = await p2;

        expect(mockIo).toHaveBeenCalledTimes(1);
        expect(s1).toBe(s2);
        expect(s1).toBe(fakeSocket);

        const s3 = await connectMonitorBridge();
        expect(mockIo).toHaveBeenCalledTimes(1);
        expect(s3).toBe(fakeSocket);
    });
});


