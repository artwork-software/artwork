import assert from 'node:assert/strict';
import test from 'node:test';
import {
    aggregateStaffing,
    countShiftStaffing,
    shiftCraftId,
    staffingKey,
    staffingLevel,
    staffingOpen,
    weekStaffingKey,
} from '../../resources/js/Helper/shiftStaffing.js';

const worker = (qualificationId, overbooked = false) => ({
    pivot: { shift_qualification_id: qualificationId, is_overbooked: overbooked },
});

test('countShiftStaffing sums demand and counts only regular workers on required functions', () => {
    const shift = {
        shifts_qualifications: [
            { shift_qualification_id: 1, value: 2 },
            { shift_qualification_id: 2, value: 1 },
            { shift_qualification_id: 3, value: 0 },
            { shift_qualification_id: 4, value: null },
        ],
        workers: [
            worker(1),
            worker(1, true), // überbucht → zählt nicht
            worker(2),
            worker(3), // Funktion ohne Bedarf → zählt nicht
        ],
    };

    assert.deepEqual(countShiftStaffing(shift), { required: 3, staffed: 2 });
});

test('countShiftStaffing accepts map-shaped qualifications and missing workers', () => {
    const shift = {
        shifts_qualifications: { a: { shift_qualification_id: 7, value: '3' } },
    };
    assert.deepEqual(countShiftStaffing(shift), { required: 3, staffed: 0 });
    assert.deepEqual(countShiftStaffing(null), { required: 0, staffed: 0 });
});

test('shiftCraftId reads craft.id, craftId and craft_id', () => {
    assert.equal(shiftCraftId({ craft: { id: 5 } }), 5);
    assert.equal(shiftCraftId({ craftId: '6' }), 6);
    assert.equal(shiftCraftId({ craft_id: 7 }), 7);
    assert.equal(shiftCraftId({}), null);
});

test('aggregateStaffing groups by craft+day and by craft+week', () => {
    const shiftA = { craftId: 1, shifts_qualifications: [{ shift_qualification_id: 1, value: 2 }], workers: [worker(1)] };
    const shiftB = { craftId: 1, shifts_qualifications: [{ shift_qualification_id: 1, value: 1 }], workers: [worker(1)] };
    const shiftC = { craftId: 2, shifts_qualifications: [{ shift_qualification_id: 2, value: 4 }], workers: [] };
    const shiftNoCraft = { shifts_qualifications: [{ shift_qualification_id: 2, value: 4 }], workers: [] };

    const result = aggregateStaffing([
        { dateKey: '07.09.2026', weekNumber: 37, shifts: [shiftA, shiftC, shiftNoCraft] },
        { dateKey: '07.09.2026', weekNumber: 37, shifts: [shiftB] }, // anderer Raum, gleicher Tag
        { dateKey: '08.09.2026', weekNumber: 37, shifts: [shiftA] },
        { dateKey: '14.09.2026', weekNumber: 38, shifts: [shiftB] },
    ]);

    assert.deepEqual(result.get(staffingKey(1, '07.09.2026')), { required: 3, staffed: 2 });
    assert.deepEqual(result.get(staffingKey(1, '08.09.2026')), { required: 2, staffed: 1 });
    assert.deepEqual(result.get(staffingKey(2, '07.09.2026')), { required: 4, staffed: 0 });
    assert.deepEqual(result.get(weekStaffingKey(1, 37)), { required: 5, staffed: 3 });
    assert.deepEqual(result.get(weekStaffingKey(1, 38)), { required: 1, staffed: 1 });
    assert.equal(result.get(weekStaffingKey(2, 38)), undefined);
    assert.equal(result.has(staffingKey(1, '14.09.2026')), true);
});

test('staffingLevel thresholds: none, low (<50 %), partial (<100 %), full', () => {
    assert.equal(staffingLevel({ required: 0, staffed: 0 }), 'none');
    assert.equal(staffingLevel(undefined), 'none');
    assert.equal(staffingLevel({ required: 12, staffed: 5 }), 'low');
    assert.equal(staffingLevel({ required: 12, staffed: 6 }), 'partial');
    assert.equal(staffingLevel({ required: 12, staffed: 11 }), 'partial');
    assert.equal(staffingLevel({ required: 12, staffed: 12 }), 'full');
    assert.equal(staffingLevel({ required: 12, staffed: 13 }), 'full');
});

test('staffingOpen never goes negative', () => {
    assert.equal(staffingOpen({ required: 12, staffed: 9 }), 3);
    assert.equal(staffingOpen({ required: 2, staffed: 5 }), 0);
    assert.equal(staffingOpen(null), 0);
});
