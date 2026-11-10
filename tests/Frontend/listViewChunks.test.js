import assert from 'node:assert/strict';
import test from 'node:test';
import {
    addDays,
    chunkRanges,
    chunksOwningDays,
    chunksToLoadNow,
    mergeDayChunk,
    ownedRange,
    prioritizeChunks,
    rangesNearVisibleDays,
} from '../../resources/js/Helper/listViewChunks.js';

test('addDays crosses month, year and DST boundaries', () => {
    assert.equal(addDays('2026-10-24', 7), '2026-10-31');
    assert.equal(addDays('2026-12-30', 3), '2027-01-02');
    assert.equal(addDays('2026-03-28', 1), '2026-03-29');
    assert.equal(addDays('2026-03-29', 1), '2026-03-30');
});

test('chunkRanges covers the period without gaps or overlaps', () => {
    assert.deepEqual(chunkRanges('2026-07-08', '2026-07-24', 7), [
        { start: '2026-07-08', end: '2026-07-14' },
        { start: '2026-07-15', end: '2026-07-21' },
        { start: '2026-07-22', end: '2026-07-24' },
    ]);
    assert.deepEqual(chunkRanges('2026-07-25', '2026-07-24', 7), []);
    assert.deepEqual(chunkRanges('2026-07-24', '2026-07-24', 7), [{ start: '2026-07-24', end: '2026-07-24' }]);
});

test('first and last chunk own the days outside the period', () => {
    assert.deepEqual(ownedRange({ start: '2026-07-01', end: '2026-07-07' }, '2026-07-01', '2026-07-24'), { from: null, to: '2026-07-07' });
    assert.deepEqual(ownedRange({ start: '2026-07-08', end: '2026-07-14' }, '2026-07-01', '2026-07-24'), { from: '2026-07-08', to: '2026-07-14' });
    assert.deepEqual(ownedRange({ start: '2026-07-22', end: '2026-07-24' }, '2026-07-01', '2026-07-24'), { from: '2026-07-22', to: null });
    assert.deepEqual(ownedRange({ start: '2026-07-01', end: '2026-07-24' }, '2026-07-01', '2026-07-24'), { from: null, to: null });
});

test('mergeDayChunk replaces exactly the owned days, drops vanished ones and keeps the rest sorted', () => {
    const loaded = [{ day: '2026-06-30', v: 'old-before' }, { day: '2026-07-02', v: 'old' }, { day: '2026-07-05', v: 'gone' }, { day: '2026-07-09', v: 'other' }];
    const chunk = [{ day: '2026-06-30', v: 'new-before' }, { day: '2026-07-02', v: 'new' }, { day: '2026-07-03', v: 'added' }];
    assert.deepEqual(mergeDayChunk(loaded, chunk, { from: null, to: '2026-07-07' }), [
        { day: '2026-06-30', v: 'new-before' },
        { day: '2026-07-02', v: 'new' },
        { day: '2026-07-03', v: 'added' },
        { day: '2026-07-09', v: 'other' },
    ]);
});

test('mergeDayChunk ignores days a middle chunk does not own', () => {
    const merged = mergeDayChunk([{ day: '2026-07-01' }], [{ day: '2026-07-08' }, { day: '2026-06-01' }], { from: '2026-07-08', to: '2026-07-14' });
    assert.deepEqual(merged.map((d) => d.day), ['2026-07-01', '2026-07-08']);
});

test('appending all chunks of a period in any order yields the full result', () => {
    const full = [{ day: '2026-06-29' }, { day: '2026-07-01' }, { day: '2026-07-09' }, { day: '2026-07-24' }, { day: '2026-07-30' }];
    const ranges = chunkRanges('2026-07-01', '2026-07-24', 7);
    const daysOf = (range) => {
        const owned = ownedRange(range, '2026-07-01', '2026-07-24');
        return full.filter((d) => (owned.from === null || d.day >= owned.from) && (owned.to === null || d.day <= owned.to));
    };
    for (const order of [ranges, [...ranges].reverse(), prioritizeChunks(ranges, ['2026-07-16'])]) {
        let local = [];
        for (const range of order) {
            local = mergeDayChunk(local, daysOf(range), ownedRange(range, '2026-07-01', '2026-07-24'));
        }
        assert.deepEqual(local, full);
    }
});

test('prioritizeChunks loads chunks with visible days first', () => {
    const ranges = chunkRanges('2026-07-01', '2026-07-24', 7);
    assert.deepEqual(prioritizeChunks(ranges, ['2026-07-16', '2026-07-17']).map((r) => r.start), ['2026-07-15', '2026-07-01', '2026-07-08', '2026-07-22']);
    assert.deepEqual(prioritizeChunks(ranges, []).map((r) => r.start), ['2026-07-01', '2026-07-08', '2026-07-15', '2026-07-22']);
});

// Sechs Monate in Wochenblöcken (26 Blöcke) — nach einer Aktion sollen nur wenige sofort laden
const HALF_YEAR = { periodStart: '2026-07-01', periodEnd: '2026-12-29' };
const halfYearRanges = chunkRanges(HALF_YEAR.periodStart, HALF_YEAR.periodEnd, 7);
const starts = (ranges) => ranges.map((range) => range.start);
// Jeder zweite Tag hat Schichten (gerenderte Tageszeilen)
const everyOtherDay = [];
for (let day = HALF_YEAR.periodStart; day <= HALF_YEAR.periodEnd; day = addDays(day, 2)) {
    everyOtherDay.push(day);
}

