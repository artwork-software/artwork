import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

test('timeline icon sits above the event properties, away from the cell "+" button in the bottom-right corner', () => {
    const source = read('resources/js/Components/Calendar/Elements/Events/FullEventInCalendar.vue');
    const rightColumnStart = source.indexOf('<!-- Kontext-Menü -->');
    const rightColumn = source.slice(rightColumnStart, source.indexOf('<!-- CONTENT: Kompakte Ansicht', rightColumnStart));

    const timelineIndex = rightColumn.indexOf('@click="openTimelineModal"');
    const propertiesIndex = rightColumn.indexOf('v-for="property in event.eventProperties"');

    assert.ok(timelineIndex > -1, 'timeline icon missing in the right column');
    assert.ok(propertiesIndex > -1, 'property icons missing in the right column');
    assert.ok(timelineIndex < propertiesIndex, 'timeline icon must come before the property icons');
});

test('the cell "+" button is capped at 28px so it covers less of the last cell item', () => {
    const source = read('resources/js/Components/Calendar/Elements/CalendarDayRow.vue');

    assert.match(source, /const addEventButtonSize = computed\(\(\) => Math\.min\(28, /);
});
