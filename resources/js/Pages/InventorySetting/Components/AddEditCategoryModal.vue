<template>
    <ArtworkBaseModal
        :title="category ? 'Edit category' : 'Add category'"
        :description="category ? '' : 'Add a new category to the inventory management system.'"
        modal-size="sm:max-w-3xl"
        @close="$emit('close')"
    >
        <form id="inventoryCategoryForm" @submit.prevent="createOrUpdateCategory">
            <BaseInput
                id="category-name"
                v-model="categoryForm.name"
                label="Category Name"
                required
                :error="categoryForm.errors.name"
                class="mb-8"
            />

            <div class="mb-3">
                <BasePageTitle
                    title="Article properties"
                    description="Add properties to the category that the items in this category should have."
                />
                <SettingsGuideBanner
                    variant="static"
                    class="mt-3"
                    title="How do properties work here?"
                    :paragraphs="[
                        'The properties assigned here become the fields of the article form for this category.',
                        'The drag & drop order determines the order of the fields in the form and of the columns in the article overview.',
                        'Sub-categories add further fields on top of the fields of the category.',
                    ]"
                />
                <div class="my-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-border">
                        <thead>
                        <tr class="divide-x divide-border-subtle">
                            <th scope="col" class="py-3.5 pr-4 text-left text-sm font-semibold text-text">{{ $t('Name') }}</th>
                            <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Type') }}</th>
                            <th scope="col" class="py-3.5 pl-4 text-left text-sm font-semibold text-text">{{ $t('Default Value') }}</th>
                        </tr>
                        </thead>
                        <draggable
                            tag="tbody"
                            :list="categoryForm.properties"
                            item-key="id"
                            handle=".drag-handle"
                            ghost-class="opacity-50"
                            class="divide-y divide-border-subtle bg-surface"
                        >
                            <template #item="{ element: property }">
                                <tr class="divide-x divide-border-subtle">
                                    <td class="py-4 pr-4 text-sm font-medium whitespace-nowrap text-text first-letter:capitalize">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2">
                                                <span class="drag-handle inline-flex rounded p-1 hover:bg-surface-sunken" :aria-label="$t('Drag to reorder')">
                                                    <component :is="IconGripVertical" class="size-4" aria-hidden="true" />
                                                </span>
                                                {{ property?.name }}
                                            </div>
                                            <button
                                                type="button"
                                                class="text-text-muted transition-colors duration-150 hover:text-danger"
                                                :aria-label="$t('Remove')"
                                                @click="removePropertyFromCategory(property)"
                                            >
                                                <component :is="IconTrash" class="size-5" aria-hidden="true" />
                                            </button>
                                        </div>
                                    </td>
                                    <td class="p-4 text-sm/5 font-bold whitespace-nowrap text-text-subtle capitalize cursor-default">
                                        {{ $t(capitalizeFirstLetter(property?.type)) }}
                                    </td>
                                    <td class="py-2 pl-4 text-sm whitespace-nowrap text-text-subtle">
                                        <BaseInput
                                            v-if="usesPlainInput(property)"
                                            :id="'category-default-' + property.id"
                                            v-model="property.defaultValue"
                                            :type="property.type"
                                            label="Default Value"
                                            :show-label="false"
                                            :placeholder="$t('Default Value')"
                                            :enable-time-autofill="false"
                                            is-small
                                        />
                                        <BaseCheckbox
                                            v-else-if="property.type === 'checkbox'"
                                            :id="'category-default-' + property.id"
                                            v-model="property.defaultValue"
                                        >
                                            <template #label>
                                                <span class="sr-only">{{ $t('Default Value') }}</span>
                                            </template>
                                        </BaseCheckbox>
                                        <BaseCombobox
                                            v-else-if="property.type === 'room'"
                                            v-model="property.defaultValue"
                                            :items="rooms ?? []"
                                            :placeholder="$t('Please select')"
                                            :empty-text="$t('No results')"
                                        />
                                        <BaseCombobox
                                            v-else-if="property.type === 'manufacturer'"
                                            v-model="property.defaultValue"
                                            :items="manufacturers ?? []"
                                            :placeholder="$t('Please select')"
                                            :empty-text="$t('No results')"
                                        />
                                        <SearchableSelect
                                            v-else-if="property.type === 'selection'"
                                            v-model="property.defaultValue"
                                            :options="property.select_values"
                                            :placeholder="$t('Please select')"
                                        />
                                    </td>
                                </tr>
                            </template>
                        </draggable>
                        <tbody>
                            <tr>
                                <td colspan="3" class="py-4 text-sm font-medium whitespace-nowrap text-text">
                                    <PropertiesMenu has-no-offset>
                                        <template #button>
                                            <span class="flex items-center gap-x-2 font-lexend font-bold text-text-subtle transition-colors duration-200 hover:text-text-muted">
                                                <component :is="IconLibraryPlus" class="size-5" aria-hidden="true" />
                                                {{ $t('Add property') }}
                                            </span>
                                        </template>
                                        <template #menu>
                                            <template v-if="filteredPropertiesByCategory.length > 0">
                                                <button
                                                    v-for="availableProperty in filteredPropertiesByCategory"
                                                    :key="availableProperty.id"
                                                    type="button"
                                                    class="block w-full rounded-lg px-4 py-3 text-left text-sm/5 font-semibold text-text transition-colors duration-200 hover:bg-surface-sunken"
                                                    @click="addPropertyToCategory(availableProperty)"
                                                >
                                                    {{ availableProperty.name }}
                                                </button>
                                            </template>
                                            <p v-else class="px-4 py-3 text-xs text-text-muted">
                                                {{ $t('All properties are already added to the category.') }}
                                            </p>
                                        </template>
                                    </PropertiesMenu>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <div class="flex items-start justify-between gap-4">
                    <BasePageTitle
                        title="Sub-Categories"
                        description="Add sub-categories to the category."
                    />
                    <BaseUIButton
                        variant="ghost"
                        size="sm"
                        icon="IconCategoryPlus"
                        label="Add sub-category"
                        class="shrink-0"
                        @click="addEmptySubCategory"
                    />
                </div>

                <div class="my-4 w-full">
                    <Disclosure
                        v-for="subCategory in subCategories"
                        :key="subCategory.uid"
                        v-slot="{ open }"
                        as="div"
                        class="mt-1"
                    >
                        <DisclosureButton class="flex w-full justify-between rounded-lg bg-surface-sunken px-2 py-3 text-left text-sm font-medium text-text hover:bg-surface-hover focus:outline-none focus-visible:ring focus-visible:ring-accent-600">
                            <span class="flex w-full items-center justify-between gap-x-2 pr-5">
                                <span v-if="subCategory.name">{{ subCategory.name }}</span>
                                <span v-else class="text-text-muted">{{ $t('Sub-Category without name. Please add a name') }}</span>
                                <component
                                    :is="IconTrash"
                                    class="size-5 cursor-pointer text-text-muted transition-colors duration-150 hover:text-danger"
                                    :aria-label="$t('Delete sub-category')"
                                    @click.stop="askRemoveSubCategory(subCategory)"
                                />
                            </span>
                            <component
                                :is="IconChevronUp"
                                :class="open ? 'rotate-180 transform' : ''"
                                class="size-5 text-text-muted"
                                aria-hidden="true"
                            />
                        </DisclosureButton>
                        <DisclosurePanel class="px-4 pt-4 pb-2 text-sm text-text-subtle">
                            <BaseInput
                                :id="'sub-category-name-' + subCategory.uid"
                                v-model="subCategory.name"
                                label="Sub-Category Name"
                                required
                                class="mb-8"
                            />

                            <div class="mb-3">
                                <BasePageTitle
                                    title="Article properties"
                                    description="Add properties to the category that the items in this category should have."
                                />
                                <div class="my-4 overflow-x-auto">
                                    <table class="min-w-full divide-y divide-border">
                                        <thead>
                                        <tr class="divide-x divide-border-subtle">
                                            <th scope="col" class="py-3.5 pr-4 text-left text-sm font-semibold text-text">{{ $t('Name') }}</th>
                                            <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Type') }}</th>
                                            <th scope="col" class="py-3.5 pl-4 text-left text-sm font-semibold text-text">{{ $t('Default Value') }}</th>
                                        </tr>
                                        </thead>
                                        <draggable
                                            tag="tbody"
                                            :list="subCategory.properties"
                                            item-key="id"
                                            handle=".drag-handle"
                                            ghost-class="opacity-50"
                                            class="divide-y divide-border-subtle bg-surface"
                                        >
                                            <template #item="{ element: property }">
                                                <tr class="divide-x divide-border-subtle">
                                                    <td class="py-4 pr-4 text-sm font-medium whitespace-nowrap text-text first-letter:capitalize">
                                                        <div class="flex items-center justify-between gap-2">
                                                            <div class="flex items-center gap-2">
                                                                <span class="drag-handle inline-flex rounded p-1 hover:bg-surface-sunken" :aria-label="$t('Drag to reorder')">
                                                                    <component :is="IconGripVertical" class="size-4" aria-hidden="true" />
                                                                </span>
                                                                {{ property?.name }}
                                                            </div>
                                                            <button
                                                                type="button"
                                                                class="text-text-muted transition-colors duration-150 hover:text-danger"
                                                                :aria-label="$t('Remove')"
                                                                @click="removePropertyFromSubCategory(property, subCategory)"
                                                            >
                                                                <component :is="IconTrash" class="size-5" aria-hidden="true" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="p-4 text-sm/5 font-bold whitespace-nowrap text-text-subtle capitalize cursor-default">
                                                        {{ $t(capitalizeFirstLetter(property?.type)) }}
                                                    </td>
                                                    <td class="py-2 pl-4 text-sm whitespace-nowrap text-text-subtle">
                                                        <BaseInput
                                                            v-if="usesPlainInput(property)"
                                                            :id="'sub-category-' + subCategory.uid + '-default-' + property.id"
                                                            v-model="property.defaultValue"
                                                            :type="property.type"
                                                            label="Default Value"
                                                            :show-label="false"
                                                            :placeholder="$t('Default Value')"
                                                            :enable-time-autofill="false"
                                                            is-small
                                                        />
                                                        <BaseCheckbox
                                                            v-else-if="property.type === 'checkbox'"
                                                            :id="'sub-category-' + subCategory.uid + '-default-' + property.id"
                                                            v-model="property.defaultValue"
                                                        >
                                                            <template #label>
                                                                <span class="sr-only">{{ $t('Default Value') }}</span>
                                                            </template>
                                                        </BaseCheckbox>
                                                        <BaseCombobox
                                                            v-else-if="property.type === 'room'"
                                                            v-model="property.defaultValue"
                                                            :items="rooms ?? []"
                                                            :placeholder="$t('Please select')"
                                                            :empty-text="$t('No results')"
                                                        />
                                                        <BaseCombobox
                                                            v-else-if="property.type === 'manufacturer'"
                                                            v-model="property.defaultValue"
                                                            :items="manufacturers ?? []"
                                                            :placeholder="$t('Please select')"
                                                            :empty-text="$t('No results')"
                                                        />
                                                        <SearchableSelect
                                                            v-else-if="property.type === 'selection'"
                                                            v-model="property.defaultValue"
                                                            :options="property.select_values"
                                                            :placeholder="$t('Please select')"
                                                        />
                                                    </td>
                                                </tr>
                                            </template>
                                        </draggable>
                                        <tbody>
                                        <tr>
                                            <td colspan="3" class="py-4 text-sm font-medium whitespace-nowrap text-text">
                                                <PropertiesMenu has-no-offset>
                                                    <template #button>
                                                        <span class="flex items-center gap-x-2 font-lexend font-bold text-text-subtle transition-colors duration-200 hover:text-text-muted">
                                                            <component :is="IconLibraryPlus" class="size-5" aria-hidden="true" />
                                                            {{ $t('Add property') }}
                                                        </span>
                                                    </template>
                                                    <template #menu>
                                                        <template v-if="getFilteredPropertiesForSubCategory(subCategory).length > 0">
                                                            <button
                                                                v-for="availableProperty in getFilteredPropertiesForSubCategory(subCategory)"
                                                                :key="availableProperty.id"
                                                                type="button"
                                                                class="block w-full rounded-lg px-4 py-3 text-left text-sm/5 font-semibold text-text transition-colors duration-200 hover:bg-surface-sunken"
                                                                @click="addPropertyToSubCategory(availableProperty, subCategory)"
                                                            >
                                                                {{ availableProperty.name }}
                                                            </button>
                                                        </template>
                                                        <p v-else class="px-4 py-3 text-xs text-text-muted">
                                                            {{ $t('All properties are already added to the sub-category.') }}
                                                        </p>
                                                    </template>
                                                </PropertiesMenu>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </DisclosurePanel>
                    </Disclosure>
                </div>
            </div>
        </form>

        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="categoryForm.processing"
                @click="$emit('close')"
            />
            <BaseUIButton
                type="submit"
                form="inventoryCategoryForm"
                :label="category ? 'Update' : 'Create'"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="categoryForm.processing"
                :disabled="!canSubmit"
            />
        </template>

        <ConfirmDeleteModal
            v-if="subCategoryToDelete"
            :title="$t('Delete sub-category')"
            :description="$t('Are you sure you want to delete this sub-category? Articles in this sub-category will be deleted as well.')"
            @delete="confirmRemoveSubCategory"
            @closed="subCategoryToDelete = null"
        />
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, onMounted, ref} from 'vue'
import {router, useForm} from '@inertiajs/vue3'
import {Disclosure, DisclosureButton, DisclosurePanel} from '@headlessui/vue'
import {IconChevronUp, IconGripVertical, IconLibraryPlus, IconTrash} from '@tabler/icons-vue'
import draggable from 'vuedraggable'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'
import BaseCombobox from '@/Artwork/Inputs/BaseCombobox.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import SearchableSelect from '@/Artwork/Listbox/SearchableSelect.vue'
import BasePageTitle from '@/Artwork/Titles/BasePageTitle.vue'
import SettingsGuideBanner from '@/Artwork/Guide/SettingsGuideBanner.vue'
import PropertiesMenu from '@/Components/Menu/PropertiesMenu.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'

