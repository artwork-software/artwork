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

        const room = findRoomById(eventData.roomId);
        if (!room) return;
        eventData.days_of_event.forEach((day) => {
            if (!room.content[day]) return;

            const eventsAtDay = room.content[day].events;
            const eventIndex = eventsAtDay.findIndex((event) => event.id === eventData.id);

            const newEvent = {
                id: eventData.id,
                start: eventData.startTime,
                end: eventData.end,
                eventName: eventData.eventName,
                description: eventData.description,
                audience: eventData.audience,
                isLoud: eventData.is_loud,
                projectId: eventData.projectId,
                projectName: eventData?.projectName,
                eventTypeId: eventData.event_type_id,
                eventTypeName: eventData.eventTypeName,
                eventTypeAbbreviation: eventData.eventTypeAbbreviation,
                eventTypeColor: eventData.eventTypeColor,
                created_at: eventData.created_at,
                allDay: eventData.allDay,
                shifts: eventData.shifts,
                days_of_event: eventData.days_of_event,
                days_of_shifts: eventData.days_of_shifts,
                option_string: eventData.option_string,
                formatted_dates: eventData.formatted_dates,
                timesWithoutDates: eventData.timesWithoutDates,
                is_series: eventData.is_series,
                sub_events: eventData.sub_events,
                timelines: eventData.timelines,
                occupancy_option: eventData.occupancy_option,
                eventTypeColorBackground: eventData.event_type_color_background,
                event_type_color: eventData.event_type_color,
                start_hour: eventData.start_hour,
                event_length_in_hours: eventData.event_length_in_hours,
                hours_to_next_day: eventData.hours_to_next_day,
                minutes_form_start_hour_to_start: eventData.minutes_form_start_hour_to_start,
                roomId: eventData.roomId,
                roomName: eventData.roomName,
                eventStatusId: eventData.eventStatusId,
                eventStatusColor: eventData.eventStatusColor,
                created_by: {
                    id: eventData.created_by?.id,
                    profile_photo_url: eventData.created_by?.profile_photo_url,
                    first_name: eventData.created_by?.first_name,
                    last_name: eventData.created_by?.last_name,
                }
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
