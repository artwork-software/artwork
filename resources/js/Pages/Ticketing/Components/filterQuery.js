import { router } from '@inertiajs/vue3'

/** The section's filters live in the query string; empty ones are left out so the address stays readable. */
export function visitWithFilters(routeName, filters) {
    const query = Object.fromEntries(Object.entries(filters).filter(([, value]) => (Array.isArray(value) ? value.length > 0 : value)))
    router.get(route(routeName), query, { preserveScroll: true, preserveState: true })
}
