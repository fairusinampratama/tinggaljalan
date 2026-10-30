import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';

// Exercise the actual inline bootstrap with Blade's three ID arguments rendered.
// This verifies our queue/loading behavior, not Google's processing of the queue.
const template = fs.readFileSync(new URL('../../resources/views/partials/google-ads-consent.blade.php', import.meta.url), 'utf8');
const inline = template.match(/<script[^>]*>([\s\S]*?)<\/script>/)[1];

function start(ids, saved, storageFails = false) {
  const scripts = [];
  const storage = new Map(saved ? [['tinggaljalan-consent-v1', saved]] : []);
  const queue = [{ event: 'existing-event' }];
  const window = {
    dataLayer: queue,
    localStorage: {
      getItem(key) { if (storageFails) throw new Error('Unavailable'); return storage.get(key); },
      setItem(key, value) { if (storageFails) throw new Error('Unavailable'); storage.set(key, value); },
    },
    dispatchEvent() {},
  };
  const document = {
    createElement() { return {}; },
    getElementById(id) { return scripts.find((script) => script.id === id); },
    head: { appendChild(script) { scripts.push(script); } },
  };
  const code = inline.replace(/\}\)\(window, document,[\s\S]*$/, `})(window, document, ${ids.map((id) => JSON.stringify(id)).join(', ')});`);
  const context = vm.createContext({ window, document, CustomEvent: class {} });
  const run = () => vm.runInContext(code, context);
  run();
  return { window, scripts, queue, run };
}

for (let mask = 1; mask < 8; mask++) {
  const ids = [mask & 1 ? 'AW-18427027980' : '', mask & 2 ? 'GTM-58KNQXXZ' : '', mask & 4 ? 'G-1L9N8XTME6' : ''];
  test(`denied defaults before destination config; GTM waits for consent: combination ${mask}`, () => {
    const state = start(ids);
    const api = state.window.TinggalJalanConsent;
    assert.equal(state.window.dataLayer, state.queue);
    assert.equal(state.scripts.length, 0);
    assert.equal(state.queue[1][0], 'consent');
    assert.equal(state.queue[1][1], 'default');
    assert.ok(Object.values(state.queue[1][2]).every((value) => value === 'denied'));
    for (const id of ['AW-18427027980', 'G-1L9N8XTME6']) {
      assert.equal(state.queue.filter((entry) => entry[0] === 'config' && entry[1] === id).length, ids.includes(id) ? 1 : 0);
    }
    api.deny();
    assert.equal(state.scripts.length, 0);
    api.grant();
    api.grant();
    state.run(); // Re-executing the bootstrap must not reset or configure twice.
    // The shared Google library is a static script, not dynamically injected.
    assert.equal(state.scripts.filter((script) => script.id === 'tinggaljalan-google-ads').length, 0);
    assert.equal(state.scripts.filter((script) => script.id === 'tinggaljalan-gtm').length, ids[1] ? 1 : 0);
    for (const id of ['AW-18427027980', 'G-1L9N8XTME6']) {
      assert.equal(state.queue.filter((entry) => entry[0] === 'config' && entry[1] === id).length, ids.includes(id) ? 1 : 0);
    }
    const consent = state.queue.filter((entry) => entry[0] === 'consent');
    assert.equal(consent[0][1], 'default');
    assert.ok(Object.values(consent[0][2]).every((value) => value === 'denied'));
    assert.ok(Object.values(consent.at(-1)[2]).every((value) => value === 'granted'));
    api.deny();
    assert.equal(api.status(), 'denied');
    assert.ok(Object.values(state.queue.at(-1)[2]).every((value) => value === 'denied'));
    assert.equal(start(ids, 'denied').scripts.length, 0);
    assert.equal(start(ids, 'invalid').scripts.length, 0);
    const remembered = start(ids, 'granted');
    assert.deepEqual(remembered.scripts.map((script) => script.src), state.scripts.map((script) => script.src));
    if (ids[0] || ids[2]) {
      assert.ok(remembered.queue.findIndex((entry) => entry[0] === 'consent' && entry[1] === 'update') < remembered.queue.findIndex((entry) => entry[0] === 'config'));
    }
  });
}

test('unavailable storage retains denied defaults and permits explicit acceptance for GTM', () => {
  const state = start(['AW-18427027980', 'GTM-58KNQXXZ', 'G-1L9N8XTME6'], undefined, true);
  assert.equal(state.scripts.length, 0);
  state.window.TinggalJalanConsent.grant();
  assert.equal(state.scripts.length, 1);
  state.window.TinggalJalanConsent.deny();
  assert.ok(Object.values(state.queue.at(-1)[2]).every((value) => value === 'denied'));
});
