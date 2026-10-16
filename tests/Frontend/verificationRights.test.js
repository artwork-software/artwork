import assert from 'node:assert/strict';
import test from 'node:test';
import { canRequestVerificationInBulk } from '../../resources/js/Helper/verificationRights.js';

const base = { isAdmin: false, canEditPlanning: false, canPlanFixed: false, userId: 7, selectedEvents: [] };

test('admins and people who may plan or edit planned events always get the bulk button', () => {
    assert.equal(canRequestVerificationInBulk({ ...base, isAdmin: true }), true);
    assert.equal(canRequestVerificationInBulk({ ...base, canEditPlanning: true }), true);
    assert.equal(canRequestVerificationInBulk({ ...base, canPlanFixed: true }), true);
});

test('creators without those permissions get it once an own planned event is selected', () => {
    const ownPlanned = { isPlanning: true, created_by: { id: 7 } };
    const foreignPlanned = { isPlanning: true, created_by: { id: 8 } };

    assert.equal(canRequestVerificationInBulk({ ...base, selectedEvents: [foreignPlanned, ownPlanned] }), true);
    assert.equal(canRequestVerificationInBulk({ ...base, selectedEvents: [foreignPlanned] }), false);
    assert.equal(canRequestVerificationInBulk({ ...base, selectedEvents: [] }), false);
});

test('own fixed events, deleted creators and unknown events do not count', () => {
    assert.equal(canRequestVerificationInBulk({
        ...base,
        selectedEvents: [{ isPlanning: false, created_by: { id: 7 } }, { isPlanning: true, created_by: null }, null],
    }), false);
    assert.equal(canRequestVerificationInBulk({
        ...base,
        userId: undefined,
        selectedEvents: [{ isPlanning: true, created_by: { id: undefined } }],
    }), false);
});
