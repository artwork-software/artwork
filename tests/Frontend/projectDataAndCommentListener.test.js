import assert from 'node:assert/strict';
import test from 'node:test';

/**
 * Echo-Ersatz mit connector.channels wie laravel-echo (Schlüssel 'private-<name>'): private() legt
 * den Kanal an, stopListening entfernt genau den übergebenen Handler.
 */
function createFakeEcho() {
    const handlers = new Map();
    const channels = {};
    const makeChannel = (name) => ({
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
    });

    return {
        connector: { channels },
        private(name) {
            channels['private-' + name] ??= makeChannel(name);
            return channels['private-' + name];
        },
        emit(name, event, data) {
            return Promise.all((handlers.get(`${name}|${event}`) ?? []).map((handler) => handler(data)));
        },
        handlerCount() {
            let count = 0;
            for (const list of handlers.values()) count += list.length;
            return count;
        },
    };
}

function deferred() {
    let resolve;
    const promise = new Promise((res) => { resolve = res; });
    return { promise, resolve };
}

const flush = () => new Promise((resolve) => setImmediate(resolve));

async function loadDataListener() {
    globalThis.Echo = createFakeEcho();
    return import('../../resources/js/Composeables/Listener/useProjectDataListener.js');
}

async function loadCommentListener() {
    globalThis.Echo = createFakeEcho();
    return import('../../resources/js/Composeables/Listener/useCommentListener.js');
}

test('data listener reloads only its own component and applies the fetched value', async () => {
    const { useProjectDataListener } = await loadDataListener();
    const component = { id: 5, project_value: null };
    const fetched = [];
    const listener = useProjectDataListener(component, 7, {
        fetchValue: async (projectId, componentId) => {
            fetched.push([projectId, componentId]);
            return { id: 1, project_id: 7, component_id: 5, data: { text: 'neu' } };
        },
    });
    listener.init();

    await Echo.emit('project.7', '.data.updated', { data: { id: 2, project_id: 7, component_id: 6 } });
    assert.deepEqual(fetched, []);

    await Echo.emit('project.7', '.data.updated', { data: { id: 1, project_id: 7, component_id: 5 } });
    assert.deepEqual(fetched, [[7, 5]]);
    assert.deepEqual(component.project_value.data, { text: 'neu' });
    listener.stop();
});

test('data listener ignores a forbidden reload and only applies the latest response', async () => {
    const { useProjectDataListener } = await loadDataListener();
    const component = { id: 5, project_value: { id: 1, component_id: 5, data: { text: 'alt' } } };
    const first = deferred();
    const second = deferred();
    const responses = [first.promise, second.promise];
    const listener = useProjectDataListener(component, 7, { fetchValue: () => responses.shift() });
    listener.init();

    const event = { data: { id: 1, project_id: 7, component_id: 5 } };
    const firstRun = Echo.emit('project.7', '.data.updated', event);
    const secondRun = Echo.emit('project.7', '.data.updated', event);
    second.resolve({ id: 1, component_id: 5, data: { text: 'neuer' } });
    await flush();
    first.resolve({ id: 1, component_id: 5, data: { text: 'veraltet' } });
    await Promise.all([firstRun, secondRun]);

    assert.equal(component.project_value.data.text, 'neuer');

    const failing = useProjectDataListener(component, 7, { fetchValue: () => Promise.reject(new Error('403')) });
    failing.init();
    await Echo.emit('project.7', '.data.updated', event);
    assert.equal(component.project_value.data.text, 'neuer');
    listener.stop();
    failing.stop();
});

test('stop removes only the own handler and never creates a subscription', async () => {
    const { useProjectDataListener } = await loadDataListener();
    const listenerA = useProjectDataListener({ id: 5, project_value: null }, 7, { fetchValue: async () => null });
    const listenerB = useProjectDataListener({ id: 6, project_value: null }, 7, { fetchValue: async () => null });
    listenerA.init();
    listenerB.init();
    assert.equal(Echo.handlerCount(), 2);

    listenerA.stop();
    assert.equal(Echo.handlerCount(), 1);

    // Kanal unbekannt (z.B. an anderer Stelle verlassen): stop() darf kein neues Abo anlegen
    const orphan = useProjectDataListener({ id: 9, project_value: null }, 99, { fetchValue: async () => null });
    orphan.init();
    delete Echo.connector.channels['private-project.99'];
    orphan.stop();
    assert.equal(Echo.connector.channels['private-project.99'], undefined);
    listenerB.stop();
});

