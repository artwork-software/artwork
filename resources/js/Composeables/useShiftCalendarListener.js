import { router } from '@inertiajs/vue3';

export function useShiftCalendarListener(newShiftPlanData) {

    function findRoomById(roomId) {
        return newShiftPlanData.find((shiftPlanObject) => shiftPlanObject.roomId === roomId);
    }

    function updateOrAddShift(shiftsAtDay, data) {
        const shiftIndex = shiftsAtDay.findIndex((shift) => shift.id === data.shift.id);
        if (shiftIndex !== -1) {
            shiftsAtDay[shiftIndex] = data.shift;
        } else {
            shiftsAtDay.push(data.shift);
        }
    }

    function updateShiftInRoomAndEvents(daysOfShift, data, roomId) {
        const room = findRoomById(roomId);
        if (!room) return;

        daysOfShift.forEach((day) => {
            if (!room.content[day]) return;

            const eventsAtDay = room.content[day].events;
            const shiftsAtDay = room.content[day].shifts;

            if (data.shift.event_id) {
                const event = eventsAtDay.find((event) => event.id === data.shift.event_id);
                if (event) {
                    const shiftIndex = event.shifts.findIndex((shift) => shift.id === data.shift.id);
                    if (shiftIndex !== -1) {
                        event.shifts[shiftIndex] = data.shift;
                    } else {
                        event.shifts.push(data.shift);
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
        newShiftPlanData.forEach((room) => {
            Object.keys(room.content).forEach((day) => {
                const eventsAtDay = room.content[day].events;
                const eventIndex = eventsAtDay.findIndex((event) => event.id === eventData.id);
                if (eventIndex !== -1) {
                    eventsAtDay.splice(eventIndex, 1);
                }
            });
        });

        const room = findRoomById(eventData.room_id);
        if (!room) return;

        eventData.days_of_event.forEach((day) => {
            if (!room.content[day]) return;

            const eventsAtDay = room.content[day].events;
            const eventIndex = eventsAtDay.findIndex((event) => event.id === eventData.id);

            const newEvent = {
                id: eventData.id,
                start: eventData.earliest_start_datetime,
                end: eventData.latest_end_datetime,
                eventName: eventData.eventName,
                description: eventData.description,
                audience: eventData.audience,
                isLoud: eventData.is_loud,
                projectId: eventData.project_id,
                projectName: eventData?.project?.name,
                eventTypeId: eventData.event_type_id,
                eventTypeName: eventData.event_type.name,
                eventTypeAbbreviation: eventData.event_type.abbreviation,
                eventTypeColor: eventData.event_type.hex_code,
                created_at: eventData.created_at,
                allDay: eventData.allDay,
                shifts: eventData.shifts,
                days_of_event: eventData.days_of_event,
                formatted_dates: eventData.formatted_dates,
                timesWithoutDates: eventData.times_without_dates,
                is_series: eventData.is_series,
                sub_events: eventData.sub_events,
                timelines: eventData.timelines
            };

            if (eventIndex === -1) {
                eventsAtDay.push(newEvent);
            } else {
                eventsAtDay[eventIndex] = newEvent;
            }
        });
    }

    function addShiftsToRoomAndDay(shifts) {
        shifts.forEach((shift) => {
            const room = findRoomById(shift.room_id);
            if (!room) return;

            shift.days_of_shift.forEach((day) => {
                if (!room.content[day]) return;

                const shiftsAtDay = room.content[day].shifts;
                updateOrAddShift(shiftsAtDay, { shift });
            });
        });
    }


    function updateShiftForUserOrEntity(data) {
        const { shift } = data;

        newShiftPlanData.forEach((room) => {
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
        const { shift, room_id } = data;
        const room = findRoomById(room_id);
        if (!room) return;

        shift.days_of_shift.forEach((day) => {
            if (!room.content[day]) return;

            const shiftsAtDay = room.content[day].shifts;
            const eventsAtDay = room.content[day].events;

            const shiftIndex = shiftsAtDay.findIndex((existingShift) => existingShift.id === shift.id);
            if (shiftIndex !== -1) {
                shiftsAtDay.splice(shiftIndex, 1);
            }

            eventsAtDay.forEach((event) => {
                if (event.id === shift.event_id) {
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
        newShiftPlanData.forEach((room) => {
            Echo.private('shift-plan.room.' + room.roomId)
                .listen('.shift-created', (data) => {
                    updateShiftInRoomAndEvents(data.days_of_shift, data, data.room_id);
                })
                .listen('.shift-assign-entity', (data) => {
                    updateShiftInRoomAndEvents(data.days_of_shift, data, data.room_id);
                })
                .listen('.shift-remove-entity', (data) => {
                    updateShiftForUserOrEntity(data);
                })
                .listen('.shift-updated', (data) => {
                    updateShiftInRoomAndEvents(data.days_of_shift, data, data.room_id);
                })
                .listen('.shift-updated.in.event', (data) => {
                    updateShiftInRoomAndEvents(data.days_of_shift, data, data.room_id);
                });


            Echo.private('destroy.events.room.' + room.roomId)
                .listen('.shift-destroyed.in.event', (data) => {
                    //console.log(data);
                    removeShiftFromRoomAndEvents(data);
                });


            Echo.private('event.room.' + room.roomId)
                .listen('.event.created', (data) => {
                    addEventToRoomAndDay(data.event);
                })
                .listen('.event.removed', (data) => {
                    newShiftPlanData.forEach((room) => {
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
