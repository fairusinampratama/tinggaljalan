import { defineConfig, devices } from '@playwright/test';
import path from 'node:path';

const baseURL = 'http://127.0.0.1:4173';
const databasePath = path.resolve('storage/framework/testing/browser.sqlite');
const applicationEnvironment = {
    ...process.env,
    APP_ENV: 'testing',
    APP_URL: baseURL,
    CACHE_STORE: 'array',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: databasePath,
    MAIL_MAILER: 'array',
    QUEUE_CONNECTION: 'sync',
    SESSION_DRIVER: 'database',
};

export default defineConfig({
    testDir: './tests/Browser',
    fullyParallel: false,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? 'github' : 'list',
    use: {
        baseURL,
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=4173 --no-reload',
        env: applicationEnvironment,
        reuseExistingServer: !process.env.CI,
        timeout: 30_000,
        url: baseURL,
    },
    projects: [
        {
            name: 'desktop-chromium',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'desktop-firefox',
            use: { ...devices['Desktop Firefox'] },
        },
        {
            name: 'desktop-webkit',
            use: { ...devices['Desktop Safari'] },
        },
        {
            name: 'iphone-webkit',
            use: { ...devices['iPhone 13'] },
        },
        {
            name: 'android-chromium',
            use: { ...devices['Pixel 7'] },
        },
    ],
});
