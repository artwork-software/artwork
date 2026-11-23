import assert from 'node:assert/strict';
import test from 'node:test';

import { attachmentFrom } from '../../resources/js/Components/AiAssistant/aiAttachment.js';
import { renderAiMarkdown } from '../../resources/js/Components/AiAssistant/aiMarkdown.js';

// core-ki-bar-attachment / Protokoll 1.2: Downloads aus artwork-ai nur als relative URL der Instanz.

test('a pdf attachment with a relative url is accepted', () => {
    assert.deepEqual(
        attachmentFrom({ url: '/ai/exports/0199a1b2', filename: 'Projekte Q1.pdf', title: 'Projekte Q1', mime: 'application/pdf' }),
        { url: '/ai/exports/0199a1b2', filename: 'Projekte Q1.pdf', title: 'Projekte Q1', mime: 'application/pdf' },
    );
});

test('absolute, protocol-relative and script urls are dropped', () => {
    for (const url of ['https://evil.example/x.pdf', '//evil.example/x.pdf', 'javascript:alert(1)', undefined]) {
        assert.equal(attachmentFrom({ url, filename: 'x.pdf', title: 'x', mime: 'application/pdf' }), null, String(url));
    }
});

test('unknown mime types and broken payloads are dropped', () => {
    assert.equal(attachmentFrom({ url: '/ai/exports/1', filename: 'x.exe', title: 'x', mime: 'application/octet-stream' }), null);
    assert.equal(attachmentFrom(null), null);
    assert.equal(attachmentFrom('nope'), null);
});

test('missing names fall back to something readable', () => {
    assert.deepEqual(
        attachmentFrom({ url: '/ai/exports/1', filename: ' ', title: '', mime: 'application/pdf' }),
        { url: '/ai/exports/1', filename: 'export.pdf', title: 'export.pdf', mime: 'application/pdf' },
    );
});

test('the transcript link of a download stays clickable after reload', () => {
    const html = renderAiMarkdown('Fertig.\n\n[Projekte Q1.pdf](/ai/exports/0199a1b2)');

    assert.match(html, /<a href="\/ai\/exports\/0199a1b2">Projekte Q1\.pdf<\/a>/);
});

test('markdown tables are rendered as tables', () => {
    const html = renderAiMarkdown('| Projekt | Termine |\n|---|---|\n| Tosca | 42 |');

    assert.match(html, /<table>/);
    assert.match(html, /<td>Tosca<\/td>/);
});
