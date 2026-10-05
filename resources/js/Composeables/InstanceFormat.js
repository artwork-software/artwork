import { usePage } from '@inertiajs/vue3'
import { createInstanceFormatter } from '@/Helper/instanceFormat.js'

/** Regionale Formate der Instanz in Komponenten (liest den geteilten Prop "instanceFormat") */
export function useInstanceFormat() {
    return createInstanceFormatter(usePage().props.instanceFormat)
}
