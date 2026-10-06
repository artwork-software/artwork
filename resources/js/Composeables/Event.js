import dayjs from "dayjs";
import axios from "axios";
import {ref} from "vue";
import {parseYmd, toDmy} from "@/Helper/IsoWeek.js";

export function useEvent() {
    const getDaysOfEvent = (startDate, endDate) => {
            // Tage lokal rechnen: new Date('YYYY-MM-DD') wäre UTC-Mitternacht, setDate + toISOString
            // lieferte an der Sommerzeit-Umstellung den Vortag doppelt bzw. ließ den letzten Tag weg
            const toLocalDay = (value) => {
                const parsed = parseYmd(value) ?? new Date(value);
                return new Date(parsed.getFullYear(), parsed.getMonth(), parsed.getDate());
            };
            const days = [];
            const end = toLocalDay(endDate);
            for (let d = toLocalDay(startDate); d <= end; d.setDate(d.getDate() + 1)) {
                days.push(toDmy(d));
            }
            return days;
        },
        formatEventDateByDayJs = (date) => {
            return dayjs(date).format('YYYY-MM-DD');
        },
        reloadRoomsAndDaysForCalendar = async (
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
        useCalendarReload = (projectId) => {
            const showReceivesNewDataOverlay = ref(false),
                hasReceivedNewCalendarData = ref(false),
                hasReceivedNewEventsWithoutRoomData = ref(false),
                receivedRoomData = ref([]),
                receivedEventsWithoutRoom = ref([]),
                handleReload = async (
                    desiredRoomIdsToReload,
                    desiredDaysToReload,
                    reloadEventsWithoutRoom = false
                ) => {
                    showReceivesNewDataOverlay.value = true;
                    const {roomData, eventsWithoutRoom} = await reloadRoomsAndDaysForCalendar(
                        desiredRoomIdsToReload,
                        desiredDaysToReload,
                        projectId,
                        reloadEventsWithoutRoom
                    );

                    receivedRoomData.value = roomData;
                    hasReceivedNewCalendarData.value = true;

                    if (reloadEventsWithoutRoom) {
                        receivedEventsWithoutRoom.value = eventsWithoutRoom;
                        hasReceivedNewEventsWithoutRoomData.value = true;
                    }
                };

            return {
                showReceivesNewDataOverlay,
                hasReceivedNewCalendarData,
                hasReceivedNewEventsWithoutRoomData,
                receivedRoomData,
                receivedEventsWithoutRoom,
                handleReload
            };
        };

    return {
        getDaysOfEvent,
        formatEventDateByDayJs,
        useCalendarReload
    };
}
