<template>
    <td class="py-4 pr-4 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0 first-letter:capitalize">{{ category?.name }}</td>
    <td class="p-4 text-sm whitespace-nowrap text-gray-500 capitalize">
        {{ category.subcategories.map((category) => category.name).join(', ') }}
    </td>
    <td class="p-4 text-sm whitespace-nowrap text-gray-500 capitalize">
        {{ category.properties.map((property) => property.name).join(', ') }}
    </td>
    <td class="py-4 pr-4 pl-4 text-sm whitespace-nowrap text-gray-500 sm:pr-0">
        <div class="flex items-center gap-x-4">
            <button type="button" class="text-artwork-buttons-create hover:text-artwork-buttons-hover">
                <component is="IconEdit" @click="showAddEditCategoryModal = true" class="h-5 w-5" aria-hidden="true" />
            </button>
            <button type="button" class="text-red-600 hover:text-red-900">
                <component is="IconTrash" class="h-5 w-5" aria-hidden="true" @click="showDeleteConfirmation = true" />
            </button>

        </div>
    </td>

    <AddEditCategoryModal
        :category="category"
        :properties="properties"
        :rooms="rooms"
        :manufacturers="manufacturers"
        v-if="showAddEditCategoryModal"
        @close="showAddEditCategoryModal = false"
    />

    <ConfirmDeleteModal
        v-if="showDeleteConfirmation"
        :title="$t('Delete category')"
        :description="$t('Are you sure you want to delete this category? Articles and Sub-Categories in this category will be deleted as well.')"
        @delete="deleteCategory"
        @closed="showDeleteConfirmation = false"
    />
</template>

<script setup>

import AddEditCategoryModal from "@/Pages/InventorySetting/Components/AddEditCategoryModal.vue";
import {ref} from "vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {router} from "@inertiajs/vue3";

const props = defineProps({
    category: {
        type: Object,
        required: true
    },
    properties: {
        type: Object,
        required: true
    },
    rooms: {
        type: Object,
        required: true
    },
    manufacturers: {
        type: Object,
        required: true
    }
})

const showAddEditCategoryModal = ref(false);
const showDeleteConfirmation = ref(false)

const deleteCategory = () => {
    router.delete(route('inventory-management.settings.categories.delete', props.category.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirmation.value = false
        },
        onError: () => {
            showDeleteConfirmation.value = false
        }
    })
}
</script>

<style scoped>

</style>
