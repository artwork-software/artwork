import test from 'node:test';
import assert from 'node:assert/strict';
import {
    daysUntilDate,
    matchesMoneySourceSearch,
    moneySourceRemainingAmount,
    moneySourceUsedPercent,
} from '../../resources/js/Pages/MoneySources/moneySourceListTools.js';

test('remaining amount adds the (negative) position sum to the volume', () => {
    assert.equal(moneySourceRemainingAmount({amount: 1000, sumOfPositions: -250}), 750);
    assert.equal(moneySourceRemainingAmount({amount: '1000.50', sumOfPositions: '-1500'}), -499.5);
    assert.equal(moneySourceRemainingAmount({amount: 1000}), 1000);
    assert.equal(moneySourceRemainingAmount(null), 0);
});

test('used percent is clamped and survives a volume of zero', () => {
    assert.equal(moneySourceUsedPercent({amount: 1000, sumOfPositions: -250}), 25);
    assert.equal(moneySourceUsedPercent({amount: 1000, sumOfPositions: -1500}), 100);
    // Einnahmen erhöhen den Rest: nichts verbraucht
    assert.equal(moneySourceUsedPercent({amount: 1000, sumOfPositions: 200}), 0);
    assert.equal(moneySourceUsedPercent({amount: 0, sumOfPositions: 0}), 0);
    assert.equal(moneySourceUsedPercent({amount: 0, sumOfPositions: -10}), 100);
});

test('days until a date count whole calendar days', () => {
    const today = new Date(2026, 10, 19, 15, 30);
    assert.equal(daysUntilDate('2026-11-19', today), 0);
    assert.equal(daysUntilDate('2026-11-20', today), 1);
    assert.equal(daysUntilDate('2026-12-19 00:00:00', today), 30);
    assert.equal(daysUntilDate('2026-11-18', today), -1);
    assert.equal(daysUntilDate(null, today), null);
    assert.equal(daysUntilDate('irgendwann', today), null);
});

test('search covers name, description, funder, categories and group name', () => {
    const source = {
        name: 'Kulturstiftung',
        description: 'Förderung Tanz',
        source_name: 'Stadt Hamburg',
        categories: [{name: 'Öffentlich'}],
    };
    assert.equal(matchesMoneySourceSearch(source, ''), true);
    assert.equal(matchesMoneySourceSearch(source, 'STIFTUNG'), true);
    assert.equal(matchesMoneySourceSearch(source, 'tanz'), true);
    assert.equal(matchesMoneySourceSearch(source, 'hamburg'), true);
    assert.equal(matchesMoneySourceSearch(source, 'öffentlich'), true);
    assert.equal(matchesMoneySourceSearch(source, 'spielzeit', 'Spielzeit 26/27'), true);
    assert.equal(matchesMoneySourceSearch(source, 'privat'), false);
    assert.equal(matchesMoneySourceSearch({name: null}, 'x'), false);
});

test('tree nests sources under their group and keeps the given order', async () => {
    const {buildMoneySourceTree} = await import('../../resources/js/Pages/MoneySources/moneySourceListTools.js');
    const list = [
        {id: 3, name: 'A einzeln', is_group: false, group_id: null},
        {id: 1, name: 'Gruppe', is_group: true, group_id: null},
        {id: 4, name: 'Kind B', is_group: false, group_id: 1},
        {id: 5, name: 'Kind A', is_group: false, group_id: 1},
        {id: 6, name: 'Verwaist', is_group: false, group_id: 99},
    ];

    assert.deepEqual(
        buildMoneySourceTree(list).map(entry => `${entry.depth}:${entry.source.name}:${entry.childCount}:${entry.orphan}`),
        ['0:A einzeln:0:false', '0:Gruppe:2:false', '1:Kind B:0:false', '1:Kind A:0:false', '0:Verwaist:0:true']
    );
    // eingeklappte Gruppe behält die Zahl ihrer Quellen, zeigt sie aber nicht
    assert.deepEqual(
        buildMoneySourceTree(list, {1: true}).map(entry => `${entry.source.name}:${entry.childCount}`),
        ['A einzeln:0', 'Gruppe:2', 'Verwaist:0']
    );
    assert.deepEqual(buildMoneySourceTree(null), []);
});

test('summary ignores groups to avoid counting their sources twice', async () => {
    const {summarizeMoneySources} = await import('../../resources/js/Pages/MoneySources/moneySourceListTools.js');
    const summary = summarizeMoneySources([
        {is_group: true, amount: 5000, sumOfPositions: -700},
        {is_group: false, amount: 1000, sumOfPositions: -200},
        {is_group: false, amount: 3000, sumOfPositions: -500},
    ]);
    assert.deepEqual(summary, {count: 2, volume: 4000, remaining: 3300, used: 700, usedPercent: 18});
    assert.deepEqual(summarizeMoneySources([]), {count: 0, volume: 0, remaining: 0, used: 0, usedPercent: 0});
});

test('timeline accumulates positions per day in date order', async () => {
    const {buildRemainingTimeline} = await import('../../resources/js/Pages/MoneySources/moneySourceListTools.js');
    const timeline = buildRemainingTimeline(1000, [
        {created_at: '20.03.2026', type: 'COST', value: '100,50'},
        {created_at: '19.03.2026', type: 'COST', value: '200'},
        {created_at: '20.03.2026', type: 'EARNING', value: '50'},
        {created_at: 'unbekannt', type: 'COST', value: '999'},
    ]);
    assert.deepEqual(timeline.map(point => [point.date, point.remaining]), [['19.03.2026', 800], ['20.03.2026', 749.5]]);
    assert.deepEqual(buildRemainingTimeline(1000, []), []);
});
