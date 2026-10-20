import assert from 'node:assert/strict';
import test from 'node:test';
import { createdShiftsCarryWorkers, shiftPlanFlashOnlyReload } from '../../resources/js/Helper/shiftPlanReloads.js';

test('shift plan pages only reload the flash data after an action', () => {
    assert.deepEqual(shiftPlanFlashOnlyReload('Shifts/ShiftPlan'), { only: ['flash'] });
    assert.deepEqual(shiftPlanFlashOnlyReload('Shifts/ShiftPlanDailyView'), { only: ['flash'] });
});

test('every other page keeps the full reload', () => {
    assert.deepEqual(shiftPlanFlashOnlyReload('Projects/Tab/TabContent'), {});
    assert.deepEqual(shiftPlanFlashOnlyReload('Shifts/ShiftPlanListView'), {});
    assert.deepEqual(shiftPlanFlashOnlyReload(undefined), {});
    assert.deepEqual(shiftPlanFlashOnlyReload(null), {});
});

test('empty new shifts do not reload the person rows', () => {
    assert.equal(createdShiftsCarryWorkers([{ id: 1, workers: [] }, { id: 2, workers: [] }]), false);
    assert.equal(createdShiftsCarryWorkers([]), false);
});

test('shifts that already carry people reload the person rows', () => {
    assert.equal(createdShiftsCarryWorkers([{ id: 1, workers: [] }, { id: 2, workers: [{ id: 7, type: 'user' }] }]), true);
});

test('unknown payload shapes reload to stay on the safe side', () => {
    assert.equal(createdShiftsCarryWorkers(undefined), true);
    assert.equal(createdShiftsCarryWorkers(null), true);
    assert.equal(createdShiftsCarryWorkers([{ id: 1 }]), true);
    assert.equal(createdShiftsCarryWorkers([null]), true);
});
