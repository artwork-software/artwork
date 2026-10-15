import assert from 'node:assert/strict';
import test from 'node:test';
import { addDays, chunkRanges, mergeDayChunk, ownedRange, prioritizeChunks } from '../../resources/js/Helper/listViewChunks.js';

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
