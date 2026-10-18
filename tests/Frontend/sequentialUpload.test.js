import test from 'node:test';
import assert from 'node:assert/strict';
import {
    INLINE_FEEDBACK_HEADER,
    hasInlineFeedback,
    inertiaUploadErrorMessage,
    isInvalidResponse,
    submitInertiaForm,
    uploadSequentially,
} from '../../resources/js/Helper/sequentialUpload.js';

test('files are uploaded one after another, never in parallel', async () => {
    const log = [];
    let running = 0;

    const result = await uploadSequentially(['a.pdf', 'b.pdf', 'c.pdf'], async (file) => {
        running++;
        assert.equal(running, 1, 'zwei Uploads gleichzeitig');
        log.push(`start ${file}`);
        await new Promise((resolve) => setTimeout(resolve, 5));
        log.push(`end ${file}`);
        running--;
    });

    assert.deepEqual(log, ['start a.pdf', 'end a.pdf', 'start b.pdf', 'end b.pdf', 'start c.pdf', 'end c.pdf']);
    assert.deepEqual(result.uploaded, ['a.pdf', 'b.pdf', 'c.pdf']);
    assert.deepEqual(result.failed, []);
});

test('a failing file is reported and the others are still uploaded', async () => {
    const result = await uploadSequentially(['ok.pdf', 'zu-gross.pdf', 'auch-ok.pdf'], async (file) => {
        if (file === 'zu-gross.pdf') {
            throw new Error('413');
        }
    });

    assert.deepEqual(result.uploaded, ['ok.pdf', 'auch-ok.pdf']);
    assert.equal(result.failed.length, 1);
    assert.equal(result.failed[0].file, 'zu-gross.pdf');
});

/** Router-Ersatz: router.on('invalid', …) mit Abmeldefunktion wie bei Inertia. */
const router = {
    listeners: new Set(),
    on(type, listener) {
        assert.equal(type, 'invalid');
        this.listeners.add(listener);
        return () => this.listeners.delete(listener);
    },
    emit(event) {
        for (const listener of this.listeners) listener(event);
    },
};

/** useForm-Ersatz: ruft die Callbacks so auf, wie Inertia es je nach Ausgang tut. */
function fakeForm(outcome) {
    return {
        calls: [],
        post(url, options) {
            this.calls.push({ url, options });
            queueMicrotask(() => {
                if (outcome === 'success') options.onSuccess({ url });
                if (outcome === 'errorPage') options.onSuccess({ url, component: 'Errors/403' });
                if (outcome === 'error') options.onError({ file: 'Ungültig' });
                if (outcome === 'invalid413') {
                    router.emit({ detail: { response: { status: 413, config: { headers: options.headers } } } });
                }
                options.onFinish({});
            });
        },
    };
}

test('submitInertiaForm resolves on success and calls the own callbacks too', async () => {
    let ownSuccess = false;
    const page = await submitInertiaForm(fakeForm('success'), 'post', '/upload', { onSuccess: () => { ownSuccess = true; } });

    assert.deepEqual(page, { url: '/upload' });
    assert.equal(ownSuccess, true);
});

test('submitInertiaForm rejects with the validation errors', async () => {
    await assert.rejects(submitInertiaForm(fakeForm('error'), 'post', '/upload'), { errors: { file: 'Ungültig' } });
});

test('submitInertiaForm rejects when the visit ends without result (cancelled/invalid)', async () => {
    await assert.rejects(submitInertiaForm(fakeForm('cancelled'), 'post', '/upload'), { cancelled: true });
});

test('submitInertiaForm rejects a rendered error page (403) and skips the own success callback', async () => {
    let ownSuccess = false;
    await assert.rejects(
        submitInertiaForm(fakeForm('errorPage'), 'post', '/upload', { onSuccess: () => { ownSuccess = true; } }),
        { cancelled: true, status: 403 },
    );
    assert.equal(ownSuccess, false);
});

test('a chain stops after an error the caller marks as fatal and reports the rest as skipped', async () => {
    const attempted = [];
    const result = await uploadSequentially(
        ['a.pdf', 'zu-gross.pdf', 'b.pdf', 'c.pdf'],
        async (file) => {
            attempted.push(file);
            if (file === 'zu-gross.pdf') {
                throw { cancelled: true, status: 413 };
            }
        },
        { stopOnError: isInvalidResponse }
    );

    assert.deepEqual(attempted, ['a.pdf', 'zu-gross.pdf']);
    assert.deepEqual(result.uploaded, ['a.pdf']);
    assert.deepEqual(result.failed.map(({ file }) => file), ['zu-gross.pdf']);
    assert.deepEqual(result.skipped, ['b.pdf', 'c.pdf']);
});

test('inertia uploads are marked so app.js leaves the error message to the modal', async () => {
    const form = fakeForm('success');
    await submitInertiaForm(form, 'post', '/upload', { headers: { 'X-Other': 'x' } }, { router });

    assert.deepEqual(form.calls[0].options.headers, { 'X-Other': 'x', [INLINE_FEEDBACK_HEADER]: '1' });
    assert.equal(hasInlineFeedback({ config: { headers: form.calls[0].options.headers } }), true);
    // axios normalisiert Header (AxiosHeaders mit get())
    assert.equal(hasInlineFeedback({ config: { headers: { get: (name) => (name === INLINE_FEEDBACK_HEADER ? '1' : undefined) } } }), true);
    assert.equal(hasInlineFeedback({ config: { headers: { Accept: 'text/html' } } }), false);
    assert.equal(hasInlineFeedback(undefined), false);
    assert.equal(router.listeners.size, 0, 'invalid-Listener nicht abgemeldet');
});

test('an invalid 413 response rejects with its status and gets an understandable message', async () => {
    const translate = (key) => `übersetzt: ${key}`;

    const error = await submitInertiaForm(fakeForm('invalid413'), 'post', '/upload', {}, { router }).catch((e) => e);

    assert.deepEqual(error, { cancelled: true, status: 413 });
    assert.equal(isInvalidResponse(error), true);
    assert.match(inertiaUploadErrorMessage(error, translate), /too large/);
    assert.equal(router.listeners.size, 0);
});

test('upload error messages prefer validation errors and fall back to a generic text', () => {
    const translate = (key) => key;

    assert.equal(inertiaUploadErrorMessage({ errors: { file: ['Dateityp nicht erlaubt'] } }, translate), 'Dateityp nicht erlaubt');
    assert.equal(inertiaUploadErrorMessage({ cancelled: true, status: 500 }, translate), 'Upload failed');
    assert.equal(isInvalidResponse({ errors: { file: 'x' } }), false);
});
