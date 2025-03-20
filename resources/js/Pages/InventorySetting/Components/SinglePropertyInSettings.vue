<template>
    <td class="py-4 pr-4 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0 first-letter:capitalize">{{ property?.name }}</td>
    <td class="p-4 text-sm whitespace-nowrap text-gray-500">{{ property.tooltip_text ?? $t('No tooltip text') }}</td>
    <td class="p-4 text-sm whitespace-nowrap text-gray-500">{{ $t(capitalizeFirstLetter(property?.type)) }}</td>
    <td class="p-4 text-sm whitespace-nowrap text-gray-500">
        <span v-if="property?.is_filterable" class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-green-600/20 ring-inset">{{ $t('Yes') }}</span>
        <span v-else class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-red-600/20 ring-inset">{{ $t('No') }}</span>
    </td>
    <td class="p-4 text-sm whitespace-nowrap text-gray-500">
        <span v-if="property?.show_in_list" class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-green-600/20 ring-inset">{{ $t('Yes') }}</span>
        <span v-else class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-red-600/20 ring-inset">{{ $t('No') }}</span>
    </td>
    <td class="py-4 pr-4 pl-4 text-sm whitespace-nowrap text-gray-500 sm:pr-0">
        <div class="flex items-center gap-x-4">
            <button type="button" class="text-artwork-buttons-create hover:text-artwork-buttons-hover">
                <component is="IconEdit" class="h-5 w-5" aria-hidden="true" @click="showAddEditPropertyModal = true" />
            </button>
            <button type="button" class="text-red-600 hover:text-red-900">
                <component is="IconTrash" class="h-5 w-5" aria-hidden="true" @click="showDeleteConfirmation = true" />
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

</style>