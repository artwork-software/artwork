<template>
    <div class="relative p-5 rounded-lg border-2 h-full cursor-pointer flex items-center justify-center border-gray-300 hover:border-artwork-buttons-create duration-200 ease-in-out group">
        <div class="absolute bg-artwork-buttons-create/40 inset-0 rounded-md hidden group-hover:block">
            <div class="flex items-center justify-center gap-x-4 h-full">
                <div class="rounded-full p-1 bg-red-500 shadow-md" @click="showDeleteModal = true">
                    <component :is="IconX" class="size-5 text-white" />
                </div>
            </div>
        </div>
        <div class="flex flex-col items-center justify-between">
            <ComponentIcons :type="component.component.type" />
            <div>{{ $t(component.component.name) }}</div>
        </div>
    </div>

    <ConfirmDeleteModal
        v-if="showDeleteModal"
        @closed="showDeleteModal = false"
        @delete="deleteComponent"
        :title="$t('Delete component')"
        :description="$t('Are you sure you want to delete this component?')"
    />
</template>

<script setup>

import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {ref} from "vue";
import {router} from "@inertiajs/vue3";
import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";
import {IconX} from "@tabler/icons-vue";

const props = defineProps({
    component: {
        type: Object,
        required: true,
    }
})

const showDeleteModal = ref(false);


const deleteComponent = () => {
    showDeleteModal.value = false;
    router.delete(route('project-print-layout.components.destroy', {printLayoutComponent: props.component.id}));
}
</script>

<style scoped>

</style>