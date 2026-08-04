<template>
    <div class="rounded-2xl border border-border-subtle bg-white p-5 shadow-xs">
        <div class="flex items-start justify-between gap-3 mb-1">
            <div>
                <h3 class="text-sm font-semibold text-text">{{ $t('Audience categories') }}</h3>
                <p class="mt-1 text-xs text-text-subtle max-w-2xl">
                    {{ $t('Categories break visitors down into e.g. full price, reduced and free tickets. The calculation role determines how a category counts in the free-ticket and reduced-ticket rates — regardless of its name.') }}
                </p>
            </div>
            <button class="ui-button-add inline-flex items-center gap-2 shrink-0" @click="startCreate">
                <IconCirclePlus class="size-5" stroke-width="1" />
                {{ $t('Add category') }}
            </button>
        </div>

        <div
            v-if="pageError"
            class="mt-3 rounded-md border border-warning-border bg-warning-surface px-3 py-2 text-xs text-warning"
        >
            {{ pageError }}
        </div>

        <div v-if="loading" class="py-6 text-center text-sm text-text-subtle">{{ $t('Loading') }}…</div>

        <template v-else>
            <div v-if="categories.length === 0" class="py-6 text-center text-sm text-text-subtle">
                {{ $t('No categories yet.') }}
            </div>

            <draggable
                v-else
                v-model="categories"
                item-key="id"
                handle=".drag-handle"
                @end="saveOrder"
                tag="div"
                class="space-y-2 mt-3"
            >
                <template #item="{ element }">
                    <div
                        class="flex items-center gap-3 rounded-lg border border-border-subtle bg-white p-3 hover:border-border transition"
                        :class="{ 'opacity-60': !element.is_active }"
                    >
                        <IconGripVertical class="drag-handle size-5 text-text-subtle cursor-grab flex-shrink-0" />

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-text truncate">{{ element.name }}</p>
                            <p class="text-xs text-text-subtle">
                                {{ pricingTypeLabel(element.pricing_type) }}
                                <span v-if="!element.is_active"> · {{ $t('deactivated') }}</span>
                            </p>
                        </div>

                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button
                                type="button"
                                class="ui-button bg-white hover:bg-surface-sunken transition text-xs px-2"
                                @click="toggleActive(element)"
                            >
                                {{ element.is_active ? $t('Deactivate') : $t('Activate') }}
                            </button>
                            <button type="button" class="ui-button bg-white hover:bg-surface-sunken transition" @click="startEdit(element)">
                                <IconEdit class="size-4 text-accent-600" />
                            </button>
                            <button type="button" class="ui-button bg-danger-surface hover:bg-danger-surface transition" @click="askDelete(element)">
                                <IconTrash class="size-4 text-danger" />
                            </button>
                        </div>
                    </div>
                </template>
            </draggable>
        </template>

        <!-- Anlage/Bearbeitung -->
        <ArtworkBaseModal
            v-if="showEditModal"
            :title="editing?.id ? $t('Edit category') : $t('Add category')"
            :description="$t('The calculation role determines how the category counts in the quotas.')"
            @close="closeEdit"
        >
            <div class="space-y-4 px-6 pb-6">
                <BaseInput id="bi_audience_category_name" v-model="form.name" :label="$t('Name')" />
                <ArtworkBaseListbox
                    v-model="form.pricingTypeItem"
                    :items="pricingTypeItems"
                    by="id"
                    option-label="name"
                    :label="$t('Calculation role')"
                />
                <p v-if="formError" class="text-sm text-danger">{{ formError }}</p>
                <div class="flex justify-end gap-2">
                    <BaseUIButton :label="$t('Save')" :disabled="saving || !form.name?.trim()" hide-icon @click="submitEdit" />
                </div>
            </div>
        </ArtworkBaseModal>

        <!-- Löschen -->
        <ConfirmDeleteModal
            v-if="showDeleteModal"
            @closed="showDeleteModal = false"
            @delete="deleteCategory"
            :title="$t('Delete category')"
            :description="$t('Categories with recorded values can only be deactivated, not deleted.')"
        />
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import draggable from 'vuedraggable';
import { IconCirclePlus, IconEdit, IconTrash, IconGripVertical } from '@tabler/icons-vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue';
import { useTranslation } from '@/Composeables/Translation.js';

const t = useTranslation();

const categories = ref([]);
const loading = ref(true);

const pricingTypeItems = [
    { id: 'full', name: t('Full price') },
    { id: 'reduced', name: t('Reduced') },
    { id: 'free', name: t('Free') },
];

const pricingTypeLabel = (type) => pricingTypeItems.find(i => i.id === type)?.name ?? type;

const load = async () => {
    try {
        const { data } = await axios.get(route('bi.audience-categories.index'));
        categories.value = data;
    } finally {
        loading.value = false;
    }
};

onMounted(load);

// --- Anlage / Bearbeitung ---

const showEditModal = ref(false);
const editing = ref(null);
const saving = ref(false);
const formError = ref(null);
const form = reactive({ name: '', pricingTypeItem: pricingTypeItems[0] });

const startCreate = () => {
    editing.value = null;
    form.name = '';
    form.pricingTypeItem = pricingTypeItems[0];
    formError.value = null;
    showEditModal.value = true;
};

const startEdit = (category) => {
    editing.value = category;
    form.name = category.name;
    form.pricingTypeItem = pricingTypeItems.find(i => i.id === category.pricing_type) ?? pricingTypeItems[0];
    formError.value = null;
    showEditModal.value = true;
};

const closeEdit = () => {
    showEditModal.value = false;
    editing.value = null;
};

const submitEdit = async () => {
    saving.value = true;
    formError.value = null;
    const payload = { name: form.name.trim(), pricing_type: form.pricingTypeItem?.id ?? 'full' };
    try {
        if (editing.value?.id) {
            await axios.patch(route('bi.audience-categories.update', editing.value.id), payload);
        } else {
            await axios.post(route('bi.audience-categories.store'), payload);
        }
        await load();
        closeEdit();
    } catch (error) {
        formError.value = error?.response?.data?.message ?? t('Saving failed. Please try again.');
    } finally {
        saving.value = false;
    }
};

const toggleActive = async (category) => {
    await axios.patch(route('bi.audience-categories.update', category.id), { is_active: !category.is_active });
    await load();
};

// --- Löschen ---

const showDeleteModal = ref(false);
const toDelete = ref(null);
const pageError = ref(null);

const askDelete = (category) => {
    toDelete.value = category;
    pageError.value = null;
    showDeleteModal.value = true;
};

const deleteCategory = async () => {
    try {
        await axios.delete(route('bi.audience-categories.destroy', toDelete.value.id));
        toDelete.value = null;
        await load();
    } catch (error) {
        // 422 = Werte vorhanden → Kategorie kann nur deaktiviert werden
        pageError.value = error?.response?.data?.message ?? t('Saving failed. Please try again.');
    } finally {
        showDeleteModal.value = false;
    }
};

const saveOrder = async () => {
    await axios.post(route('bi.audience-categories.order'), {
        ordered_ids: categories.value.map(c => c.id),
    });
};
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
