import {computed, getCurrentInstance} from 'vue'

/**
 * AZK-Badge in den Schichtplan-Kacheln (DragElement/HighlightUserCell/MultiEditUserCell):
 * Farbe aus den Rohminuten (Fallback: Textformat "+10:30 h" / "−2:00 h" / "1h 30m"),
 * Tooltip "Stand: Nachtbuchung bis {gestern}".
 */
export function useWorkTimeBalanceBadge(props) {
    const $t = getCurrentInstance()?.proxy?.$t ?? ((s) => s)

    const balanceClass = computed(() => {
        if (typeof props.workTimeBalanceMinutes === 'number') {
            if (props.workTimeBalanceMinutes > 0) return 'text-success'
            if (props.workTimeBalanceMinutes < 0) return 'text-danger'
            return 'text-white'
        }
        const val = props.workTimeBalance
        if (!val) return 'text-white'
        const compact = String(val).replace(/\s+/g, '')
        if (/^[−-]/.test(compact)) return /[1-9]/.test(compact) ? 'text-danger' : 'text-white'
        return /[1-9]/.test(compact) ? 'text-success' : 'text-white'
    })

    const balanceTooltip = computed(() => {
        const d = new Date()
        d.setDate(d.getDate() - 1)
        const formatted = `${String(d.getDate()).padStart(2, '0')}.${String(d.getMonth() + 1).padStart(2, '0')}.${d.getFullYear()}`
        return $t('As of: nightly booking up to {0}', [formatted])
    })

    return {balanceClass, balanceTooltip}
}
