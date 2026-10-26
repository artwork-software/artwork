import test from 'node:test';
import assert from 'node:assert/strict';
import {attachModalDrag} from '../../resources/js/Helper/draggableModal.js';

class CountingTarget extends EventTarget {
    constructor() {
        super();
        this.active = new Map();
    }

    addEventListener(type, listener) {
        this.active.set(type, (this.active.get(type) ?? 0) + 1);
        super.addEventListener(type, listener);
    }

    removeEventListener(type, listener) {
        if (this.active.get(type)) {
            this.active.set(type, this.active.get(type) - 1);
        }
        super.removeEventListener(type, listener);
    }

    count(type) {
        return this.active.get(type) ?? 0;
    }
}

function mouse(type, clientX = 0, clientY = 0) {
    const event = new Event(type);
    event.clientX = clientX;
    event.clientY = clientY;
    return event;
}

function fakeDocument() {
    const doc = new CountingTarget();
    const classes = new Set();
    doc.body = {classList: {add: (c) => classes.add(c), remove: (c) => classes.delete(c)}};
    doc.classes = classes;
    return doc;
}

test('document listeners exist only while dragging', () => {
    const doc = fakeDocument();
    const handle = new CountingTarget();
    const container = {offsetLeft: 10, offsetTop: 20, style: {}};

    attachModalDrag(container, handle, doc);
    assert.equal(doc.count('mousemove'), 0);

    handle.dispatchEvent(mouse('mousedown', 15, 25));
    assert.equal(doc.count('mousemove'), 1);
    assert.equal(doc.count('mouseup'), 1);

    doc.dispatchEvent(mouse('mouseup'));
    assert.equal(doc.count('mousemove'), 0);
    assert.equal(doc.count('mouseup'), 0);
});

test('reopening many times does not pile up document listeners', () => {
    const doc = fakeDocument();
    const container = {offsetLeft: 0, offsetTop: 0, style: {}};
    let detach = null;

    for (let i = 0; i < 25; i++) {
        detach?.();
        const handle = new CountingTarget();
        detach = attachModalDrag(container, handle, doc);
        handle.dispatchEvent(mouse('mousedown'));
        doc.dispatchEvent(mouse('mouseup'));
    }

    assert.equal(doc.count('mousemove'), 0);
    assert.equal(doc.count('mouseup'), 0);
});

test('cleanup during a drag removes everything', () => {
    const doc = fakeDocument();
    const handle = new CountingTarget();
    const detach = attachModalDrag({offsetLeft: 0, offsetTop: 0, style: {}}, handle, doc);

    handle.dispatchEvent(mouse('mousedown'));
    detach();

    assert.equal(doc.count('mousemove'), 0);
    assert.equal(handle.count('mousedown'), 0);
    assert.equal(doc.classes.has('select-none'), false);
});

test('dragging moves the container by the grab offset', async () => {
    const doc = fakeDocument();
    const handle = new CountingTarget();
    const container = {offsetLeft: 100, offsetTop: 50, style: {}};
    attachModalDrag(container, handle, doc);

    handle.dispatchEvent(mouse('mousedown', 110, 60));
    doc.dispatchEvent(mouse('mousemove', 210, 160));
    await new Promise((resolve) => setTimeout(resolve, 5));

    assert.equal(container.style.left, '200px');
    assert.equal(container.style.top, '150px');
    assert.equal(doc.classes.has('select-none'), true);
});

test('missing elements are tolerated', () => {
    assert.doesNotThrow(() => attachModalDrag(null, null, fakeDocument())());
});