test('saved value is applied locally and synced to other instances of the same component', async () => {
    const { useProjectDataListener } = await loadDataListener();
    const sidebar = { id: 5, project_value: null };
    const mainTab = { id: 5, project_value: { id: 1, component_id: 5, data: { links: [] } } };
    const otherComponent = { id: 6, project_value: null };
    const pending = deferred();
    const newerForeignValue = { id: 1, component_id: 5, data: { links: [{ label: 'B', url: 'https://b.test' }] } };
    const responses = [pending.promise, Promise.resolve(newerForeignValue)];
    const sidebarListener = useProjectDataListener(() => sidebar, 7, { fetchValue: () => responses.shift() });
    const mainListener = useProjectDataListener(() => mainTab, 7, { fetchValue: async () => null });
    const otherListener = useProjectDataListener(() => otherComponent, 7, { fetchValue: async () => null });
    [sidebarListener, mainListener, otherListener].forEach((listener) => listener.init());

    const reload = Echo.emit('project.7', '.data.updated', { data: { id: 1, project_id: 7, component_id: 5 } });
    const savedValue = { id: 1, project_id: 7, component_id: 5, data: { links: [{ label: 'A', url: 'https://a.test' }] } };
    sidebarListener.saved(savedValue);

    assert.deepEqual(sidebar.project_value.data, savedValue.data);
    assert.deepEqual(mainTab.project_value.data, savedValue.data);
    assert.equal(otherComponent.project_value, null);

    // Die verworfene (ältere) Antwort wird nicht übernommen, aber einmal nachgeholt – der Server
    // kann inzwischen einen neueren fremden Stand haben
    pending.resolve({ id: 1, component_id: 5, data: { links: [] } });
    await reload;
    assert.deepEqual(sidebar.project_value.data, newerForeignValue.data);
    assert.equal(responses.length, 0);

    mainListener.stop();
    sidebarListener.saved({ ...savedValue, data: { links: [] } });
    assert.deepEqual(mainTab.project_value.data, savedValue.data);
    sidebarListener.stop();
    otherListener.stop();
});

test('getter follows component objects replaced by Inertia visits', async () => {
    const { useProjectDataListener } = await loadDataListener();
    const props = { data: { id: 5, project_value: null } };
    const listener = useProjectDataListener(() => props.data, 7, {
        fetchValue: async () => ({ id: 1, component_id: 5, data: { text: 'live' } }),
    });
    listener.init();

    const replaced = { id: 5, project_value: null };
    const original = props.data;
    props.data = replaced;
    await Echo.emit('project.7', '.data.updated', { data: { id: 1, project_id: 7, component_id: 5 } });

    assert.equal(replaced.project_value.data.text, 'live');
    assert.equal(original.project_value, null);
    listener.stop();
});

test('network errors are retried once, forbidden responses are not', async () => {
    const { useProjectDataListener } = await loadDataListener();
    const component = { id: 5, project_value: null };
    let calls = 0;
    const networkFailingOnce = useProjectDataListener(() => component, 7, {
        fetchValue: async () => {
            calls++;
            if (calls === 1) throw new Error('Network Error');
            return { id: 1, component_id: 5, data: { text: 'nach Wiederholung' } };
        },
    });
    networkFailingOnce.init();
    await Echo.emit('project.7', '.data.updated', { data: { id: 1, project_id: 7, component_id: 5 } });
    assert.equal(calls, 2);
    assert.equal(component.project_value.data.text, 'nach Wiederholung');
    networkFailingOnce.stop();

    let forbiddenCalls = 0;
    const forbidden = useProjectDataListener(() => component, 7, {
        fetchValue: async () => {
            forbiddenCalls++;
            throw Object.assign(new Error('Forbidden'), { response: { status: 403 } });
        },
    });
    forbidden.init();
    await Echo.emit('project.7', '.data.updated', { data: { id: 1, project_id: 7, component_id: 5 } });
    assert.equal(forbiddenCalls, 1);
    assert.equal(component.project_value.data.text, 'nach Wiederholung');
    forbidden.stop();
});

