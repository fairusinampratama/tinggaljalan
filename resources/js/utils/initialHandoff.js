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
  if (image.complete) return image.naturalWidth ? image.decode().then(() => true, () => false) : Promise.resolve(false);
  return new Promise(resolve => {
    image.addEventListener('load', () => image.decode().then(() => resolve(true), () => resolve(false)), { once: true });
    image.addEventListener('error', () => resolve(false), { once: true });
  });
}

export function revealInitialApp({ server, stage }, onFailure) {
  const images = [stage.querySelector('nav img'), stage.querySelector('#home img')].filter(Boolean);
  // IDs must stay unique while the server view is interactive. Restore them
  // only after removing that view, before showing the prepared application.
  const ids = [...stage.querySelectorAll('[id]')].map(node => [node, node.id]);
  ids.forEach(([node, id]) => { node.id = `initial-stage-${id}`; });
  let cancelled = false;
  let timer;
  const timeout = new Promise(resolve => { timer = setTimeout(() => resolve(false), 15000); });
  Promise.race([Promise.all(images.map(imageReady)).then(states => states.every(Boolean)), timeout]).then(ready => {
    clearTimeout(timer);
    if (cancelled) return;
    if (!ready) { onFailure(); return; }
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