defineOptions({name: 'AddEditCategoryModal'})

const props = defineProps({
    category: {
        type: Object,
        required: false,
        default: null,
    },
    properties: {
        type: [Array, Object],
        required: false,
        default: () => [],
    },
    rooms: {
        type: [Array, Object],
        required: false,
        default: () => [],
    },
    manufacturers: {
        type: [Array, Object],
        required: false,
        default: () => [],
    },
})

const emit = defineEmits(['close'])

const categoryForm = useForm({
    id: props.category?.id ?? null,
    name: props.category?.name ?? '',
    properties: [],
    subcategories: props.category?.subcategories ?? [],
})

const subCategories = ref([])

// Stabiler Schlüssel für Unterkategorien, auch für noch nicht gespeicherte (id = null)
let subCategoryUid = 0
const nextSubCategoryUid = () => ++subCategoryUid

const SPECIAL_INPUT_TYPES = ['file', 'checkbox', 'room', 'manufacturer', 'selection']
const usesPlainInput = (property) => !SPECIAL_INPUT_TYPES.includes(property.type)

const availableProperties = computed(() => props.properties ?? [])

const filteredPropertiesByCategory = computed(() => availableProperties.value.filter(
    property => !categoryForm.properties.some(p => p.id === property.id)
))

// Nur prüfen, ob bereits in der Hauptkategorie oder in dieser Unterkategorie
const getFilteredPropertiesForSubCategory = (currentSubCategory) => availableProperties.value.filter(
    property => !categoryForm.properties.some(p => p.id === property.id)
        && !currentSubCategory.properties.some(p => p.id === property.id)
)

