import { defineConfig } from '@playwright/test';

const port = Number(process.env.PLAYWRIGHT_PORT ?? 8000);
if (!Number.isInteger(port) || port < 1 || port > 65535) {
    throw new Error('PLAYWRIGHT_PORT must be a valid TCP port.');
}
const url = `http://127.0.0.1:${port}`;

export default defineConfig({
    testDir: './tests/e2e',
    workers: 1,
    use: { baseURL: process.env.APP_URL ?? url },
    webServer: {
        command: `cd public && php -S 127.0.0.1:${port} -t . ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`,
        url,
        reuseExistingServer:
            process.env.PLAYWRIGHT_REUSE_SERVER === 'true' || process.env.PLAYWRIGHT_PORT === undefined,
    },
});
