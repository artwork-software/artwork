import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { exceedsSeriesLimit, latestSeriesEnd } from '../../resources/js/Helper/vacationSeriesLimit.js';

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

test('Serien dürfen genau fünf Jahre laufen, nicht länger', () => {
    assert.equal(latestSeriesEnd('2026-11-16'), '2031-11-16');
    assert.equal(exceedsSeriesLimit('2026-11-16', '2031-11-16'), false);
    assert.equal(exceedsSeriesLimit('2026-11-16', '2031-11-17'), true);
    assert.equal(exceedsSeriesLimit('2026-11-16', '2100-01-01'), true);
});

test('29. Februar läuft wie auf dem Server auf den 1. März über', () => {
    assert.equal(latestSeriesEnd('2028-02-29'), '2033-03-01');
});

test('fehlende oder ungültige Daten sind keine Grenzverletzung', () => {
    assert.equal(exceedsSeriesLimit(null, '2031-01-01'), false);
    assert.equal(exceedsSeriesLimit('2026-11-16', null), false);
    assert.equal(exceedsSeriesLimit('kaputt', '2031-01-01'), false);
});

test('das Abwesenheits-Modal prüft Zeitraum und wöchentliche Wiederholung gegen die Grenze', () => {
    const modal = read('resources/js/Pages/Users/Components/AddEditVacationsModal.vue');
    assert.match(modal, /exceedsSeriesLimit\(entry\.start_date, entry\.end_date\)/);
    assert.match(modal, /exceedsSeriesLimit\(entry\.start_date, entry\.repeat_until\)/);
});
