import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

/**
 * Gemeinsame Navigationsdaten für Seitenleiste (Desktop) und Kopfleiste (Handy) des Gastbereichs.
 */
export function useExternalNavigation() {
    const page = usePage()

    const scopes = computed(() => page.props.accessible_scopes ?? [])

    const groupedScopes = computed(() => {
        const groups = new Map()
        for (const scope of scopes.value) {
            const key = scope.project.id
            if (!groups.has(key)) {
                groups.set(key, { project: scope.project, scopes: [] })
            }
            groups.get(key).scopes.push(scope)
        }
        return Array.from(groups.values())
    })

    function scopeHref(scope) {
        return route('external.project.tab.show', { project: scope.project.id, tab: scope.tab.id })
    }

    function isCurrentScope(scope) {
        if (!route().current('external.project.tab.show')) return false
        const params = route().params ?? {}
        return String(params.project) === String(scope.project.id) && String(params.tab) === String(scope.tab.id)
    }

    function logout() {
        router.post(route('external.logout'))
    }

    return { page, scopes, groupedScopes, scopeHref, isCurrentScope, logout }
}

export function formatExternalDate(iso) {
    if (!iso) return '—'
    return new Date(iso).toLocaleDateString()
}
