import assert from 'node:assert/strict';
import test from 'node:test';
import {
    MANUAL_BASE_URL,
    SETTINGS_GUIDE_AREAS_WITHOUT_MANUAL,
    displaySettingsManualPath,
    manualLanguage,
    manualUrl,
    settingsGuideManualPath,
} from '../../resources/js/Helper/manualLinks.js';

test('manual language follows the app locale, German is the fallback', () => {
    assert.equal(manualLanguage('en'), 'en');
    assert.equal(manualLanguage('en-GB'), 'en');
    assert.equal(manualLanguage('de'), 'de');
    assert.equal(manualLanguage(''), 'de');
    assert.equal(manualLanguage(null), 'de');
    assert.equal(manualLanguage('fr'), 'de');
});

test('manual URLs point to /<lang>/docs and keep anchors', () => {
    assert.equal(manualUrl('', 'de'), `${MANUAL_BASE_URL}/de/docs`);
    assert.equal(manualUrl(undefined, 'en'), `${MANUAL_BASE_URL}/en/docs`);
    assert.equal(
        manualUrl('/calendar/views-and-display-settings#navigation', 'de'),
        `${MANUAL_BASE_URL}/de/docs/calendar/views-and-display-settings#navigation`,
    );
    assert.equal(manualUrl('start', 'en'), `${MANUAL_BASE_URL}/en/docs/start`);
});

test('display settings link to the calendar or shift plan page by view', () => {
    for (const view of ['calendar', 'calendar_daily', 'planning', 'planning_daily']) {
        assert.equal(displaySettingsManualPath(view), 'calendar/views-and-display-settings');
    }
    for (const view of ['shift_week', 'shift_day', 'project_shift_tab', 'shift_list']) {
        assert.equal(displaySettingsManualPath(view), 'shift-plan/views-and-display-settings');
    }
});

test('settings guide banners resolve tab first, then area', () => {
    assert.equal(settingsGuideManualPath('settings-guide.shift.rules'), 'shift-plan/rules-and-compensation');
    assert.equal(settingsGuideManualPath('settings-guide.shift.general'), 'shift-plan/settings');
    assert.equal(settingsGuideManualPath('settings-guide.shift.general.craft-functions'), 'shift-plan/settings');
    assert.equal(settingsGuideManualPath('settings-guide.tool.mail'), 'system/tool-settings#mail');
    assert.equal(
        settingsGuideManualPath('settings-guide.tool.communication-and-legal.letterhead'),
        'system/tool-settings#kommunikation--rechtliches',
    );
    assert.equal(settingsGuideManualPath('settings-guide.material-sets.index'), 'inventory/material-sets');
    assert.equal(settingsGuideManualPath('settings-guide.unknown-area.x'), null);
    assert.equal(settingsGuideManualPath('crm-settings-workflow-help-collapsed'), null);
    assert.equal(settingsGuideManualPath(null), null);
});

test('every storage key used by a settings banner has a manual page', async () => {
    const { readFileSync, readdirSync, statSync } = await import('node:fs');
    const { join } = await import('node:path');
    const root = new URL('../../resources/js', import.meta.url).pathname;
    const files = [];
    const walk = (dir) => {
        for (const name of readdirSync(dir)) {
            const full = join(dir, name);
            if (statSync(full).isDirectory()) walk(full);
            else if (name.endsWith('.vue')) files.push(full);
        }
    };
    walk(root);
    const keys = new Set();
    for (const file of files) {
        for (const match of readFileSync(file, 'utf8').matchAll(/settings-guide\.[a-z0-9.-]+/g)) {
            keys.add(match[0]);
        }
    }
    const missing = [...keys].filter((key) => settingsGuideManualPath(key) === null
        && !SETTINGS_GUIDE_AREAS_WITHOUT_MANUAL.includes(key.split('.')[1]));
    assert.deepEqual(missing, []);
});
