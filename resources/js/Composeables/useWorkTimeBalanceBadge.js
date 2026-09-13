import {computed, getCurrentInstance} from 'vue'

/**
 * AZK-Badge in den Schichtplan-Kacheln (DragElement/HighlightUserCell/MultiEditUserCell):
 * Farbe aus den Rohminuten (Fallback: Textformat "+10:30 h" / "−2:00 h" / "1h 30m"),
 * Tooltip "Stand: Nachtbuchung bis {gestern}".
 */
export function useWorkTimeBalanceBadge(props, options = {}) {
    const $t = getCurrentInstance()?.proxy?.$t ?? ((s) => s)

    // themed: Farben aus der Farbwelt des Schichtplan-Personenbereichs (--uo-*, hell/dunkel),
    // sonst die Basistoken (helle Modale, z. B. Ersatz suchen)
    const classes = options.themed
        ? {plus: 'text-[var(--uo-success-text)]', minus: 'text-[var(--uo-danger-text)]', zero: 'text-[var(--uo-text)]'}
        : {plus: 'text-success', minus: 'text-danger', zero: 'text-white'}

    const balanceClass = computed(() => {
        if (typeof props.workTimeBalanceMinutes === 'number') {
            if (props.workTimeBalanceMinutes > 0) return classes.plus
            if (props.workTimeBalanceMinutes < 0) return classes.minus
            return classes.zero
        }
        const val = props.workTimeBalance
        if (!val) return classes.zero
        const compact = String(val).replace(/\s+/g, '')
        if (/^[−-]/.test(compact)) return /[1-9]/.test(compact) ? classes.minus : classes.zero
        return /[1-9]/.test(compact) ? classes.plus : classes.zero
    })

    const balanceTooltip = computed(() => {
        const d = new Date()
        d.setDate(d.getDate() - 1)
        const formatted = `${String(d.getDate()).padStart(2, '0')}.${String(d.getMonth() + 1).padStart(2, '0')}.${d.getFullYear()}`
        return $t('As of: nightly booking up to {0}', [formatted])
    })

    return {balanceClass, balanceTooltip}
}
