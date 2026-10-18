import assert from 'node:assert/strict'
import test from 'node:test'
import {isSafeHttpUrl, safeLinkHref, safeLinkTarget} from '../../resources/js/Helper/SafeUrl.js'

test('http(s), mailto and tel stay unchanged', () => {
    assert.equal(safeLinkHref('https://beispiel.de/a?b=1'), 'https://beispiel.de/a?b=1')
    assert.equal(safeLinkHref('  http://beispiel.de  '), 'http://beispiel.de')
    assert.equal(safeLinkHref('mailto:info@beispiel.de'), 'mailto:info@beispiel.de')
    assert.equal(safeLinkHref('tel:+4940123'), 'tel:+4940123')
})

test('relative paths with a single leading slash stay on the same origin', () => {
    // LNK-1: wurde zu https:///projects/12
    assert.equal(safeLinkHref('/projects/12'), '/projects/12')
    assert.equal(safeLinkHref('/projects/12?tab=3#info'), '/projects/12?tab=3#info')
    assert.equal(safeLinkHref('/'), '/')
})

test('protocol-relative and backslash variants never count as relative paths', () => {
    assert.equal(safeLinkHref('//beispiel.de/pfad'), 'https://beispiel.de/pfad')
    assert.equal(safeLinkHref('/\t/beispiel.de'), 'https://beispiel.de')
    assert.equal(safeLinkHref('/\\beispiel.de'), '#')
})

test('UNC paths are not clickable and are not turned into https', () => {
    // LNK-1: wurde zu https://\\server…
    assert.equal(safeLinkHref('\\\\server\\share\\datei.pdf'), '#')
    assert.equal(safeLinkTarget('\\\\server\\share'), null)
})

test('addresses without a scheme get https', () => {
    assert.equal(safeLinkHref('www.beispiel.de'), 'https://www.beispiel.de')
    assert.equal(safeLinkHref('beispiel.de:8080/pfad'), 'https://beispiel.de:8080/pfad')
})

test('dangerous and unsupported schemes stay blocked', () => {
    for (const value of [
        'javascript:alert(1)',
        ' JaVa\tScript:alert(1)',
        'data:text/html;base64,PHNjcmlwdD4=',
        'vbscript:msgbox(1)',
        'file:///C:/daten.txt',
        'smb://server/share',
        'teams:/l/chat/0/0',
        'ms-word:ofe|u|https://beispiel.de/a.docx',
        'onenote:https://beispiel.de/notiz',
        'C:\\daten\\datei.txt',
    ]) {
        assert.equal(safeLinkHref(value), '#', value)
        assert.equal(safeLinkTarget(value), null, value)
    }
})

test('empty or non-string values are not clickable', () => {
    assert.equal(safeLinkHref(''), '#')
    assert.equal(safeLinkHref('   '), '#')
    assert.equal(safeLinkHref(null), '#')
    assert.equal(safeLinkTarget(undefined), null)
})

test('isSafeHttpUrl only accepts absolute http(s) addresses', () => {
    assert.equal(isSafeHttpUrl('https://beispiel.de'), true)
    assert.equal(isSafeHttpUrl('/projects/12'), false)
    assert.equal(isSafeHttpUrl('javascript:alert(1)'), false)
})
