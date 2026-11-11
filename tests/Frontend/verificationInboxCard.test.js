import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

const card = read('resources/js/Pages/EventVerification/Components/SingleEventVerificationRequest.vue');

test('die Kachel der Eingangsliste zeigt alle angefragten Prüfenden einer Anfrage mit Status', () => {
    assert.match(card, /eventVerification\?\.round_verifiers/);
    assert.match(card, /v-for="verifier in roundVerifiers"/);
    assert.match(card, /verifierStatusClasses\[verifier\.status\]/);
    assert.match(card, /\$t\('Requested from'\)/);
});

test('die Kachel nennt die anfragende Person', () => {
    assert.match(card, /eventVerification\.requester\.full_name/);
    assert.match(card, /\$t\('Requested by'\)/);
});

test('"Requested from" ist in beiden Sprachen übersetzt', () => {
    assert.equal(JSON.parse(read('lang/de.json'))['Requested from'], 'Angefragt bei');
    assert.equal(JSON.parse(read('lang/en.json'))['Requested from'], 'Requested from');
});
