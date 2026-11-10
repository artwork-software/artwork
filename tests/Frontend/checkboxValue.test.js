import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { isCheckboxChecked } from '../../resources/js/Helper/CheckboxValue.js';

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

test('gespeichertes "0" und andere Nein-Werte gelten als nicht angehakt', () => {
    for (const value of ['0', 'false', 'FALSE', 'off', '', ' 0 ', 0, false, null, undefined]) {
        assert.equal(isCheckboxChecked(value), false, `Wert ${JSON.stringify(value)}`);
    }
});

test('gespeichertes "1" und andere Ja-Werte gelten als angehakt', () => {
    for (const value of ['1', 'true', 'on', 1, true]) {
        assert.equal(isCheckboxChecked(value), true, `Wert ${JSON.stringify(value)}`);
    }
});

test('Inventar-Anzeigen werten Checkbox-Werte über den Helfer aus, nicht über truthy', () => {
    const display = read('resources/js/Composeables/InventoryPropertyDisplay.js');
    assert.match(display, /isCheckboxChecked\(pivotValue\) \? \$t\('Yes'\)/);
    assert.match(display, /property\.type === 'checkbox'\) return isCheckboxChecked\(pivotValue\)/);

    const detail = read('resources/js/Pages/Inventory/Components/Article/Modals/ArticleDetailModal.vue');
    assert.match(detail, /isCheckboxChecked\(property\.pivot\.value\) \? \$t\('Yes'\)/);
    assert.doesNotMatch(detail, /return property\.pivot\.value \? \$t\('Yes'\)/);
});
