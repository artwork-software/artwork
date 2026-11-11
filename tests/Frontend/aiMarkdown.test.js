import assert from 'node:assert/strict';
import test from 'node:test';

import { isRelativeHref, renderAiMarkdown } from '../../resources/js/Components/AiAssistant/aiMarkdown.js';

// core-ki-bar-ui / Konzept C4 §4: Antworten aus artwork-ai sind Modellinhalt. Nur relative Ziele
// duerfen Links werden, rohes HTML muss escaped erscheinen.

test('only single-slash paths count as relative', () => {
    assert.equal(isRelativeHref('/projects/1'), true);
    assert.equal(isRelativeHref('//evil.example/steal'), false);
    assert.equal(isRelativeHref('https://artwork.art'), false);
    assert.equal(isRelativeHref('javascript:alert(1)'), false);
    assert.equal(isRelativeHref('mailto:someone@example.org'), false);
    assert.equal(isRelativeHref(undefined), false);
});

test('relative links stay links', () => {
    const html = renderAiMarkdown('Siehe [Dienstplan](/shifts/plan).');

    assert.match(html, /<a href="\/shifts\/plan">Dienstplan<\/a>/);
});

test('absolute and protocol-relative links are rendered as text', () => {
    const html = renderAiMarkdown('[artwork.art](https://artwork.art) und [evil](//evil.example/steal)');

    assert.ok(!html.includes('<a '), `unerwarteter Link: ${html}`);
    assert.match(html, /artwork\.art/);
    assert.match(html, /\(\/\/evil\.example\/steal\)/);
});

test('javascript: urls never become links', () => {
    const html = renderAiMarkdown('[klick](javascript:alert(1))');

    assert.ok(!html.includes('<a '), `unerwarteter Link: ${html}`);
});

test('raw html is escaped, not rendered', () => {
    const html = renderAiMarkdown('<script>alert("xss")</script> <b>fett</b>');

    assert.ok(!html.includes('<script>'), html);
    assert.ok(!html.includes('<b>'), html);
    assert.match(html, /&lt;script&gt;/);
});

test('bare urls are not linkified', () => {
    const html = renderAiMarkdown('Mehr unter https://artwork.art im Netz.');

    assert.ok(!html.includes('<a '), `unerwarteter Link: ${html}`);
});

test('lists and emphasis survive the filter', () => {
    const html = renderAiMarkdown('- Erster\n- **Zweiter**\n');

    assert.match(html, /<ul>/);
    assert.match(html, /<strong>Zweiter<\/strong>/);
});

test('empty input renders nothing', () => {
    assert.equal(renderAiMarkdown(''), '');
    assert.equal(renderAiMarkdown(null), '');
});
