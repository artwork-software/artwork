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

test('deleting a restricted project tab warns that its contents become admin-only', () => {
    const hint = 'Comments, checklists and documents from this tab will then only be visible to admins.';
    assert.equal(de[hint], 'Kommentare, Checklisten und Dokumente aus diesem Tab sehen danach nur noch Admins.');
    assert.equal(en[hint], hint);
    assert.equal(de['Delete tab'], 'Tab löschen');
    assert.ok(de['Are you sure you want to delete the tab {0}?'] && en['Are you sure you want to delete the tab {0}?']);

    const source = read('resources/js/Pages/Settings/Components/SingleTabComponent.vue');
    assert.match(source, /if \(!isRestricted\.value\) \{\s*return question;/);
    assert.ok(source.includes(`$t("${hint}")`));
    // Löschen erst nach Bestätigung
    assert.match(source, /function removeTab\(\) \{[\s\S]*?showDeleteTabModal\.value = true;[\s\S]*?\}/);
});

test('the regional formats page does not promise the date format in PDFs and exports', () => {
    const source = read('resources/js/Pages/ToolSettings/Formats/Index.vue');
    assert.doesNotMatch(source, /in the interface, in PDFs and in exports/);

    const key = 'Number and currency formats apply in the interface and in many PDFs and exports. The date format is currently only used in individual places of the interface (e.g. budget comments, sources of funding, document requests and BI snapshots); PDFs and exports keep their own date format.';
    assert.ok(source.includes(key));
    assert.ok(de[key]?.startsWith('Zahlen- und Währungsformat'));
    assert.equal(en[key], key);
});

test('inventory quick-edit errors shown in the modal are translated', () => {
    const controller = read('artwork/Modules/Inventory/Http/Controllers/InventoryArticleController.php');
    for (const key of ['Invalid field.', 'Quantity is derived from the individual inventory items.']) {
        assert.ok(controller.includes(`__('${key}')`), key);
        assert.ok(!controller.includes(`=> '${key}'`), key);
        assert.ok(de[key] && de[key] !== key, `de.json: ${key}`);
        assert.equal(en[key], key);
    }
});
