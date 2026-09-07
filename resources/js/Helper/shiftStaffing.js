/**
 * Besetzungs-Aggregation für die Gewerks-Kopfzeile der Personenleiste im
 * Dienstplan (Wochengrid): „besetzt/Bedarf" je Gewerk und Tag sowie je KW.
 *
 * Reine Funktionen ohne Vue-Abhängigkeit, damit sie per
 * `node --test tests/Frontend/shiftStaffing.test.js` testbar sind.
 *
 * Bedarf   = Summe shifts_qualifications.value (null/0 zählt nicht)
 * Besetzt  = reguläre Zuweisungen auf geforderte Funktionen — überbuchte
 *            Zuweisungen (pivot.is_overbooked) zählen nicht, wie
 *            computedShiftsQualificationsWithWorkerCount in ShiftDropElement.
 */

/** @typedef {{ required: number, staffed: number }} StaffingCount */

export function shiftCraftId(shift) {
    const id = shift?.craft?.id ?? shift?.craftId ?? shift?.craft_id ?? null
    return id == null ? null : Number(id)
}

function toArray(value) {
    if (Array.isArray(value)) return value
    if (value && typeof value === 'object') return Object.values(value)
    return []
}

/** @returns {StaffingCount} */
export function countShiftStaffing(shift) {
    const qualifications = toArray(shift?.shifts_qualifications)
    const workers = toArray(shift?.workers)

    let required = 0
    let staffed = 0

    for (const qualification of qualifications) {
        const value = Number(qualification?.value ?? 0)
        if (!Number.isFinite(value) || value <= 0) continue
        required += value

        const qualificationId = Number(qualification?.shift_qualification_id)
        for (const worker of workers) {
            const pivot = worker?.pivot ?? {}
            if (Number(pivot.shift_qualification_id) !== qualificationId) continue
            if (pivot.is_overbooked) continue
            staffed += 1
        }
    }

    return { required, staffed }
}

export function staffingKey(craftId, dateKey) {
    return `${craftId}|${dateKey}`
}

export function weekStaffingKey(craftId, weekNumber) {
    return `${craftId}|kw${weekNumber}`
}

/**
 * Aggregiert einmalig über alle Tag-Zellen.
 *
 * @param {Iterable<{ dateKey: string, weekNumber: number|string|undefined, shifts: any[] }>} entries
 *        eine Zeile je Raum×Tag (die Aufrufer-Seite liefert die Schichten der Zelle)
 * @returns {Map<string, StaffingCount>} keyed nach staffingKey()/weekStaffingKey()
 */
export function aggregateStaffing(entries) {
    const result = new Map()

    const add = (key, count) => {
        const existing = result.get(key)
        if (existing) {
            existing.required += count.required
            existing.staffed += count.staffed
        } else {
            result.set(key, { required: count.required, staffed: count.staffed })
        }
    }

    for (const entry of entries) {
        for (const shift of toArray(entry?.shifts)) {
            const craftId = shiftCraftId(shift)
            if (craftId == null) continue

            const count = countShiftStaffing(shift)
            if (count.required === 0 && count.staffed === 0) continue

            add(staffingKey(craftId, entry.dateKey), count)
            if (entry.weekNumber != null) {
                add(weekStaffingKey(craftId, entry.weekNumber), count)
            }
        }
    }

    return result
}

/**
 * Summiert Teilergebnisse (z. B. je Raum) zu einer Gesamt-Map. Die Summierung über
 * (Räume × Gewerke × Tage) Einträge ist billig gegenüber der Zell-Iteration in aggregateStaffing.
 *
 * @param {Iterable<Map<string, StaffingCount>|null|undefined>} partials
 * @returns {Map<string, StaffingCount>}
 */
export function mergeStaffing(partials) {
    const result = new Map()
    for (const partial of partials) {
        if (!partial) continue
        for (const [key, count] of partial) {
            const existing = result.get(key)
            if (existing) {
                existing.required += count.required
                existing.staffed += count.staffed
            } else {
                result.set(key, { required: count.required, staffed: count.staffed })
            }
        }
    }
    return result
}

/**
 * Memo je Raum: das Teilergebnis eines Raums wird am Raum-Objekt (WeakMap) unter seiner Version
 * (room.__v aus bumpRoomVersion) gehalten und nur neu berechnet, wenn sich die Version ändert —
 * ein Broadcast für EINEN Raum aggregiert damit nur diesen Raum neu. clear() verwirft alles
 * (Zeitraum-/Zoomwechsel: die Tagesspalten ändern sich); neue Raum-Objekte (Neuladen des Plans)
 * treffen von selbst keinen alten Eintrag.
 */
export function createRoomStaffingMemo() {
    let cache = new WeakMap() // room -> { version, result }
    let computeCalls = 0
    return {
        /**
         * @template T
         * @param {object} room
         * @param {number|string} version
         * @param {() => T} compute
         * @returns {T}
         */
        get(room, version, compute) {
            const hit = cache.get(room)
            if (hit && hit.version === version) return hit.result
            computeCalls += 1
            const result = compute()
            cache.set(room, { version, result })
            return result
        },
        clear() {
            cache = new WeakMap()
        },
        /** Zähler der tatsächlichen Neuberechnungen (Tests/Diagnose) */
        get computeCalls() {
            return computeCalls
        },
    }
}

/**
 * Gesamtaggregation über alle Räume mit Memo je Raum (siehe createRoomStaffingMemo).
 *
 * @param {Iterable<{ room: object, version: number|string, entries: () => Iterable<{ dateKey: string, weekNumber: any, shifts: any[] }> }>} rooms
 * @param {ReturnType<typeof createRoomStaffingMemo>} memo
 * @returns {Map<string, StaffingCount>}
 */
export function aggregateStaffingByRoom(rooms, memo) {
    const partials = []
    for (const { room, version, entries } of rooms) {
        partials.push(memo.get(room, version, () => aggregateStaffing(entries())))
    }
    return mergeStaffing(partials)
}

/**
 * Ampel: 'none' (kein Bedarf), 'low' (< 50 %), 'partial' (< 100 %), 'full' (voll oder mehr)
 * @param {StaffingCount|null|undefined} count
 */
export function staffingLevel(count) {
    const required = Number(count?.required ?? 0)
    const staffed = Number(count?.staffed ?? 0)
    if (required <= 0) return 'none'
    if (staffed >= required) return 'full'
    if (staffed * 2 < required) return 'low'
    return 'partial'
}

/** Offene Plätze (nie negativ) */
export function staffingOpen(count) {
    const required = Number(count?.required ?? 0)
    const staffed = Number(count?.staffed ?? 0)
    return Math.max(0, required - staffed)
}
