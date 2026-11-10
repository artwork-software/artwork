import assert from 'node:assert/strict';
import test from 'node:test';
import { canOpenPlanningCalendar, canViewPlanning } from '../../resources/js/Helper/planningRights.js';

const canWith = (...permissions) => (permission) => permissions.includes(permission);

test('viewPlanning mirrors EventPolicy: admin, view or edit permission', () => {
    assert.equal(canViewPlanning({ isAdmin: true, can: canWith() }), true);
    assert.equal(canViewPlanning({ isAdmin: false, can: canWith('can see planning calendar') }), true);
    assert.equal(canViewPlanning({ isAdmin: false, can: canWith('can edit planning calendar') }), true);
    assert.equal(canViewPlanning({ isAdmin: false, can: canWith('create events without request') }), false);
});

test('viewPlanning ignores the module switch, like the policy and the planning channels', () => {
    const can = canWith('can see planning calendar');

    assert.equal(canViewPlanning({ isAdmin: false, can, moduleSettings: { planning_calendar: false } }), true);
});

test('opening the planning calendar additionally needs the module, missing switch counts as enabled', () => {
    const can = canWith('can see planning calendar');

    assert.equal(canOpenPlanningCalendar({ isAdmin: false, can, moduleSettings: { planning_calendar: true } }), true);
    assert.equal(canOpenPlanningCalendar({ isAdmin: false, can, moduleSettings: undefined }), true);
    assert.equal(canOpenPlanningCalendar({ isAdmin: false, can, moduleSettings: { planning_calendar: false } }), false);
    assert.equal(canOpenPlanningCalendar({ isAdmin: true, can: canWith(), moduleSettings: { planning_calendar: false } }), false);
    assert.equal(canOpenPlanningCalendar({ isAdmin: false, can: canWith(), moduleSettings: {} }), false);
});
