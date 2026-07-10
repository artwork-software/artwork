import assert from 'node:assert/strict';
import test from 'node:test';
import {
    componentHasNonDefaultValue,
    countFolderNonDefaultValues,
} from '../../resources/js/Helper/ProjectComponentValueState.js';

function component(type, defaults, projectValue = null) {
    return {
        type,
        data: defaults,
        project_value: projectValue === null ? null : { data: projectValue },
    };
}

test('a configured default does not count as entered project data', () => {
    assert.equal(componentHasNonDefaultValue(component('TextField', { text: 'Bla bla bla' })), false);
    assert.equal(
        componentHasNonDefaultValue(component('TextField', { text: 'Bla bla bla' }, { text: 'Bla bla bla' })),
        false
    );
});

test('empty and whitespace-only project values do not count as entered data', () => {
    assert.equal(componentHasNonDefaultValue(component('TextField', { text: '' }, { text: '' })), false);
    assert.equal(componentHasNonDefaultValue(component('TextArea', { text: '' }, { text: '   ' })), false);
});

test('text values are compared with their defaults after HTML normalization', () => {
    assert.equal(
        componentHasNonDefaultValue(component('TextArea', { text: 'First\nSecond' }, { text: 'First<br />Second' })),
        false
    );
    assert.equal(
        componentHasNonDefaultValue(component('TextArea', { text: '' }, { text: '<p>Entered text</p>' })),
        true
    );
});

test('checkbox and dropdown values use their type-specific empty states', () => {
    assert.equal(componentHasNonDefaultValue(component('Checkbox', { checked: null }, { checked: false })), false);
    assert.equal(componentHasNonDefaultValue(component('Checkbox', { checked: null }, { checked: true })), true);
    assert.equal(componentHasNonDefaultValue(component('DropDown', { selected: null }, { selected: null })), false);
    assert.equal(
        componentHasNonDefaultValue(component('DropDown', { selected: null }, { selected: { value: 'Option 1' } })),
        true
    );
});

test('link lists ignore empty rows and technical ids', () => {
    assert.equal(
        componentHasNonDefaultValue(component('LinkList', {}, { links: [{ id: 'row-1', label: ' ', url: '' }] })),
        false
    );
    assert.equal(
        componentHasNonDefaultValue(component('LinkList', {}, { links: [{ id: 'row-1', label: 'Artwork', url: '' }] })),
        true
    );
});

test('a folder is marked when any supported child has a non-default value', () => {
    const children = [
        { component: component('DropDown', { selected: null }) },
        { component: component('TextField', { text: '' }, { text: 'Entered' }) },
    ];

    assert.equal(countFolderNonDefaultValues(children), 1);
});

test('a folder count includes every child with a meaningful non-default value', () => {
    const children = [
        { component: component('Checkbox', { checked: false }, { checked: true }) },
        { component: component('DropDown', { selected: null }, { selected: 'Option 1' }) },
        { component: component('TextField', { text: 'Default' }, { text: 'Default' }) },
    ];

    assert.equal(countFolderNonDefaultValues(children), 2);
});

test('layout and unsupported component types do not mark a folder', () => {
    const children = [
        { component: component('Title', { title: 'Static title' }, { title: 'Changed' }) },
        { component: component('SeparatorComponent', { showLine: true }) },
    ];

    assert.equal(countFolderNonDefaultValues(children), 0);
});
