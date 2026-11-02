import assert from 'node:assert/strict';
import test from 'node:test';
import { createdShiftDays, createdShiftsCarryWorkers, shiftPlanFlashOnlyReload } from '../../resources/js/Helper/shiftPlanReloads.js';

test('shift plan pages only reload the flash data after an action', () => {
    assert.deepEqual(shiftPlanFlashOnlyReload('Shifts/ShiftPlan'), { only: ['flash'] });
    assert.deepEqual(shiftPlanFlashOnlyReload('Shifts/ShiftPlanDailyView'), { only: ['flash'] });
    assert.deepEqual(shiftPlanFlashOnlyReload('Shifts/ShiftPlanListView'), { only: ['flash'] });
    // Projekt-Schichten-Tab: die eingebettete Tagesansicht übernimmt flash.shiftPlanUpdate selbst
    assert.deepEqual(shiftPlanFlashOnlyReload('Projects/Tab/TabContent'), { only: ['flash'] });
});

test('every other page keeps the full reload', () => {
    assert.deepEqual(shiftPlanFlashOnlyReload('Projects/Show'), {});
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

test('created shift days are unique start dates of the saved shifts', () => {
    assert.deepEqual(createdShiftDays({ shifts: [
        { id: 1, startDate: '2026-10-30' },
        { id: 2, startDate: '2026-10-31' },
        { id: 3, startDate: '2026-10-30' },
    ] }), ['2026-10-30', '2026-10-31']);
});

test('no created shift days without a usable payload', () => {
    assert.deepEqual(createdShiftDays(null), []);
    assert.deepEqual(createdShiftDays({ shift: { id: 1 } }), []);
    assert.deepEqual(createdShiftDays({ shifts: [{ id: 1 }, null] }), []);
});
