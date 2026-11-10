import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

const source = read('resources/js/Layouts/Components/NotificationComponents/NotificationBlock.vue');

test('"Settings for this type" link is readable without hovering the notification', () => {
    const linkStart = source.lastIndexOf('<Link', source.indexOf("$t('Settings for this type')"));
    const link = source.slice(linkStart, source.indexOf('</Link>', linkStart));

    assert.ok(linkStart > -1, 'settings link missing');
    assert.match(link, /class="[^"]*\btext-accent-600\b/, 'link needs the accent color without hover');
    assert.doesNotMatch(link, /opacity-0/, 'link must not be hidden until hover');
    assert.doesNotMatch(link, /notification\.hovered/, 'link visibility must not depend on hover');
});

test('"View history" link has a single text color', () => {
    const historyStart = source.lastIndexOf('<div', source.indexOf("$t('View history')"));
    const history = source.slice(historyStart, source.indexOf('>', historyStart));

    assert.match(history, /\btext-accent-600\b/);
    assert.doesNotMatch(history, /\btext-text-subtle\b/);
});
