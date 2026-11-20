import test from 'node:test';
import assert from 'node:assert/strict';
import { useAutosaveForm } from '../../resources/js/Composeables/useAutosaveForm.js';

// Nachbau eines Inertia-Formulars: Daten, gespeicherter Stand und die gesendeten Anfragen
function fakeForm(initial) {
    const form = {
        values: { ...initial },
        saved: { ...initial },
        errors: {},
        requests: [],
        data: () => ({ ...form.values }),
        get isDirty() {
            return JSON.stringify(form.values) !== JSON.stringify(form.saved);
        },
        defaults: (values) => {
            form.saved = { ...(values ?? form.values) };
        },
        clearErrors: () => {},
        patch: (url, options) => form.requests.push({ url, options, sent: { ...form.values } }),
    };

    return form;
}

test('ohne Änderung wird nichts gesendet', () => {
    const form = fakeForm({ name: 'Anna', phone: '' });
    useAutosaveForm(form, () => '/save').save();

    assert.equal(form.requests.length, 0);
});

test('was während des Speicherns getippt wird, geht hinterher und gilt nicht als gespeichert', () => {
    const form = fakeForm({ name: 'Anna', phone: '' });
    const { save, saved } = useAutosaveForm(form, () => '/save');

    form.values.name = 'Anne';
    save();
    // Eingabe im nächsten Feld, bevor die Antwort da ist
    form.values.phone = '040';
    form.requests[0].options.onSuccess();

    assert.equal(form.requests.length, 2);
    assert.deepEqual(form.requests[1].sent, { name: 'Anne', phone: '040' });
    assert.equal(saved.value, false);

    form.requests[1].options.onSuccess();

    assert.equal(form.isDirty, false);
    assert.equal(form.requests.length, 2);
    assert.equal(saved.value, true);
});

test('nach einem erfolgreichen Speichern löst erneutes Verlassen keine Anfrage aus', () => {
    const form = fakeForm({ name: 'Anna' });
    const { save } = useAutosaveForm(form, () => '/save');

    form.values.name = 'Anne';
    save();
    form.requests[0].options.onSuccess();
    save();

    assert.equal(form.requests.length, 1);
});
