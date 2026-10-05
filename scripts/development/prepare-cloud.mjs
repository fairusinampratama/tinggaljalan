import { copyFileSync, mkdirSync, existsSync, writeFileSync } from 'node:fs';
import { spawnSync } from 'node:child_process';
import path from 'node:path';
import { root, environment } from './cloud-runtime.mjs';

if (process.argv[2] !== '--reset-demo') {
    console.error('Use --reset-demo to recreate only storage/framework/testing/cloud.sqlite.');
    process.exit(1);
}

function run(command, args) {
    const result = spawnSync(command, args, { cwd: root, env: environment, stdio: 'inherit' });
    if (result.error) throw result.error;
    if (result.status !== 0) process.exit(result.status ?? 1);
}

run(process.env.COMPOSER_BINARY || 'composer', ['install', '--prefer-dist', '--no-interaction']);
run('npm', ['ci', '--ignore-scripts']);
if (!existsSync(environment.DB_DATABASE)) writeFileSync(environment.DB_DATABASE, '');
run('php', ['artisan', 'migrate:fresh', '--seed', '--force']);
run('php', ['artisan', 'tinker', '--execute=' + [
    "App\\Models\\EmailGatewaySetting::query()->update(['is_enabled' => false]);",
    "App\\Models\\WhatsappGatewaySetting::query()->update(['is_enabled' => false]);",
    "App\\Models\\NotificationSetting::current()->update(['is_enabled' => false, 'email_enabled' => false, 'whatsapp_enabled' => false]);",
    "App\\Models\\PaymentSetting::query()->update(['is_enabled' => false, 'mode' => 'sandbox']);",
    "App\\Models\\SiteSetting::query()->update(['logo_url' => '/images/logo-tj.png']);",
].join(' ')]);

const heroDirectory = path.join(root, 'storage/app/public/admin/hero');
mkdirSync(heroDirectory, { recursive: true });
for (const name of ['hero-bromo.jpg', 'destination-tumpak-sewu.jpg']) {
    copyFileSync(path.join(root, 'public/images', name), path.join(heroDirectory, name));
}
run('php', ['artisan', 'storage:link']);
run('php', ['artisan', 'images:generate-responsive', '--missing']);
run('npm', ['run', 'build:performance']);
console.log('Demo ready. Start with: node scripts/development/start-cloud.mjs');
