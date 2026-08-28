<template>
    <td class="py-4 pr-4 pl-6 text-sm font-medium whitespace-nowrap text-text first-letter:capitalize max-w-name truncate" :title="property?.name">{{ property?.name }}</td>
        <td class="p-4 text-sm whitespace-nowrap text-text-subtle max-w-tooltip truncate" :title="property.tooltip_text ?? $t('No tooltip text')">{{ property.tooltip_text ?? $t('No tooltip text') }}</td>
        <td class="p-4 text-sm whitespace-nowrap text-text-subtle">{{ $t(capitalizeFirstLetter(property?.type)) }}</td>
        <td class="p-4 text-sm whitespace-nowrap text-text-subtle">
            <span v-if="property?.is_filterable" class="inline-flex items-center rounded-md bg-success-surface px-2 py-1 text-xs font-medium text-success ring-1 ring-success ring-inset">{{ $t('Yes') }}</span>
            <span v-else class="inline-flex items-center rounded-md bg-danger-surface px-2 py-1 text-xs font-medium text-danger ring-1 ring-danger ring-inset">{{ $t('No') }}</span>
        </td>
        <td class="p-4 text-sm whitespace-nowrap text-text-subtle">
            <span v-if="property?.show_in_list" class="inline-flex items-center rounded-md bg-success-surface px-2 py-1 text-xs font-medium text-success ring-1 ring-success ring-inset">{{ $t('Yes') }}</span>
            <span v-else class="inline-flex items-center rounded-md bg-danger-surface px-2 py-1 text-xs font-medium text-danger ring-1 ring-danger ring-inset">{{ $t('No') }}</span>
        </td>
    <td class="p-4 text-sm whitespace-nowrap text-text-subtle">
        <span v-if="property?.is_required" class="inline-flex items-center rounded-md bg-success-surface px-2 py-1 text-xs font-medium text-success ring-1 ring-success ring-inset">{{ $t('Yes') }}</span>
        <span v-else class="inline-flex items-center rounded-md bg-danger-surface px-2 py-1 text-xs font-medium text-danger ring-1 ring-danger ring-inset">{{ $t('No') }}</span>
    </td>

    <!-- Actions column -->
    <td class="py-5 pr-4 pl-4 text-sm whitespace-nowrap text-text-subtle actions-column sticky right-0">
        <div class="flex items-center gap-x-4">
            <button type="button" class="text-accent-600 hover:text-accent-700">
                <component :is="IconEdit" class="h-5 w-5" aria-hidden="true" @click="showAddEditPropertyModal = true" />
            </button>
            <button type="button" class="text-danger hover:text-danger" v-if="property.is_deletable">
                <component :is="IconTrash" class="h-5 w-5" aria-hidden="true" @click="showDeleteConfirmation = true" />
            </button>
        </div>
    </td>

    <ConfirmDeleteModal
        v-if="showDeleteConfirmation"
        :title="$t('Delete property')"
        :description="$t('Are you sure you want to delete this property?')"
        @delete="deleteProperty"
        @closed="showDeleteConfirmation = false"
    />

    <AddEditArticlePropertyModal
        v-if="showAddEditPropertyModal"
        @close="showAddEditPropertyModal = false"
        :property="property"
    />
</template>

<script setup>

import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {ref} from "vue";
import {router} from "@inertiajs/vue3";
import AddEditArticlePropertyModal from "@/Pages/InventorySetting/Components/AddEditArticlePropertyModal.vue";
import {IconEdit, IconTrash} from "@tabler/icons-vue";

const props = defineProps({
    property: {
        type: Object,
        required: true
    }
})

const showDeleteConfirmation = ref(false)
const showAddEditPropertyModal = ref(false)
const capitalizeFirstLetter = (val) => {
    return String(val).charAt(0).toUpperCase() + String(val).slice(1);
}

const deleteProperty = () => {
    router.delete(route('inventory-management.settings.properties.delete', {inventoryArticleProperty: props.property.id}), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirmation.value = false
        }
    })
}
</script>

<style scoped>
.actions-column {
    width: 100px;
    min-width: 100px;
    background-color: white;
    /* Abgrenzung zur darunter durchscrollenden Tabelle (H-Scroll-Fall) */
    border-left: 1px solid #e5e7eb;
    box-shadow: -4px 0 6px -2px rgba(0, 0, 0, 0.06);
}

.max-w-name {
    max-width: 200px;
}

.max-w-tooltip {
    max-width: 250px;
}
</style>
