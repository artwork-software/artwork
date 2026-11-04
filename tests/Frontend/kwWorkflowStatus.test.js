import assert from 'node:assert/strict';
import test from 'node:test';
import {
    INDIVIDUAL_TIMES_KEY,
    individualTimeWorkflowBadge,
    resolveKwWorkflowStatus,
} from '../../resources/js/Helper/kwWorkflowStatus.js';

test('resolveKwWorkflowStatus prefers the craft entry and falls back to individual times', () => {
    const map = {
        3: { 19: 'requested', 20: 'open' },
        [INDIVIDUAL_TIMES_KEY]: { 19: 'committed', 20: 'committed', 21: 'committed' },
    };

    assert.equal(resolveKwWorkflowStatus(map, 3, 19), 'requested');
    assert.equal(resolveKwWorkflowStatus(map, 3, 20), 'open', 'open blocks the fallback');
    assert.equal(resolveKwWorkflowStatus(map, 3, 21), 'committed', 'week with only individual times');
    assert.equal(resolveKwWorkflowStatus(map, 7, 21), 'committed', 'craft row without any shifts');
    assert.equal(resolveKwWorkflowStatus(map, 3, 22), null);
    assert.equal(resolveKwWorkflowStatus(null, 3, 19), null);
    assert.equal(resolveKwWorkflowStatus({ 3: { 19: 'committed' } }, 3, 19), 'committed');
});

test('individualTimeWorkflowBadge labels requested and released times only', () => {
    assert.equal(individualTimeWorkflowBadge('committed').label, 'Released');
    assert.equal(individualTimeWorkflowBadge('requested').label, 'In release request');
    assert.equal(individualTimeWorkflowBadge(null), null);
    assert.equal(individualTimeWorkflowBadge(undefined), null);
});
