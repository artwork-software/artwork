import assert from 'node:assert/strict'
import test from 'node:test'
import {hasUserInteractedSince, isRecentUserInteraction, markUserInteraction} from '../../resources/js/Helper/userInteraction.js'

test('a focus loss counts as user-initiated only after a click or key press elsewhere', () => {
    // ohne jede Aktion: kein Nutzer-Fokuswechsel (z. B. virtuelle Liste rendert neu)
    assert.equal(isRecentUserInteraction(400, performance.now()), false)
    const focusedAt = performance.now()
    assert.equal(hasUserInteractedSince(focusedAt), false)

    markUserInteraction({target: null})
    const now = performance.now()
    assert.equal(isRecentUserInteraction(400, now), true)
    assert.equal(isRecentUserInteraction(400, now + 1000), false)
    assert.equal(hasUserInteractedSince(focusedAt), true)
    // der Klick, der das Feld geöffnet hat, liegt VOR dem Fokussieren
    assert.equal(hasUserInteractedSince(performance.now() + 1), false)
})
