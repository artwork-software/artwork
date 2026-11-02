import test from 'node:test';
import assert from 'node:assert/strict';
import {safeGetItem, safeRemoveItem, safeSetItem} from '../../resources/js/Helper/safeStorage.js';
import {createLocaleSwitcher, resolveInitialLocale} from '../../resources/js/Helper/appLocale.js';

function memoryStorage() {
    const data = new Map();
    return {
        getItem: (key) => (data.has(key) ? data.get(key) : null),
        setItem: (key, value) => data.set(key, String(value)),
        removeItem: (key) => data.delete(key),
    };
}

function withWindow(windowStub, run) {
    const previous = globalThis.window;
    const restore = () => {
        globalThis.window = previous;
    };
    globalThis.window = windowStub;
    let result;
    try {
        result = run();
    } catch (error) {
        restore();
        throw error;
    }
    if (result && typeof result.then === 'function') {
        return result.finally(restore);
    }
    restore();
    return result;
}

function blockedWindow() {
    return {
        get localStorage() {
            throw new Error('SecurityError');
        },
        get sessionStorage() {
            throw new Error('SecurityError');
        },
    };
}

function fakeI18n(initialLocale, loaded = [initialLocale]) {
    const messages = new Map(loaded.map((locale) => [locale, {}]));
    return {
        global: {
            locale: {value: initialLocale},
            get availableLocales() {
                return [...messages.keys()];
            },
            setLocaleMessage: (locale, value) => messages.set(locale, value),
            messages,
        },
    };
}

test('blocked browser storage never throws', () => {
    withWindow(blockedWindow(), () => {
        assert.equal(safeGetItem('locale'), null);
        assert.equal(safeSetItem('locale', 'en'), false);
        assert.doesNotThrow(() => safeRemoveItem('locale'));
        assert.equal(safeGetItem('x', 'session'), null);
    });
});

test('storage helpers read and write when available', () => {
    withWindow({localStorage: memoryStorage(), sessionStorage: memoryStorage()}, () => {
        assert.equal(safeSetItem('locale', 'en'), true);
        assert.equal(safeGetItem('locale'), 'en');
        assert.equal(safeGetItem('locale', 'session'), null);
        safeRemoveItem('locale');
        assert.equal(safeGetItem('locale'), null);
    });
});

test('server language wins over a stale stored locale', () => {
    withWindow({localStorage: memoryStorage()}, () => {
        safeSetItem('locale', 'en');
        assert.equal(resolveInitialLocale('de'), 'de');
        assert.equal(resolveInitialLocale(''), 'en');
    });
    withWindow(blockedWindow(), () => {
        assert.equal(resolveInitialLocale(''), 'de');
    });
});

test('switching to a not yet loaded language loads its messages first', async () => {
    const i18n = fakeI18n('de');
    const html = {lang: 'de'};
    const loaded = [];
    const switchLocale = createLocaleSwitcher(i18n, async (locale) => {
        loaded.push(locale);
        return {Hello: 'Hello'};
    }, html);

    await withWindow({localStorage: memoryStorage()}, async () => {
        await switchLocale('en');
        assert.equal(safeGetItem('locale'), 'en');
    });

    assert.deepEqual(loaded, ['en']);
    assert.equal(i18n.global.locale.value, 'en');
    assert.equal(html.lang, 'en');
    assert.deepEqual(i18n.global.messages.get('en'), {Hello: 'Hello'});
});

test('switching to an already loaded language does not reload it', async () => {
    const i18n = fakeI18n('de', ['de', 'en']);
    let loads = 0;
    const switchLocale = createLocaleSwitcher(i18n, async () => {
        loads++;
        return {};
    }, null);

    await withWindow(blockedWindow(), () => switchLocale('en'));

    assert.equal(loads, 0);
    assert.equal(i18n.global.locale.value, 'en');
});

test('failed message load keeps the current language', async () => {
    const i18n = fakeI18n('de');
    const switchLocale = createLocaleSwitcher(i18n, async () => {
        throw new Error('offline');
    }, null);

    await withWindow({localStorage: memoryStorage()}, () => switchLocale('en'));

    assert.equal(i18n.global.locale.value, 'de');
});
