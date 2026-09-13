import assert from 'node:assert/strict';
import test from 'node:test';
import {
    aggregateStaffing,
    aggregateStaffingByRoom,
    countShiftStaffing,
    createRoomStaffingMemo,
    mergeStaffing,
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

test('mergeStaffing sums partial maps without mutating them', () => {
    const a = new Map([[staffingKey(1, '07.09.2026'), { required: 2, staffed: 1 }]]);
    const b = new Map([
        [staffingKey(1, '07.09.2026'), { required: 1, staffed: 1 }],
        [staffingKey(2, '07.09.2026'), { required: 4, staffed: 0 }],
    ]);

    const merged = mergeStaffing([a, null, b]);

    assert.deepEqual(merged.get(staffingKey(1, '07.09.2026')), { required: 3, staffed: 2 });
    assert.deepEqual(merged.get(staffingKey(2, '07.09.2026')), { required: 4, staffed: 0 });
    assert.deepEqual(a.get(staffingKey(1, '07.09.2026')), { required: 2, staffed: 1 }, 'input must stay untouched');
});

test('aggregateStaffingByRoom recomputes only the room whose version changed', () => {
    const shift = (craftId, value, staffed) => ({
        craftId,
        shifts_qualifications: [{ shift_qualification_id: 1, value }],
        workers: Array.from({ length: staffed }, () => worker(1)),
    });
    const roomA = { roomId: 1, __v: 0, content: { '07.09.2026': { shifts: [shift(1, 2, 1)] } } };
    const roomB = { roomId: 2, __v: 0, content: { '07.09.2026': { shifts: [shift(1, 3, 3)] } } };
    const memo = createRoomStaffingMemo();
    const run = () => aggregateStaffingByRoom(
        [roomA, roomB].map((room) => ({
            room,
            version: room.__v,
            entries: () => Object.entries(room.content).map(([dateKey, cell]) => ({ dateKey, weekNumber: 37, shifts: cell.shifts })),
        })),
        memo,
    );

    const first = run();
    assert.deepEqual(first.get(staffingKey(1, '07.09.2026')), { required: 5, staffed: 4 });
    assert.equal(memo.computeCalls, 2, 'initial run aggregates every room');

    // unveränderte Versionen → kein Raum wird neu berechnet
    run();
    assert.equal(memo.computeCalls, 2);

    // Broadcast für Raum B (bumpRoomVersion) → nur Raum B neu
    roomB.content['07.09.2026'].shifts = [shift(1, 3, 1)];
    roomB.__v += 1;
    const second = run();
    assert.equal(memo.computeCalls, 3, 'only the changed room is aggregated again');
    assert.deepEqual(second.get(staffingKey(1, '07.09.2026')), { required: 5, staffed: 2 });

    // clear() (Zeitraum-/Zoomwechsel) → alle Räume neu
    memo.clear();
    run();
    assert.equal(memo.computeCalls, 5);

    // Neues Raum-Objekt (Plan neu geladen) mit gleicher id/Version darf keinen alten Eintrag treffen
    const roomAReloaded = { ...roomA, content: { '07.09.2026': { shifts: [shift(1, 9, 0)] } } };
    const third = aggregateStaffingByRoom(
        [{ room: roomAReloaded, version: roomAReloaded.__v, entries: () => [{ dateKey: '07.09.2026', weekNumber: 37, shifts: roomAReloaded.content['07.09.2026'].shifts }] }],
        memo,
    );
    assert.equal(memo.computeCalls, 6);
    assert.deepEqual(third.get(staffingKey(1, '07.09.2026')), { required: 9, staffed: 0 });
});
