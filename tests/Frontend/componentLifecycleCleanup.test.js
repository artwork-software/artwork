import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const jsRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../resources/js');
const read = (file) => readFileSync(path.join(jsRoot, file), 'utf8');

/** Quelltext von onMounted(async () => { … bis zum ersten await (Teil, der noch mit aktiver Instanz läuft). */
const syncPartOfAsyncOnMounted = (source) => {
    const start = source.indexOf('onMounted(async () => {');
    assert.notEqual(start, -1, 'onMounted(async …) nicht gefunden');
    // erste Codezeile mit await (Kommentare erwähnen das Wort auch)
    const firstAwait = source.slice(start).search(/\n\s*(?:const [^\n]*=\s*)?await /);
    return source.slice(start, start + firstAwait);
};

test('shift plan installs its navigation guard and watchers before the first await', () => {
    const source = read('Pages/Shifts/ShiftPlan.vue');
    const syncPart = syncPartOfAsyncOnMounted(source);

    // Nach einem await würde ein schon verlassener Plan router.visit noch überschreiben
    assert.match(syncPart, /setupInertiaNavigationGuard\(\)/);
    // Watcher nach einem await gehören zu keiner Instanz und werden beim Verlassen nicht gestoppt
    assert.match(syncPart, /watch\(\s*\(\) => props\.days/);
    assert.match(syncPart, /document\.addEventListener\(eventName, onFullscreenChange\)/);
    // Zurücksetzen nur, wenn noch der eigene Wrapper aktiv ist
    assert.match(source, /router\.visit === guardedVisit/);
    assert.match(source, /document\.removeEventListener\(eventName, onFullscreenChange\)/);
    // Keine anonymen Fullscreen-Listener mehr (nie entfernbar)
    assert.doesNotMatch(source, /addEventListener\('\w*fullscreenchange', \(\)/i);
});

test('shift plan daily view stops setup when it was left during the initial load', () => {
    const source = read('Pages/Shifts/ShiftPlanDailyView.vue');
    const afterLoad = source.slice(source.indexOf('loadProjectDayAssignments(),\n    ])'));

    assert.match(afterLoad.slice(0, 200), /if \(_isUnmounted\) return/);
    // Template-Ref ist in onUnmounted schon null: disconnect statt unobserve(ref)
    assert.match(source, /ro\?\.disconnect\(\)/);
});

test('series changes refetch the calendar, because the backend sends no live update per event', () => {
    const calendar = read('Components/Calendar/BaseCalendar.vue');
    const eventModal = read('Layouts/Components/EventComponent.vue');
    const eventTile = read('Components/Calendar/Elements/Events/FullEventInCalendar.vue');

    assert.match(calendar, /provide\('calendarRefetchLoadedMonths', refetchLoadedMonths\)/);
    assert.match(eventTile, /inject\('calendarRefetchLoadedMonths', null\)/);
    assert.match(eventModal, /inject\('calendarRefetchLoadedMonths', null\)/);
    // Löschen (folgende/alle), Serie beenden, Bearbeiten mehrerer Serientermine
    assert.match(eventModal, /events\.series\.delete[\s\S]{0,120}refetchCalendarAfterSeriesChange\(\)/);
    assert.match(eventModal, /if \(mode === 'end'\) refetchCalendarAfterSeriesChange\(\)/);
    assert.match(eventModal, /seriesScope\.value !== 'single'\) refetchCalendarAfterSeriesChange\(\)/);
});

test('room request notification is only removed after the event request succeeded', () => {
    const source = read('Layouts/Components/NotificationSectionComponent.vue');

    assert.match(source, /await axios\.put\('\/events\/' \+ this\.requestToAnswerWithRoomChange\.id[^\n]*\n\s*await this\.deleteNotification\(\)/);
    assert.match(source, /await axios\.delete\(`\/events\/\$\{this\.eventToDelete\.id\}`\);\n\s*await this\.deleteNotification\(\)/);
});

test('material issue pdf lightbox registers its keyboard handler', () => {
    const source = read('Pages/Projects/Components/Issue/ProjectMaterialIssueComponent.vue');

    assert.match(source, /onMounted\(\(\) => window\.addEventListener\('keydown', onKey\)\)/);
});
