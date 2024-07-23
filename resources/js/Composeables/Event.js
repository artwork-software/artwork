import dayjs from "dayjs";
import axios from "axios";

export function useEvent() {
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
        reloadRoomsAndDays = async (
            desiredRoomIdsToReload,
            desiredDaysToReload,
            desiredProjectId,
            reloadEventsWithoutRoom
        ) => {
            let roomData = null;
            let eventsWithoutRoom = null;

            await axios.get(
                route('events.for-rooms-by-days-and-project'),
                {
                    params: {
                        rooms: desiredRoomIdsToReload,
                        days: desiredDaysToReload,
                        projectId: desiredProjectId,
                        reloadEventsWithoutRoom: reloadEventsWithoutRoom
                    }
                }
            ).then((response) => {
                roomData = response.data.roomData;
                eventsWithoutRoom = response.data.eventsWithoutRoom;
            });

            return {roomData, eventsWithoutRoom};
        },
        formatEventDateByDayJs = (date) => {
            return dayjs(date).format('YYYY-MM-DD');
        };

    return {
        getDaysOfEvent,
        reloadRoomsAndDays,
        formatEventDateByDayJs
    };
}
