import { ref, provide, inject } from 'vue'

const LOOKUPS_KEY = Symbol('shiftPlanLookups')

/**
 * Create and provide shift plan lookup maps.
 * Call this once in the root ShiftPlan component.
 *
 * @returns {{ lookups: Ref, setLookups: Function, resolveCraft: Function, resolveEventType: Function, resolveProject: Function, resolveShiftGroup: Function }}
 */
export function provideShiftPlanLookups() {
    const lookups = ref({
        craftsById: {},
        eventTypesById: {},
        projectsById: {},
        shiftGroupsById: {},
    })

    function setLookups(newLookups) {
        if (!newLookups) return
        lookups.value = {
            craftsById: newLookups.craftsById ?? {},
            eventTypesById: newLookups.eventTypesById ?? {},
            projectsById: newLookups.projectsById ?? {},
            shiftGroupsById: newLookups.shiftGroupsById ?? {},
        }
    }

    function mergeLookups(newLookups) {
        if (!newLookups) return
        const current = lookups.value
        lookups.value = {
            craftsById: { ...current.craftsById, ...(newLookups.craftsById ?? {}) },
            eventTypesById: { ...current.eventTypesById, ...(newLookups.eventTypesById ?? {}) },
            projectsById: { ...current.projectsById, ...(newLookups.projectsById ?? {}) },
            shiftGroupsById: { ...current.shiftGroupsById, ...(newLookups.shiftGroupsById ?? {}) },
        }
    }

    function resolveCraft(craftId) {
        if (craftId == null) return null
        return lookups.value.craftsById[craftId] ?? null
    }

    function resolveEventType(eventTypeId) {
        if (eventTypeId == null) return null
        return lookups.value.eventTypesById[eventTypeId] ?? null
    }

    function resolveProject(projectId) {
        if (projectId == null) return null
        return lookups.value.projectsById[projectId] ?? null
    }

    function resolveShiftGroup(shiftGroupId) {
        if (shiftGroupId == null) return null
        return lookups.value.shiftGroupsById[shiftGroupId] ?? null
    }

    const api = {
        lookups,
        setLookups,
        mergeLookups,
        resolveCraft,
        resolveEventType,
        resolveProject,
        resolveShiftGroup,
    }

    provide(LOOKUPS_KEY, api)
    return api
}

/**
 * Inject shift plan lookups in a child component.
 * @returns {{ lookups: Ref, resolveCraft: Function, resolveEventType: Function, resolveProject: Function, resolveShiftGroup: Function }}
 */
export function useShiftPlanLookups() {
    const api = inject(LOOKUPS_KEY)
    if (!api) {
        // Fallback for components that may render outside the shift plan context
        return {
            lookups: ref({ craftsById: {}, eventTypesById: {}, projectsById: {}, shiftGroupsById: {} }),
            setLookups: () => {},
            mergeLookups: () => {},
            resolveCraft: () => null,
            resolveEventType: () => null,
            resolveProject: () => null,
            resolveShiftGroup: () => null,
        }
    }
    return api
}
