export function useEvents() {
    const getDaysOfEvent = (startDate, endDate) => {
            let days = [];
            let start = new Date(startDate);
            let end = new Date(endDate);
            for (let d = start; d <= end; d.setDate(d.getDate() + 1)) {
                let dayParts = new Date(d).toISOString().slice(0, 10).split('-');
                days.push(dayParts[2] + '.' + dayParts[1] + '.' + dayParts[0]);
            }
            return days;
        },
        reloadRoomsAndDays = async (desiredRoomIdsToReload, desiredDaysToReload, desiredProjectId) => {
            let roomMap = new Map();

            for (const desiredRoomId of desiredRoomIdsToReload) {
                for (const desiredDay of desiredDaysToReload) {
                    await axios.get(
                        route(
                            'events.events-for-date-and-room',
                            {
                                room: desiredRoomId,
                                date: desiredDay,
                                projectId: desiredProjectId
                            }
                        )
                    ).then((response) => {
                        if (!roomMap.has(desiredRoomId)) {
                            roomMap.set(desiredRoomId, ((new Map()).set(desiredDay, response.data)));
                        }

                        roomMap.get(desiredRoomId).set(desiredDay, response.data);
                    });
                }
            }

            return roomMap;
        }
    ;

    return {
        getDaysOfEvent,
        reloadRoomsAndDays
    };
}
