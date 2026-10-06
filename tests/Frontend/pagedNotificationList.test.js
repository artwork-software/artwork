import test from 'node:test';
import assert from 'node:assert/strict';
import {
    applyPageResponse,
    beginPageRequest,
    createPagedList,
} from '../../resources/js/Helper/pagedNotificationList.js';

const page = (number, ids, lastPage = 3) => ({
    data: ids.map((id) => ({id})),
    current_page: number,
    last_page: lastPage,
    total: 60,
});
const ids = (list) => list.items.map((item) => item.id);

test('page 1 replaces the list, later pages are appended', () => {
    const list = createPagedList();
    applyPageResponse(list, beginPageRequest(list, 1), page(1, ['a', 'b']));
    applyPageResponse(list, beginPageRequest(list, 2), page(2, ['c']));

    assert.deepEqual(ids(list), ['a', 'b', 'c']);
    assert.equal(list.page, 2);
});

test('overlapping reloads never append a page twice', () => {
    const list = createPagedList();
    applyPageResponse(list, beginPageRequest(list, 1), page(1, ['a', 'b']));
    applyPageResponse(list, beginPageRequest(list, 2), page(2, ['c', 'd']));

    // reload A loads page 1, then reload B starts before A continues with page 2
    const reloadA = beginPageRequest(list, 1);
    assert.equal(applyPageResponse(list, reloadA, page(1, ['a', 'b'])), true);
    const reloadB = beginPageRequest(list, 1);
    assert.equal(beginPageRequest(list, 2, reloadA), null, 'reload A stops');
    assert.equal(applyPageResponse(list, reloadB, page(1, ['a', 'b'])), true);
    applyPageResponse(list, beginPageRequest(list, 2, reloadB), page(2, ['c', 'd']));

    assert.deepEqual(ids(list), ['a', 'b', 'c', 'd']);
});

test('a response of an outdated reload is dropped', () => {
    const list = createPagedList();
    applyPageResponse(list, beginPageRequest(list, 1), page(1, ['a']));
    const loadMore = beginPageRequest(list, 2);
    beginPageRequest(list, 1);

    assert.equal(applyPageResponse(list, loadMore, page(2, ['x'])), false);
    assert.deepEqual(ids(list), ['a']);
});

test('entries shifted between pages are not listed twice', () => {
    const list = createPagedList();
    applyPageResponse(list, beginPageRequest(list, 1), page(1, ['a', 'b']));
    applyPageResponse(list, beginPageRequest(list, 2), page(2, ['b', 'c']));

    assert.deepEqual(ids(list), ['a', 'b', 'c']);
});
