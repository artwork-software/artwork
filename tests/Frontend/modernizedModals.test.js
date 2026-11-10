import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(`../../resources/js/${path}`, import.meta.url), 'utf8');

/** Modale, die vom alten BaseModal-Muster auf ArtworkBaseModal umgestellt wurden. */
const modernizedModals = [
    'Layouts/Components/AddBudgetTemplateComponent.vue',
    'Layouts/Components/UseTemplateComponent.vue',
    'Layouts/Components/UseTemplateFromProjectBudgetComponent.vue',
    'Layouts/Components/RenameTableComponent.vue',
    'Layouts/Components/ContractModuleDeleteModal.vue',
    'Layouts/Components/FileDeleteModal.vue',
    'Layouts/Components/MoneySourceFileDeleteModal.vue',
    'Layouts/Components/ContractDeleteModal.vue',
    'Layouts/Components/ConfirmEventRequestComponent.vue',
    'Layouts/Components/AnswerEventRequestComponent.vue',
    'Layouts/Components/AnswerEventRequestWithRoomChangeComponent.vue',
    'Layouts/Components/RoomRequestDialogComponent.vue',
    'Layouts/Components/MoneySourceFileEditModal.vue',
    'Layouts/Components/MoneySourceFileUploadModal.vue',
    'Layouts/Components/ContractModuleUploadModal.vue',
    'Layouts/Components/SageAssignedDataModal.vue',
    'Layouts/Components/RoomAttributeEditModal.vue',
    'Layouts/Components/ProjectDataEditModal.vue',
    'Pages/Settings/EventType/Components/Modals/DeleteStandardEventTypeModal.vue',
    'Pages/Settings/EventType/Components/Modals/DeleteBiTagConfirmationModal.vue',
    'Pages/Settings/EventType/Components/Modals/DeleteEventTypeConfirmationModal.vue',
    'Pages/Events/Components/GeneralCalendarAboSettingModal.vue',
    'Pages/Updates/Components/ShowUpdateDetailModal.vue',
];

for (const path of modernizedModals) {
    test(`${path} uses ArtworkBaseModal instead of the legacy modal pattern`, () => {
        const source = read(path);

        assert.match(source, /<ArtworkBaseModal\b/);
        assert.doesNotMatch(source, /Components\/Modals\/BaseModal(\.vue)?['"]/);
        assert.doesNotMatch(source, /text-\[clamp\(24px,3vw,30px\)\]/);
        assert.doesNotMatch(source, /modal-image=/);
        assert.doesNotMatch(source, /General\/Buttons\/FormButton/);
    });
}

test('page-level modals no longer use the legacy BaseModal', () => {
    for (const path of [
        'Pages/Departments/Show.vue',
        'Pages/Departments/DepartmentManagement.vue',
        'Pages/Profile/Show.vue',
        'Pages/MoneySources/MoneySourceManagement.vue',
        'Pages/Rooms/Show.vue',
        'Pages/EventVerification/Index.vue',
    ]) {
        assert.doesNotMatch(read(path), /Components\/Modals\/BaseModal(\.vue)?['"]/, path);
    }
});

test('budget template modals close only after the request succeeded', () => {
    for (const path of [
        'Layouts/Components/AddBudgetTemplateComponent.vue',
        'Layouts/Components/UseTemplateComponent.vue',
        'Layouts/Components/UseTemplateFromProjectBudgetComponent.vue',
        'Layouts/Components/RenameTableComponent.vue',
    ]) {
        const source = read(path);

        assert.match(source, /onSuccess: \(\) => closeModal\(true\)/, path);
        assert.match(source, /:processing="processing"/, path);
    }
});

test('the project import excludes the current project and debounces the search', () => {
    const source = read('Layouts/Components/UseTemplateFromProjectBudgetComponent.vue');

    assert.match(source, /project\.id !== props\.projectId/);
    assert.match(source, /debounce\(/);
});

test('the budget success feedback is a toast, not a modal', () => {
    const source = read('Layouts/Components/BudgetComponent.vue');
    const template = source.slice(0, source.indexOf('<script'));

    assert.match(template, /<NotificationToast\s+v-model:show="showSuccessModal"/);
    assert.doesNotMatch(template, /text-\[clamp\(24px,3vw,30px\)\]/);
});

test('BaseInput placeholders in the migrated search fields are translated', () => {
    // BaseInput übersetzt placeholder nicht selbst
    for (const path of [
        'Layouts/Components/UseTemplateFromProjectBudgetComponent.vue',
        'Pages/Departments/Show.vue',
        'Pages/Departments/DepartmentManagement.vue',
    ]) {
        const source = read(path);
        const baseInputs = source.match(/<BaseInput\b[^>]*>/gs) ?? [];

        for (const tag of baseInputs) {
            assert.doesNotMatch(tag, /\splaceholder="[^"]+"/, `${path}: ${tag}`);
        }
    }
});

test('the CRM export project search sends the parameter the search endpoint validates', () => {
    // SearchRequest verlangt "query"; mit "search" kam immer 422 und nie ein Treffer
    const source = read('Pages/CRM/Components/CrmExportModal.vue');

    assert.match(source, /route\('projects\.search'\), \{\s*params: \{ query: projectSearch\.value \}/);
});

test('no component uses the removed legacy modal building blocks', async () => {
    const { readdirSync, statSync, existsSync } = await import('node:fs');
    const root = new URL('../../resources/js/', import.meta.url);
    const offenders = [];

    const walk = (dir) => {
        for (const entry of readdirSync(dir)) {
            const path = new URL(entry, dir);
            if (statSync(path).isDirectory()) {
                walk(new URL(`${entry}/`, dir));
            } else if (entry.endsWith('.vue') || entry.endsWith('.js')) {
                const source = readFileSync(path, 'utf8');
                if (/Components\/Modals\/(BaseModal|ModalHeader)(\.vue)?['"]/.test(source)) {
                    offenders.push(path.pathname);
                }
            }
        }
    };
    walk(root);

    assert.deepEqual(offenders, []);
    assert.equal(existsSync(new URL('Components/Modals/BaseModal.vue', root)), false);
});

test('shared confirmation dialogs keep the cancel action working for @close listeners', () => {
    // Elf Aufrufer hören nur auf @close; „Nein, doch nicht“ muss auch dort schließen
    const source = read('Layouts/Components/ConfirmDeleteModal.vue');

    assert.match(source, /emit\('closed'/);
    assert.match(source, /emit\('close'/);
});
