import { mkdtemp, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import pngToIco from 'png-to-ico';
import sharp from 'sharp';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const source = path.join(root, 'public/images/logo-tj.png');
const publicDirectory = path.join(root, 'public');
const workDirectory = await mkdtemp(path.join(tmpdir(), 'tinggaljalan-favicon-'));

async function squareLogo(size) {
  const inset = Math.round(size * 0.8);

  return sharp(source)
    .resize(inset, inset, {
      fit: 'contain',
      background: { r: 0, g: 0, b: 0, alpha: 0 },
    })
    .extend({
      top: Math.floor((size - inset) / 2),
      bottom: Math.ceil((size - inset) / 2),
      left: Math.floor((size - inset) / 2),
      right: Math.ceil((size - inset) / 2),
      background: { r: 0, g: 0, b: 0, alpha: 0 },
    })
    .png()
    .toBuffer();
}

try {
  await writeFile(path.join(publicDirectory, 'favicon.png'), await squareLogo(512));
  await writeFile(path.join(publicDirectory, 'favicon-96x96.png'), await squareLogo(96));
  await writeFile(path.join(publicDirectory, 'apple-touch-icon.png'), await squareLogo(180));

  const icoSources = [];
  for (const size of [16, 32, 48]) {
    const filename = path.join(workDirectory, `favicon-${size}.png`);
    await writeFile(filename, await squareLogo(size));
    icoSources.push(filename);
  }

  await writeFile(path.join(publicDirectory, 'favicon.ico'), await pngToIco(icoSources));
} finally {
  await rm(workDirectory, { recursive: true, force: true });
}
