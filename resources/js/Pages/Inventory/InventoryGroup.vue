<template>
    <tr>
        <td :colspan="colspan" class="pl-3 p-2 cursor-pointer bg-secondary text-xs">
            <div class="w-full h-full flex flex-row items-center relative gap-x-2">
                <div
                    class="cursor-pointer overflow-hidden overflow-ellipsis whitespace-nowrap"
                    @dblclick="toggleGroupEdit()">
                    {{ group.name }}
                </div>
                <div @click="toggleGroup()">
                    <IconChevronUp v-if="groupShown" class="w-5 h-5"/>
                    <IconChevronDown v-else class="w-5 h-5"/>
                </div>
                <div
                    :class="[groupClicked ? '' : 'hidden', 'flex flex-row items-center bg-secondary text-black gap-x-2 w-full -left-[4px] z-10 absolute']">
                    <input
                        type="text"
                        ref="groupInputRef"
                        class="w-full p-1 border-0 text-xs text-black"
                        v-model="groupValue"
                        @keyup.enter="applyGroupValueChange()"
                        @keyup.esc="denyGroupValueChange()">
                    <IconCheck class="w-5 h-5 hover:text-green-500" @click="applyGroupValueChange()"/>
                    <IconX class="w-5 h-5 hover:text-red-500" @click="denyGroupValueChange()"/>
                </div>
            </div>
        </td>
    </tr>
    <AddNewItem v-if="groupShown"/>
    <template v-if="groupShown" v-for="(item) in group.items">
        <InventoryItem :item="item"/>
    </template>
</template>

<script setup>

import InventoryItem from "@/Pages/Inventory/InventoryItem.vue";
import {ref} from "vue";
import {IconChevronDown, IconChevronUp, IconCheck, IconX} from "@tabler/icons-vue";
import AddNewItem from "@/Pages/Inventory/AddNewItem.vue";
const props = defineProps({
        colspan: Number,
        group: Object
    }),
    groupInputRef = ref(null),
    groupShown = ref(true),
    groupClicked = ref(false),
    groupValue = ref(props.group.name),
    toggleGroup = () => {
        groupShown.value = !groupShown.value;
    },
    toggleGroupEdit = () => {
        groupClicked.value = !groupClicked.value;

        if (groupClicked.value) {
            setTimeout(() => {
                groupInputRef.value.select();
            }, 5);
        }
    },
    applyGroupValueChange = () => {
        props.group.name = groupValue.value;
        toggleGroupEdit();
    },
    denyGroupValueChange = () => {
        groupValue.value = props.group.name;
        toggleGroupEdit();
    };
;
</script>
