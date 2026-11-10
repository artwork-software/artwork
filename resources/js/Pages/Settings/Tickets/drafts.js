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

/* Mehrfachkarten and Abos: tickets sends a type in the shape it takes back (cents, days, wall
   times on the house's clock); the draft only turns amounts into input strings and back. */

/** A new type in that same shape: ten admissions on a card, two a month on an Abo, a year's term, fully covered. */
function newPassType(kind) {
    return {
        kind,
        name: '',
        description: '',
        price: null,
        monthlyPrice: null,
        upfrontPrice: null,
        termMode: 'months',
        termMonths: 12,
        seasonEnds: null,
        saleEnds: null,
        afterTerm: 'ends',
        usesWindow: 'month',
        rolloverMax: 0,
        uses: kind === 'subscription' ? 2 : 10,
        taxTreatment: null,
        validityDays: null,
        validUntil: null,
        discountKind: 'percent',
        discountPercent: 100,
        discountAmount: null,
        maxUsesPerOrder: null,
        maxUsesPerEvent: null,
        maxUsesPerPeriod: null,
        limitPeriod: 'week',
        redeemableInCheckout: true,
        stampable: true,
        allEvents: false,
        eventIds: [],
        groupIds: [],
    }
}

export function passTypeDraft(passType, kind) {
    const source = passType ?? newPassType(kind)
    const text = (value) => (value === null ? '' : String(value))
    return {
        ...source,
        price: fromCents(source.price),
        monthlyPrice: fromCents(source.monthlyPrice),
        upfrontPrice: fromCents(source.upfrontPrice),
        discountAmount: fromCents(source.discountAmount),
        uses: text(source.uses),
        termMonths: text(source.termMonths),
        rolloverMax: text(source.rolloverMax),
        validityDays: text(source.validityDays),
        discountPercent: text(source.discountPercent),
        maxUsesPerOrder: text(source.maxUsesPerOrder),
        maxUsesPerEvent: text(source.maxUsesPerEvent),
        maxUsesPerPeriod: text(source.maxUsesPerPeriod),
        seasonEnds: source.seasonEnds ?? '',
        saleEnds: source.saleEnds ?? '',
        validUntil: source.validUntil ?? '',
        validity: source.validityDays ? 'days' : source.validUntil ? 'until' : 'unlimited',
        eventIds: [...source.eventIds],
        groupIds: [...source.groupIds],
    }
}

/** Only what the kind and term use; the rest goes as null so tickets stores nothing stale. */
export function passTypePayload(draft) {
    const subscription = draft.kind === 'subscription'
    const count = (value) => (String(value ?? '').trim() === '' ? null : Number(value))
    const when = (condition, value) => (condition ? value : null)
    return {
        kind: draft.kind,
        name: draft.name.trim(),
        description: draft.description.trim() || null,
        price: when(!subscription, toCents(draft.price)),
        uses: Number(draft.uses),
        monthlyPrice: when(subscription, toCents(draft.monthlyPrice)),
        upfrontPrice: when(subscription && draft.termMode !== 'open', toCents(draft.upfrontPrice)),
        termMode: draft.termMode,
        termMonths: when(subscription && draft.termMode === 'months', count(draft.termMonths)),
        seasonEnds: when(subscription && draft.termMode === 'season', draft.seasonEnds || null),
        saleEnds: draft.saleEnds || null,
        afterTerm: draft.afterTerm,
        usesWindow: draft.usesWindow,
        rolloverMax: subscription && draft.usesWindow === 'month' ? (count(draft.rolloverMax) ?? 0) : 0,
        taxTreatment: draft.taxTreatment,
        validityDays: when(!subscription && draft.validity === 'days', count(draft.validityDays)),
        validUntil: when(!subscription && draft.validity === 'until', draft.validUntil || null),
        discountKind: draft.discountKind,
        discountPercent: when(draft.discountKind === 'percent', count(draft.discountPercent)),
        discountAmount: when(draft.discountKind === 'fixed', toCents(draft.discountAmount)),
        maxUsesPerOrder: count(draft.maxUsesPerOrder),
        maxUsesPerEvent: count(draft.maxUsesPerEvent),
        maxUsesPerPeriod: count(draft.maxUsesPerPeriod),
        limitPeriod: draft.limitPeriod,
        redeemableInCheckout: draft.redeemableInCheckout,
        stampable: draft.stampable,
        allEvents: draft.allEvents,
        eventIds: draft.allEvents ? [] : draft.eventIds,
        groupIds: draft.allEvents ? [] : draft.groupIds,
    }
}
