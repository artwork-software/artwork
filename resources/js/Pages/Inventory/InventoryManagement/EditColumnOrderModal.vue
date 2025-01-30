<template>
    <BaseModal v-if="show" @closed="close">
        <div class="edit-column-select-options-modal-container">
            <h1 class="headline1">
                {{ $t('Column Order') }}
            </h1>
            <span>{{ $t('Define the order of the columns in the inventory overview.') }}</span>
            <draggable ghost-class="opacity-50" key="draggableKey" item-key="id" :list="columns" @start="dragging = true" @end="dragging = false">
                <template #item="{element}" :key="element.id">
                    <div v-show="!element.temporary" class="flex group" @mouseover="showMenu = element.id" :key="element.id" @mouseout="showMenu = false">
                        <div class="flex bg-artwork-project-background py-5 px-4 my-1 rounded-lg flex-wrap w-full" :key="element.id" :class="dragging ? 'cursor-grabbing' : 'cursor-grab'">
                            <IconDragDrop class="my-auto xsDark h-5 w-5 hidden group-hover:block"/>
                            {{ element.name }}
                        </div>
                    </div>
                </template>
            </draggable>
        </div>
        <div class="flex justify-center mt-2">
            <FormButton :text="$t('Save')" @click="updateColumnOrder(columns)"/>
        </div>
    </BaseModal>
</template>

<script setup>
import BaseModal from "@/Components/Modals/BaseModal.vue";
import {IconDragDrop} from "@tabler/icons-vue";
import draggable from "vuedraggable";
import {ref} from "vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

const emits = defineEmits(['closed']),
    props = defineProps({
        show: {
            type: Boolean,
            required: true
        },
        columns: {
            type: Array,
            required: true
        }
    }),
    showMenu = ref(false),
    dragging = ref(false),
    updateColumnOrder = (columns) => {
        emits.call(this, 'closed', true, columns);
    },
    close = () => {
        emits.call(this, 'closed');
    }
</script>
