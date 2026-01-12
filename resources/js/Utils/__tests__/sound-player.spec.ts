import { describe, expect, it, vi } from 'vitest';
import { createSoundPlayer } from '../sound-player';

function fakeAudio() {
    return {
        volume: 0,
        currentTime: 0,
        play: vi.fn(() => Promise.resolve()),
        pause: vi.fn(),
    } as any as HTMLAudioElement;
}

describe('sound-player', () => {
    it('debounces per sound name to avoid rapid replays', () => {
        const audio = fakeAudio();
        const now = vi.fn(() => 0);

        const player = createSoundPlayer({
            now,
            getAudio: () => audio,
            per_sound_cooldown_ms: { notification: 1000 },
        });

        expect(player.play('notification')).toBe(true);
        expect(audio.play).toHaveBeenCalledTimes(1);

        now.mockReturnValue(200);
        expect(player.play('notification')).toBe(false);
        expect(audio.play).toHaveBeenCalledTimes(1);

        now.mockReturnValue(1200);
        expect(player.play('notification')).toBe(true);
        expect(audio.play).toHaveBeenCalledTimes(2);
    });

    it('pauses any currently playing sound before starting a different one', () => {
        const notification = fakeAudio();
        const success = fakeAudio();

        const now = vi.fn(() => 0);
        const getAudio = (name: string) => (name === 'notification' ? notification : success);

        const player = createSoundPlayer({
            now,
            getAudio,
            per_sound_cooldown_ms: { notification: 0, success: 0 },
        });

        expect(player.play('notification')).toBe(true);
        expect(notification.play).toHaveBeenCalledTimes(1);

        now.mockReturnValue(100);
        expect(player.play('success')).toBe(true);
        expect(notification.pause).toHaveBeenCalledTimes(1);
        expect(success.play).toHaveBeenCalledTimes(1);
    });
});





