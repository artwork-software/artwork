import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';

const de = JSON.parse(readFileSync(new URL('../../lang/de.json', import.meta.url), 'utf8'));
const en = JSON.parse(readFileSync(new URL('../../lang/en.json', import.meta.url), 'utf8'));
const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

// Keys introduced to replace hardcoded German text and concatenated labels.
const KEYS = {
    '(Copy)': ['(Kopie)', '(Copy)'],
    Narrow: ['Schmal', 'Narrow'],
    Wide: ['Breit', 'Wide'],
    'Very wide': ['Sehr breit', 'Very wide'],
    Germany: ['Deutschland', 'Germany'],
    Switzerland: ['Schweiz', 'Switzerland'],
    'Via interface': ['Über Schnittstelle', 'Via interface'],
    'Event history': ['Terminverlauf', 'Event history'],
    'Book working hours': ['Arbeitsstunden buchen', 'Book working hours'],
    'Jump by day': ['Springen um Tag', 'Jump by day'],
    'Jump by calendar week': ['Springen um Kalenderwoche', 'Jump by calendar week'],
    'Jump by month': ['Springen um Monat', 'Jump by month'],
    'Work time accounting': ['Arbeitszeitberechnung', 'Work time accounting'],
};

test('new translation keys exist in German and English', () => {
    for (const [key, [german, english]] of Object.entries(KEYS)) {
        assert.equal(de[key], german, `de.json: ${key}`);
        assert.equal(en[key], english, `en.json: ${key}`);
    }
});

test('English values of previously awkward labels are fixed', () => {
    assert.equal(en['Request appointments verification'], 'Request event verification');
    assert.equal(en['My Operational plan'], 'My operational plan');
});

test('scroll-mode labels are single keys, not "Jump around" + unit', () => {
    for (const file of [
        'resources/js/Layouts/Components/ShiftPlanComponents/ShiftPlanFunctionBar.vue',
        'resources/js/Layouts/Components/ShiftPlanComponents/ShiftPlanListViewFunctionBar.vue',
    ]) {
        assert.doesNotMatch(read(file), /Jump around/, file);
    }
});

test('no hardcoded German UI text remains in the fixed components', () => {
    assert.doesNotMatch(read('resources/js/Artwork/Modals/CalendarSettingsModal.vue'), /Schmal|Sehr breit|'Breit/);
    assert.doesNotMatch(read('resources/js/Pages/Settings/Holidays/Index.vue'), /name: "Deutschland"|name: "Schweiz"/);
    assert.doesNotMatch(read('resources/js/Pages/Inventory/Components/Article/Modals/AddEditArticleModal.vue'), /aria-label="Kopieren"/);
});

test('the manual booking button in the user profile is translated', () => {
    const source = read('resources/js/Pages/Users/UserWorkTimes.vue');
    assert.ok(!source.includes('label="Arbeitszeit Buchen"'), 'hardcoded German label');
    assert.ok(source.includes("$t('Book working hours')"));
});

test('shift settings texts only promise what the code does', () => {
    const source = read('resources/js/Pages/Settings/ShiftSettings.vue');
    const removed = [
        // es gibt nur EINE Planer-Liste je Gewerk, keine Planer je Mitarbeitendentyp
        'Define crafts to which you can later assign employees and shifts. Additionally, you can specify which users are allowed to assign what type of employee shifts.',
        // Freelancer und Dienstleister nehmen nicht am Zu-/Absage-Flow teil (ShiftConfirmationEligibilityService)
        'Scheduled people can accept or decline their committed shifts in their own operational plan. Planners can record responses on behalf of freelancers and service providers.',
    ];
    for (const key of removed) {
        assert.ok(!source.includes(key), `ShiftSettings.vue still uses: ${key}`);
        assert.equal(de[key], undefined, `de.json still has: ${key}`);
        assert.equal(en[key], undefined, `en.json still has: ${key}`);
    }

    const crafts = 'Define crafts to which you can later assign employees and shifts. For each craft, you can also set the craft management and which users may plan its shifts.';
    const confirmation = 'Scheduled users with the permission “Accept or decline shifts” can accept or decline their committed shifts in their own operational plan. Planners can record the response on behalf of these users. Freelancers and service providers do not take part.';
    for (const key of [crafts, confirmation]) {
        assert.ok(source.includes(`$t('${key}')`), `ShiftSettings.vue misses: ${key}`);
        assert.equal(en[key], key, `en.json: ${key}`);
        assert.ok(de[key], `de.json: ${key}`);
    }
    assert.match(de[confirmation], /Freelancer und Dienstleister nehmen nicht teil/);
    assert.match(de[confirmation], /Darf Schichten annehmen\/ablehnen/);
});

test('aria-labels in the craft modal are bound, not literal text', () => {
    const source = read('resources/js/Layouts/Components/AddCraftsModal.vue');
    // ohne Doppelpunkt landet "$t(...)" bzw. "{{...}}" wörtlich im Attribut
    assert.doesNotMatch(source, /(?<!:)aria-label="(\{\{|\$t\()/);
    assert.ok(source.includes(`:aria-label="$t('Remove')"`));
    assert.ok(source.includes(`:aria-label="$t('Delete department management')"`));
});

test('craft modal uses "Gewerkleitung" consistently in German', () => {
    assert.equal(de['Craft manager'], 'Gewerkleitung');
    assert.equal(de['Add department management'], 'Gewerkleitung hinzufügen');
    assert.equal(de['Delete department management'], 'Gewerkleitung löschen');
});
