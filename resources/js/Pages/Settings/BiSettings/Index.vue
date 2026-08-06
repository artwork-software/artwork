<template>
    <ProjectSettingsHeader
        :title="$t('BI Field Settings')"
        :description="$t('Manage custom fields for Business Intelligence.')"
    >
        <template #actions>
            <BaseUIButton variant="primary" hide-icon class="inline-flex items-center gap-2" @click="openCreateModal">
                <IconCirclePlus class="size-5" stroke-width="1" />
                {{ $t('Create BI field') }}
            </BaseUIButton>
        </template>

        <SettingsGuideBanner
            storage-key="settings-guide.project.bi-fields"
            title="How does this area work?"
            :paragraphs="[
                'This tab bundles two tools: audience categories structure how ticket numbers are recorded, BI fields are free additional fields for your projects.',
            ]"
        />

        <div class="mt-4 space-y-6">
            <BiAudienceCategoryManager />

            <div class="rounded-2xl border border-border-subtle bg-white p-5 shadow-xs">
                <SettingsGuideBanner
                    class="mb-4"
                    variant="inline"
                    storage-key="settings-guide.project.bi-fields.fields"
                    title="What are BI fields?"
                    :paragraphs="[
                        'BI fields appear in the BI section of the project detail page and as extra columns in the BI export.',
                        'Drag the fields to change their order — it defines both the display order and the export order.',
                        'Only simple field types are available on purpose, so the values stay exportable.',
                    ]"
                />
                <div v-if="biFields.length === 0" class="text-center text-sm text-text-subtle py-8">
                    {{ $t('No BI fields created yet.') }}
                </div>

                <draggable
                    v-else
                    v-model="orderedFields"
                    item-key="id"
                    handle=".drag-handle"
                    @end="saveOrder"
                    tag="div"
                    class="space-y-2"
                >
                    <template #item="{ element }">
                        <div class="flex items-center gap-3 rounded-lg border border-border-subtle bg-white p-3 hover:border-border transition">
                            <IconGripVertical class="drag-handle size-5 text-text-subtle cursor-grab flex-shrink-0" />

                            <ComponentIcons :type="element.type" class="flex-shrink-0" />

                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-text truncate">{{ element.name }}</p>
                                <p class="text-xs text-text-subtle">{{ $t(element.type) }}</p>
                            </div>

                            <div class="flex items-center gap-2 flex-shrink-0">
                                <BaseUIButton
                                    type="button"
                                    hide-icon
                                    class="bg-white hover:bg-surface-sunken transition"
                                    @click="openEditModal(element)"
                                >
                                    <IconEdit class="size-4 text-accent-600" />
                                </BaseUIButton>
                                <BaseUIButton
                                    type="button"
                                    hide-icon
                                    class="!bg-danger-surface hover:!bg-danger-surface transition"
                                    @click="confirmDelete(element)"
                                >
                                    <IconTrash class="size-4 text-danger" />
                                </BaseUIButton>
                            </div>
                        </div>
                    </template>
                </draggable>
            </div>
        </div>

        <!-- Create / Edit Modal -->
        <ComponentModal
            v-if="showModal"
            :show="showModal"
            :mode="modalMode"
            :tab-component-types="tabComponentTypes"
            :component-id="editingFieldId"
            store-route-name="bi.settings.store"
            update-route-name="bi.settings.update"
            @close="closeModal"
        />

        <!-- Delete Confirmation -->
        <ConfirmDeleteModal
            v-if="showDeleteModal"
            @closed="showDeleteModal = false"
            @delete="deleteField"
            :title="$t('Delete BI field')"
            :description="$t('Are you sure you want to delete this BI field?')"
        />
    </ProjectSettingsHeader>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import { IconCirclePlus, IconEdit, IconTrash, IconGripVertical } from '@tabler/icons-vue';
import ProjectSettingsHeader from '@/Pages/Settings/Components/ProjectSettingsHeader.vue';
import SettingsGuideBanner from '@/Artwork/Guide/SettingsGuideBanner.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import BiAudienceCategoryManager from '@/Pages/Settings/BiSettings/Components/BiAudienceCategoryManager.vue';
import ComponentModal from '@/Pages/Settings/ComponentManagement/Components/ComponentModal.vue';
import ComponentIcons from '@/Components/Globale/ComponentIcons.vue';
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue';

defineOptions({ name: 'BiSettingsIndex' });

const props = defineProps({
    biFields: { type: Array, default: () => [] },
    tabComponentTypes: { type: Object, default: () => ({}) },
});

const orderedFields = ref([...props.biFields]);

// Keep the list in sync when Inertia refreshes the biFields prop (e.g. after create/edit/delete)
watch(() => props.biFields, (value) => {
    orderedFields.value = [...value];
});
const showModal = ref(false);
const modalMode = ref('create');
const editingFieldId = ref(null);
const showDeleteModal = ref(false);
const fieldToDelete = ref(null);

function openCreateModal() {
    modalMode.value = 'create';
    editingFieldId.value = null;
    showModal.value = true;
}

function openEditModal(field) {
    modalMode.value = 'edit';
    editingFieldId.value = field.id;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingFieldId.value = null;
}

function confirmDelete(field) {
    fieldToDelete.value = field;
    showDeleteModal.value = true;
}

function deleteField() {
    if (!fieldToDelete.value) return;
    router.delete(route('bi.settings.destroy', { component: fieldToDelete.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            fieldToDelete.value = null;
        },
    });
}

function saveOrder() {
    const ids = orderedFields.value.map(f => f.id);
    router.post(route('bi.settings.update-order'), { ordered_ids: ids }, {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<style scoped>
.shadow-xs {
    --tw-shadow: 0 1px 2px rgb(0 0 0 / 0.05);
    --tw-shadow-colored: 0 1px 2px var(--tw-shadow-color);
    box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000),
    var(--tw-ring-shadow, 0 0 #0000),
    var(--tw-shadow);
}
</style>
