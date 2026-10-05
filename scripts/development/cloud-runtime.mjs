import { randomBytes } from 'node:crypto';
import { mkdirSync, existsSync, readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export const root = fileURLToPath(new URL('../../', import.meta.url));
const directory = path.join(root, 'storage/framework/testing');
mkdirSync(directory, { recursive: true });
const keyPath = path.join(directory, 'cloud.key');
if (!existsSync(keyPath)) {
    writeFileSync(keyPath, `base64:${randomBytes(32).toString('base64')}`, { mode: 0o600 });
}

// Explicit overrides keep this disposable database separate from any existing .env.
export const environment = {
    ...process.env,
    APP_ENV: 'testing',
    APP_DEBUG: 'true',
    APP_KEY: readFileSync(keyPath, 'utf8').trim(),
    APP_URL: 'http://127.0.0.1:4173',
    APP_CONFIG_CACHE: path.join(directory, 'cloud-config.php'),
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: path.join(directory, 'cloud.sqlite'),
    DB_URL: '',
    CACHE_STORE: 'array',
    SESSION_DRIVER: 'database',
    QUEUE_CONNECTION: 'sync',
    MAIL_MAILER: 'array',
    GOOGLE_ADS_ID: '',
    GOOGLE_ADS_CONSENT_ENABLED: 'false',
    MIDTRANS_CLIENT_KEY: 'mock-midtrans-client-key',
    MIDTRANS_SERVER_KEY: 'mock-midtrans-server-key',
    SMTP_PASSWORD: 'mock-brevo-smtp-key',
    WHATSPIE_API_TOKEN: 'mock-whatspie-api-token',
};
