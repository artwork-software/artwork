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
