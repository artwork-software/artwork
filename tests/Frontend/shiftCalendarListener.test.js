import assert from 'node:assert/strict';
import { register } from 'node:module';
import test from 'node:test';

register('./support/viteAliasHooks.mjs', import.meta.url);

/**
 * Minimaler Echo-Ersatz: merkt sich Handler pro Kanal/Event und kann Broadcasts zustellen.
 */
function createFakeEcho() {
    const handlers = new Map();
    const channel = (name) => ({
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
        private: channel,
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

function room(roomId, days, shifts = []) {
    const content = {};
    for (const day of days) content[day] = { eventIds: [], shiftIds: [] };
    const shiftsById = {};
    for (const shift of shifts) {
        shiftsById[shift.id] = shift;
        for (const day of days) {
            if (day === '08.06.2026') content[day].shiftIds.push(shift.id);
        }
    }
    return { roomId, roomName: `Raum ${roomId}`, content, shiftsById, eventsById: {} };
}

function shift(overrides = {}) {
    return {
        id: 7,
        startDate: '2026-06-08',
        endDate: '2026-06-08',
        start: '10:00',
        end: '14:00',
        break_minutes: 0,
        eventId: null,
        craftId: 1,
        roomId: 1,
        projectId: 3,
        workers: [],
        ...overrides,
    };
}

const days = ['08.06.2026', '09.06.2026'];

async function setup(options = {}) {
    globalThis.Echo = createFakeEcho();
    const { useShiftCalendarListener } = await import('../../resources/js/Composeables/Listener/useShiftCalendarListener.js');
    const data = { value: [room(1, days, [shift()]), room(2, days)] };
    const listener = useShiftCalendarListener(data, options);
    listener.init();
    return { data, listener, echo: globalThis.Echo };
}

test('room change moves the shift instead of leaving a ghost in the old room', async () => {
    const ctx = await setup();

    ctx.echo.emit('shift-plan.room.2', '.shift-created', { shift: shift({ roomId: 2 }), roomId: 2, previousRoomId: 1 });

    const [oldRoom, newRoom] = ctx.data.value;
    assert.equal(oldRoom.shiftsById[7], undefined);
    assert.deepEqual(oldRoom.content['08.06.2026'].shiftIds, []);
    assert.equal(newRoom.shiftsById[7].roomId, 2);
    assert.equal(newRoom.shiftsById[7].projectId, 3);
    assert.deepEqual(newRoom.content['08.06.2026'].shiftIds, [7]);
    assert.ok(oldRoom.__v > 0, 'old room cache key must be bumped');
});

test('broadcast on the old room channel clears the shift even if the new room is not loaded', async () => {
    const ctx = await setup();

    ctx.echo.emit('shift-plan.room.1', '.shift-created', { shift: shift({ roomId: 99 }), roomId: 99, previousRoomId: 1 });

    const [oldRoom] = ctx.data.value;
    assert.equal(oldRoom.shiftsById[7], undefined);
    assert.deepEqual(oldRoom.content['08.06.2026'].shiftIds, []);
});

test('date change within the same room removes the old day', async () => {
    const ctx = await setup();

    ctx.echo.emit('shift-plan.room.1', '.shift-created', {
        shift: shift({ startDate: '2026-06-09', endDate: '2026-06-09' }),
        roomId: 1,
    });

    const [sameRoom] = ctx.data.value;
    assert.deepEqual(sameRoom.content['08.06.2026'].shiftIds, []);
    assert.deepEqual(sameRoom.content['09.06.2026'].shiftIds, [7]);
});

test('project filter drops shifts that moved to another project', async () => {
    const ctx = await setup({ projectFilterId: () => 3 });

    ctx.echo.emit('shift-plan.room.1', '.shift-created', { shift: shift({ projectId: 4 }), roomId: 1 });

    const [sameRoom] = ctx.data.value;
    assert.equal(sameRoom.shiftsById[7], undefined);
    assert.deepEqual(sameRoom.content['08.06.2026'].shiftIds, []);
});

test('applyShiftUpdate applies the save response and reloads affected worker rows', async () => {
    const reloaded = [];
    const ctx = await setup({ onWorkerNeedReload: (id, type) => reloaded.push(`${type}:${id}`) });
    ctx.data.value[0].shiftsById[7].workers = [{ id: 5, type: 'user' }];

    // Gewerkwechsel: Server entfernt alle Personen — die bisherige Person muss nachgeladen werden
    ctx.listener.applyShiftUpdate({ shift: shift({ roomId: 2, craftId: 2, workers: [] }), roomId: 2 });

    assert.equal(ctx.data.value[1].shiftsById[7].craftId, 2);
    assert.deepEqual(reloaded, ['user:5']);
});

test('dispose removes every handler registered by the instance', async () => {
    const ctx = await setup();
    assert.ok(ctx.echo.handlerCount() > 0);

    ctx.listener.dispose();

    assert.equal(ctx.echo.handlerCount(), 0);
});
