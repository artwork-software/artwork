import assert from 'node:assert/strict';
import test from 'node:test';
import { bulkBookingModeIn, findRoom, isRoomAdminOf, mayAnswerRoomRequestIn, roomAdminIds } from '../../resources/js/Helper/roomAdminRights.js';

test('room admins count as id list and as user objects', () => {
    assert.deepEqual(roomAdminIds({ admins: [3, 5] }), [3, 5]);
    assert.deepEqual(roomAdminIds({ admins: [{ id: 3 }, { id: '5' }] }), [3, 5]);
    assert.deepEqual(roomAdminIds({ admins: null }), []);
    assert.deepEqual(roomAdminIds(null), []);
});

test('isRoomAdminOf finds the room in lists and keyed objects', () => {
    const rooms = [{ id: 1, admins: [7] }, { id: 2, admins: [{ id: 8 }] }];

    assert.equal(isRoomAdminOf(rooms, 1, 7), true);
    assert.equal(isRoomAdminOf(rooms, 2, 8), true);
    assert.equal(isRoomAdminOf(rooms, 1, 8), false);
    assert.equal(isRoomAdminOf({ 1: { id: 1, admins: [7] } }, 1, 7), true);
    assert.equal(isRoomAdminOf(rooms, 99, 7), false);
    assert.equal(isRoomAdminOf(rooms, 1, undefined), false);
    assert.equal(findRoom(rooms, undefined), null);
});

test('mayAnswerRoomRequestIn mirrors EventPolicy::answerRoomRequest', () => {
    const rooms = [{ id: 1, admins: [7] }, { id: 2, admins: [] }];
    const base = { rooms, userId: 8, isAdmin: false, canBookWithoutRequest: false };

    assert.equal(mayAnswerRoomRequestIn({ ...base, roomId: 1 }), false);
    assert.equal(mayAnswerRoomRequestIn({ ...base, roomId: 1, userId: 7 }), true);
    assert.equal(mayAnswerRoomRequestIn({ ...base, roomId: 1, canBookWithoutRequest: true }), true);
    assert.equal(mayAnswerRoomRequestIn({ ...base, roomId: 1, isAdmin: true }), true);
    assert.equal(mayAnswerRoomRequestIn({ ...base, roomId: 2 }), true);
});

test('bulkBookingModeIn mirrors the multi-edit booking check (direct, room request, none)', () => {
    const closedRoom = { id: 1, admins: [7], everyone_can_book: false, requestable_by: [9] };
    const base = {
        room: closedRoom,
        userId: 8,
        isAdmin: false,
        isPlanning: false,
        directBookingOnly: false,
        canBookWithoutRequest: false,
        canEditPlanning: false,
        canRequestRooms: false,
    };

    assert.equal(bulkBookingModeIn(base), 'none');
    assert.equal(bulkBookingModeIn({ ...base, canRequestRooms: true }), 'request');
    assert.equal(bulkBookingModeIn({ ...base, userId: 9 }), 'request');
    assert.equal(bulkBookingModeIn({ ...base, userId: 7 }), 'direct');
    assert.equal(bulkBookingModeIn({ ...base, canBookWithoutRequest: true }), 'direct');
    assert.equal(bulkBookingModeIn({ ...base, isAdmin: true }), 'direct');
    assert.equal(bulkBookingModeIn({ ...base, room: { ...closedRoom, everyone_can_book: true } }), 'direct');

    // "Termine immer direkt buchbar": Anfragerecht bucht direkt, sonst gibt es keine Anfrage
    assert.equal(bulkBookingModeIn({ ...base, directBookingOnly: true, canRequestRooms: true }), 'direct');
    assert.equal(bulkBookingModeIn({ ...base, directBookingOnly: true, userId: 9 }), 'none');

    // Geplante Termine kennen keine Raumanfrage
    assert.equal(bulkBookingModeIn({ ...base, isPlanning: true, canRequestRooms: true }), 'none');
    assert.equal(bulkBookingModeIn({ ...base, isPlanning: true, canEditPlanning: true }), 'direct');
});
