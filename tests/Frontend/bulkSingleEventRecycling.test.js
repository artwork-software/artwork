import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

// DynamicScroller recycelt BulkSingleEvent: nach Umsortieren (Datum/Raum/Typ) kann dieselbe
// Komponenteninstanz einen anderen Termin zeigen. Asynchrone Handler müssen den Termin daher
// vor dem Request festhalten und danach nur noch diese Referenz benutzen.
const source = readFileSync(
    new URL('../../resources/js/Pages/Projects/Components/BulkComponents/BulkSingleEvent.vue', import.meta.url),
    'utf8',
);

const handlerBody = (name) => {
    const start = source.indexOf(`const ${name} = `);
    assert.notEqual(start, -1, name);
    const end = source.indexOf('\n};\n', start);
    return source.slice(start, end);
};

for (const handler of ['onStartDateFocusOut', 'onRoomChange', 'onTypeChange', 'updateEventInDatabase', 'saveDescription']) {
    test(`${handler} works on the event captured before the request`, () => {
        const body = handlerBody(handler);
        assert.match(body, /const event = props\.event;/);
        const rest = body
            .replace('const event = props.event;', '')
            .replace('props.event === event', '');
        assert.doesNotMatch(rest, /props\.event\b/);
    });
}
