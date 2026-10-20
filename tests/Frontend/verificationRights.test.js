import assert from 'node:assert/strict';
import test from 'node:test';
import {
    canAnswerVerificationInBulk,
    canApproveEvent,
    canApproveInBulk,
    canRequestVerificationInBulk,
    isApprovedOnRequest,
    requestOnlyApprovesSelection,
} from '../../resources/js/Helper/verificationRights.js';

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

test('bulk approve/reject needs a selected event with an open verification of a verifiable event type', () => {
    const pendingTypeA = { hasVerification: true, eventType: { id: 1 } };
    const pendingTypeB = { hasVerification: true, eventType: { id: 2 } };
    const settledTypeA = { hasVerification: false, eventType: { id: 1 } };

    assert.equal(canAnswerVerificationInBulk({ verifierForEventTypIds: [1], selectedEvents: [pendingTypeB, pendingTypeA] }), true);
    assert.equal(canAnswerVerificationInBulk({ verifierForEventTypIds: [1], selectedEvents: [pendingTypeB] }), false);
    assert.equal(canAnswerVerificationInBulk({ verifierForEventTypIds: [1], selectedEvents: [settledTypeA] }), false);
    assert.equal(canAnswerVerificationInBulk({ verifierForEventTypIds: [], selectedEvents: [pendingTypeA] }), false);
    assert.equal(canAnswerVerificationInBulk({ verifierForEventTypIds: null, selectedEvents: [pendingTypeA, null] }), false);
    assert.equal(canAnswerVerificationInBulk({ verifierForEventTypIds: ['1'], selectedEvents: [{ hasVerification: true, eventType: null }, pendingTypeA] }), true);
});

const approveRights = {
    isAdmin: false,
    canEditPlanning: false,
    userId: 7,
    verifierForEventTypIds: [],
    approvedOnRequestEventTypeIds: [],
};

test('requests of event types without verification, without type or with the person as sole verifier approve at once', () => {
    assert.equal(isApprovedOnRequest({ event: { eventType: { id: 3 } }, approvedOnRequestEventTypeIds: [3] }), true);
    assert.equal(isApprovedOnRequest({ event: { eventType: { id: 3 } }, approvedOnRequestEventTypeIds: ['3'] }), true);
    assert.equal(isApprovedOnRequest({ event: { eventType: null }, approvedOnRequestEventTypeIds: [] }), true);
    assert.equal(isApprovedOnRequest({ event: { eventType: { id: 4 } }, approvedOnRequestEventTypeIds: [3] }), false);
    assert.equal(isApprovedOnRequest({ event: null, approvedOnRequestEventTypeIds: [3] }), false);
});

test('admins approve every planned event, with or without open verification, but no fixed event', () => {
    const admin = { ...approveRights, isAdmin: true };

    assert.equal(canApproveEvent({ ...admin, event: { isPlanning: true, hasVerification: false, eventType: { id: 1 } } }), true);
    assert.equal(canApproveEvent({ ...admin, event: { isPlanning: true, hasVerification: true, eventType: { id: 1 } } }), true);
    assert.equal(canApproveEvent({ ...admin, event: { isPlanning: false, hasVerification: false, eventType: { id: 1 } } }), false);
});

test('verifiers approve open verifications of their event types', () => {
    const pending = { isPlanning: true, hasVerification: true, eventType: { id: 1 }, created_by: { id: 8 } };

    assert.equal(canApproveEvent({ ...approveRights, verifierForEventTypIds: [1], event: pending }), true);
    assert.equal(canApproveEvent({ ...approveRights, verifierForEventTypIds: [2], event: pending }), false);
});

test('sole verifiers approve planned events they may request without a prior request', () => {
    const ownPlanned = { isPlanning: true, hasVerification: false, eventType: { id: 1 }, created_by: { id: 7 } };
    const foreignPlanned = { ...ownPlanned, created_by: { id: 8 } };
    const soleVerifier = { ...approveRights, approvedOnRequestEventTypeIds: [1] };

    assert.equal(canApproveEvent({ ...soleVerifier, event: ownPlanned }), true);
    assert.equal(canApproveEvent({ ...soleVerifier, event: foreignPlanned }), false);
    assert.equal(canApproveEvent({ ...soleVerifier, canEditPlanning: true, event: foreignPlanned }), true);
    assert.equal(canApproveEvent({ ...approveRights, event: ownPlanned }), false);
});

test('bulk approve needs at least one approvable event', () => {
    const fixed = { isPlanning: false, hasVerification: false, eventType: { id: 1 } };
    const planned = { isPlanning: true, hasVerification: false, eventType: { id: 1 } };

    assert.equal(canApproveInBulk({ ...approveRights, isAdmin: true, selectedEvents: [fixed, planned] }), true);
    assert.equal(canApproveInBulk({ ...approveRights, isAdmin: true, selectedEvents: [fixed, null] }), false);
    assert.equal(canApproveInBulk({ ...approveRights, selectedEvents: [planned] }), false);
});

test('request is hidden only when every requestable selected event would be approved at once', () => {
    const base = { isAdmin: false, canEditPlanning: false, userId: 7, approvedOnRequestEventTypeIds: [1] };
    const ownDirect = { isPlanning: true, hasVerification: false, eventType: { id: 1 }, created_by: { id: 7 } };
    const ownReviewed = { ...ownDirect, eventType: { id: 2 } };
    const foreignReviewed = { ...ownReviewed, created_by: { id: 8 } };
    const pendingReviewed = { ...ownReviewed, hasVerification: true };

    assert.equal(requestOnlyApprovesSelection({ ...base, selectedEvents: [ownDirect, foreignReviewed, pendingReviewed] }), true);
    assert.equal(requestOnlyApprovesSelection({ ...base, selectedEvents: [ownDirect, ownReviewed] }), false);
    assert.equal(requestOnlyApprovesSelection({ ...base, canEditPlanning: true, selectedEvents: [ownDirect, foreignReviewed] }), false);
    assert.equal(requestOnlyApprovesSelection({ ...base, selectedEvents: [pendingReviewed] }), false);
    assert.equal(requestOnlyApprovesSelection({ ...base, selectedEvents: [] }), false);
});
