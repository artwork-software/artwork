import test from 'node:test';
import assert from 'node:assert/strict';
import {
    allAreasOpened,
    filterAreas,
    hasActiveRoomCriteria,
    nameAlreadyExists,
    orderRoomsByArea,
    pruneSelection,
    roomIdsOf,
    roomRowFacts,
    roomsFollowAreaOrder,
    selectionState,
    toggleRoomIds,
    usageCounts,
} from '../../resources/js/Pages/Areas/Composables/areaListTools.js';

const areas = [
    {
        id: 1,
        name: 'Haupthaus',
        rooms: [
            {id: 10, name: 'Große Bühne', room_categories: [{id: 1, name: 'Spielstätte'}], room_attributes: [{id: 7}]},
            {id: 11, name: 'Foyer', room_categories: [], room_attributes: []},
        ],
    },
    {
        id: 2,
        name: 'Probenzentrum',
        rooms: [{id: 20, name: 'Probebühne 1', room_categories: [{id: 2, name: 'Probenraum'}], room_attributes: []}],
    },
    {id: 3, name: 'Leeres Areal', rooms: []},
];

test('ohne Kriterien bleibt die Liste samt leerer Areale unverändert', () => {
    assert.equal(hasActiveRoomCriteria({search: '  ', categoryIds: [], attributeIds: []}), false);
    assert.equal(filterAreas(areas, {search: ''}), areas);
});

test('die Suche findet Räume unabhängig von Groß- und Kleinschreibung und blendet Areale ohne Treffer aus', () => {
    const result = filterAreas(areas, {search: 'bühne'});
    assert.deepEqual(result.map((area) => area.id), [1, 2]);
    assert.deepEqual(result[0].rooms.map((room) => room.id), [10]);
    assert.deepEqual(result[1].rooms.map((room) => room.id), [20]);
    assert.deepEqual(filterAreas(areas, {search: 'gibt es nicht'}), []);
});

test('passt der Suchbegriff auf das Areal, bleiben alle seine Räume stehen', () => {
    const result = filterAreas(areas, {search: 'haupt'});
    assert.deepEqual(result.map((area) => area.id), [1]);
    assert.deepEqual(result[0].rooms.map((room) => room.id), [10, 11]);
});

test('Kategorie- und Eigenschaftsfilter greifen gemeinsam mit der Suche', () => {
    assert.deepEqual(filterAreas(areas, {categoryIds: [2]}).map((area) => area.id), [2]);
    assert.deepEqual(filterAreas(areas, {categoryIds: [1], attributeIds: [7]})[0].rooms.map((room) => room.id), [10]);
    assert.deepEqual(filterAreas(areas, {categoryIds: [1], attributeIds: [99]}), []);
    assert.deepEqual(filterAreas(areas, {search: 'haupt', categoryIds: [1]})[0].rooms.map((room) => room.id), [10]);
    // die Ausgangsliste wird nicht verändert
    assert.equal(areas[0].rooms.length, 2);
});

test('Namensprüfung erkennt Dubletten ohne Rücksicht auf Schreibweise und Leerzeichen', () => {
    const items = [{name: 'Büro'}, {name: 'Lager'}];
    assert.equal(nameAlreadyExists(items, ' büro '), true);
    assert.equal(nameAlreadyExists(items, 'Werkstatt'), false);
    assert.equal(nameAlreadyExists(items, ''), false);
    assert.equal(nameAlreadyExists(null, 'Büro'), false);
});

test('alle Areale gelten nur als aufgeklappt, wenn jedes in der Liste steht', () => {
    assert.equal(allAreasOpened(areas, [1, 2, 3]), true);
    assert.equal(allAreasOpened(areas, [1, 2]), false);
    assert.equal(allAreasOpened([], []), false);
});

test('Kurzinfos einer Raumzeile', () => {
    assert.deepEqual(
        roomRowFacts({capacity: 200, room_categories: [{name: 'Spielstätte'}], relevant_for_disposition: false}),
        {capacity: 200, categories: ['Spielstätte'], hiddenFromCalendars: true}
    );
    assert.deepEqual(
        roomRowFacts({capacity: 0, relevant_for_disposition: true}),
        {capacity: null, categories: [], hiddenFromCalendars: false}
    );
});

test('usageCounts zählt Räume je Kategorie und Eigenschaft über alle Areale', () => {
    const list = [
        {rooms: [{id: 1, room_categories: [{id: 5}, {id: 6}], room_attributes: [{id: 9}]}]},
        {rooms: [{id: 2, room_categories: [{id: 5}]}, {id: 3}]},
        {},
    ];

    assert.deepEqual(usageCounts(list, 'room_categories'), {5: 2, 6: 1});
    assert.deepEqual(usageCounts(list, 'room_attributes'), {9: 1});
    assert.deepEqual(usageCounts(null, 'room_categories'), {});
});

test('Auswahl: Räume gemeinsam schalten, Zustand lesen und verschwundene Räume entfernen', () => {
    const list = [{rooms: [{id: 1}, {id: 2}]}, {rooms: [{id: 3}]}];

    assert.deepEqual(roomIdsOf(list), [1, 2, 3]);
    assert.deepEqual(toggleRoomIds([3], [1, 2]).sort(), [1, 2, 3]);
    assert.deepEqual(toggleRoomIds([1, 2, 3], [1, 2]), [3]);
    // teilweise gewählt: der Rest kommt dazu, nichts fällt weg
    assert.deepEqual(toggleRoomIds([1], [1, 2]).sort(), [1, 2]);
    assert.deepEqual(toggleRoomIds([1], []), [1]);

    assert.equal(selectionState([], [1, 2]), 'none');
    assert.equal(selectionState([1], [1, 2]), 'some');
    assert.equal(selectionState([1, 2, 3], [1, 2]), 'all');
    assert.equal(selectionState([1], []), 'none');

    assert.deepEqual(pruneSelection([1, 3, 99], list), [1, 3]);
    assert.deepEqual(pruneSelection([1], []), []);
});

test('orderRoomsByArea ordnet nach Arealen und behält die Reihenfolge innerhalb eines Areals', () => {
    const areaOrder = [{id: 2}, {id: 1}];
    const rooms = [
        {id: 10, area_id: 1},
        {id: 20, area_id: 2},
        {id: 11, area_id: 1},
        {id: 30, area_id: 77},
        {id: 21, area_id: 2},
    ];

    assert.deepEqual(orderRoomsByArea(rooms, areaOrder).map((room) => room.id), [20, 21, 10, 11, 30]);
    assert.equal(roomsFollowAreaOrder(rooms, areaOrder), false);
    assert.equal(roomsFollowAreaOrder(orderRoomsByArea(rooms, areaOrder), areaOrder), true);
    assert.equal(roomsFollowAreaOrder([], areaOrder), true);
    // die Eingabe bleibt unverändert
    assert.deepEqual(rooms.map((room) => room.id), [10, 20, 11, 30, 21]);
});
