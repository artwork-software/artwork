// Composable: useBulkEventsBroadcastUpdater.js
// Hört auf Broadcasts und aktualisiert die Events-Liste automatisch
import { onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

// eventsRef: Vue ref auf die Events-Liste (eventsRef.value = array)
// options: optionales Objekt { onEvent, onError }
// Hört auf den Public Channel bulk.events und verarbeitet die Aktionen
// payload: { event, action: 'created'|'updated'|'deleted' }
export function useBulkEventsBroadcastUpdater(eventsRef, options = {}) {
    let channel = null;
    const echo = window.Echo;

    function getUserBulkSortId() {
        try {
            const page = usePage();
            return parseInt(page.props.auth?.user?.bulk_sort_id ?? 0, 10) || 0;
        } catch (e) {
            return 0;
        }
    }

    function compareByRoomName(a, b) {
        const an = (a.room?.name || a.roomName || '').toString();
        const bn = (b.room?.name || b.roomName || '').toString();
        return an.localeCompare(bn);
    }

    function compareByEventTypeName(a, b) {
        const an = (a.type?.name || a.eventTypeName || '').toString();
        const bn = (b.type?.name || b.eventTypeName || '').toString();
        return an.localeCompare(bn);
    }

    function compareByStartTime(a, b) {
        const dayA = a.day || a.date || '';
        const dayB = b.day || b.date || '';
        if (dayA === dayB) {
            const as = a.start_time || a.startTime || '';
            const bs = b.start_time || b.startTime || '';
            if (as === bs) {
                const ar = a.room?.position ?? a.roomPosition ?? 0;
                const br = b.room?.position ?? b.roomPosition ?? 0;
                return (ar || 0) - (br || 0);
            }
            return (as || '').localeCompare(bs || '');
        }
        return (dayA || '').localeCompare(dayB || '');
    }

    function sortEventsArray(arr) {
        const sortId = getUserBulkSortId();
        if (sortId === 1) {
            arr.sort((a, b) => compareByRoomName(a, b));
        } else if (sortId === 2) {
            arr.sort((a, b) => compareByEventTypeName(a, b));
        } else if (sortId === 3) {
            arr.sort((a, b) => compareByStartTime(a, b));
        }
        // for other sort modes, leave server order
    }

    function handleBroadcast(payload) {
        const { event, action } = payload || {};
        if (!event || !event.id || !action) return;
        const idx = eventsRef.value.findIndex(e => e.id === event.id);

        console.log(event);

        if (action === 'created' || action === 'updated') {
            if (idx !== -1) {
                // merge existing with incoming fields but do NOT overwrite start/end with empty/undefined
                const existing = eventsRef.value[idx];
                const merged = { ...existing };
                Object.keys(event).forEach((k) => {
                    // preserve existing times when incoming value is empty string or undefined
                    if ((k === 'start_time' || k === 'end_time') && (event[k] === '' || typeof event[k] === 'undefined')) {
                        return;
                    }
                    // also avoid overwriting room object completely if incoming room is null/undefined
                    if (k === 'room' && (event[k] === null || typeof event[k] === 'undefined')) {
                        return;
                    }
                    merged[k] = event[k];
                });
                eventsRef.value[idx] = merged;
            } else {
                // push new event
                eventsRef.value.push(event);
            }
            // Nach Änderungen die Sortierung gemäß ProjectController (bulk_sort_id) wiederherstellen
            const copy = [...eventsRef.value];
            sortEventsArray(copy);
            eventsRef.value.splice(0, eventsRef.value.length, ...copy);
        } else if (action === 'deleted') {
            if (idx !== -1) {
                eventsRef.value.splice(idx, 1);
            }
        }

        if (options.onEvent) options.onEvent(event, action);
    }

    onMounted(() => {
        if (!echo) {
            if (options.onError) options.onError('Echo not found');
            return;
        }
        channel = echo.channel('bulk.events')
            .listen('.bulk.event.changed', handleBroadcast);
    });

    onUnmounted(() => {
        if (echo && channel) {
            echo.leave('bulk.events');
        }
    });
}
