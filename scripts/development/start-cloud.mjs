import { existsSync } from 'node:fs';
import { spawn } from 'node:child_process';
import { root, environment } from './cloud-runtime.mjs';

if (!existsSync(environment.DB_DATABASE)) {
    console.error('Prepare the demo first: node scripts/development/prepare-cloud.mjs --reset-demo');
    process.exit(1);
}
const server = spawn('php', ['artisan', 'serve', '--host=0.0.0.0', '--port=4173', '--no-reload'], {
    cwd: root, env: environment, stdio: 'inherit',
});
server.on('error', (error) => { console.error(error.message); process.exit(1); });
server.on('exit', (code) => process.exit(code ?? 1));
for (const signal of ['SIGINT', 'SIGTERM']) process.on(signal, () => server.kill(signal));