const toEditableProperty = (property, idx) => {
    let defaultValue = property.pivot?.value ?? ''
    if (property.type === 'checkbox') {
        defaultValue = defaultValue === 'true'
    }
    return {
        id: property.id,
        name: property.name,
        type: property.type,
        select_values: property.select_values,
        defaultValue,
        position: property.pivot?.position ?? idx,
    }
}

const newEditableProperty = (property) => ({
    id: property.id,
    name: property.name,
    type: property.type,
    select_values: property.select_values,
    defaultValue: property.type === 'checkbox' ? false : '',
})

const byPosition = (a, b) => (a.position ?? 0) - (b.position ?? 0)

const addEmptySubCategory = () => {
    subCategories.value.push({
        uid: nextSubCategoryUid(),
        id: null,
        name: '',
        properties: [],
    })
}

const addPropertyToCategory = (property) => {
    if (!categoryForm.properties) {
        categoryForm.properties = []
    }
    if (!categoryForm.properties.some(p => p.id === property.id)) {
        categoryForm.properties.push(newEditableProperty(property))
    }
}

const addPropertyToSubCategory = (property, subCategory) => {
    if (!subCategory.properties) {
        subCategory.properties = []
    }
    if (!subCategory.properties.some(p => p.id === property.id)) {
        subCategory.properties.push(newEditableProperty(property))
    }
}

