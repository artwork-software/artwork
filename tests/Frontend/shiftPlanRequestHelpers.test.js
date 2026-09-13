import assert from 'node:assert/strict';
import test from 'node:test';
import {
    canResubmitRequest,
    canWithdrawRequest,
    isRequesterOf,
    shiftPlanRequestWeekLabel,
} from '../../resources/js/Pages/ShiftPlanRequests/components/shiftPlanRequestHelpers.js';

test('isRequesterOf prefers requested_by_user_id, then requested_by.id, then assumeOwn', () => {
    assert.equal(isRequesterOf(null, 5), false);
    assert.equal(isRequesterOf({ requested_by_user_id: 5 }, 5), true);
    assert.equal(isRequesterOf({ requested_by_user_id: '5' }, 5), true, 'numeric strings from payloads');
    assert.equal(isRequesterOf({ requested_by_user_id: 6 }, 5), false);
    // id-Feld gewinnt gegen requested_by-Objekt
    assert.equal(isRequesterOf({ requested_by_user_id: 6, requested_by: { id: 5 } }, 5), false);
    assert.equal(isRequesterOf({ requested_by: { id: 5 } }, 5), true);
    assert.equal(isRequesterOf({ requested_by: { id: 6 } }, 5), false);
    // ohne Angabe: nur mit assumeOwn (z. B. Liste „nur eigene Anfragen")
    assert.equal(isRequesterOf({ requested_by_user_id: null }, 5), false);
    assert.equal(isRequesterOf({ requested_by_user_id: null }, 5, true), true);
    assert.equal(isRequesterOf({}, 5, true), true);
    assert.equal(isRequesterOf({ requested_by: {} }, 5, true), true);
});

test('canWithdrawRequest only for pending requests of the requester', () => {
    assert.equal(canWithdrawRequest({ status: 'pending', requested_by_user_id: 5 }, 5), true);
    assert.equal(canWithdrawRequest({ status: 'pending', requested_by_user_id: 6 }, 5), false);
    assert.equal(canWithdrawRequest({ status: 'approved', requested_by_user_id: 5 }, 5), false);
    assert.equal(canWithdrawRequest({ status: 'rejected', requested_by_user_id: 5 }, 5), false);
    assert.equal(canWithdrawRequest({ status: 'pending' }, 5), false);
    assert.equal(canWithdrawRequest({ status: 'pending' }, 5, true), true);
    assert.equal(canWithdrawRequest(null, 5, true), false);
});

test('canResubmitRequest only for rejected requests of the requester', () => {
    assert.equal(canResubmitRequest({ status: 'rejected', requested_by_user_id: 5 }, 5), true);
    assert.equal(canResubmitRequest({ status: 'rejected', requested_by_user_id: 6 }, 5), false);
    assert.equal(canResubmitRequest({ status: 'pending', requested_by_user_id: 5 }, 5), false);
    assert.equal(canResubmitRequest({ status: 'rejected' }, 5), false);
    assert.equal(canResubmitRequest({ status: 'rejected' }, 5, true), true);
    assert.equal(canResubmitRequest(undefined, 5), false);
});

test('shiftPlanRequestWeekLabel shows the Monday–Sunday range for valid weeks', () => {
    assert.equal(shiftPlanRequestWeekLabel({ week_number: 37, year: 2026 }), 'KW 37 / 2026 (07.09.2026 – 13.09.2026)');
    assert.equal(shiftPlanRequestWeekLabel({ week_number: '1', year: '2026' }), 'KW 1 / 2026 (29.12.2025 – 04.01.2026)');
    assert.equal(shiftPlanRequestWeekLabel({ week_number: 53, year: 2026 }), 'KW 53 / 2026 (28.12.2026 – 03.01.2027)');
});

test('shiftPlanRequestWeekLabel degrades gracefully for invalid or missing weeks', () => {
    // KW 53 existiert 2025 nicht → kein Datumsbereich, aber die Eingabe bleibt sichtbar
    assert.equal(shiftPlanRequestWeekLabel({ week_number: 53, year: 2025 }), 'KW 53 / 2025');
    assert.equal(shiftPlanRequestWeekLabel({ week_number: 0, year: 2026 }), 'KW 0 / 2026');
    assert.equal(shiftPlanRequestWeekLabel({}), 'KW – / –');
    assert.equal(shiftPlanRequestWeekLabel(null), 'KW – / –');
});
