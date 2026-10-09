// Keep the usable server document until replacement media is ready. This is
// temporary preparation of the same visitor UI, never a crawler-only view.
export function prepareInitialHandoff(el) {
  const server = el.querySelector('.server-seo-content');
  if (!server) return null;
  const stage = document.createElement('div');
  stage.dataset.initialStage = '';
  stage.setAttribute('aria-hidden', 'true');
  stage.inert = true;
  stage.style.cssText = 'position:fixed;inset:0;visibility:hidden;pointer-events:none;overflow:auto;';
  el.append(stage);
  return { server, stage };
}

function imageReady(image) {
  return new Promise(resolve => {
    let settled = false;
    const finish = () => {
      if (settled) return;
      settled = true;
      image.removeEventListener('load', finish);
      image.removeEventListener('error', finish);
      if (image.naturalWidth) image.decode().then(resolve, resolve);
      else resolve();
    };
    image.addEventListener('load', finish);
    image.addEventListener('error', finish);
    // A new picture can report complete before source selection starts.
    requestAnimationFrame(() => { if (image.complete) finish(); });
  });
}

export function revealInitialApp({ server, stage }) {
  const images = [stage.querySelector('nav img'), stage.querySelector('#home img')].filter(Boolean);
  // IDs must stay unique while the server view is interactive. Restore them
  // only after removing that view, before showing the prepared application.
  const ids = [...stage.querySelectorAll('[id]')].map(node => [node, node.id]);
  ids.forEach(([node, id]) => { node.id = `initial-stage-${id}`; });
  let cancelled = false;
  let timer;
  const timeout = new Promise(resolve => { timer = setTimeout(() => resolve(false), 15000); });
  // Image errors must not disable navigation or booking. Wait for loaded
  // media to decode, but reveal the usable app when requests settle or time out.
  Promise.race([Promise.all(images.map(imageReady)), timeout]).then(() => {
    clearTimeout(timer);
    if (cancelled) return;
    let id;
    try { id = decodeURIComponent(location.hash.slice(1)); } catch { /* malformed fragment */ }
    const oldTarget = id ? [...server.querySelectorAll('[id]')].find(node => node.id === id) : null;
    const top = oldTarget?.getBoundingClientRect().top;
    // Complete the DOM and scroll handoff in one turn, before the next paint.
    [...stage.parentElement.childNodes].forEach(node => { if (node !== stage) node.remove(); });
    ids.forEach(([node, original]) => { node.id = original; });
    stage.removeAttribute('style');
    stage.removeAttribute('aria-hidden');
    stage.inert = false;
    delete stage.dataset.initialStage;
    const target = id ? document.getElementById(id) : null;
    if (target && top !== undefined) window.scrollBy({ top: target.getBoundingClientRect().top - top, behavior: 'instant' });
    requestAnimationFrame(() => requestAnimationFrame(() => document.documentElement.removeAttribute('data-initial-fragment')));
  });
  return () => { cancelled = true; clearTimeout(timer); };
}
