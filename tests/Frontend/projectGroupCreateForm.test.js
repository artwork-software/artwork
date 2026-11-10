import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const source = readFileSync(new URL('../../resources/js/Layouts/Components/ProjectCreateModal.vue', import.meta.url), 'utf8');
const groupForm = source.slice(source.indexOf('<div v-if="createProjectGroup || (project && project.is_group)">'), source.indexOf('</template>\n\n<script setup>'));

test('the project group form offers the status field and shows why creating fails', () => {
    assert.ok(groupForm.length > 0, 'group form missing');
    assert.match(groupForm, /<ProjectStateSelect[\s\S]*?:required="isStateRequired"[\s\S]*?:error="showInvalidProjectStateHelpText/);
    assert.match(groupForm, /showInvalidProjectNameHelpText/);
});

test('project and group form share the same status component', () => {
    assert.equal(source.match(/<ProjectStateSelect/g)?.length, 2);
});
