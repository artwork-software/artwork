import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

test('calendar shift cards lay the craft tint over an opaque surface so holiday stripes do not show through', () => {
    const source = read('resources/js/Components/Calendar/Elements/ShiftInCalendarCell.vue');

    assert.match(source, /backgroundColor: 'var\(--color-surface\)'/);
    assert.match(source, /backgroundImage: `linear-gradient\(\$\{tint\}, \$\{tint\}\)`/);
    assert.equal((source.match(/:style="cardBackgroundStyle"/g) ?? []).length, 1, 'compact tile must use the opaque style');
    assert.equal((source.match(/:style="\{ \.\.\.cardBackgroundStyle, zoom: contentZoom \}"/g) ?? []).length, 1, 'full card must use the opaque style');
    assert.doesNotMatch(source, /:style="\{ backgroundColor: `\$\{craftColor\}\$\{isFollowUpDay/);
});
