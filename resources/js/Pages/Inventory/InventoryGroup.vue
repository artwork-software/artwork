<template>
    <tr @click="toggleGroup()">
        <td :colspan="colspan" class="border-4 pl-3 p-2 cursor-pointer bg-secondary text-xs">
            <div class="flex flex-row items-center gap-x-3">
                <span>{{ group.name }}</span>
                <IconChevronUp v-if="groupShown" class="w-5 h-5"/>
                <IconChevronDown v-else class="w-5 h-5"/>
            </div>
        </td>
    </tr>
    <template v-if="groupShown" v-for="(item) in group.items">
        <InventoryItem :item="item"/>
    </template>
</template>

<script setup>

import InventoryItem from "@/Pages/Inventory/InventoryItem.vue";
import {ref} from "vue";
import {IconChevronDown, IconChevronUp} from "@tabler/icons-vue";

const props = defineProps({
        colspan: Number,
        group: Object
    }),
    groupShown = ref(true),
    toggleGroup = () => {
        groupShown.value = !groupShown.value;
    };
;
</script>
