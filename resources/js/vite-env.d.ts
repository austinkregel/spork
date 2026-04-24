/// <reference types="vite/client" />

interface ImportMetaEnv {
    readonly VITE_MONITOR_BRIDGE_URL?: string;
    readonly VITE_MONITOR_BRIDGE_PORT?: string;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}

declare module '*.vue' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, any>;
    export default component;
}