test('a refresh loads only the chunks in view instead of all 26', () => {
    assert.equal(halfYearRanges.length, 26);
    const due = chunksToLoadNow(halfYearRanges, {
        ...HALF_YEAR,
        visibleDays: ['2026-10-15', '2026-10-17'],
        renderedDays: everyOtherDay,
    });

    assert.deepEqual(starts(due), ['2026-10-14']);
});

test('chunks next to the visible area load too while no rendered day lies in between', () => {
    // sichtbar 15.07.; davor gerendert erst wieder 01.07. — die leeren Tage dazwischen grenzen an die Sicht
    const near = rangesNearVisibleDays(halfYearRanges, {
        ...HALF_YEAR,
        visibleDays: ['2026-07-15'],
        renderedDays: ['2026-07-01', '2026-07-15', '2026-07-17'],
    });

    // 01.07.–07.07.: Tage nach der gerenderten 01.07.-Zeile liegen direkt über der Sicht
    assert.deepEqual(starts(near), ['2026-07-01', '2026-07-08', '2026-07-15']);
});

test('scrolled to the last rendered day, the empty trailing weeks and the open last chunk load too', () => {
    const near = rangesNearVisibleDays(halfYearRanges, {
        ...HALF_YEAR,
        visibleDays: ['2026-12-01'],
        renderedDays: ['2026-11-29', '2026-12-01'],
    });

    assert.deepEqual(starts(near), ['2026-11-25', '2026-12-02', '2026-12-09', '2026-12-16', '2026-12-23']);
});

test('the first chunk owns days before the period, so a visible earlier day keeps it near', () => {
    const near = rangesNearVisibleDays(halfYearRanges, {
        ...HALF_YEAR,
        visibleDays: ['2026-06-29'],
        renderedDays: ['2026-06-29', '2026-07-03'],
    });

    assert.deepEqual(starts(near), ['2026-07-01']);
});

test('without visible days nothing loads, unless nothing is rendered at all', () => {
    const view = { ...HALF_YEAR, visibleDays: [] };

    assert.deepEqual(rangesNearVisibleDays(halfYearRanges, { ...view, renderedDays: ['2026-07-01'] }), []);
    assert.equal(rangesNearVisibleDays(halfYearRanges, { ...view, renderedDays: [] }).length, 26);
});

test('affected days load their chunks at once, visible chunks first', () => {
    const due = chunksToLoadNow(halfYearRanges, {
        ...HALF_YEAR,
        visibleDays: ['2026-10-15'],
        renderedDays: everyOtherDay,
        affectedDays: ['2026-07-02', '2027-01-15', null],
    });

    assert.deepEqual(starts(due), ['2026-10-14', '2026-07-01', '2026-12-23']);
});

test('chunksOwningDays maps days to their chunks, including days outside the period', () => {
    const owning = chunksOwningDays(
        halfYearRanges,
        ['2026-06-01', '2026-08-20', '2027-03-01'],
        HALF_YEAR.periodStart,
        HALF_YEAR.periodEnd
    );

    assert.deepEqual(starts(owning), ['2026-07-01', '2026-08-19', '2026-12-23']);
    assert.deepEqual(chunksOwningDays(halfYearRanges, [], HALF_YEAR.periodStart, HALF_YEAR.periodEnd), []);
});

test('loading due chunks now and stale ones while scrolling ends in exactly the server result', () => {
    const { periodStart, periodEnd } = HALF_YEAR;
    const daysOf = (data, range) => {
        const owned = ownedRange(range, periodStart, periodEnd);
        return data.filter((d) => (owned.from === null || d.day >= owned.from) && (owned.to === null || d.day <= owned.to));
    };
    const before = [{ day: '2026-06-30', v: 1 }, ...everyOtherDay.map((day) => ({ day, v: 1 })), { day: '2026-12-31', v: 1 }];
    // Server nach der Aktion: ein Tag entfallen, ein Tag neu, ein Tag geändert, Tag nach dem Zeitraum entfallen
    const after = before
        .filter((d) => d.day !== '2026-09-02' && d.day !== '2026-12-31')
        .map((d) => (d.day === '2026-11-11' ? { ...d, v: 2 } : d))
        .concat({ day: '2026-08-12', v: 2 })
        .sort((a, b) => (a.day < b.day ? -1 : 1));

    let local = before;
    const stale = new Set(starts(halfYearRanges));
    const load = (range) => {
        local = mergeDayChunk(local, daysOf(after, range), ownedRange(range, periodStart, periodEnd));
        stale.delete(range.start);
    };
    const view = (visibleDays) => ({ periodStart, periodEnd, visibleDays, renderedDays: local.map((d) => d.day) });

    chunksToLoadNow(halfYearRanges, { ...view(['2026-07-15']), affectedDays: ['2026-09-02'] }).forEach(load);
    // sichtbarer Block, sein Vorgänger (leerer 14.07. grenzt an die Sicht) und der betroffene — statt 26
    assert.deepEqual(starts(halfYearRanges.filter((range) => !stale.has(range.start))), ['2026-07-08', '2026-07-15', '2026-09-02']);

    // Von oben nach unten durch die Liste scrollen: jede gerenderte Tageszeile ist einmal allein in Sicht
    for (let index = 0; index < local.length; index++) {
        const day = local[index].day;
        rangesNearVisibleDays(halfYearRanges.filter((range) => stale.has(range.start)), view([day])).forEach(load);
    }

    assert.equal(stale.size, 0);
    assert.deepEqual(local, after);
});
