import { mkdir, rm, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { spawnSync } from 'node:child_process';

const databasePath = path.resolve('storage/framework/testing/browser.sqlite');

await mkdir(path.dirname(databasePath), { recursive: true });
await rm(databasePath, { force: true });
await writeFile(databasePath, '');

const result = spawnSync(
    'php',
    ['artisan', 'migrate:fresh', '--seed', '--force'],
    {
        env: {
            ...process.env,
            APP_ENV: 'testing',
            APP_URL: 'http://127.0.0.1:4173',
            CACHE_STORE: 'array',
            DB_CONNECTION: 'sqlite',
            DB_DATABASE: databasePath,
            MAIL_MAILER: 'array',
            QUEUE_CONNECTION: 'sync',
            SESSION_DRIVER: 'array',
        },
        stdio: 'inherit',
    },
);

if (result.status !== 0) {
    process.exit(result.status ?? 1);
}
