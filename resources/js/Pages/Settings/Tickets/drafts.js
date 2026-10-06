/* What the editors work on: rooms with address and price classes, reductions.
   Drafts keep strings for the inputs; the payload helpers turn them into
   what the request rules expect (cents, integers). */

export function toCents(value) {
    const normalized = String(value ?? '').trim().replace(',', '.')
    if (normalized === '') return null
    return Math.round(Number(normalized) * 100)
}

/** For a `type="number"` input, which only accepts a dot; a comma would leave the field blank. */
export function fromCents(cents) {
    return cents === null || cents === undefined ? '' : (cents / 100).toFixed(2)
}

export function formatEuro(cents) {
    return `${(cents / 100).toFixed(2).replace('.', ',')} €`
}

export function emptyZone(name, capacity = 0) {
    return { name, capacity: String(capacity ?? 0), price: '' }
}

export function zonePlaces(room) {
    return room.zones.reduce((sum, zone) => sum + (Number(zone.capacity) || 0), 0)
}

/** A room as the editor shows it: linked venue first, house address as the fallback. */
export function roomDraft(room, address, freeSeatingName) {
    const venue = room.venue ?? null
    return {
        id: room.id,
        name: room.name,
        capacity: room.capacity,
        linked: venue !== null,
        selected: venue !== null,
        street: venue?.street ?? address.street,
        postal_code: venue?.postalCode ?? address.postal_code,
        city: venue?.city ?? address.city,
        country: venue?.country ?? address.country,
        zones: venue
            ? venue.zones.map((zone) => ({ name: zone.name, capacity: String(zone.capacity ?? 0), price: fromCents(zone.defaultPriceCents) }))
            : [emptyZone(freeSeatingName, room.capacity)],
    }
}

export function roomPayload(room) {
    return {
        id: room.id,
        street: room.street.trim(),
        postal_code: room.postal_code.trim(),
        city: room.city.trim(),
        country: room.country,
        zones: room.zones.map((zone) => ({
            name: zone.name.trim(),
            capacity: Number(zone.capacity) || 0,
            default_price_cents: toCents(zone.price),
        })),
    }
}

export function roomsValid(rooms) {
    return rooms.every((room) => room.zones.every((zone) => zone.name.trim() !== ''))
}

export function emptyReduction() {
    return { name: '', kind: 'percent', value: '', requires_proof: true, default_enabled: false }
}

/** A reduction as tickets holds it: basis points or cents → "50" / "2,50". */
export function reductionDraft(reduction) {
    return {
        name: reduction.name,
        kind: reduction.kind,
        value: fromCents(reduction.value),
        requires_proof: reduction.requiresProof,
        default_enabled: reduction.defaultEnabled,
    }
}

export function reductionPayload(reduction) {
    return {
        name: reduction.name.trim(),
        kind: reduction.kind,
        // Percent is stored in basis points (50 % → 5000), a fixed amount in cents.
        value: toCents(reduction.value) ?? 0,
        requires_proof: reduction.requires_proof,
        default_enabled: reduction.default_enabled,
    }
}

export function reductionsValid(reductions) {
    return reductions.every((r) => r.name.trim() !== '' && Number(String(r.value).replace(',', '.')) > 0)
}

/* Legal details and the shop's legal pages — the wizard step and the billing tab check alike. */

const filled = (value) => String(value ?? '').trim() !== ''

/** The house's terms, privacy policy and imprint — the shop shows all three to its buyers. */
export const LEGAL_PAGES = ['terms', 'privacy', 'imprint']

/** No PDF picked yet, for a draft's initial state. */
export const noLegalFiles = () => Object.fromEntries(LEGAL_PAGES.map((page) => [`${page}_file`, null]))

/**
 * The fields still keeping the block from complete, as tickets judges it: register and website
 * stay optional, one tax id is enough (`vat_id` stands for both), and each legal page is a link,
 * a picked PDF or one already stored in tickets.
 */
export function missingDetails(b, storedFiles = {}) {
    const missing = ['legal_name', 'legal_form', 'street', 'postal_code', 'city', 'country', 'contact_name', 'contact_phone']
        .filter((key) => !filled(b[key]))
    if (!filled(b.vat_id) && !filled(b.tax_number)) missing.push('vat_id')
    for (const page of LEGAL_PAGES) {
        if (!filled(b[`${page}_url`]) && !b[`${page}_file`] && !storedFiles[page]) missing.push(`${page}_url`)
    }
    return missing
}

/** The legal forms tickets knows (`house_legal_form`), in the words of the interface. */
export function legalFormNames(t) {
    return {
        verein: t('Registered association (e.V.)'),
        ggmbh: 'gGmbH',
        gmbh: 'GmbH',
        einzelunternehmen: t('Sole proprietorship'),
        gbr: 'GbR',
        oeffentlich: t('Public body (city, state, federal)'),
        sonstige: t('Other legal form'),
    }
}