onMounted(() => {
    if (!props.category) {
        return
    }
    categoryForm.properties = (props.category.properties ?? []).map(toEditableProperty).sort(byPosition)
    subCategories.value = (props.category.subcategories ?? []).map((sub) => ({
        uid: nextSubCategoryUid(),
        id: sub.id,
        name: sub.name,
        properties: (sub.properties ?? []).map(toEditableProperty).sort(byPosition),
    }))
})

const checkIfAllSubCategoriesHasName = computed(() => subCategories.value.some(subCategory => !subCategory.name))

const canSubmit = computed(() => !categoryForm.processing
    && String(categoryForm.name ?? '').trim() !== ''
    && !checkIfAllSubCategoriesHasName.value)

const removePropertyFromCategory = (property) => {
    categoryForm.properties = categoryForm.properties.filter(p => p.id !== property.id)
}

const removePropertyFromSubCategory = (property, subCategory) => {
    subCategory.properties = subCategory.properties.filter(p => p.id !== property.id)
}

const capitalizeFirstLetter = (val) => String(val).charAt(0).toUpperCase() + String(val).slice(1)

const normalizeProp = (p, idx) => {
    let val = p?.defaultValue
    if (p?.type === 'checkbox') {
        val = (val === true || val === 'true') ? 'true' : 'false'
    } else {
        val = (val ?? '') + ''
    }
    return {id: p.id, defaultValue: val, position: idx}
}

