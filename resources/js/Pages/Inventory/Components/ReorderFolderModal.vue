<template>
    <BaseModal @closed="$emit('close')">
        <ModalHeader :title="$t('Reorder Folders')" :description="$t('Drag and drop folders to reorder them.')" />


        <transition
            enter-active-class="duration-300 ease-out"
            enter-from-class="transform opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="transform opacity-0"
        >
            <div class="my-3 text-xs bg-green-600 px-3 py-1.5 text-white rounded-lg" v-show="showSaveSuccess">
                {{ $t('Saved. The changes have been successfully applied.') }}
            </div>
        </transition>
        <div>
            <draggable ghost-class="opacity-50" key="draggableKey" item-key="id" :list="folders" @start="dragging=true" @end="dragging=false" @change="updateFolderOrder(folders)">
                <template #item="{element}" :key="element.id">
                    <div class="flex group">
                        <div class="flex bg-artwork-project-background py-5 px-4 my-1 rounded-lg flex-wrap w-full" :key="element.id" :class="dragging? 'cursor-grabbing' : 'cursor-grab'">
                            <div class="flex w-full">
                                <div class="flex">
                                    <IconDragDrop class="my-auto xsDark h-5 w-5 hidden group-hover:block"/>
                                    <Link :href="route('rooms.show',{room: element.id})" class="ml-4 my-auto xsDark">
                                        {{ element.name }}
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </draggable>
        </div>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import draggable from "vuedraggable";
import {IconDragDrop} from "@tabler/icons-vue";
import {Link, router} from "@inertiajs/vue3";
import {ref} from "vue";

const props = defineProps({
    folders: {
        type: Object,
        required: true
    }
})

const dragging = ref(false)
const showMenu = ref(null)
const showSaveSuccess = ref(false)
const updateFolderOrder = (folders) => {
    folders.map((folder, index) => {
        folder.order = index + 1
    })

    router.patch(route('inventory-management.inventory.folder.update.order'), {
        folders: folders
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showSaveSuccess.value = true
            setTimeout(() => {
                showSaveSuccess.value = false
            }, 3000)
        }
    })
}

const emits = defineEmits(['close'])

</script>

<style scoped>

</style>