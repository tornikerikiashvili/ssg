import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

function element() {
    const classes = new Set();
    return {
        hidden: false, style: {}, dataset: {}, listeners: {}, children: [],
        classList: {
            contains: name => classes.has(name),
            toggle(name, enabled) { enabled ? classes.add(name) : classes.delete(name); },
        },
        setAttribute(name, value) { this[name] = value; },
        addEventListener(name, callback) { this.listeners[name] = callback; },
        replaceChildren() { this.children = []; },
        append(child) { this.children.push(child); },
    };
}

function setup(sizes = [1024, 2048, 3072], page = 'game') {
    const cards = sizes.map((_, index) => index < 2 ? 'a' : 'b').map((categoryId, index) => {
        const card = element();
        card.dataset = { categoryId, resourceId: String(index + 1), title: String(index), createdAt: String(index) };
        const checkbox = element();
        card.querySelector = selector => selector === '[data-asset-size]' ? { dataset: { assetSize: sizes[index] } } : checkbox;
        return card;
    });
    const filters = element(), categories = element(), form = element(), download = element(), selectAll = element();
    const label = element(), checkbox = element(), hint = element(), inputs = element();
    const actions = element(), summary = element(), error = element();
    const category = { value: '' }, sort = { value: 'name' };
    filters.querySelector = selector => ({ '.filter_buttons': categories, '[name="asset_category"]:checked': category, '[name="asset_sort"]:checked': sort, '[data-accordion-trigger] p': element() })[selector];
    filters.querySelectorAll = () => [];
    filters.reset = () => { category.value = ''; sort.value = 'name'; };
    actions.querySelector = () => download;
    selectAll.querySelector = selector => selector === '.button_text' ? label : checkbox;
    const nodes = { '#asset-selection-actions': actions, '#asset-selection-summary': summary, '#asset-selection-error': error, '#game-asset-filters': filters, '#resources .assets_list': element(), '#resources [data-select-all]': selectAll, '#asset-archive': form, '#asset-selection-hint': hint, '#asset-selection-inputs': inputs, '#asset-filter-empty': element() };
    const document = { querySelector: selector => nodes[selector], querySelectorAll: () => cards, createElement: element };
    nodes['#download-asset-filters'] = filters;
    nodes['#asset-selection-actions [data-select-all]'] = selectAll;
    const view = readFileSync(new URL(`../../resources/views/client/${page}.blade.php`, import.meta.url), 'utf8');
    const end = page === 'game' ? view.indexOf("new Swiper('.cards-swiper'") : view.indexOf("$('[data-open-folder]').click(function()");
    const api = runInNewContext(view.slice(view.indexOf('const assetFilters ='), end) + (page === 'downloads' ? '\n({openAssetFolder, closeAssetFolder});' : ''), { document });
    function openFolder(indices) {
        const folder = element();
        folder.dataset.dropboxCategoryIds = '["a", "b"]';
        folder.querySelectorAll = () => indices.map(index => cards[index]);
        const list = element(), empty = element();
        folder.querySelector = selector => selector === '.assets_list' ? list : empty;
        api.openAssetFolder(folder);
    }
    if (page === 'downloads') openFolder(cards.map((_, index) => index));
    return { openFolder, closeFolder: () => api.closeAssetFolder(), cards, categories, form, download, selectAll, hint, inputs, actions, summary, error, checkbox,
        click(index) { cards[index].listeners.click({ target: { closest: () => null } }); },
        changeCategory(value) { category.value = value; filters.listeners.change({ target: { name: 'asset_category' } }); },
    };
}

