import dayjs from "dayjs";
import axios from "axios";
import {ref} from "vue";

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
        },
        reloadRoomsAndDaysAndWorkersForShiftPlanWithoutWorkers = async(
            desiredRoomIdsToReload,
            desiredDaysToReload,
        ) => {
            let roomData = null;

            await axios.get(
                route('shifts.events.for-rooms-by-days-and-project-no-workers'),
                {
                    params: {
                        rooms: desiredRoomIdsToReload,
                        days: desiredDaysToReload,
                    }
                }
            ).then((response) => {
                roomData = response.data.roomData;
            });

            return {roomData};
        },
        reloadRoomsAndDaysAndWorkersForShiftPlan = async (
            desiredRoomIdsToReload,
            desiredDaysToReload,
            desiredWorkersToReload,
        ) => {
            let roomData = null;
            let workerData = null;

            await axios.get(
                route('shifts.events.for-rooms-by-days-and-project'),
                {
                    params: {
                        rooms: desiredRoomIdsToReload,
                        days: desiredDaysToReload,
                        workers: desiredWorkersToReload
                    }
                }
            ).then((response) => {
                roomData = response.data.roomData;
                workerData = response.data.workerData;
            });

            return {roomData, workerData};
        },
        useShiftPlanReload = () => {
            const showReceivesNewDataOverlay = ref(false),
                hasReceivedNewShiftPlanData = ref(false),
                hasReceivedNewShiftPlanWorkerData = ref(false),
                receivedRoomData = ref([]),
                receivedWorkerData = ref([]),
                handleReload = async (
                    desiredRoomIdsToReload,
                    desiredDaysToReload,
                    desiredWorkersToReload
                ) => {
                    showReceivesNewDataOverlay.value = true;
                    if (desiredWorkersToReload.length === 0) {
                        const  {roomData} = await reloadRoomsAndDaysAndWorkersForShiftPlanWithoutWorkers(
                            desiredRoomIdsToReload,
                            desiredDaysToReload
                        );
                        receivedRoomData.value = roomData;
                        hasReceivedNewShiftPlanData.value = true;
                        return;
                    }
                    const {roomData, workerData} = await reloadRoomsAndDaysAndWorkersForShiftPlan(
                        desiredRoomIdsToReload,
                        desiredDaysToReload,
                        desiredWorkersToReload
                    );

                    receivedRoomData.value = roomData;
                    receivedWorkerData.value = workerData;
                    hasReceivedNewShiftPlanData.value = true;
                    hasReceivedNewShiftPlanWorkerData.value = true;
                };

            return {
                showReceivesNewDataOverlay,
                hasReceivedNewShiftPlanData,
                hasReceivedNewShiftPlanWorkerData,
                receivedRoomData,
                receivedWorkerData,
                handleReload
            };
        };

    return {
        getDaysOfEvent,
        formatEventDateByDayJs,
        useCalendarReload,
        useShiftPlanReload
    };
}
