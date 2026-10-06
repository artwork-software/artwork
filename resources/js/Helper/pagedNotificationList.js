/**
 * Paged notification list (unread/archived) that is reloaded from several triggers at once:
 * "show more", archive actions and the collected refresh after Inertia visits.
 *
 * Every load of page 1 starts a new generation. Responses of an older generation are dropped,
 * so overlapping reloads never append the same page twice. A reload of pages 2..N passes the
 * generation of its own page 1 and stops as soon as a newer reload has started.
 */
export function createPagedList() {
    return {items: [], page: 0, total: 0, lastPage: 1, loading: false, generation: 0};
}

/**
 * @param {ReturnType<typeof createPagedList>} list
 * @param {number} page
 * @param {number|null} expectedGeneration generation the request belongs to (reload of pages 2..N)
 * @returns {number|null} generation of this request, null = do not start it
 */
export function beginPageRequest(list, page, expectedGeneration = null) {
    if (page === 1) {
        list.generation += 1;
        return list.generation;
    }
    if (expectedGeneration !== null && expectedGeneration !== list.generation) {
        return null;
    }
    return list.generation;
}

/**
 * @param {ReturnType<typeof createPagedList>} list
 * @param {number} generation
 * @param {{data: Array<{id: string}>, current_page: number, last_page: number, total: number}} response
 * @returns {boolean} false when the response belongs to an outdated reload and was dropped
 */
export function applyPageResponse(list, generation, response) {
    if (generation !== list.generation) {
        return false;
    }
    if (response.current_page === 1) {
        list.items = response.data;
    } else {
        const loadedIds = new Set(list.items.map((item) => item.id));
        list.items = list.items.concat(response.data.filter((item) => !loadedIds.has(item.id)));
    }
    list.page = response.current_page;
    list.lastPage = response.last_page;
    list.total = response.total;

    return true;
}
