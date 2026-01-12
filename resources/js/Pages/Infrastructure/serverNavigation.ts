export const buildServerNavigation = (server: { id: number }) => {
    const base = server?.id ? `/-/servers/${server.id}` : '/-/servers';

    return [
        { id: 'overview', name: 'Overview', href: base, icon: 'HomeModernIcon' },
        { id: 'console', name: 'SSH Console', href: `${base}/console`, icon: 'CommandLineIcon' },
        { id: 'keys', name: 'SSH Keys', href: `${base}/keys`, icon: 'KeyIcon' },
        { id: 'workers', name: 'Supervised Workers', href: `${base}/workers`, icon: 'QueueListIcon' },
        { id: 'crontab', name: 'Crontab', href: `${base}/crontab`, icon: 'ClockIcon' },
        { id: 'logs', name: 'Logs', href: `${base}/logs`, icon: 'Square3Stack3DIcon' },
    ];
};

























