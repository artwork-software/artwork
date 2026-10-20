import assert from 'node:assert/strict';
import test from 'node:test';
import { findRoom, isRoomAdminOf, roomAdminIds } from '../../resources/js/Helper/roomAdminRights.js';

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
