import test from 'node:test';
import assert from 'node:assert/strict';
import {
    displayedAccessUsersOf,
    sharedDepartmentsOf,
    sharedUsersOf,
} from '../../resources/js/Helper/sharedAccess.js';

// Form der Budget-Informationen: serialisiertes Eloquent-Modell (siehe ProjectBudgetInformationControllerTest)
const budgetFile = { id: 1, name: 'budget.pdf', accessing_users: [{ id: 5 }, { id: 9 }] };
const budgetContract = {
    id: 2,
    accessing_users: [{ id: 5 }],
    accessing_departments: [{ id: 3, name: 'Technik' }],
};
// Form der aufbereiteten Vertragslisten (ProjectController / ContractResource)
const mappedContract = { id: 3, accessibleUsers: [{ id: 7 }], accessibleDepartments: [{ id: 4 }] };

test('share lists are read from the serialized model relation', () => {
    assert.deepEqual(sharedUsersOf(budgetFile).map((user) => user.id), [5, 9]);
    assert.deepEqual(sharedUsersOf(budgetContract).map((user) => user.id), [5]);
    assert.deepEqual(sharedDepartmentsOf(budgetContract).map((department) => department.id), [3]);
});

test('share lists are read from mapped arrays', () => {
    assert.deepEqual(sharedUsersOf(mappedContract).map((user) => user.id), [7]);
    assert.deepEqual(sharedDepartmentsOf(mappedContract).map((department) => department.id), [4]);
});

test('the returned list is a copy so editing a form does not mutate the prop', () => {
    const users = sharedUsersOf(budgetFile);
    users.splice(0, 1);

    assert.equal(budgetFile.accessing_users.length, 2);
});

test('missing lists and missing items yield empty lists', () => {
    assert.deepEqual(sharedUsersOf(null), []);
    assert.deepEqual(sharedUsersOf({ id: 1 }), []);
    assert.deepEqual(sharedDepartmentsOf(undefined), []);
});

test('the overview display list includes project managers, the stored share list does not', () => {
    const overviewRow = {
        accessibleUsers: [{ id: 7 }],
        displayedAccessUsers: [{ id: 7 }, { id: 8 }],
    };

    assert.deepEqual(displayedAccessUsersOf(overviewRow).map((user) => user.id), [7, 8]);
    assert.deepEqual(sharedUsersOf(overviewRow).map((user) => user.id), [7]);
    // ohne eigene Anzeigeliste wird die Freigabeliste angezeigt
    assert.deepEqual(displayedAccessUsersOf(budgetFile).map((user) => user.id), [5, 9]);
});
