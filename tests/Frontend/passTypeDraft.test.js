import assert from 'node:assert/strict'
import test from 'node:test'
import { passTypeDraft, passTypePayload } from '../../resources/js/Pages/Settings/Tickets/drafts.js'

const card = {
    id: 'p1',
    status: 'active',
    issuedCount: 3,
    kind: 'card',
    name: '10er-Karte',
    description: '',
    price: 9000,
    monthlyPrice: null,
    upfrontPrice: null,
    termMode: 'months',
    termMonths: null,
    seasonEnds: null,
    saleEnds: '2026-12-31',
    afterTerm: 'ends',
    usesWindow: 'month',
    rolloverMax: 0,
    uses: 10,
    taxTreatment: null,
    validityDays: 365,
    validUntil: null,
    discountKind: 'fixed',
    discountPercent: null,
    discountAmount: 1500,
    maxUsesPerOrder: 2,
    maxUsesPerEvent: null,
    maxUsesPerPeriod: null,
    limitPeriod: 'week',
    redeemableInCheckout: true,
    stampable: false,
    allEvents: false,
    eventIds: ['e1'],
    groupIds: ['g1'],
}

test('a new card starts with ten fully covered admissions', () => {
    const draft = passTypeDraft(null, 'card')

    assert.equal(draft.uses, '10')
    assert.equal(draft.discountPercent, '100')
    assert.equal(draft.validity, 'unlimited')
    assert.equal(draft.price, '')
})

test('a new subscription starts with two admissions a month over a year', () => {
    const draft = passTypeDraft(null, 'subscription')

    assert.equal(draft.uses, '2')
    assert.equal(draft.termMonths, '12')
    assert.equal(draft.usesWindow, 'month')
})

test('a type from tickets survives the round trip through the form', () => {
    const draft = passTypeDraft(card, 'card')

    assert.equal(draft.price, '90.00')
    assert.equal(draft.validity, 'days')
    assert.equal(draft.maxUsesPerEvent, '')
    assert.notEqual(draft.eventIds, card.eventIds)

    const payload = passTypePayload(draft)
    assert.equal(payload.price, 9000)
    assert.equal(payload.discountAmount, 1500)
    assert.equal(payload.discountPercent, null)
    assert.equal(payload.validityDays, 365)
    assert.equal(payload.maxUsesPerOrder, 2)
    assert.equal(payload.maxUsesPerEvent, null)
    assert.equal(payload.saleEnds, '2026-12-31')
    assert.deepEqual(payload.groupIds, ['g1'])
})
