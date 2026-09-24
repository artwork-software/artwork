import { getDaysInRange } from '@/Composeables/calendarDateUtils.js'

// subscribeShiftChannels: false → nur Termin-Kanäle. Die Schicht-Kanäle sind in routes/channels.php auf
// Dienstplan-Sichtrecht beschränkt; ein Abonnement ohne Recht erzeugt nur 403-Konsolenfehler.
// projectFilterId: Getter für die Projekt-ID, auf die die Ansicht eingeschränkt ist (Projekt-Schichten-Tab
// ohne „Schichten anderer Projekte anzeigen") — null = keine Einschränkung. Spiegelt den Server-Filter,
// sonst tauchen live Schichten fremder Projekte auf bzw. bleiben nach einem Projektwechsel stehen.
export function useShiftCalendarListener(newShiftPlanData, { onWorkersNeedReload, onWorkerNeedReload, onEventsChanged, onShiftDataChanged, onLookupsReceived, subscribeShiftChannels = true, projectFilterId = null } = {}) {

    function isShiftVisibleInView(shift) {
        const projectId = typeof projectFilterId === 'function' ? projectFilterId() : projectFilterId;
        if (projectId === null || projectId === undefined) return true;
        return Number(shift.projectId ?? shift.project_id) === Number(projectId);
    }

    function findLoadedShift(shiftId) {
        for (const room of newShiftPlanData.value || []) {
            const shift = room.shiftsById?.[shiftId];
            if (shift) return shift;
        }
        return null;
    }

    function workerKeys(shift) {
        return (shift?.workers ?? []).map((w) => `${w.type}:${w.id}`);
    }

    /**
     * Personenzeilen der Wochenansicht nachladen, wenn sich an der Schicht etwas geändert hat, das dort
     * sichtbar ist (Zeit, Datum, Gewerk, Besetzung). Beim Gewerkwechsel entfernt der Server alle
     * Personen ohne eigenen Broadcast — daher alte UND neue Besetzung nachladen.
     */
    function reloadWorkersAffectedByShiftChange(previousShift, shift) {
        if (!onWorkerNeedReload && !onWorkersNeedReload) return;

        const relevantFields = ['startDate', 'endDate', 'start', 'end', 'break_minutes', 'craftId'];
        const fieldsChanged = !previousShift
            || relevantFields.some((field) => previousShift[field] !== shift[field]);
        const previousKeys = workerKeys(previousShift);
        const nextKeys = workerKeys(shift);
        const workersChanged = previousKeys.length !== nextKeys.length
            || previousKeys.some((key) => !nextKeys.includes(key));

        if (!fieldsChanged && !workersChanged) return;

        const workers = [...(previousShift?.workers ?? []), ...(shift.workers ?? [])];
        if (workers.length === 0) return;

        if (!onWorkerNeedReload) {
            onWorkersNeedReload();
            return;
        }
        const seen = new Set();
        for (const worker of workers) {
            const key = `${worker.type}:${worker.id}`;
            if (seen.has(key)) continue;
            seen.add(key);
            onWorkerNeedReload(worker.id, resolveWorkerType(worker.type));
        }
    }

    function resolveWorkerType(entityType) {
        const map = { 0: 'user', 1: 'freelancer', 2: 'serviceProvider', 'service_provider': 'serviceProvider' }
        return map[entityType] ?? entityType
    }

    function getShiftDays(shift) {
        return shift.daysOfShift || getDaysInRange(shift.startDate, shift.endDate)
    }

    function getEventDays(event) {
        return event.daysOfEvent || getDaysInRange(event.start, event.end)
    }

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

    function bumpRoomVersion(room) {
        room.__v = (room.__v ?? 0) + 1;
    }

    /**
     * Entfernt eine eigenständige Schicht aus allen Räumen/Tagen, an denen sie laut neuem Stand
     * nicht mehr liegt. Ohne das bliebe sie nach einem Raum- oder Datumswechsel als Geist an der
     * alten Stelle stehen (der Broadcast kommt nur mit dem neuen Raum/Datum).
     */
    function removeShiftFromStalePositions(shift, targetRoom) {
        const targetDays = new Set(targetRoom ? getShiftDays(shift) : []);
        // Ohne auflösbares Datum (Altdaten) die Tage im Zielraum nicht anfassen
        const keepTargetDays = targetRoom && targetDays.size === 0;
        let updated = false;

        for (const room of newShiftPlanData.value) {
            const isTarget = room === targetRoom;
            let roomTouched = false;

            if (!isTarget && room.shiftsById && room.shiftsById[shift.id] !== undefined) {
                delete room.shiftsById[shift.id];
                roomTouched = true;
            }

            if (isTarget && keepTargetDays) continue;

            for (const day in room.content || {}) {
                if (isTarget && targetDays.has(day)) continue;
                const ids = room.content[day]?.shiftIds;
                if (!Array.isArray(ids)) continue;
                const i = ids.indexOf(shift.id);
                if (i !== -1) {
                    ids.splice(i, 1);
                    roomTouched = true;
                }
            }

            if (roomTouched) {
                bumpRoomVersion(room);
                updated = true;
            }
        }

        return updated;
    }

    function updateShiftInRoomAndEvents(data, roomId, { reloadWorkers = false } = {}) {
        // Projekt-/Gewerk-/Gruppen-Lookups zuerst einmischen: Nach einem Projektwechsel kennt der
        // Client das neue Projekt sonst nicht (nicht im Initial-Load) und zeigt die Schicht bis zum
        // Neuladen als "ohne Projekt".
        if (data.lookups && onLookupsReceived) {
            onLookupsReceived(data.lookups);
        }

        // Nur event-LOSE Schichten ins Raum-Raster upserten: Der Server-Load filtert
        // whereNull(event_id) — eine per Broadcast eingefügte Event-Schicht würde nach
        // dem nächsten Reload wieder verschwinden (Live-Ansicht ≠ Server-Wahrheit).
        const isStandaloneShift = !(data.shift.eventId ?? data.shift.event_id);

        if (reloadWorkers) {
            reloadWorkersAffectedByShiftChange(findLoadedShift(data.shift.id), data.shift);
        }

        // Gehört die Schicht (nicht mehr) in diese Ansicht, nur alte Positionen räumen
        if (isStandaloneShift && !isShiftVisibleInView(data.shift)) {
            if (removeShiftFromStalePositions(data.shift, null) && onShiftDataChanged) onShiftDataChanged();
            return;
        }

        // Zielraum = aktueller Raum der Schicht (nach Raumwechsel ≠ Kanal, auf dem der
        // Broadcast für den alten Raum ankommt)
        const room = findRoomById(data.shift.roomId ?? roomId);

        let updated = false;

        // Raum-/Datumswechsel: alte Positionen räumen — auch wenn der neue Raum in dieser
        // Ansicht gar nicht geladen ist (Raumfilter), sonst bleibt die Schicht im alten Raum stehen.
        if (isStandaloneShift && removeShiftFromStalePositions(data.shift, room ?? null)) {
            updated = true;
        }

        if (!room) {
            if (updated && onShiftDataChanged) onShiftDataChanged();
            return;
        }

        if (room.shiftsById && isStandaloneShift) {
            // Always upsert into shiftsById (handles both new and existing standalone shifts)
            room.shiftsById[data.shift.id] = data.shift;
            updated = true;

            // Ensure the shift is registered in content[day].shiftIds for each day
            for (const day of getShiftDays(data.shift)) {
                if (!room.content || !room.content[day]) continue;
                if (!room.content[day].shiftIds) room.content[day].shiftIds = [];
                if (!room.content[day].shiftIds.includes(data.shift.id)) {
                    room.content[day].shiftIds.push(data.shift.id);
                }
            }
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

        if (updated) {
            bumpRoomVersion(room);
            if (onShiftDataChanged) onShiftDataChanged();
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
                // Der ShiftPlan cached Zell-Inhalte über room.__v — ohne Bump
                // bleibt der alte Stand sichtbar (Raumwechsel: alter Raum).
                bumpRoomVersion(room);
            }
        }

        const room = findRoomById(eventData.roomId);
        if (!room) return false;

        if (!room.eventsById) room.eventsById = {};
        room.eventsById[eventData.id] = eventData;

        for (const day of getEventDays(eventData)) {
            if (!room.content[day]) continue;
            if (!room.content[day].eventIds) room.content[day].eventIds = [];
            if (!room.content[day].eventIds.includes(eventData.id)) {
                room.content[day].eventIds.push(eventData.id);
                sortEventIdsByStart(room, day);
            }
        }
        bumpRoomVersion(room);
        if (onEventsChanged) onEventsChanged();
        return true;
    }

    function sortEventIdsByStart(room, day) {
        const ids = room.content?.[day]?.eventIds;
        if (!Array.isArray(ids) || !room.eventsById) return;
        ids.sort((a, b) => {
            const ea = room.eventsById[a];
            const eb = room.eventsById[b];
            if (!ea?.start || !eb?.start) return 0;
            return new Date(ea.start.replace(' ', 'T')) - new Date(eb.start.replace(' ', 'T'));
        });
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
        const daysOfEvent = getEventDays(eventData);
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
            sortArrayByStartDateTimes(room.content[dayKey].events);
        }

        if (onEventsChanged) onEventsChanged();
        return true;
    }

    function addShiftsToRoomAndDay(shifts) {
        let updated = false;

        for (const shift of shifts) {
            if (!isShiftVisibleInView(shift)) continue;
            const room = findRoomById(shift.roomId);
            if (!room) continue;

            if (!room.shiftsById) room.shiftsById = {};
            room.shiftsById[shift.id] = shift;
            let roomUpdated = false;

            for (const day of getShiftDays(shift)) {
                if (!room.content[day]) continue;
                if (!room.content[day].shiftIds) room.content[day].shiftIds = [];
                if (!room.content[day].shiftIds.includes(shift.id)) {
                    room.content[day].shiftIds.push(shift.id);
                    roomUpdated = true;
                }
            }

            if (roomUpdated) {
                bumpRoomVersion(room);
                updated = true;
            }
        }
        if (updated && onShiftDataChanged) onShiftDataChanged();
        return updated;
    }

    function updateShiftForUserOrEntity(data) {
        const { shift } = data;
        let updated = false;

        for (const room of newShiftPlanData.value) {
            let roomUpdated = false;
            if (room.shiftsById && room.shiftsById[shift.id] !== undefined) {
                room.shiftsById[shift.id] = shift;
                roomUpdated = true;
            }
            if (room.eventsById) {
                for (const eventId of Object.keys(room.eventsById)) {
                    const event = room.eventsById[eventId];
                    if (Array.isArray(event.shifts)) {
                        const idx = event.shifts.findIndex((s) => s.id === shift.id);
                        if (idx !== -1) {
                            event.shifts[idx] = shift;
                            roomUpdated = true;
                        }
                    }
                }
            }
            if (roomUpdated) {
                bumpRoomVersion(room);
                updated = true;
            }
        }

        if (updated) {
            if (onShiftDataChanged) onShiftDataChanged();
            if (onWorkersNeedReload) onWorkersNeedReload();
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

        for (const day of getShiftDays(shift)) {
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

        if (updated) {
            bumpRoomVersion(room);
            if (onShiftDataChanged) onShiftDataChanged();
            if (onWorkerNeedReload && Array.isArray(shift.workers) && shift.workers.length > 0) {
                const seen = new Set();
                for (const w of shift.workers) {
                    const key = `${w.type}:${w.id}`;
                    if (seen.has(key)) continue;
                    seen.add(key);
                    onWorkerNeedReload(w.id, resolveWorkerType(w.type));
                }
            } else if (onWorkersNeedReload) {
                onWorkersNeedReload();
            }
        }
    }

    // Registrierte Handler — für dispose(). Echo.private() liefert pro Kanal dasselbe Objekt; ohne
    // stopListening hängt jeder Remount (Filterwechsel mit preserveState:false) einen weiteren Handler
    // mit veralteter Closure an. Bewusst kein Echo.leave(): das träfe andere Abonnenten desselben Kanals.
    const registeredHandlers = [];
    let disposed = false;

    function listen(channelName, eventName, handler) {
        Echo.private(channelName).listen(eventName, handler);
        registeredHandlers.push({ channelName, eventName, handler });
    }

    function onWorkerEntityChanged(data) {
        updateShiftInRoomAndEvents(data, data.roomId);
        if (onWorkerNeedReload && data.entity && data.entityType !== undefined) {
            onWorkerNeedReload(data.entity, resolveWorkerType(data.entityType));
        } else if (onWorkersNeedReload) {
            onWorkersNeedReload();
        }
    }

    function onShiftChanged(data) {
        updateShiftInRoomAndEvents(data, data.roomId, { reloadWorkers: true });
    }

    function onEventRemoved(data) {
        for (const currentRoom of newShiftPlanData.value) {
            let roomTouched = false;
            // ShiftPlan structure
            if (currentRoom.eventsById && currentRoom.eventsById[data.event.id]) {
                delete currentRoom.eventsById[data.event.id];
                roomTouched = true;
            }
            for (const day in currentRoom.content || {}) {
                // ShiftPlan structure: eventIds
                const ids = currentRoom.content[day].eventIds;
                if (Array.isArray(ids)) {
                    const i = ids.indexOf(data.event.id);
                    if (i !== -1) {
                        ids.splice(i, 1);
                        roomTouched = true;
                    }
                }
                // BaseCalendar structure: events array
                const events = currentRoom.content[day].events;
                if (Array.isArray(events)) {
                    const idx = events.findIndex(e => e.id === data.event.id);
                    if (idx !== -1) events.splice(idx, 1);
                }
            }
            // room.__v ist der Cache-Key der ShiftPlan-Zellen — ohne Bump
            // bleibt der gelöschte Termin dort sichtbar.
            if (roomTouched) bumpRoomVersion(currentRoom);
        }
        if (onEventsChanged) onEventsChanged();
    }

    function onMultiShiftsCreated(data) {
        // Merge project/craft/group lookups first so newly assigned projects
        // render immediately instead of only after a full reload.
        if (data.lookups && onLookupsReceived) {
            onLookupsReceived(data.lookups);
        }

        const updated = addShiftsToRoomAndDay(data.shifts);

        // If shifts were added, we might want to reload certain data
        if (updated && onWorkersNeedReload) {
            onWorkersNeedReload();
        }
    }

    function onWorkerRowChanged(data) {
        if (onWorkerNeedReload) {
            onWorkerNeedReload(data.workerId, resolveWorkerType(data.workerType));
        } else if (onWorkersNeedReload) {
            onWorkersNeedReload();
        }
    }

    function init() {
        if (disposed) return;
        // Check if newShiftPlanData.value is iterable before attempting to iterate
        if (!newShiftPlanData.value || !Array.isArray(newShiftPlanData.value)) {
            console.warn('useShiftCalendarListener: newShiftPlanData.value is not an array or is null/undefined:', newShiftPlanData.value);
            return;
        }

        // Set up listeners for each room
        for (const room of newShiftPlanData.value) {
            if (subscribeShiftChannels) {
                // Shift plan room events
                const shiftChannel = 'shift-plan.room.' + room.roomId;
                listen(shiftChannel, '.shift-created', onShiftChanged);
                listen(shiftChannel, '.shift-assign-entity', onWorkerEntityChanged);
                listen(shiftChannel, '.shift-remove-entity', onWorkerEntityChanged);
                listen(shiftChannel, '.shift-updated', onShiftChanged);
                listen(shiftChannel, '.shift-updated.in.event', onShiftChanged);

                // Destroy events room
                listen('destroy.events.room.' + room.roomId, '.shift-destroyed.in.event', removeShiftFromRoomAndEvents);
            }

            // Event room
            const eventChannel = 'event.room.' + room.roomId;
            listen(eventChannel, '.event.created', (data) => addEventToRoomAndDay(data.event));
            listen(eventChannel, '.event.updated', (data) => addEventToRoomAndDay(data.event));
            listen(eventChannel, '.event.removed', onEventRemoved);
        }

        // Multi-shifts channel
        if (subscribeShiftChannels) {
            listen('shift-plan.multi-shifts', '.multi-shifts-created', onMultiShiftsCreated);
        }

        // Individual times channel
        listen('shift-plan.individual-times', '.individual-time.changed', onWorkerRowChanged);

        // Verfügbarkeit/Abwesenheit einer Person geändert (Verfügbarkeitskalender, Tagesstatus,
        // Multi-Edit) → Personenzeile nachladen, damit Beschriftung und Konflikt-Ring aktuell sind.
        listen('shift-plan.worker-availability', '.worker-availability.changed', onWorkerRowChanged);
    }

    /** Alle von dieser Instanz registrierten Handler abmelden (onBeforeUnmount der Ansicht). */
    function dispose() {
        disposed = true;
        for (const { channelName, eventName, handler } of registeredHandlers.splice(0)) {
            Echo.private(channelName).stopListening(eventName, handler);
        }
    }

    /**
     * Antwort des eigenen Speicherns sofort anwenden (gleiche Form wie der Broadcast) — die Ansicht
     * aktualisiert sich so auch, wenn der Broadcast verspätet kommt oder ausbleibt. Idempotent zum
     * späteren Broadcast.
     */
    function applyShiftUpdate(data) {
        if (!data?.shift) return;
        updateShiftInRoomAndEvents(data, data.roomId ?? data.shift.roomId, { reloadWorkers: true });
    }

    return { init, dispose, applyShiftUpdate };
}
