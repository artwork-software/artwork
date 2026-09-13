import { computed, unref } from 'vue'

/**
 * Rechte-Katalog im Frontend: berechnet pro Recht den Status gegen den Zustand der Person und der Instanz.
 *
 * catalog = { modules: [...], instance: { modules, settings, features }, user: { permissions, is_admin } | null }
 * selected = Ref<string[]> der gesetzten Rechtenamen (v-model der Seite)
 *
 * Status pro Recht:
 *  - active:              gesetzt und wirksam
 *  - implied:             nicht selbst gesetzt, aber durch ein gesetztes Superset enthalten
 *  - blocked_by_module:   Modul deaktiviert (Recht bleibt gespeichert, wirkt nicht)
 *  - missing_requirement: harte Voraussetzung fehlt (anderes Recht, Setting, Feature)
 *  - inactive:            nicht gesetzt
 */
export function usePermissionCatalog(catalogRef, selectedRef) {
    const catalog = computed(() => unref(catalogRef) ?? { modules: [], instance: { modules: {}, settings: {}, features: {} }, user: null })
    const instance = computed(() => catalog.value.instance ?? { modules: {}, settings: {}, features: {} })
    const modules = computed(() => catalog.value.modules ?? [])

    /** name => definition (inkl. tier + module key) */
    const definitions = computed(() => {
        const map = {}
        for (const module of modules.value) {
            for (const list of ['tiers', 'extras', 'advanced']) {
                for (const def of module[list] ?? []) {
                    map[def.name] = { ...def, module: module.key }
                }
            }
        }
        return map
    })

    /** name => Liste der Rechte, die es direkt enthalten */
    const impliedBy = computed(() => {
        const map = {}
        for (const def of Object.values(definitions.value)) {
            for (const implied of def.implies ?? []) {
                ;(map[implied] ??= []).push(def.name)
            }
        }
        return map
    })

    const selectedSet = computed(() => new Set(unref(selectedRef) ?? []))

    /** transitiv alle Rechte, die durch die Auswahl enthalten sind (inkl. Auswahl selbst) */
    const effectiveSet = computed(() => {
        const result = new Set()
        const queue = [...selectedSet.value]
        while (queue.length) {
            const name = queue.shift()
            if (result.has(name)) continue
            result.add(name)
            for (const implied of definitions.value[name]?.implies ?? []) {
                if (!result.has(implied)) queue.push(implied)
            }
        }
        return result
    })

    const isAdmin = computed(() => !!catalog.value.user?.is_admin)

    function moduleEnabled(module) {
        if (!module?.module_setting) return true
        const value = instance.value.modules?.[module.module_setting]
        return value !== false
    }

    function requirementStatus(requirement) {
        switch (requirement.type) {
            case 'module':
                return instance.value.modules?.[requirement.value] === false ? 'missing' : 'ok'
            case 'permission':
                return effectiveSet.value.has(requirement.value) || isAdmin.value ? 'ok' : 'missing'
            case 'setting':
                return instance.value.settings?.[requirement.value] ? 'ok' : 'missing'
            case 'feature':
                return instance.value.features?.[requirement.value] ? 'ok' : 'missing'
            case 'role':
                return isAdmin.value ? 'ok' : 'missing'
            default:
                return 'unknown'
        }
    }

    /** Voraussetzungen mit Live-Status. Weiche Alternativen (hard=false) gelten als erfüllt, sobald eine davon erfüllt ist. */
    function requirementsFor(def) {
        const list = (def.requires ?? []).map((requirement) => ({ ...requirement, status: requirementStatus(requirement) }))
        const soft = list.filter((r) => !r.hard && r.type !== 'project_team')
        const anySoftOk = soft.some((r) => r.status === 'ok')
        return list.map((r) => (!r.hard && r.type !== 'project_team' && anySoftOk ? { ...r, status: 'ok' } : r))
    }

    function hardMissing(def) {
        const reqs = requirementsFor(def)
        const hard = reqs.filter((r) => r.hard && r.type !== 'project_team' && r.status === 'missing')
        const soft = reqs.filter((r) => !r.hard && r.type !== 'project_team')
        const softAllMissing = soft.length > 0 && soft.every((r) => r.status === 'missing')
        return [...hard, ...(softAllMissing ? soft : [])]
    }

    function statusOf(name) {
        const def = definitions.value[name]
        if (!def) return 'inactive'
        const module = modules.value.find((m) => m.key === def.module)
        const selected = selectedSet.value.has(name)
        const implied = !selected && effectiveSet.value.has(name)
        if (!selected && !implied) return 'inactive'
        if (!moduleEnabled(module)) return 'blocked_by_module'
        if (hardMissing(def).some((r) => r.type !== 'module')) return 'missing_requirement'
        return implied ? 'implied' : 'active'
    }

    /** Rechte, die dieses Recht enthalten UND aktuell gesetzt sind */
    function activeSupersetsOf(name) {
        return (impliedBy.value[name] ?? []).filter((superset) => effectiveSet.value.has(superset))
    }

    /** Rechte, die beim Setzen mitgesetzt werden (transitiv) */
    function impliedClosure(name) {
        const result = new Set()
        const queue = [...(definitions.value[name]?.implies ?? [])]
        while (queue.length) {
            const n = queue.shift()
            if (result.has(n)) continue
            result.add(n)
            for (const implied of definitions.value[n]?.implies ?? []) queue.push(implied)
        }
        return [...result]
    }

    /** Rechte, die beim Entfernen ebenfalls entfernt werden müssten, weil sie dieses Recht enthalten */
    function dependentsOf(name) {
        const result = new Set()
        const queue = [...(impliedBy.value[name] ?? [])]
        while (queue.length) {
            const n = queue.shift()
            if (result.has(n)) continue
            if (selectedSet.value.has(n)) result.add(n)
            for (const superset of impliedBy.value[n] ?? []) queue.push(superset)
        }
        return [...result]
    }

    /** Neue Auswahl nach Setzen eines Rechts (inkl. Implikationen) */
    function withGranted(name) {
        return [...new Set([...selectedSet.value, name, ...impliedClosure(name)])]
    }

    /** Neue Auswahl nach Entfernen eines Rechts (inkl. abhängiger Supersets) */
    function withRevoked(name) {
        const remove = new Set([name, ...dependentsOf(name)])
        return [...selectedSet.value].filter((n) => !remove.has(n))
    }

    function visibleDefinitions(module, includeAdvanced = false) {
        const lists = includeAdvanced ? ['tiers', 'extras', 'advanced'] : ['tiers', 'extras']
        return lists.flatMap((list) => module[list] ?? []).filter((d) => !d.hidden)
    }

    function moduleSummary(module) {
        const all = visibleDefinitions(module, true).filter((d) => !d.hidden)
        const granted = all.filter((d) => effectiveSet.value.has(d.name)).length
        return { total: all.length, granted, enabled: moduleEnabled(module) }
    }

    return {
        catalog,
        modules,
        definitions,
        instance,
        isAdmin,
        selectedSet,
        effectiveSet,
        moduleEnabled,
        requirementsFor,
        hardMissing,
        statusOf,
        activeSupersetsOf,
        impliedClosure,
        dependentsOf,
        withGranted,
        withRevoked,
        visibleDefinitions,
        moduleSummary,
    }
}
