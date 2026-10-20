/**
 * Raumadmin-Prüfung im Frontend. Räume kommen je nach Seite mit `admins` als ID-Liste (RoomDTO) oder als
 * User-Objekte – beides zählt. Reines JS-Modul (tests/Frontend/roomAdminRights.test.js).
 */

/**
 * @param {{admins?: Array<number|{id?: number}|null>|null}|null|undefined} room
 * @returns {number[]}
 */
export function roomAdminIds(room) {
    return (room?.admins ?? [])
        .map((admin) => Number(admin?.id ?? admin))
        .filter((id) => Number.isFinite(id));
}

/**
 * @param {Array<object>|Record<string, object>|null|undefined} rooms Liste oder nach ID geschlüsseltes Objekt
 * @param {number|null|undefined} roomId
 * @returns {object|null}
 */
export function findRoom(rooms, roomId) {
    if (roomId === null || roomId === undefined) {
        return null;
    }
    const list = Array.isArray(rooms) ? rooms : Object.values(rooms ?? {});

    return list.find((candidate) => Number(candidate?.id) === Number(roomId)) ?? null;
}

/**
 * @param {Array<object>|Record<string, object>|null|undefined} rooms
 * @param {number|null|undefined} roomId
 * @param {number|null|undefined} userId
 * @returns {boolean}
 */
export function isRoomAdminOf(rooms, roomId, userId) {
    if (userId === null || userId === undefined) {
        return false;
    }

    return roomAdminIds(findRoom(rooms, roomId)).includes(Number(userId));
}
