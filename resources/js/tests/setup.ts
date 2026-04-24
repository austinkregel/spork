import { config } from '@vue/test-utils';

config.global.stubs = {
    transition: false,
    teleport: false,
};

if (typeof window !== 'undefined' && !window.matchMedia) {
    window.matchMedia = () => ({
        matches: false,
        addListener: () => {},
        removeListener: () => {},
        addEventListener: () => {},
        removeEventListener: () => {},
        dispatchEvent: () => false,
    });
}

