import test from 'node:test';
import assert from 'node:assert/strict';
import {
    calculateShiftPlanRequestScrollDelta,
    findActiveShiftPlanRequestRow,
    isShiftPlanRequestComparisonTarget,
} from '../../resources/js/Pages/ShiftPlanRequests/components/shiftPlanRequestNavigation.js';

test('it does not capture a row while the week navigator is not sticky', () => {
    const activeRow = findActiveShiftPlanRequestRow({
        headerRect: {top: 180, bottom: 270},
        rows: [{rowKey: 'user-1', top: 300, bottom: 500}],
        viewportHeight: 720,
    });

    assert.equal(activeRow, null);
});

test('it captures the row currently crossing the review line', () => {
    const activeRow = findActiveShiftPlanRequestRow({
        headerRect: {top: 0, bottom: 92},
        rows: [
            {rowKey: 'user-1', top: -40, bottom: 125},
            {rowKey: 'user-2', top: 141, bottom: 310},
        ],
        viewportHeight: 720,
    });

    assert.deepEqual(activeRow, {
        rowKey: 'user-2',
        rowOffset: 49,
    });
});

test('it keeps a partially reviewed row at the same offset', () => {
    const scrollDelta = calculateShiftPlanRequestScrollDelta({
        headerBottom: 92,
        rowTop: 248,
        rowOffset: -36,
    });

    assert.equal(scrollDelta, 192);
});

test('it rejects stale comparison context for a different request', () => {
    assert.equal(isShiftPlanRequestComparisonTarget({targetRequestId: 2}, 3), false);
    assert.equal(isShiftPlanRequestComparisonTarget({targetRequestId: 2}, 2), true);
});
