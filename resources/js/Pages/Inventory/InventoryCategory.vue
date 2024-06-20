<template>
    <tr @click="toggleCategory()">
        <td :colspan="colspan" class="pl-3 p-2 border-4 cursor-pointer bg-primary text-white subpixel-antialiased text-sm">
            <div class="flex flex-row items-center gap-x-3">
                <span>{{ category.name }}</span>
                <IconChevronUp v-if="categoryShown" class="w-5 h-5"/>
                <IconChevronDown v-else class="w-5 h-5"/>
            </div>
        </td>
    </tr>
    <template v-if="categoryShown" v-for="(group) in category.groups">
        <InventoryGroup :group="group" :colspan="colspan"/>
    </template>
</template>

<script setup>

import InventoryGroup from "@/Pages/Inventory/InventoryGroup.vue";
import {IconChevronDown, IconChevronUp} from "@tabler/icons-vue";
import {ref} from "vue";

const props = defineProps({
        category: Object,
        colspan: Number
    }),
    categoryShown = ref(true),
    toggleCategory = () => {
        categoryShown.value = !categoryShown.value;
    };
</script>
