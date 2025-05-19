import { router } from '@inertiajs/vue3';

export function useShiftCalendarListener(newShiftPlanData) {

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

        let updated = false;

        for (const day of daysOfShift) {
            if (!room.content[day]) continue;

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
                    updated = true;
                }
            } else {
                // Check if shift was actually added or updated
                const shiftIndex = shiftsAtDay.findIndex((shift) => shift.id === data.shift.id);
                const wasAdded = shiftIndex === -1;

                updateOrAddShift(shiftsAtDay, data);

                if (wasAdded || shiftIndex !== -1) {
                    updated = true;
                }
            }
        }

        // Only reload if something was actually updated
        if (updated) {
            router.reload({
                only: ['usersForShifts', 'freelancersForShifts', 'serviceProvidersForShifts'],
            });
        }
    }

    function addEventToRoomAndDay(eventData) {
        // First, remove the event from all rooms if it exists
        // Use a Map to track which days had the event for better performance
        const daysWithEvent = new Map();

        for (const room of newShiftPlanData.value) {
            for (const day in room.content) {
                const eventsAtDay = room.content[day].events;
                const eventIndex = eventsAtDay.findIndex((event) => event.id === eventData.id);
                if (eventIndex !== -1) {
                    eventsAtDay.splice(eventIndex, 1);
                    // Track which days had the event
                    if (!daysWithEvent.has(day)) {
                        daysWithEvent.set(day, true);
                    }
                }
            }
        }

        // Then add the event to the correct room and days
        const room = findRoomById(eventData.roomId);
        if (!room) return;

        let updated = false;

        for (const day of eventData.daysOfEvent) {
            if (!room.content[day]) continue;

            const eventsAtDay = room.content[day].events;
            const eventIndex = eventsAtDay.findIndex((event) => event.id === eventData.id);

            if (eventIndex === -1) {
                eventsAtDay.push(eventData);
                sortArrayByStartDateTimes(eventsAtDay);
                updated = true;
            } else {
                // Only update if the event data has changed
                if (JSON.stringify(eventsAtDay[eventIndex]) !== JSON.stringify(eventData)) {
                    eventsAtDay[eventIndex] = eventData;
                    updated = true;
                }
            }
        }

        // Return whether any updates were made (useful for parent functions)
        return updated;
    }

    function addShiftsToRoomAndDay(shifts) {
        let updated = false;

        for (const shift of shifts) {
            const room = findRoomById(shift.roomId);
            if (!room) continue;

            for (const day of shift.daysOfShift) {
                if (!room.content[day]) continue;

                const shiftsAtDay = room.content[day].shifts;

                // Check if shift exists before updating
                const shiftIndex = shiftsAtDay.findIndex((existingShift) => existingShift.id === shift.id);
                const wasAdded = shiftIndex === -1;

                updateOrAddShift(shiftsAtDay, { shift });

                if (wasAdded || shiftIndex !== -1) {
                    updated = true;
                }
            }
        }

        return updated;
    }

    function updateShiftForUserOrEntity(data) {
        const { shift } = data;
        let updated = false;

        // Use for...of loops for better performance with early termination
        for (const room of newShiftPlanData.value) {
            for (const day in room.content) {
                const shiftsAtDay = room.content[day].shifts;
                const eventsAtDay = room.content[day].events;

                // Update shift in shifts array
                const shiftIndex = shiftsAtDay.findIndex((existingShift) => existingShift.id === shift.id);
                if (shiftIndex !== -1) {
                    shiftsAtDay[shiftIndex] = shift;
                    updated = true;
                }

                // Update shift in events
                for (const event of eventsAtDay) {
                    const eventShiftIndex = event.shifts.findIndex((existingShift) => existingShift.id === shift.id);
                    if (eventShiftIndex !== -1) {
                        event.shifts[eventShiftIndex] = shift;
                        updated = true;
                    }
                }
            }
        }

        // Only reload if something was actually updated
        if (updated) {
            router.reload({
                only: ['usersForShifts', 'freelancersForShifts', 'serviceProvidersForShifts'],
            });
        }
    }

    function removeShiftFromRoomAndEvents(data) {
        const { shift, roomId } = data;
        const room = findRoomById(roomId);
        if (!room) return;

        let updated = false;

        for (const day of shift.daysOfShift) {
            if (!room.content[day]) continue;

            const shiftsAtDay = room.content[day].shifts;
            const eventsAtDay = room.content[day].events;

            // Remove shift from shifts array
            const shiftIndex = shiftsAtDay.findIndex((existingShift) => existingShift.id === shift.id);
            if (shiftIndex !== -1) {
                shiftsAtDay.splice(shiftIndex, 1);
                updated = true;
            }

            // Remove shift from events
            for (const event of eventsAtDay) {
                if (event.id === shift.eventId) {
                    const eventShiftIndex = event.shifts.findIndex((existingShift) => existingShift.id === shift.id);
                    if (eventShiftIndex !== -1) {
                        event.shifts.splice(eventShiftIndex, 1);
                        updated = true;
                    }
                }
            }
        }

        // Only reload if something was actually removed
        if (updated) {
            router.reload({
                only: ['usersForShifts', 'freelancersForShifts', 'serviceProvidersForShifts'],
            });
        }
    }

    function init() {
        // Set up listeners for each room
        for (const room of newShiftPlanData.value) {
            // Shift plan room events
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
                .listen('.event.removed', (data) => {
                    // Optimize event removal with a single loop
                    let eventRemoved = false;

                    for (const currentRoom of newShiftPlanData.value) {
                        for (const day in currentRoom.content) {
                            const eventsAtDay = currentRoom.content[day].events;
                            const eventIndex = eventsAtDay.findIndex((event) => event.id === data.event.id);
                            if (eventIndex !== -1) {
                                eventsAtDay.splice(eventIndex, 1);
                                eventRemoved = true;
                            }
                        }
                    }

                    // If an event was removed, we might want to reload certain data
                    if (eventRemoved) {
                        // This is commented out because the original code didn't reload after event removal
                        // But we could add a reload here if needed
                        // router.reload({ only: ['someData'] });
                    }
                });
        }

        // Multi-shifts channel
        Echo.channel('shift-plan.multi-shifts')
            .listen('.multi-shifts-created', (data) => {
                const updated = addShiftsToRoomAndDay(data.shifts);

                // If shifts were added, we might want to reload certain data
                if (updated) {
                    router.reload({
                        only: ['usersForShifts', 'freelancersForShifts', 'serviceProvidersForShifts'],
                    });
                }
            });
    }

    return { init };
}
