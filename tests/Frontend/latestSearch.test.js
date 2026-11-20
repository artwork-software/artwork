import test from 'node:test';
import assert from 'node:assert/strict';
import { createLatestSearch } from '../../resources/js/Composeables/latestSearch.js';

// Zeitgeber von Hand: Rückrufe sammeln und gezielt auslösen
function manualTimers() {
    const pending = new Map();
    let id = 0;

    return {
        setTimer: (callback) => {
            pending.set(++id, callback);
            return id;
        },
        clearTimer: (timerId) => pending.delete(timerId),
        runAll: async () => {
            const callbacks = [...pending.values()];
            pending.clear();
            await Promise.all(callbacks.map((callback) => callback()));
        },
        count: () => pending.size,
    };
}

test('mehrere Tastendrücke lösen nur eine Anfrage aus', async () => {
    const timers = manualTimers();
    const queries = [];
    const search = createLatestSearch({
        fetch: async (query) => { queries.push(query); return [query]; },
        onResults: () => {},
        ...timers,
    });

    search('a');
    search('at');
    search('atl');
    assert.equal(timers.count(), 1);
    await timers.runAll();

    assert.deepEqual(queries, ['atl']);
});

test('eine ältere Antwort überschreibt die neuere nicht', async () => {
    const timers = manualTimers();
    const resolvers = {};
    const received = [];
    const search = createLatestSearch({
        fetch: (query) => new Promise((resolve) => { resolvers[query] = resolve; }),
        onResults: (results) => received.push(results),
        ...timers,
    });

    search('alt');
    const first = timers.runAll();
    search('neu');
    const second = timers.runAll();
    resolvers.neu(['neu']);
    resolvers.alt(['alt']);
    await Promise.all([first, second]);

    assert.deepEqual(received, [['neu']]);
});

test('leere Eingabe ruft onClear und bricht die wartende Suche ab', async () => {
    const timers = manualTimers();
    let cleared = 0;
    const received = [];
    const search = createLatestSearch({
        fetch: async (query) => [query],
        onResults: (results) => received.push(results),
        onClear: () => { cleared++; },
        ...timers,
    });

    search('abc');
    search('   ');
    await timers.runAll();

    assert.equal(cleared, 1);
    assert.deepEqual(received, []);
});

test('der Suchbegriff wird ohne Leerzeichen am Rand gesucht', async () => {
    const timers = manualTimers();
    const queries = [];
    const search = createLatestSearch({
        fetch: async (query) => { queries.push(query); return []; },
        onResults: () => {},
        ...timers,
    });

    search('  atlas ');
    await timers.runAll();

    assert.deepEqual(queries, ['atlas']);
});

test('ein Fehler der Anfrage lässt die Suche weiterarbeiten', async () => {
    const timers = manualTimers();
    const received = [];
    let fail = true;
    const search = createLatestSearch({
        fetch: async (query) => {
            if (fail) throw new Error('Netzwerk');
            return [query];
        },
        onResults: (results) => received.push(results),
        ...timers,
    });

    search('eins');
    await timers.runAll();
    fail = false;
    search('zwei');
    await timers.runAll();

    assert.deepEqual(received, [['zwei']]);
});
