/**
 * Vorgaben für Tagegeld und Frühstücksabzug eines Künstler*innenaufenthalts: Werte der Unterkunft gehen vor,
 * sonst die allgemeinen Vorgaben aus den Künstler*innenaufenthalt-Einstellungen, sonst 0.
 */
export function accommodationResidencyDefaults(accommodation, generalDefaults = {}) {
    return {
        daily_allowance: accommodation?.daily_allowance ?? generalDefaults.dailyAllowance ?? 0,
        breakfast_deduction_per_day: accommodation?.breakfast_deduction_per_day
            ?? generalDefaults.breakfastDeduction
            ?? 0,
    }
}
