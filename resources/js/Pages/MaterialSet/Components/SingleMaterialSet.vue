<template>
    <tr :key="set.id" class="divide-x divide-gray-200">
        <td class="py-4 pr-4 pl-4 text-sm font-medium text-gray-900 sm:pl-0">{{ set.name }}</td>
        <td class="p-4 text-sm text-gray-500 w-[50%]">
            <div class="line-clamp-1">
                {{ set.description || '–' }}
            </div>
        </td>
        <td class="p-4 text-sm text-gray-500 w-fit">
            <ToolTipWithTextComponent :text="set.items?.length + ' ' + $t('Items in this set.')" direction="top" :tooltip-text="createToolTipTextByItems" />
        </td>
        <td class="py-4 pr-4 pl-4 text-sm text-gray-500 sm:pr-0">
            <div class="flex space-x-3">
                <button @click="showCreateOrUpdateMaterialSetModal = true" class="text-blue-600 hover:underline text-sm">
                    <component is="IconEdit" class="size-4 mr-1" />
                </button>
                <button @click="showConfirmDeleteModal = true" class="text-red-600 hover:underline text-sm">
                    <component is="IconTrash" class="size-4 mr-1" />
                </button>
            </div>
        </td>
    </tr>

    <CreateOrUpdateMaterialSetModal
        v-if="showCreateOrUpdateMaterialSetModal"
        @close="showCreateOrUpdateMaterialSetModal = false"
        :material-set="set"
        />

    <ConfirmDeleteModal
        v-if="showConfirmDeleteModal"
        @closed="showConfirmDeleteModal = false"
        @delete="deleteSet"
        :title="$t('Delete Material Set')"
        :description="$t('Are you sure you want to delete this material set? This action cannot be undone.')"
    />
</template>

<script setup>

import {computed, defineAsyncComponent, ref} from "vue";
import {router} from "@inertiajs/vue3";

const props = defineProps({
    set: {
        type: Object,
        required: true
    }
})

const showCreateOrUpdateMaterialSetModal = ref(false);
const showConfirmDeleteModal = ref(false);


const CreateOrUpdateMaterialSetModal = defineAsyncComponent({
    loader: () => import('@/Pages/MaterialSet/Components/CreateOrUpdateMaterialSetModal.vue'),
    delay: 200,
    timeout: 5000
})

const ConfirmDeleteModal = defineAsyncComponent({
    loader: () => import('@/Layouts/Components/ConfirmDeleteModal.vue'),
    delay: 200,
    timeout: 5000
})

const ToolTipWithTextComponent = defineAsyncComponent({
    loader: () => import('@/Components/ToolTips/ToolTipWithTextComponent.vue'),
    delay: 200,
    timeout: 5000
})

const createToolTipTextByItems = computed(() => {
    // return item name with count
    return props.set.items.map(item => item.quantity + 'x ' + item.name).join(', ')  + ' (' + props.set.items.length + ' Artikel)';
})

const deleteSet = () => {
    router.delete(route('material-sets.destroy', props.set.id), {
        preserveScroll: true,
        onSuccess: () => {
            showConfirmDeleteModal.value = false;
        },
        onError: (error) => {
            console.error('Error deleting material set:', error);
        }
    });
}


</script>

<style scoped>

</style>