const createOrUpdateCategory = () => {
    if (!canSubmit.value) {
        return
    }

    // Erst beim Absenden normalisieren, damit die Tabelle bei Validierungsfehlern Name und Typ behält
    categoryForm.transform((data) => ({
        ...data,
        properties: (data.properties ?? []).map(normalizeProp),
        subcategories: (subCategories.value ?? []).map(sc => ({
            id: sc.id ?? null,
            name: sc.name ?? '',
            properties: (sc.properties ?? []).map(normalizeProp),
        })),
    }))

    const options = {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    }

    if (categoryForm.id) {
        categoryForm.patch(route('inventory-management.settings.categories.update', {inventoryCategory: categoryForm.id}), options)
    } else {
        categoryForm.post(route('inventory-management.settings.categories.create'), options)
    }
}

const subCategoryToDelete = ref(null)

// Vor dem Löschen nachfragen: im Backend werden auch alle Artikel der Unterkategorie gelöscht
const askRemoveSubCategory = (subCategory) => {
    subCategoryToDelete.value = subCategory
}

const confirmRemoveSubCategory = () => {
    if (subCategoryToDelete.value) {
        removeSubCategoryFromCategory(subCategoryToDelete.value)
    }
    subCategoryToDelete.value = null
}

const removeSubCategoryFromCategory = (subCategory) => {
    // Nach Objekt filtern: ungespeicherte Unterkategorien haben alle id = null
    const removeLocally = () => {
        subCategories.value = subCategories.value.filter(sub => sub !== subCategory)
    }
    if (subCategory.id) {
        router.delete(route('inventory-management.settings.categories.subcategories.delete', {inventorySubCategory: subCategory.id}), {
            preserveScroll: true,
            onSuccess: removeLocally,
        })
    } else {
        removeLocally()
    }
}
</script>

<style scoped>
.drag-handle { cursor: grab; }
.drag-handle:active { cursor: grabbing; }
</style>
