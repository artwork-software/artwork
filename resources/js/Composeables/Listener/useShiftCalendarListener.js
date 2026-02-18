export function useShiftCalendarListener(newShiftPlanData, { onWorkersNeedReload } = {}) {

    function sortArrayByStartDateTimes(array) {
        // Create a cache for parsed dates to avoid repeated parsing
        const dateCache = new Map();

        const getDate = (item) => {
            if (!dateCache.has(item.id)) {
                dateCache.set(item.id, new Date(item.start.replace(" ", "T")));
            }
            return dateCache.get(item.id);
        };

        return array.sort((a, b) => {
            const aStart = getDate(a);
            const bStart = getDate(b);
            return aStart - bStart;
        });
    }

    function findRoomById(roomId) {
        return newShiftPlanData.value.find((shiftPlanObject) =>
            (shiftPlanObject.roomId ?? shiftPlanObject.id) === roomId
        );
    }

    function updateOrAddShift(shiftsAtDay, data) {
        const shiftIndex = shiftsAtDay.findIndex((shift) => shift.id === data.shift.id);
        if (shiftIndex !== -1) {
            shiftsAtDay[shiftIndex] = data.shift;
        } else {
            shiftsAtDay.push(data.shift);
            sortArrayByStartDateTimes(shiftsAtDay);
        }
    }

    function updateShiftInRoomAndEvents(daysOfShift, data, roomId) {
        const room = findRoomById(roomId);
        if (!room) return;

        let updated = false;

        if (room.shiftsById && room.shiftsById[data.shift.id] !== undefined) {
            room.shiftsById[data.shift.id] = data.shift;
            updated = true;
        }

        if (room.eventsById && data.shift.eventId && room.eventsById[data.shift.eventId]) {
            const event = room.eventsById[data.shift.eventId];
            if (Array.isArray(event.shifts)) {
                const idx = event.shifts.findIndex((s) => s.id === data.shift.id);
                if (idx !== -1) {
                    event.shifts[idx] = data.shift;
                } else {
                    event.shifts.push(data.shift);
                    sortArrayByStartDateTimes(event.shifts);
                }
                updated = true;
            }
        }

        if (updated && onWorkersNeedReload) {
            onWorkersNeedReload();
        }
    }

    function addEventToRoomAndDay(eventData) {
        // Check if this is BaseCalendar structure (content[day].events array) or ShiftPlan structure (eventsById)
        const isBaseCalendarStructure = newShiftPlanData.value.some(room => {
            if (!room.content) return false;
            for (const day in room.content) {
                if (Array.isArray(room.content[day]?.events)) return true;
            }
            return false;
        });

        if (isBaseCalendarStructure) {
            return updateEventInBaseCalendar(eventData);
        }

        // ShiftPlan structure handling
        for (const room of newShiftPlanData.value) {
            if (room.eventsById && room.eventsById[eventData.id]) {
                delete room.eventsById[eventData.id];
                for (const day in room.content || {}) {
                    const ids = room.content[day].eventIds;
                    if (Array.isArray(ids)) {
                        const i = ids.indexOf(eventData.id);
                        if (i !== -1) ids.splice(i, 1);
                    }
                }
            }
        }

        const room = findRoomById(eventData.roomId);
        if (!room) return false;

        if (!room.eventsById) room.eventsById = {};
        room.eventsById[eventData.id] = eventData;

        for (const day of eventData.daysOfEvent || []) {
            if (!room.content[day]) continue;
            if (!room.content[day].eventIds) room.content[day].eventIds = [];
            if (!room.content[day].eventIds.includes(eventData.id)) {
                room.content[day].eventIds.push(eventData.id);
            }
        }
        return true;
    }

    function updateEventInBaseCalendar(eventData) {
        const roomId = eventData.roomId ?? eventData.room_id;

        // First, remove the event from all rooms/days (in case room changed)
        for (const room of newShiftPlanData.value) {
            if (!room.content) continue;
            for (const day in room.content) {
                const events = room.content[day]?.events;
                if (Array.isArray(events)) {
                    const idx = events.findIndex(e => e.id === eventData.id);
                    if (idx !== -1) {
                        events.splice(idx, 1);
                    }
                }
            }
        }

        // Find the target room
        const room = newShiftPlanData.value.find(r => (r.roomId ?? r.id) === roomId);
        if (!room || !room.content) return false;

        // Add/update the event in the correct days
        const daysOfEvent = eventData.daysOfEvent || [];
        for (const day of daysOfEvent) {
            // Day key might be in German format (dd.mm.yyyy) - need to match
            let dayKey = day;
            // Try to find matching day key in content
            if (!room.content[dayKey]) {
                // Try converting ISO to German format
                const parts = day.split('-');
                if (parts.length === 3) {
                    dayKey = `${parts[2]}.${parts[1]}.${parts[0]}`;
                }
            }

            if (!room.content[dayKey]) continue;
            if (!room.content[dayKey].events) room.content[dayKey].events = [];

            // Check if event already exists, update it; otherwise add it
            const existingIdx = room.content[dayKey].events.findIndex(e => e.id === eventData.id);
            if (existingIdx !== -1) {
                room.content[dayKey].events[existingIdx] = eventData;
            } else {
                room.content[dayKey].events.push(eventData);
            }
        }

        return true;
    }

    function addShiftsToRoomAndDay(shifts) {
        let updated = false;

        for (const shift of shifts) {
            const room = findRoomById(shift.roomId);
            if (!room) continue;

            if (!room.shiftsById) room.shiftsById = {};
            room.shiftsById[shift.id] = shift;

            for (const day of shift.daysOfShift || []) {
                if (!room.content[day]) continue;
                if (!room.content[day].shiftIds) room.content[day].shiftIds = [];
                if (!room.content[day].shiftIds.includes(shift.id)) {
                    room.content[day].shiftIds.push(shift.id);
                    updated = true;
                }
            }
        }
        return updated;
    }

    function updateShiftForUserOrEntity(data) {
        const { shift } = data;
        let updated = false;

        for (const room of newShiftPlanData.value) {
            if (room.shiftsById && room.shiftsById[shift.id] !== undefined) {
                room.shiftsById[shift.id] = shift;
                updated = true;
            }
            if (room.eventsById) {
                for (const eventId of Object.keys(room.eventsById)) {
                    const event = room.eventsById[eventId];
                    if (Array.isArray(event.shifts)) {
                        const idx = event.shifts.findIndex((s) => s.id === shift.id);
                        if (idx !== -1) {
                            event.shifts[idx] = shift;
                            updated = true;
                        }
                    }
                }
            }
        }

        if (updated && onWorkersNeedReload) {
            onWorkersNeedReload();
        }
    }

    function removeShiftFromRoomAndEvents(data) {
        const { shift, roomId } = data;
        const room = findRoomById(roomId);
        if (!room) return;

        let updated = false;

        if (room.shiftsById && room.shiftsById[shift.id] !== undefined) {
            delete room.shiftsById[shift.id];
            updated = true;
        }

        for (const day of shift.daysOfShift || []) {
            if (!room.content[day] || !Array.isArray(room.content[day].shiftIds)) continue;
            const i = room.content[day].shiftIds.indexOf(shift.id);
            if (i !== -1) {
                room.content[day].shiftIds.splice(i, 1);
                updated = true;
            }
        }

        if (room.eventsById && shift.eventId && room.eventsById[shift.eventId]) {
            const event = room.eventsById[shift.eventId];
            if (Array.isArray(event.shifts)) {
                const eventShiftIndex = event.shifts.findIndex((s) => s.id === shift.id);
                if (eventShiftIndex !== -1) {
                    event.shifts.splice(eventShiftIndex, 1);
                    updated = true;
                }
            }
        }

        // Only reload if something was actually removed
        if (updated && onWorkersNeedReload) {
            onWorkersNeedReload();
        }
    }

    function init() {
        // Check if newShiftPlanData.value is iterable before attempting to iterate
        if (!newShiftPlanData.value || !Array.isArray(newShiftPlanData.value)) {
            console.warn('useShiftCalendarListener: newShiftPlanData.value is not an array or is null/undefined:', newShiftPlanData.value);
            return;
        }

        // Set up listeners for each room
        for (const room of newShiftPlanData.value) {
            // Shift plan room events
            Echo.private('shift-plan.room.' + room.roomId)
                .listen('.shift-created', (data) => {
                    updateShiftInRoomAndEvents(data.daysOfShift, data, data.roomId);
                })
                .listen('.shift-assign-entity', (data) => {
                    updateShiftInRoomAndEvents(data.daysOfShift, data, data.roomId);
                    if (onWorkersNeedReload) {
                        onWorkersNeedReload();
                    }
                })
                .listen('.shift-remove-entity', (data) => {
                    updateShiftInRoomAndEvents(data.daysOfShift, data, data.roomId);
                    if (onWorkersNeedReload) {
                        onWorkersNeedReload();
                    }
                })
                .listen('.shift-updated', (data) => {
                    updateShiftInRoomAndEvents(data.daysOfShift, data, data.roomId);
                })
                .listen('.shift-updated.in.event', (data) => {
                    updateShiftInRoomAndEvents(data.daysOfShift, data, data.roomId);
                });

            // Destroy events room
            Echo.private('destroy.events.room.' + room.roomId)
                .listen('.shift-destroyed.in.event', (data) => {
                    removeShiftFromRoomAndEvents(data);
                });

            // Event room
            Echo.private('event.room.' + room.roomId)
                .listen('.event.created', (data) => {
                    addEventToRoomAndDay(data.event);
                })
                .listen('.event.updated', (data) => {
                    addEventToRoomAndDay(data.event);
                })
                .listen('.event.removed', (data) => {
                    for (const currentRoom of newShiftPlanData.value) {
                        // ShiftPlan structure
                        if (currentRoom.eventsById && currentRoom.eventsById[data.event.id]) {
                            delete currentRoom.eventsById[data.event.id];
                        }
                        for (const day in currentRoom.content || {}) {
                            // ShiftPlan structure: eventIds
                            const ids = currentRoom.content[day].eventIds;
                            if (Array.isArray(ids)) {
                                const i = ids.indexOf(data.event.id);
                                if (i !== -1) ids.splice(i, 1);
                            }
                            // BaseCalendar structure: events array
                            const events = currentRoom.content[day].events;
                            if (Array.isArray(events)) {
                                const idx = events.findIndex(e => e.id === data.event.id);
                                if (idx !== -1) events.splice(idx, 1);
                            }
                        }
                    }
                });
        }

        // Multi-shifts channel
        Echo.channel('shift-plan.multi-shifts')
            .listen('.multi-shifts-created', (data) => {
                const updated = addShiftsToRoomAndDay(data.shifts);

                // If shifts were added, we might want to reload certain data
                if (updated && onWorkersNeedReload) {
                    onWorkersNeedReload();
                }
            });
    }

    return { init };
}
