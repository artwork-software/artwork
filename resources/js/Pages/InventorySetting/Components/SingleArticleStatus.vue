<script setup>
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {defineAsyncComponent, ref} from "vue";

const props = defineProps({
    status: {
        type: Object,
        required: true
    }
})

const showAddEditArticleModal = ref(false);

const AddEditArticleStatusModal = defineAsyncComponent({
    loader: () => import('@/Pages/InventorySetting/Components/AddEditArticleStatusModal.vue'),
    delay: 200,
    timeout: 5000
})
</script>

<template>
    <td class="py-4 pr-4 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-0 dark:text-white">{{ status.name }}</td>
    <td class="p-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-300">
        <span :style="{ backgroundColor: status.color + '33', borderColor: status.color + '66' }" class="inline-block w-3 h-3 rounded-full border mr-2 p-3"></span>
    </td>
    <td class="py-4 pr-4 pl-4 text-sm whitespace-nowrap text-gray-500 sm:pr-0 dark:text-gray-300">
        <BaseMenu white-menu-background has-no-offset>
            <BaseMenuItem white-menu-background title="Edit" @click="showAddEditArticleModal = true" />
        </BaseMenu>
    </td>


    <AddEditArticleStatusModal
        :status="status"
        v-if="showAddEditArticleModal"
        @close="showAddEditArticleModal = false"
    />
</template>

<style scoped>

</style>
