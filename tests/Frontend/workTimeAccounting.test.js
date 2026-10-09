import test from 'node:test';
import assert from 'node:assert/strict';
import {isWorkTimeAccountingEnabled} from '../../resources/js/Helper/workTimeAccounting.js';

test('work time accounting is enabled unless the shared prop is explicitly false', () => {
    assert.equal(isWorkTimeAccountingEnabled({work_time_accounting_enabled: true}), true);
    assert.equal(isWorkTimeAccountingEnabled({work_time_accounting_enabled: false}), false);
});

test('missing props keep today\'s behaviour', () => {
    assert.equal(isWorkTimeAccountingEnabled({}), true);
    assert.equal(isWorkTimeAccountingEnabled(undefined), true);
    assert.equal(isWorkTimeAccountingEnabled(null), true);
});
