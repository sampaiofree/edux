import type { CapacitorConfig } from '@capacitor/cli';

const serverUrl = process.env.CAPACITOR_SERVER_URL ?? 'https://cursos.example.com';

let allowNavigation: string[] = [];

try {
    allowNavigation = [new URL(serverUrl).host];
} catch (_) {
    allowNavigation = [];
}

const config: CapacitorConfig = {
    appId: process.env.CAPACITOR_APP_ID ?? 'org.edux.app',
    appName: process.env.CAPACITOR_APP_NAME ?? 'EduX',
    webDir: 'public',
    bundledWebRuntime: false,
    server: {
        url: serverUrl,
        cleartext: false,
        allowNavigation,
    },
};

export default config;
