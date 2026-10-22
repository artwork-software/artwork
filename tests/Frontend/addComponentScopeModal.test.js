import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const source = readFileSync(
    new URL('../../resources/js/Pages/Settings/Components/AddComponentToTargetModal.vue', import.meta.url),
    'utf8'
);

test('the add dialog hides while the scope dialog is open, so the scope dialog is not covered', () => {
    const template = source.slice(source.indexOf('<template>'));
    const baseModalTag = template.slice(template.indexOf('<ArtworkBaseModal'), template.indexOf('>', template.indexOf('<ArtworkBaseModal')));

    assert.match(baseModalTag, /v-if="!showScopeModal && !showScopeModalForDisclosure"/);
});