test('a value synced from another instance discards an older running reload', async () => {
    const { useProjectDataListener } = await loadDataListener();
    const sidebar = { id: 5, project_value: { id: 1, component_id: 5, data: { text: 'alt' } } };
    const mainTab = { id: 5, project_value: { id: 1, component_id: 5, data: { text: 'alt' } } };
    const pending = deferred();
    const responses = [pending.promise, Promise.resolve({ id: 1, component_id: 5, data: { text: 'Server' } })];
    const sidebarListener = useProjectDataListener(() => sidebar, 7, { fetchValue: () => responses.shift() });
    const mainListener = useProjectDataListener(() => mainTab, 7, { fetchValue: async () => null });
    sidebarListener.init();
    mainListener.init();

    const reload = Echo.emit('project.7', '.data.updated', { data: { id: 1, project_id: 7, component_id: 5 } });
    mainListener.saved({ id: 1, component_id: 5, data: { text: 'gespeichert' } });
    assert.equal(sidebar.project_value.data.text, 'gespeichert');

    // Ältere Antwort wird verworfen und einmal nachgeholt (liefert den aktuellen Serverstand)
    pending.resolve({ id: 1, component_id: 5, data: { text: 'veraltet' } });
    await reload;
    assert.equal(sidebar.project_value.data.text, 'Server');
    sidebarListener.stop();
    mainListener.stop();
});

test('an own save older than the displayed value (updated_at) is not applied', async () => {
    const { useProjectDataListener, isOlderThanDisplayed } = await loadDataListener();
    const component = {
        id: 5,
        project_value: { id: 1, component_id: 5, data: { text: 'fremd, neuer' }, updated_at: '2026-10-06T10:00:05.000000Z' },
    };
    const listener = useProjectDataListener(() => component, 7, { fetchValue: async () => null });
    listener.init();

    const accepted = listener.saved({ id: 1, component_id: 5, data: { text: 'eigen' }, updated_at: '2026-10-06T10:00:01.000000Z' });
    assert.equal(accepted, false);
    assert.equal(component.project_value.data.text, 'fremd, neuer');

    assert.equal(listener.saved({ id: 1, component_id: 5, data: { text: 'eigen, neuer' }, updated_at: '2026-10-06T10:00:09.000000Z' }), true);
    assert.equal(component.project_value.data.text, 'eigen, neuer');

    // Ohne updated_at wird nicht verglichen
    assert.equal(isOlderThanDisplayed({}, { updated_at: '2026-10-06T10:00:00Z' }), false);
    listener.stop();
});

test('same updated_at with different data is applied and followed by a reload', async () => {
    const { useProjectDataListener } = await loadDataListener();
    const component = {
        id: 5,
        project_value: { id: 1, component_id: 5, data: { text: 'fremd' }, updated_at: '2026-10-06T10:00:05.000000Z' },
    };
    let fetches = 0;
    const listener = useProjectDataListener(() => component, 7, {
        fetchValue: async () => {
            fetches++;
            return { id: 1, component_id: 5, data: { text: 'Server' }, updated_at: '2026-10-06T10:00:05.000000Z' };
        },
    });
    listener.init();

    assert.equal(listener.saved({ id: 1, component_id: 5, data: { text: 'eigen' }, updated_at: '2026-10-06T10:00:05.000000Z' }), true);
    assert.equal(component.project_value.data.text, 'eigen');
    await flush();
    assert.equal(fetches, 1);
    assert.equal(component.project_value.data.text, 'Server');

    // Gleicher Zeitstempel, gleiche Daten: kein Nachladen
    listener.saved({ id: 1, component_id: 5, data: { text: 'Server' }, updated_at: '2026-10-06T10:00:05.000000Z' });
    await flush();
    assert.equal(fetches, 1);
    listener.stop();
});

test('comment listener batches bursts and stops cleanly', async (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const { useCommentListener } = await loadCommentListener();
    const calls = [];
    const listener = useCommentListener(7, (changes) => calls.push(changes), { debounceMs: 300 });
    const otherHandler = () => {};
    Echo.private('project.7').listen('.checklist.updated', otherHandler);
    listener.init();

    Echo.emit('project.7', '.comment.add', { comment: { id: 1, project_id: 7, tab_id: 3 } });
    Echo.emit('project.7', '.comment.delete', { comment: { id: 2, project_id: 7, tab_id: null } });
    t.mock.timers.tick(299);
    assert.equal(calls.length, 0);
    t.mock.timers.tick(1);
    assert.deepEqual(calls, [[
        { type: 'add', comment: { id: 1, project_id: 7, tab_id: 3 } },
        { type: 'delete', comment: { id: 2, project_id: 7, tab_id: null } },
    ]]);

    Echo.emit('project.7', '.comment.add', { comment: { id: 3, project_id: 7, tab_id: null } });
    listener.stop();
    t.mock.timers.tick(500);
    assert.equal(calls.length, 1);
    // Fremde Handler auf dem geteilten Kanal bleiben bestehen
    assert.equal(Echo.handlerCount(), 1);
});
