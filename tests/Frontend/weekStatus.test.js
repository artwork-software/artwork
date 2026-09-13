import assert from 'node:assert/strict';
import test from 'node:test';
import {
    DEADLINE_META,
    STATUS_META,
    STATUS_ORDER,
    availableActions,
    deadlineMeta,
    isEmptyCell,
    shiftPlanFilterRange,
    statusMeta,
} from '../../resources/js/Pages/Shifts/WeekStatus/weekStatus.js';

test('every status in STATUS_ORDER has complete metadata', () => {
    for (const status of STATUS_ORDER) {
        const meta = STATUS_META[status];
        assert.ok(meta, `missing meta for ${status}`);
        for (const key of ['label', 'description', 'chip', 'dot']) {
            assert.equal(typeof meta[key], 'string', `${status}.${key} must be a string`);
            assert.ok(meta[key].length > 0, `${status}.${key} must not be empty`);
        }
    }
});

test('statusMeta resolves known statuses and falls back to "none"', () => {
    assert.equal(statusMeta('committed'), STATUS_META.committed);
    assert.equal(statusMeta('rejected').label, 'Rejected');
    assert.equal(statusMeta('does-not-exist'), STATUS_META.none);
    assert.equal(statusMeta(undefined), STATUS_META.none);
    assert.equal(statusMeta(null), STATUS_META.none);
});

test('deadlineMeta returns metadata for known states and null otherwise', () => {
    assert.equal(deadlineMeta('ok'), DEADLINE_META.ok);
    assert.equal(deadlineMeta('due_soon').classes, 'bg-warning');
    assert.equal(deadlineMeta('overdue').label, 'Deadline overdue');
    assert.equal(deadlineMeta('unknown'), null);
    assert.equal(deadlineMeta(null), null);
    assert.equal(deadlineMeta(undefined), null);
    assert.equal(deadlineMeta(''), null);
});

test('isEmptyCell is true without a cell or without shifts and demand', () => {
    assert.equal(isEmptyCell(null), true);
    assert.equal(isEmptyCell(undefined), true);
    assert.equal(isEmptyCell({ status: 'none', required_slots: 0 }), true);
    assert.equal(isEmptyCell({ status: 'none' }), true);
    assert.equal(isEmptyCell({ status: 'none', required_slots: '0' }), true);
    // Bedarf ohne Schichten → Kennzahlen zeigen
    assert.equal(isEmptyCell({ status: 'none', required_slots: 3 }), false);
    assert.equal(isEmptyCell({ status: 'open', required_slots: 0 }), false);
});

test('availableActions offers nothing for empty cells', () => {
    const context = { workflowEnabled: true, canCommit: true };
    assert.deepEqual(availableActions(null, context), { canRequest: false, canCommitDirectly: false });
    assert.deepEqual(availableActions({ status: 'none' }, context), { canRequest: false, canCommitDirectly: false });
});

test('availableActions with workflow enabled only allows requests for open/rejected/partial', () => {
    const context = { workflowEnabled: true, canCommit: true };
    for (const status of ['open', 'rejected', 'partial']) {
        assert.deepEqual(availableActions({ status }, context), { canRequest: true, canCommitDirectly: false }, status);
    }
    for (const status of ['requested', 'committed']) {
        assert.deepEqual(availableActions({ status }, context), { canRequest: false, canCommitDirectly: false }, status);
    }
    // ohne Festschreibe-Recht nie
    assert.deepEqual(
        availableActions({ status: 'open' }, { workflowEnabled: true, canCommit: false }),
        { canRequest: false, canCommitDirectly: false }
    );
});

test('availableActions without workflow allows direct commit for everything not yet committed', () => {
    const context = { workflowEnabled: false, canCommit: true };
    for (const status of ['open', 'rejected', 'partial', 'requested']) {
        assert.deepEqual(availableActions({ status }, context), { canRequest: false, canCommitDirectly: true }, status);
    }
    assert.deepEqual(availableActions({ status: 'committed' }, context), { canRequest: false, canCommitDirectly: false });
    assert.deepEqual(
        availableActions({ status: 'open' }, { workflowEnabled: false, canCommit: false }),
        { canRequest: false, canCommitDirectly: false }
    );
});

test('shiftPlanFilterRange maps the backend week descriptor to start/end', () => {
    assert.deepEqual(
        shiftPlanFilterRange({ monday: '2026-09-07', sunday: '2026-09-13' }),
        { start: '2026-09-07', end: '2026-09-13' }
    );
    assert.equal(shiftPlanFilterRange(null), null);
    assert.equal(shiftPlanFilterRange({}), null);
    assert.equal(shiftPlanFilterRange({ monday: '2026-09-07' }), null);
});
