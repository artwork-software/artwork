import assert from 'node:assert/strict'
import test from 'node:test'
import { FALLBACK_SLUG, safeSlug, toSlug } from '../../resources/js/Composeables/tablerIconNames.js'
import { parseSvgShapes } from '../../resources/js/Composeables/tablerSvgShapes.js'

test('icon names resolve to plain slugs as before', () => {
    assert.equal(toSlug('IconHome2'), 'home-2')
    assert.equal(toSlug('home-2'), 'home-2')
    assert.equal(toSlug('icon_home_2'), 'home-2')
    assert.equal(toSlug(null), FALLBACK_SLUG)
})

test('names that would leave the icon folder fall back to the default icon', () => {
    for (const evil of [
        '../../storage/profile-photos/abc.jpg?',
        'home/../../x',
        'home?x=1',
        'home#x',
        'home.svg',
        'IconHome/../../x',
    ]) {
        assert.equal(toSlug(evil), FALLBACK_SLUG, evil)
    }
    assert.equal(safeSlug('a'.repeat(65)), FALLBACK_SLUG)
})

test('tabler svgs keep their shapes', () => {
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
        fill="none" stroke="currentColor" class="icon">
        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
        <path d="M5 12l-2 0l9 -9l9 9l-2 0" />
        <circle cx="12" cy="12" r="3" />
    </svg>`

    const { viewBox, shapes } = parseSvgShapes(svg)

    assert.equal(viewBox, '0 0 24 24')
    assert.deepEqual(shapes, [
        { tag: 'path', attrs: { stroke: 'none', d: 'M0 0h24v24H0z', fill: 'none' } },
        { tag: 'path', attrs: { d: 'M5 12l-2 0l9 -9l9 9l-2 0' } },
        { tag: 'circle', attrs: { cx: '12', cy: '12', r: '3' } },
    ])
})

test('scripts, handlers and foreign content are dropped', () => {
    const svg = `<svg viewBox="0 0 24 24"><animate onbegin="alert(1)" attributeName="x" />
        <script>alert(1)</script><foreignObject><img src=x onerror=alert(1)></foreignObject>
        <path d="M1 1" onclick="alert(1)" fill="url(javascript:alert(1))" style="x" /></svg>`

    const { shapes } = parseSvgShapes(svg)

    assert.deepEqual(shapes, [{ tag: 'path', attrs: { d: 'M1 1' } }])
})

test('legacy export names still stored in the database resolve to an existing icon file', async () => {
    const fs = await import('node:fs');
    const {LEGACY_ALIASES, toSlug: resolveSlug} = await import('../../resources/js/Composeables/tablerIconNames.js');
    const outline = new URL('../../node_modules/@tabler/icons/icons/outline/', import.meta.url);
    const filled = new URL('../../node_modules/@tabler/icons/icons/filled/', import.meta.url);

    assert.equal(resolveSlug('Icon123'), 'number-123');
    assert.ok(Object.keys(LEGACY_ALIASES).length > 0);
    for (const [name, slug] of Object.entries(LEGACY_ALIASES)) {
        assert.equal(resolveSlug(name), slug, name);
        const exists = slug.endsWith('-filled')
            ? fs.existsSync(new URL(slug.replace(/-filled$/, '') + '.svg', filled))
            : fs.existsSync(new URL(slug + '.svg', outline));
        assert.ok(exists, `${name} -> ${slug}`);
    }
});
