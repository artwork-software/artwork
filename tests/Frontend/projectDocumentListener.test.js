import assert from 'node:assert/strict';
import test from 'node:test';

const listenerModule = '../../resources/js/Composeables/Listener/useProjectDocumentListener.js';

/**
 * Minimaler Echo-Ersatz: merkt sich Handler pro Kanal/Event, führt wie Echo die abonnierten Kanäle
 * unter connector.channels ('private-…') und kann Broadcasts zustellen.
 */
function createFakeEcho() {
    const handlers = new Map();
    const channels = {};
    const channel = (name) => {
        channels['private-' + name] ??= {
            listen(event, handler) {
                const key = `${name}|${event}`;
                if (!handlers.has(key)) handlers.set(key, []);
                handlers.get(key).push(handler);
                return this;
            },
            stopListening(event, handler) {
                const key = `${name}|${event}`;
                handlers.set(key, (handlers.get(key) ?? []).filter((h) => h !== handler));
                return this;
            },
        };
        return channels['private-' + name];
    };

    return {
        connector: { channels },
        private: channel,
        leave(name) {
            delete channels['private-' + name];
            for (const key of [...handlers.keys()]) {
                if (key.startsWith(name + '|')) handlers.delete(key);
            }
        },
        emit(name, event, data) {
            for (const handler of handlers.get(`${name}|${event}`) ?? []) handler(data);
        },
        handlerCount() {
            let count = 0;
            for (const list of handlers.values()) count += list.length;
            return count;
        },
    };
}

async function setup(options = {}) {
    globalThis.Echo = createFakeEcho();
    const { useProjectDocumentListener } = await import(listenerModule);
    const reloads = [];
    const listener = useProjectDocumentListener(7, (documents) => reloads.push(documents), options);
    listener.init();
    return { listener, reloads, echo: globalThis.Echo };
}

test('several broadcasts in a row trigger one debounced reload with the ids only', async (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const { reloads, echo } = await setup();

    echo.emit('project.7', '.document.add', { document: { id: 11, tab_id: 3, project_id: 7 } });
    echo.emit('project.7', '.document.add', { document: { id: 12, tab_id: 3, project_id: 7 } });
    echo.emit('project.7', '.document.delete', { document: { id: 13, tab_id: null, project_id: 7 } });
    t.mock.timers.tick(299);
    assert.deepEqual(reloads, []);

    t.mock.timers.tick(1);
    assert.deepEqual(reloads, [[
        { id: 11, tab_id: 3, project_id: 7 },
        { id: 12, tab_id: 3, project_id: 7 },
        { id: 13, tab_id: null, project_id: 7 },
    ]]);
});

test('broadcasts of other projects and irrelevant tabs are ignored', async (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const { isDocumentInScope } = await import(listenerModule);
    const { reloads, echo } = await setup({ isRelevant: isDocumentInScope([3, '4']) });

    echo.emit('project.8', '.document.add', { document: { id: 1, tab_id: 3, project_id: 8 } });
    echo.emit('project.7', '.document.add', { document: { id: 2, tab_id: 5, project_id: 7 } });
    echo.emit('project.7', '.document.add', { document: { id: 3, tab_id: null, project_id: 7 } });
    t.mock.timers.tick(300);
    assert.deepEqual(reloads, []);

    echo.emit('project.7', '.document.delete', { document: { id: 4, tab_id: 4, project_id: 7 } });
    t.mock.timers.tick(300);
    assert.deepEqual(reloads, [[{ id: 4, tab_id: 4, project_id: 7 }]]);
});

test('without a known scope every document is relevant', async () => {
    const { isDocumentInScope } = await import(listenerModule);

    assert.equal(isDocumentInScope(undefined)({ id: 1, tab_id: null }), true);
    assert.equal(isDocumentInScope([])({ id: 1, tab_id: 2 }), false);
});

test('stop removes both handlers and drops a pending reload', async (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const { listener, echo, reloads } = await setup();
    assert.equal(echo.handlerCount(), 2);

    echo.emit('project.7', '.document.add', { document: { id: 1, tab_id: null, project_id: 7 } });
    listener.stop();
    t.mock.timers.tick(300);
    echo.emit('project.7', '.document.add', { document: { id: 2, tab_id: null, project_id: 7 } });
    t.mock.timers.tick(300);

    assert.equal(echo.handlerCount(), 0);
    assert.deepEqual(reloads, []);
});

test('stop does not re-subscribe a channel that was already left', async () => {
    const { listener, echo } = await setup();
    echo.leave('project.7');

    listener.stop();

    assert.equal(echo.connector.channels['private-project.7'], undefined);
});

test('only the latest request may apply its response', async () => {
    const { createLatestRequestTracker } = await import(listenerModule);
    const tracker = createLatestRequestTracker();

    const first = tracker.begin();
    const second = tracker.begin();

    assert.equal(tracker.isLatest(first), false);
    assert.equal(tracker.isLatest(second), true);
});
