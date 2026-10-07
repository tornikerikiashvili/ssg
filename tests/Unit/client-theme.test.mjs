import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

const script = readFileSync(new URL('../../resources/views/components/client-theme.blade.php', import.meta.url), 'utf8').replace(/<\/?script>/g, '');
function loadPage(storage) {
    const classes = new Set(), handlers = {}, label = {};
    const document = {
        body: { classList: {
            toggle(name, enabled) { enabled ? classes.add(name) : classes.delete(name); },
            contains: name => classes.has(name),
        } },
        documentElement: { dataset: {} },
        querySelectorAll: () => [label],
        addEventListener(name, callback) { handlers[name] = callback; },
    };
    runInNewContext(script, { document, localStorage: storage });
    return { document, label, ready: () => handlers.DOMContentLoaded(),
        toggle() { handlers.click({ target: { closest: () => ({}) }, preventDefault() {} }); },
    };
}

test('theme persists through navigation and reload in both directions', () => {
    const values = new Map();
    const storage = { getItem: key => values.get(key), setItem: (key, value) => values.set(key, value) };
    const first = loadPage(storage);
    assert.equal(first.label.textContent, 'Light');
    first.toggle();
    const second = loadPage(storage);
    assert.equal(second.document.body.classList.contains('light-mode'), true);
    second.ready();
    assert.equal(second.label.textContent, 'Dark');
    second.toggle();
    const third = loadPage(storage);
    assert.equal(third.document.body.classList.contains('light-mode'), false);
    assert.equal(third.label.textContent, 'Light');
});

test('switching still works when browser storage is blocked', () => {
    const page = loadPage({ getItem() { throw new Error('blocked'); }, setItem() { throw new Error('blocked'); } });
    page.ready();
    page.toggle();
    assert.equal(page.label.textContent, 'Dark');
    page.toggle();
    assert.equal(page.label.textContent, 'Light');
});
