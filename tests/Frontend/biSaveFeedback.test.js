import assert from 'node:assert/strict';
import { register } from 'node:module';
import test from 'node:test';

register('./support/viteAliasHooks.mjs', import.meta.url);

const { extractSaveErrorMessage } = await import('../../resources/js/Composeables/BiSaveFeedback.js');
const { setAppToastTranslator } = await import('../../resources/js/Helper/appToast.js');

setAppToastTranslator((key) => `de:${key}`);

const failed = (status, data) => ({ response: { status, data } });

test('validation errors show the first field message', () => {
    assert.equal(
        extractSaveErrorMessage(failed(422, { message: 'The given data was invalid.', errors: { amount: ['Betrag fehlt'] } })),
        'Betrag fehlt'
    );
});

test('403, 404, 5xx and network errors show the translated reason instead of the English server text', () => {
    assert.equal(
        extractSaveErrorMessage(failed(403, { message: 'This action is unauthorized.' })),
        'de:You are not allowed to perform this action.'
    );
    assert.equal(
        extractSaveErrorMessage(failed(500, { message: 'Server Error' })),
        'de:The action could not be completed. Please try again.'
    );
    assert.equal(
        extractSaveErrorMessage({ isAxiosError: true, message: 'Network Error' }),
        'de:The connection to the server failed. Please check your network and try again.'
    );
});

test('other statuses keep the server message, cancelled requests show nothing', () => {
    assert.equal(extractSaveErrorMessage(failed(409, { message: 'Bereits vergeben' })), 'Bereits vergeben');
    assert.equal(extractSaveErrorMessage({ code: 'ERR_CANCELED' }), null);
});

test('a programming error is not reported as a connection problem', () => {
    assert.equal(extractSaveErrorMessage(new TypeError('x is undefined')), null);
});
