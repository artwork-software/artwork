import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(`../../${path}`, import.meta.url), 'utf8');

test('preset shift notes are sent as "description" (Inertia ignores a "data" visit option)', () => {
    const source = read('resources/js/Layouts/Components/ShiftNoteComponent.vue');

    assert.doesNotMatch(source, /\bdata:\s*\{\s*description:/);
    assert.match(source, /\.transform\(\(data\) => \(\{ description: data\.short_description \}\)\)/);
});

test('shift and preset notes allow 10,000 characters, individual notes stay at 250', () => {
    const note = read('resources/js/Layouts/Components/ShiftNoteComponent.vue');
    assert.match(note, /return this\.isPivotMode \? 250 : 10000/);
    assert.doesNotMatch(note, /maxlength="250"/);

    const addShiftModal = read('resources/js/Pages/Projects/Components/AddShiftModal.vue');
    assert.doesNotMatch(addShiftModal, /maxlength="250"/);
    assert.match(addShiftModal, /maxlength="10000"/);
});

test('BaseTextarea passes maxlength/readonly/name to the <textarea> and exposes focus()', () => {
    const source = read('resources/js/Artwork/Inputs/BaseTextarea.vue');
    const textarea = /<textarea[\s\S]*?\/>/.exec(source)[0];

    assert.match(textarea, /:maxlength="maxlength \?\? undefined"/);
    assert.match(textarea, /:readonly="readonly"/);
    assert.match(textarea, /:name="name \|\| undefined"/);
    assert.match(textarea, /ref="textareaRef"/);
    assert.match(source, /defineExpose\(\{ focus \}\)/);
});

test('failed note saves keep the field open and show the error', () => {
    const source = read('resources/js/Layouts/Components/ShiftNoteComponent.vue');

    assert.match(source, /:error="noteError"/);
    assert.match(source, /onError: \(errors\) => this\.showErrors\(errors, 'short_description'\)/);
    assert.match(source, /onError: \(errors\) => this\.showErrors\(errors, 'description'\)/);
    assert.match(source, /this\.noteError = extractSaveErrorMessage\(error\)/);
});

test('at-a-glance multi edit sends the checked events and never an empty selection', () => {
    const source = read('resources/js/Layouts/Components/IndividualCalendarAtGlanceComponent.vue');

    assert.doesNotMatch(source, /editEvents/);
    assert.match(source, /const eventIds = \[\.\.\.checkedEventIds\.value\];/);
    assert.match(source, /:disabled="checkedEventIds\.length === 0"[\s\S]*:disabled="checkedEventIds\.length === 0"/);
    assert.match(source, /if \(event\?\.clicked\)/);
});

test('multi-edit delete uses the JSON endpoint via axios and reloads after moving', () => {
    const atAGlance = read('resources/js/Layouts/Components/IndividualCalendarAtGlanceComponent.vue');
    assert.doesNotMatch(atAGlance, /router\.post\(route\('multi-edit\.delete'\)/);
    assert.match(atAGlance, /axios\.post\(route\('multi-edit\.delete'\), \{ events: eventIds \}\)/);
    assert.match(atAGlance, /\.then\(\(\) => removeEventsLocally\(eventIds\)\)/);
    assert.match(atAGlance, /closeMultiEditModal = \(moved\) => \{[\s\S]*?clearEventSelection\(\);\s*reloadEvents\(\);/);
    assert.match(atAGlance, /watch\(\(\) => props\.eventsAtAGlance,/);
    assert.match(read('resources/js/Pages/Projects/Tab/Components/CalendarTab.vue'), /provide\('reloadCalendarTabData', reloadCalendarData\)/);

    const legacy = read('resources/js/Layouts/Components/IndividualCalendarComponent.vue');
    assert.doesNotMatch(legacy, /router\.post\(route\('multi-edit\.delete'\)/);
    assert.match(legacy, /axios\.post\(route\('multi-edit\.delete'\)/);
});

test('textarea limits only where the backend has one, with a visible counter', () => {
    const baseTextarea = read('resources/js/Artwork/Inputs/BaseTextarea.vue');
    assert.match(baseTextarea, /v-if="showCounter && maxlength"/);

    assert.doesNotMatch(read('resources/js/Pages/Projects/Tab/Components/TextArea.vue'), /maxlength/);
    for (const file of [
        'resources/js/Pages/TimelinePreset/Components/AddEditTimelinePresetModal.vue',
        'resources/js/Pages/Projects/Components/TimelineComponents/AddEditTimelineModal.vue',
    ]) {
        assert.doesNotMatch(read(file), /max-length/, file);
    }
    for (const file of [
        'resources/js/Components/Calendar/Elements/DayRemarkCell.vue',
        'resources/js/Components/Calendar/Elements/DayRemarkEditModal.vue',
    ]) {
        const source = read(file);
        assert.match(source, /:maxlength="maxLength"/, file);
        assert.doesNotMatch(source, /reicht kein maxlength/, file);
    }

    assert.match(read('resources/js/Pages/ServiceProvider/Show.vue'), /:maxlength="500" show-counter/);
    assert.match(read('app/Http/Controllers/ServiceProviderController.php'), /'note' => 'nullable\|string\|max:500'/);
    assert.match(read('resources/js/Layouts/Components/ShiftPlanComponents/ShiftConfirmationResponseModal.vue'), /:maxlength="500"\s*show-counter/);
    assert.match(read('resources/js/Pages/ShiftPlanRequests/components/AcceptShiftPlanRequestModal.vue'), /maxlength="1000"\s*show-counter/);
});

test('notification settings report 422 validation errors instead of staying silent', () => {
    const source = read('resources/js/Layouts/Components/NotificationComponents/NotificationSettingsPanel.vue');
    assert.match(source, /if \(error\?\.response\?\.status === 422\) \{\s*showAppToast\('error', extractSaveErrorMessage\(error\)/);
    assert.equal((source.match(/showValidationError\(error\);/g) ?? []).length, 3);
});
