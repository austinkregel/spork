export type SoundName =
    | 'glitch'
    | 'finished'
    | 'error'
    | 'notification'
    | 'success'
    | 'achievement'
    | (string & {});

export type SoundPlayer = {
    play: (name: SoundName) => boolean;
};

export function createSoundPlayer(args?: {
    volume?: number;
    cooldown_ms?: number;
    per_sound_cooldown_ms?: Partial<Record<SoundName, number>>;
    now?: () => number;
    getAudio?: (name: SoundName) => HTMLAudioElement | null;
}): SoundPlayer {
    const volume = args?.volume ?? 0.15;
    const cooldown_ms = args?.cooldown_ms ?? 250;
    const per_sound_cooldown_ms = args?.per_sound_cooldown_ms ?? {
        notification: 1200,
        error: 600,
        success: 400,
        achievement: 600,
        finished: 400,
        glitch: 600,
    };
    const now = args?.now ?? (() => Date.now());
    const getAudio =
        args?.getAudio ??
        ((name: SoundName) => {
            const el = document.getElementById(`${name}-sound`);
            return el instanceof HTMLAudioElement ? el : null;
        });

    const lastPlayedByName = new Map<string, number>();
    let lastPlayedAnyAt = -Infinity;
    let currentAudio: HTMLAudioElement | null = null;

    const effectiveCooldownFor = (name: SoundName) => {
        return per_sound_cooldown_ms[name] ?? cooldown_ms;
    };

    return {
        play(name: SoundName) {
            const at = now();
            const lastAnyDelta = at - lastPlayedAnyAt;
            const globalOk = lastAnyDelta >= 50; // micro-guard to reduce rapid overlap across different triggers

            const lastAt = lastPlayedByName.get(String(name)) ?? -Infinity;
            const cooldown = effectiveCooldownFor(name);
            const perSoundOk = at - lastAt >= cooldown;

            if (!globalOk || !perSoundOk) {
                return false;
            }

            const audio = getAudio(name);
            if (!audio) {
                return false;
            }

            // Prevent layering: stop any currently playing sound.
            if (currentAudio && currentAudio !== audio) {
                try {
                    currentAudio.pause();
                    currentAudio.currentTime = 0;
                } catch {
                    // no-op
                }
            }

            try {
                audio.volume = volume;
                audio.currentTime = 0;
                void audio.play();
                currentAudio = audio;
            } catch {
                return false;
            }

            lastPlayedByName.set(String(name), at);
            lastPlayedAnyAt = at;

            return true;
        },
    };
}


