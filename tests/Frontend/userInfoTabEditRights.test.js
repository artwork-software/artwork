import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';

const source = readFileSync(new URL('../../resources/js/Pages/Users/Tabs/UserInfoTab.vue', import.meta.url), 'utf8');

test('profile fields follow the server rule: admin profiles are editable by admins only', () => {
    // UserController::updateUserDetails lehnt Nicht-Admins auf fremden Admin-Profilen mit 403 ab
    assert.ok(
        source.includes("const canEditProfile = computed(() => isSignedInUser() || hasAdminRole()\n    || (can('can manage workers') && !isTargetAdmin.value))"),
        'canEditProfile no longer mirrors updateUserDetails',
    );
    assert.ok(!source.includes("isSignedInUser() || can('can manage workers')"), 'a field still ignores admin profiles');
    assert.ok(!source.includes('canEditPreferences'), 'preferences still use their own, wider rule');
    assert.equal((source.match(/:disabled="!canEditProfile"/g) ?? []).length, 9);
});

test('team changes on admin profiles are reserved for admins', () => {
    assert.match(
        source,
        /const canEditTeams = computed\(\(\) => hasTeamRights\.value\s*&& \(hasAdminRole\(\) \|\| \(!isSignedInUser\(\) && !isTargetAdmin\.value\)\)\)/,
    );
});
