import { existingPrivateChannel, stopListeningOnPrivateChannel } from './echoChannel.js';

const CHANNEL = 'events';
const EVENT_NAME = '.occupancy.updated';
const DEBOUNCE_MS = 1500;

/**
 * Zeiträume zweier Signale zusammenführen. null = unbekannter Zeitraum (alles Sichtbare neu laden) und
 * gewinnt immer; sonst frühester Beginn bis spätestes Ende (Y-m-d, als Text vergleichbar).
 *
 * @param {{start: string|null, end: string|null}|null|undefined} current
 * @param {{start?: string|null, end?: string|null}|null|undefined} incoming
 * @returns {{start: string, end: string}|null}
 */
export function mergeOccupancyRange(current, incoming) {
    const start = incoming?.start ?? null;
    const end = incoming?.end ?? start;
    if (current === null || !start) {
        return null;
    }
    if (current === undefined) {
        return { start, end };
    }
    return {
        start: start < current.start ? start : current.start,
        end: end > current.end ? end : current.end,
    };
}

/**
 * Liegt der Monat (Schlüssel 'YYYY-MM') im Zeitraum? Ohne Zeitraum: ja.
 *
 * @param {string} monthKey
 * @param {{start: string, end: string}|null} range
 */
export function monthKeyInRange(monthKey, range) {
    if (!range) {
        return true;
    }
    return monthKey >= range.start.slice(0, 7) && monthKey <= range.end.slice(0, 7);
}

/**
 * Sammel-Signal OccupancyUpdated (Massenlöschen, Wiederherstellen, KI-Stapel, Serien-Turnus): Einzeltermine
 * kommen über die Raumkanäle, diese Änderungen nur hierüber. Mehrere Signale kurz hintereinander werden zu
 * einem Nachladen gebündelt.
 *
 * @param {(range: {start: string, end: string}|null) => void} onChange
 */
export function useOccupancyUpdates(onChange) {
    let pending; // undefined = nichts offen, null = alles, sonst Zeitraum
    let timer = null;
    let listening = false;

    function handle(payload) {
        pending = mergeOccupancyRange(pending, payload);
        if (timer !== null) {
            return;
        }
        timer = setTimeout(() => {
            timer = null;
            const range = pending;
            pending = undefined;
            onChange(range);
        }, DEBOUNCE_MS);
    }

    function start() {
        const echo = globalThis.Echo;
        if (!echo || listening) {
            return;
        }
        echo.private(CHANNEL).listen(EVENT_NAME, handle);
        listening = true;
    }

    /** Nur den eigenen Handler abmelden: der Kanal kann von weiteren Ansichten geteilt sein. */
    function stop() {
        if (timer !== null) {
            clearTimeout(timer);
            timer = null;
        }
        pending = undefined;
        if (listening && existingPrivateChannel(CHANNEL)) {
            stopListeningOnPrivateChannel(CHANNEL, EVENT_NAME, handle);
        }
        listening = false;
    }

    return { start, stop, handle };
}
