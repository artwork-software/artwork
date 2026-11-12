import assert from 'node:assert/strict';
import test from 'node:test';
import { individualTimeWorkflowMarker } from '../../resources/js/Helper/individualTimeWorkflow.js';

test('released individual times get a green chip in the colours of the KW tile', () => {
    const marker = individualTimeWorkflowMarker('committed');
    assert.match(marker.chip, /bg-\[var\(--uo-kw-committed-bg\)\]/);
    assert.match(marker.chip, /ring-\[var\(--uo-kw-committed-ring\)\]/);
    assert.equal(marker.titleKey, 'Individual time released');
});

test('requested individual times get a blue chip', () => {
    const marker = individualTimeWorkflowMarker('requested');
    assert.match(marker.chip, /bg-\[var\(--uo-kw-requested-bg\)\]/);
    assert.equal(marker.titleKey, 'Individual time in release request');
});

test('individual times outside the release workflow get no chip', () => {
    assert.equal(individualTimeWorkflowMarker(null), null);
    assert.equal(individualTimeWorkflowMarker(undefined), null);
    assert.equal(individualTimeWorkflowMarker('unknown'), null);
});
