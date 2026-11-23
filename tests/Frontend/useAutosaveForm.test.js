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
        // wie bei Inertia: ein Feld, das erst ein Watcher zeitversetzt nachzieht (hier: nie)
        isDirty: true,
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

test('was während des Speicherns getippt wird, geht hinterher und gilt nicht als gespeichert', async () => {
    const form = fakeForm({ name: 'Anna', phone: '' });
    const { save, saved } = useAutosaveForm(form, () => '/save');

    form.values.name = 'Anne';
    save();
    // Eingabe im nächsten Feld, bevor die Antwort da ist
    form.values.phone = '040';
    form.requests[0].options.onSuccess();
    // die Folgeanfrage startet erst, wenn die erste abgeschlossen ist
    assert.equal(form.requests.length, 1);
    await new Promise((resolve) => setTimeout(resolve, 0));

    assert.equal(form.requests.length, 2);
    assert.deepEqual(form.requests[1].sent, { name: 'Anne', phone: '040' });
    assert.equal(saved.value, false);

    form.requests[1].options.onSuccess();

    await new Promise((resolve) => setTimeout(resolve, 0));
    assert.deepEqual(form.saved, form.values);
    assert.equal(form.requests.length, 2);
    assert.equal(saved.value, true);
});

test('nach einem erfolgreichen Speichern löst erneutes Verlassen keine Anfrage aus, auch bei veraltetem isDirty', async () => {
    const form = fakeForm({ name: 'Anna' });
    const { save } = useAutosaveForm(form, () => '/save');

    form.values.name = 'Anne';
    save();
    form.requests[0].options.onSuccess();
    await new Promise((resolve) => setTimeout(resolve, 0));
    save();

    assert.equal(form.requests.length, 1);
});
