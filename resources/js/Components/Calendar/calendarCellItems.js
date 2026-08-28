// Gemeinsame Zell-Logik für BaseCalendar (Monatsansicht, At-a-Glance) und
// CalendarDayRow: Termine + eigenständige Schichten einer Tag×Raum-Zelle
// gemischt nach effektiver Startzeit sortieren.

const toGermanDate = (iso) => {
    if (!iso || iso.length < 10) return iso;
    const [y, m, d] = iso.split("-");
    return `${d}.${m}.${y}`;
};

export const dayKey = (day) => day.fullDay ?? toGermanDate(day.withoutFormat);

// "dd.mm.yyyy" → "yyyy-mm-dd"
export const deKeyToIso = (deKey) => {
    const parts = String(deKey ?? '').split('.');
    return parts.length === 3 ? `${parts[2]}-${parts[1]}-${parts[0]}` : '';
};

export const eventsInCell = (day, room) =>
    (room.content?.[dayKey(day)]?.events ?? []);

export const shiftsInCell = (day, room) =>
    (room.content?.[dayKey(day)]?.shifts ?? []);

// Effektive Startzeit ("HH:MM") eines Items an diesem Tag: beginnt es an einem
// Vortag, zählt es ab 00:00 — so mischen sich Termine und Schichten korrekt.
export const itemStartTimeOnDay = (item, dayIso) => {
    if (item.type === 'shift') {
        const shift = item.data;
        if (shift.startDate && shift.startDate < dayIso) return '00:00';
        const time = String(shift.start ?? '');
        const match = time.match(/(\d{2}:\d{2})/);
        return match ? match[1] : '00:00';
    }
    const start = String(item.data.start ?? ''); // "Y-m-d H:i"
    const datePart = start.slice(0, 10);
    if (datePart && datePart < dayIso) return '00:00';
    const match = start.match(/(\d{2}:\d{2})$/) ?? start.match(/\s(\d{2}:\d{2})/);
    return match ? match[1] : '00:00';
};

// Termine + eigenständige Schichten einer Zelle, gemischt nach Startzeit sortiert
export const itemsInCell = (day, room) => {
    const events = eventsInCell(day, room).map((evt) => ({ type: 'event', data: evt }));
    const shifts = shiftsInCell(day, room).map((shift) => ({ type: 'shift', data: shift }));
    if (shifts.length === 0) return events;
    const dayIso = day.withoutFormat ?? deKeyToIso(dayKey(day));
    return [...events, ...shifts].sort((a, b) =>
        itemStartTimeOnDay(a, dayIso).localeCompare(itemStartTimeOnDay(b, dayIso))
    );
};
