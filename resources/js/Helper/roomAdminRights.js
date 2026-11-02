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

/**
 * Darf die Person Raumanfragen in diesem Raum zusagen/absagen? Spiegel von EventPolicy::answerRoomRequest:
 * Admins und "create events without request" überall, sonst Raumadmins (inkl. Team). Ohne Raumadmins
 * entscheidet der Server (Fallback auf die Person, die den Raum angelegt hat).
 *
 * @param {{rooms: Array<object>|Record<string, object>|null|undefined, roomId: number|null|undefined,
 *          userId: number|null|undefined, isAdmin: boolean, canBookWithoutRequest: boolean}} context
 * @returns {boolean}
 */
export function mayAnswerRoomRequestIn({rooms, roomId, userId, isAdmin, canBookWithoutRequest}) {
    if (isAdmin || canBookWithoutRequest) {
        return true;
    }

    return roomAdminIds(findRoom(rooms, roomId)).length === 0 || isRoomAdminOf(rooms, roomId, userId);
}

/**
 * Wie legen Mehrfach-Aktionen des Kalenders (Anlegen in Zellen, Duplizieren) Termine in diesem Raum an?
 * Spiegel von EventController::authorizeBulkEventCreationOrRoomRequest: 'direct' = fest gebucht,
 * 'request' = als Raumanfrage (wie im Termin-Dialog), 'none' = nicht erlaubt (Server antwortet 403).
 *
 * @param {{room: object|null|undefined, userId: number|null|undefined, isAdmin: boolean, isPlanning: boolean,
 *          directBookingOnly: boolean, canBookWithoutRequest: boolean, canEditPlanning: boolean,
 *          canRequestRooms: boolean}} context
 * @returns {'direct'|'request'|'none'}
 */
export function bulkBookingModeIn({
    room,
    userId,
    isAdmin,
    isPlanning,
    directBookingOnly,
    canBookWithoutRequest,
    canEditPlanning,
    canRequestRooms,
}) {
    if (isAdmin) {
        return 'direct';
    }
    if (isPlanning) {
        return canEditPlanning ? 'direct' : 'none';
    }

    const isRoomAdmin = userId !== null && userId !== undefined && roomAdminIds(room).includes(Number(userId));
    if (canBookWithoutRequest || (directBookingOnly && canRequestRooms) || room?.everyone_can_book || isRoomAdmin) {
        return 'direct';
    }
    if (directBookingOnly) {
        return 'none';
    }

    const isRequestableByUser = (room?.requestable_by ?? []).map(Number).includes(Number(userId));

    return canRequestRooms || isRequestableByUser ? 'request' : 'none';
}
