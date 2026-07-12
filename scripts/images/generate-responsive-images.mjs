import fs from 'node:fs/promises';
import path from 'node:path';
import sharp from 'sharp';

const root = process.cwd();
const targets = [
  {
    sourceRoot: path.join(root, 'public', 'images'),
    outputRoot: path.join(root, 'public', 'images', 'generated', 'images'),
    publicPrefix: '/images',
  },
  {
    sourceRoot: path.join(root, 'public', 'storage'),
    outputRoot: path.join(root, 'public', 'storage', 'generated', 'storage'),
    publicPrefix: '/storage',
  },
];
const widths = [480, 768, 960, 1200, 1600];
const extensions = new Set(['.jpg', '.jpeg', '.png', '.webp']);

async function exists(filePath) {
  try {
    await fs.access(filePath);
    return true;
  } catch {
    return false;
  }
}

async function listImages(directory, outputRoot) {
  try {
    const entries = await fs.readdir(directory, { withFileTypes: true });
    const files = [];

    for (const entry of entries) {
      const absolute = path.join(directory, entry.name);

      if (absolute.startsWith(outputRoot)) {
        continue;
      }

      if (entry.isDirectory()) {
        try {
          files.push(...await listImages(absolute, outputRoot));
        } catch (err) {
          console.warn(`Warning: Skipping directory "${absolute}" due to read error: ${err.message}`);
        }
        continue;
      }

      if (entry.isFile() && extensions.has(path.extname(entry.name).toLowerCase())) {
        files.push(absolute);
      }
    }

    return files;
  } catch (err) {
    console.warn(`Warning: Skipping directory "${directory}" due to read error: ${err.message}`);
    return [];
  }
}

function outputPathFor(sourcePath, sourceRoot, outputRoot, width) {
  const relative = path.relative(sourceRoot, sourcePath);
  const parsed = path.parse(relative);

  return path.join(outputRoot, parsed.dir, `${parsed.name}-${width}.webp`);
}

async function generateTarget({ sourceRoot, outputRoot, publicPrefix }) {
  if (!await exists(sourceRoot)) {
    return { publicPrefix, sources: 0, generated: 0 };
  }

  const images = await listImages(sourceRoot, outputRoot);
  let generated = 0;

  for (const image of images) {
    for (const width of widths) {
      const output = outputPathFor(image, sourceRoot, outputRoot, width);
      await fs.mkdir(path.dirname(output), { recursive: true });
      await sharp(image)
        .rotate()
        .resize({ width, withoutEnlargement: true })
        .webp({ quality: 80 })
        .toFile(output);
      generated += 1;
    }
  }

  return { publicPrefix, sources: images.length, generated };
}

const results = await Promise.all(targets.map(generateTarget));

for (const result of results) {
  console.log(`${result.publicPrefix}: generated ${result.generated} variants from ${result.sources} source images.`);
}
