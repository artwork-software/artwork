import { router } from '@inertiajs/vue3';

export function useShiftCalendarListener(newShiftPlanData) {

    function sortArrayByStartDateTimes(array) {
        return array.sort((a, b) => {
            const aStart = new Date(a.start.replace(" ", "T"));
            const bStart = new Date(b.start.replace(" ", "T"));
            return aStart - bStart;
        });
    }

    function findRoomById(roomId) {
        return newShiftPlanData.value.find((shiftPlanObject) => shiftPlanObject.roomId === roomId);
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

        daysOfShift.forEach((day) => {
            if (!room.content[day]) return;

            const eventsAtDay = room.content[day].events;
            const shiftsAtDay = room.content[day].shifts;

            if (data.shift.eventId) {
                const event = eventsAtDay.find((event) => event.id === data.shift.eventId);
                if (event) {
                    const shiftIndex = event.shifts.findIndex((shift) => shift.id === data.shift.id);
                    if (shiftIndex !== -1) {
                        event.shifts[shiftIndex] = data.shift;
                    } else {
                        event.shifts.push(data.shift);
                        sortArrayByStartDateTimes(event.shifts);
                    }
                }
            } else {
                updateOrAddShift(shiftsAtDay, data);
            }
        });

        router.reload({
            only: ['usersForShifts', 'freelancersForShifts', 'serviceProvidersForShifts'],
        });
    }

    function addEventToRoomAndDay(eventData) {
        // HIER: .value.forEach
        newShiftPlanData.value.forEach((room) => {
            Object.keys(room.content).forEach((day) => {
                const eventsAtDay = room.content[day].events;
                const eventIndex = eventsAtDay.findIndex((event) => event.id === eventData.id);
                if (eventIndex !== -1) {
                    eventsAtDay.splice(eventIndex, 1);
                }
            });
        });

        const room = findRoomById(eventData.roomId);
        if (!room) return;
        eventData.daysOfEvent.forEach((day) => {
            if (!room.content[day]) return;
            const eventsAtDay = room.content[day].events;
            const eventIndex = eventsAtDay.findIndex((event) => event.id === eventData.id);

            if (eventIndex === -1) {
                eventsAtDay.push(eventData);
                sortArrayByStartDateTimes(eventsAtDay);
            } else {
                eventsAtDay[eventIndex] = eventData;
            }
        });
    }

    function addShiftsToRoomAndDay(shifts) {
        shifts.forEach((shift) => {
            const room = findRoomById(shift.roomId);
            if (!room) return;

            shift.daysOfShift.forEach((day) => {
                if (!room.content[day]) return;
                const shiftsAtDay = room.content[day].shifts;
                updateOrAddShift(shiftsAtDay, { shift });
            });
        });
    }

    function updateShiftForUserOrEntity(data) {
        const { shift } = data;

        // HIER: .value.forEach
        newShiftPlanData.value.forEach((room) => {
            Object.keys(room.content).forEach((day) => {
                const shiftsAtDay = room.content[day].shifts;
                const eventsAtDay = room.content[day].events;

                const shiftIndex = shiftsAtDay.findIndex((existingShift) => existingShift.id === shift.id);
                if (shiftIndex !== -1) {
                    shiftsAtDay[shiftIndex] = shift;
                }

                eventsAtDay.forEach((event) => {
                    const eventShiftIndex = event.shifts.findIndex((existingShift) => existingShift.id === shift.id);
                    if (eventShiftIndex !== -1) {
                        event.shifts[eventShiftIndex] = shift;
                    }
                });
            });
        });

        router.reload({
            only: ['usersForShifts', 'freelancersForShifts', 'serviceProvidersForShifts'],
        });
    }

    function removeShiftFromRoomAndEvents(data) {
        const { shift, roomId } = data;
        const room = findRoomById(roomId);
        if (!room) return;

        shift.daysOfShift.forEach((day) => {
            if (!room.content[day]) return;

            const shiftsAtDay = room.content[day].shifts;
            const eventsAtDay = room.content[day].events;

            const shiftIndex = shiftsAtDay.findIndex((existingShift) => existingShift.id === shift.id);
            if (shiftIndex !== -1) {
                shiftsAtDay.splice(shiftIndex, 1);
            }

            eventsAtDay.forEach((event) => {
                if (event.id === shift.eventId) {
                    const eventShiftIndex = event.shifts.findIndex((existingShift) => existingShift.id === shift.id);
                    if (eventShiftIndex !== -1) {
                        event.shifts.splice(eventShiftIndex, 1);
                    }
                }
            });
        });

        router.reload({
            only: ['usersForShifts', 'freelancersForShifts', 'serviceProvidersForShifts'],
        });
    }

    function init() {
        newShiftPlanData.value.forEach((room) => {
            Echo.private('shift-plan.room.' + room.roomId)
                .listen('.shift-created', (data) => {
                    updateShiftInRoomAndEvents(data.daysOfShift, data, data.roomId);
                })
                .listen('.shift-assign-entity', (data) => {
                    updateShiftInRoomAndEvents(data.daysOfShift, data, data.roomId);
                })
                .listen('.shift-remove-entity', (data) => {
                    updateShiftForUserOrEntity(data);
                })
                .listen('.shift-updated', (data) => {
                    updateShiftInRoomAndEvents(data.daysOfShift, data, data.roomId);
                })
                .listen('.shift-updated.in.event', (data) => {
                    updateShiftInRoomAndEvents(data.daysOfShift, data, data.roomId);
                });

            Echo.private('destroy.events.room.' + room.roomId)
                .listen('.shift-destroyed.in.event', (data) => {
                    removeShiftFromRoomAndEvents(data);
                });

            Echo.private('event.room.' + room.roomId)
                .listen('.event.created', (data) => {
                    addEventToRoomAndDay(data.event);
                })
                .listen('.event.removed', (data) => {
                    newShiftPlanData.value.forEach((room) => {
                        Object.keys(room.content).forEach((day) => {
                            const eventsAtDay = room.content[day].events;
                            const eventIndex = eventsAtDay.findIndex((event) => event.id === data.event.id);
                            if (eventIndex !== -1) {
                                eventsAtDay.splice(eventIndex, 1);
                            }
                        });
                    });
                });
        });

        Echo.channel('shift-plan.multi-shifts')
            .listen('.multi-shifts-created', (data) => {
                addShiftsToRoomAndDay(data.shifts);
            });
    }

    return { init };
}
