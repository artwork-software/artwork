import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const source = readFileSync(
    new URL('../../resources/js/Layouts/Components/ShiftPlanComponents/UserShiftPlan.vue', import.meta.url),
    'utf8'
);

test('own individual times without start_date are checked against the day of the cell', () => {
    assert.match(
        source,
        /const individualTimeDate = \(individualTime\) => individualTime\?\.start_date \?\? individualTime\?\._day \?\? null/
    );
});

test('the edit button and the delete option use the cell day as fallback', () => {
    assert.match(source, /v-if="!isOwnPlan \|\| canEnterOwnTimesOn\(individualTimeDate\(i\)\)"/);
    assert.match(source, /:can-delete="!isOwnPlan \|\| canEnterOwnTimesOn\(individualTimeDate\(selectedIndividualTime\)\)"/);
    assert.doesNotMatch(source, /canEnterOwnTimesOn\(i\.start_date\)/);
});
