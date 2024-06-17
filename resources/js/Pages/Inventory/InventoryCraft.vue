<template>
    <tr>
        <td :colspan="colspan" class="h-24 pl-3 border-4 subpixel-antialiased text-2xl font-bold">
            <div class="flex flex-row items-center gap-x-2">
                <div class="flex flex-row items-center w-auto gap-x-2 cursor-pointer"
                     @click="toggleCraft()">
                    <span>{{ craft.name }}</span>
                    <IconChevronUp v-if="craftShown" class="w-5 h-5"/>
                    <IconChevronDown v-else class="w-5 h-5"/>
                </div>
                <IconLink class="w-5 h-5 cursor-pointer" @click="openShiftSettingsInNewTab()"/>
            </div>
        </td>
    </tr>
    <AddNewCategory v-if="craftShown"/>
    <template v-if="craftShown"
              v-for="(category) in craft.categories">
        <InventoryCategory :colspan="6"
                           :category="category"/>
    </template>
    <AddNewCategory v-if="craftShown"/>
</template>

<script setup>
import InventoryCategory from "@/Pages/Inventory/InventoryCategory.vue";
import {IconChevronDown, IconChevronUp, IconLink} from "@tabler/icons-vue";
import {ref} from "vue";
import AddNewCategory from "@/Pages/Inventory/AddNewCategory.vue";

const props = defineProps({
        colspan: Number,
        craft: Object,
        selectInput: Function,
        createDynamicColumnNameInputRef: Function
    }),
    craftShown = ref(true),
    toggleCraft = () => {
        craftShown.value = !craftShown.value;
    },
    openShiftSettingsInNewTab = () => {
        window.open(route('shift.settings'), '_blank');
    };
</script>