for (const page of ['game', 'downloads']) {
const setupUI = sizes => setup(sizes, page);
test(`${page}: bulk labels and category visibility follow the current selection`, () => {
    const ui = setupUI();
    assert.equal(ui.actions.hidden, true);
    ui.click(0);
    assert.equal(ui.download.textContent, 'Download selected');
    assert.equal(ui.categories.hidden, true);
    assert.equal(ui.hint.hidden, true);
    ui.selectAll.listeners.click();
    assert.equal(ui.download.textContent, 'Download all');
    assert.equal(ui.checkbox.classList.contains('is-active'), true);
    assert.equal(ui.selectAll['aria-pressed'], 'true');
    ui.selectAll.listeners.click();
    assert.equal(ui.actions.hidden, true);
    assert.equal(ui.categories.hidden, false);
    assert.equal(ui.hint.hidden, false);
});

test(`${page}: category changes clear even overlapping selections and submitted IDs`, () => {
    const ui = setupUI();
    ui.click(0);
    ui.inputs.append({ value: '1' });
    ui.changeCategory('a');
    assert.ok(ui.cards.every(card => !card.classList.contains('is-active')));
    assert.equal(ui.inputs.children.length, 0);
    assert.equal(ui.actions.hidden, true);
    ui.click(0);
    ui.selectAll.listeners.click();
    assert.equal(ui.download.textContent, 'Download all');
    assert.equal(ui.checkbox.classList.contains('is-active'), true);
    assert.equal(ui.selectAll['aria-pressed'], 'true');
    assert.equal(ui.cards[2].classList.contains('is-active'), false);
    ui.changeCategory('');
    assert.ok(ui.cards.every(card => !card.classList.contains('is-active')));
    assert.equal(ui.categories.hidden, false);
});


test(`${page}: individual selection enforces both limits and reports totals`, () => {
    const ui = setupUI([50 * 1024 * 1024, 50 * 1024 * 1024, 1]);
    ui.click(0);
    ui.click(1);
    assert.match(ui.summary.textContent, /2 \/ 50 files selected.*100.00 \/ 100 MB/);
    ui.click(2);
    assert.equal(ui.cards[2].classList.contains('is-active'), false);
    assert.match(ui.error.textContent, /exceed 100 MB/);
    ui.click(0);
    ui.click(2);
    assert.equal(ui.cards[2].classList.contains('is-active'), true);
    assert.equal(ui.error.hidden, true);
});

test(`${page}: select all refuses oversized sets without changing selection`, () => {
    for (const sizes of [Array(51).fill(1), [100 * 1024 * 1024, 1]]) {
        const ui = setupUI(sizes);
        ui.click(0);
        ui.selectAll.listeners.click();
        assert.equal(ui.cards.filter(card => card.classList.contains('is-active')).length, 1);
        assert.equal(ui.error.hidden, false);
        ui.changeCategory('a');
        assert.equal(ui.error.hidden, true);
    }
});

test(`${page}: 50 files are allowed and a 51st file is rejected`, () => {
    const ui = setupUI(Array(51).fill(1));
    for (let index = 0; index < 51; index++) ui.click(index);
    assert.equal(ui.cards.filter(card => card.classList.contains('is-active')).length, 50);
    assert.match(ui.error.textContent, /50 files/);
    ui.form.listeners.submit({ preventDefault() { assert.fail('valid selection blocked'); } });
    assert.equal(ui.inputs.children.length, 50);
});

test(`${page}: missing size cannot be selected and submit rechecks size`, () => {
    const ui = setupUI(['', 1]);
    ui.click(0);
    assert.equal(ui.cards[0].classList.contains('is-active'), false);
    assert.match(ui.error.textContent, /size is unavailable/);
    ui.cards[0].classList.toggle('is-active', true);
    let prevented = false;
    ui.form.listeners.submit({ preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
    assert.equal(ui.inputs.children.length, 0);
});

}

test('download folder changes and closing clear selections and limit select all to the open game', () => {
    const ui = setup([1024, 2048, 3072], 'downloads');
    ui.openFolder([0, 1]);
    ui.click(0);
    ui.selectAll.listeners.click();
    assert.equal(ui.cards[2].classList.contains('is-active'), false);
    ui.form.listeners.submit({ preventDefault() { assert.fail('valid selection blocked'); } });
    assert.deepEqual(ui.inputs.children.map(input => input.value), ['1', '2']);
    ui.openFolder([2]);
    assert.ok(ui.cards.every(card => !card.classList.contains('is-active')));
    assert.equal(ui.inputs.children.length, 0);
    assert.equal(ui.actions.hidden, true);
    ui.click(0);
    assert.equal(ui.cards[0].classList.contains('is-active'), false);
    ui.click(2);
    assert.equal(ui.download.textContent, 'Download all');
    ui.closeFolder();
    assert.ok(ui.cards.every(card => !card.classList.contains('is-active')));
    assert.equal(ui.inputs.children.length, 0);
    assert.equal(ui.actions.hidden, true);
});
