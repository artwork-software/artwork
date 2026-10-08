import axios from 'axios'
import { ref } from 'vue'
import { usePage } from '@inertiajs/vue3'

/* Before the time or room of dates changes, the person is asked about those on sale in
   Artwork-Tickets. The dialog sits once in AppLayout; the server refuses the move of a date on
   sale without the header handed out here. */

export const TICKETING_MOVE_CONFIRMED_HEADER = 'X-Ticketing-Move-Confirmed'

/** Connected to Artwork-Tickets; without it nothing of the ticketing shows or asks. */
export const ticketingActive = () => usePage().props.ticketing?.active === true

/** The open question for TicketingMoveDialog, or null. */
export const ticketingMoveQuestion = ref(null)

/**
 * Headers for the request that moves these dates, or null when the person cancelled or may
 * not move them.
 *
 * @param {number[]} eventIds
 * @param {{ withSeries?: boolean }} options withSeries: the move reaches the whole series of each date
 */
export async function ticketingMoveHeaders(eventIds, { withSeries = false } = {}) {
    if (!eventIds.length || !ticketingActive()) return {}

    let check
    try {
        ({ data: check } = await axios.get(route('ticketing.move-check'), {
            params: { event_ids: eventIds, with_series: withSeries ? 1 : 0 },
        }))
    } catch {
        // The question is a courtesy; without it the server still refuses a date on sale.
        return {}
    }

    if (!check.dates.length) return {}

    const confirmed = await new Promise((resolve) => {
        ticketingMoveQuestion.value = {
            dates: check.dates,
            mayMove: check.may_move,
            several: eventIds.length > 1 || withSeries,
            resolve,
        }
    })
    ticketingMoveQuestion.value = null

    return confirmed ? { [TICKETING_MOVE_CONFIRMED_HEADER]: '1' } : null
}
