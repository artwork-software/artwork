/* Productions sold online only in packages need a package the shop sells right now; without one,
   Artwork-Tickets refuses every online booking of their dates. */

/** A package on sale right now: active and inside its period (tickets judges the period). */
export function isRunningPackage(discount) {
    return discount.kind === 'package' && discount.status === 'active' && discount.period === 'running'
}

/** Whether a discount counts for a production: for all, by name, or through one of its groups. */
export function discountReaches(discount, productionId, groups) {
    return discount.allEvents
        || discount.eventIds.includes(productionId)
        || groups.some((group) => discount.groupIds.includes(group.id) && group.eventIds.includes(productionId))
}

/**
 * The package-only productions a running package reaches now but none would once `discount` is
 * replaced by `next` (null: deleted).
 */
export function strandedProductions(discounts, productions, groups, discount, next) {
    const after = discounts.flatMap((candidate) => (candidate.id !== discount.id ? [candidate] : next ? [next] : []))
    const covered = (list, production) => list.some((candidate) => isRunningPackage(candidate) && discountReaches(candidate, production.id, groups))

    return productions.filter((production) => production.packageOnly && covered(discounts, production) && !covered(after, production))
}

/** The warning for productions that would lose their last package on sale. */
export function packageOnlyWarning(stranded, t) {
    return t('{projects} are sold online only in packages: without a package on sale, their dates cannot be booked online.', {
        projects: stranded.map((production) => production.title).join(', '),
    })
}
