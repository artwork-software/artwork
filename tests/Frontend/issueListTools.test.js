import test from 'node:test';
import assert from 'node:assert/strict';
import {
    allIssuesSelected,
    compactIssueQuery,
    nextIssueSort,
    togglePageSelection,
} from '../../resources/js/Pages/IssueOfMaterial/Components/issueListTools.js';

test('sorting cycles ascending, descending, server default', () => {
    assert.deepEqual(nextIssueSort({sort: null, direction: null}, 'name'), {sort: 'name', direction: 'asc'});
    assert.deepEqual(nextIssueSort({sort: 'name', direction: 'asc'}, 'name'), {sort: 'name', direction: 'desc'});
    assert.deepEqual(nextIssueSort({sort: 'name', direction: 'desc'}, 'name'), {sort: null, direction: null});
    assert.deepEqual(nextIssueSort({sort: 'name', direction: 'desc'}, 'start_date'), {sort: 'start_date', direction: 'asc'});
    assert.deepEqual(nextIssueSort(undefined, 'name'), {sort: 'name', direction: 'asc'});
});

test('query keeps only set filters', () => {
    assert.deepEqual(
        compactIssueQuery({q: 'bühne', room_id: '', project_id: 5, special_open: true, overdue: false, ids: [3, 4], users: [], sort: null}),
        {q: 'bühne', project_id: 5, special_open: 1, ids: '3,4'}
    );
    assert.deepEqual(compactIssueQuery(null), {});
});

test('page selection keeps picks from other pages', () => {
    const page = [{id: 1}, {id: 2}];
    const selected = togglePageSelection(page, {9: true}, true);
    assert.deepEqual(selected, {9: true, 1: true, 2: true});
    assert.equal(allIssuesSelected(page, selected), true);
    assert.equal(allIssuesSelected(page, {1: true}), false);
    assert.equal(allIssuesSelected([], {1: true}), false);
    assert.deepEqual(togglePageSelection(page, selected, false), {9: true});
});
