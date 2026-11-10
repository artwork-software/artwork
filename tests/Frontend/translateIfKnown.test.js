import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { translateIfKnown } from '../../resources/js/Helper/translateIfKnown.js';

const messages = {'Export plans': 'Pläne exportieren', 'Please try again.': 'Bitte erneut versuchen.'};
const translate = (key) => messages[key];

test('known keys are translated, including keys with dots', () => {
    assert.equal(translateIfKnown('Export plans', messages, translate), 'Pläne exportieren');
    assert.equal(translateIfKnown('Please try again.', messages, translate), 'Bitte erneut versuchen.');
});

test('names with i18n syntax characters stay untouched', () => {
    for (const name of ['Kabel | 5 m', 'Slack @team', 'Projekt {neu}', 'Dr. Müller']) {
        assert.equal(translateIfKnown(name, messages, () => assert.fail(`translated ${name}`)), name);
    }
});

test('empty values and missing messages are handled', () => {
    assert.equal(translateIfKnown('', messages, translate), '');
    assert.equal(translateIfKnown(null, messages, translate), '');
    assert.equal(translateIfKnown('Export plans', null, translate), 'Export plans');
    assert.equal(translateIfKnown('toString', {}, translate), 'toString');
});

test('ArtworkBaseModal no longer runs title and description through $t', () => {
    const modal = readFileSync(new URL('../../resources/js/Artwork/Modals/ArtworkBaseModal.vue', import.meta.url), 'utf8');

    assert.doesNotMatch(modal, /\$t\(props\.(title|description)\)/);
    assert.match(modal, /modalText\(props\.title\)/);
});
