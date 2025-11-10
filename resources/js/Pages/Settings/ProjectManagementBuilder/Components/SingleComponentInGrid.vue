<template>
    <div
        class="bg-gray-100 rounded-lg px-4 py-3 cursor-grab flex items-center justify-center w-fit" :key="element.id">
        <div class="flex items-center gap-x-5">
            <div>
                <div class="xsDark">{{ $t(element.name) }}</div>
                <div class="xsLight">{{ $t(element.type) }}</div>
            </div>

            <div class="flex items-center" v-if="element.deletable">
                <BaseMenu has-no-offset>
                    <BaseMenuItem whiteMenuBackground title="Delete" :icon="IconTrash" @click="showDeleteConfirmationModal = true" />
                </BaseMenu>
            </div>
        </div>
    </div>

    <ConfirmDeleteModal
        v-if="showDeleteConfirmationModal"
        :title="$t('Delete {0}', [$t(element.name)])"
        :description="$t('Are you sure you want to delete the selected component from the project?')"
        @delete="removeComponentFormGrid"
        />
</template>

<script setup>

import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {ref} from "vue";
import {router} from "@inertiajs/vue3";
import {IconTrash} from "@tabler/icons-vue";

const props = defineProps({
    element: {
        type: Object,
        required: true,
    },
})

const showDeleteConfirmationModal = ref(false);

const removeComponentFormGrid = () => {
    router.delete(route('project-management-builder.destroy', {component: props.element.id}));
}

</script>

<style scoped>

</style>
