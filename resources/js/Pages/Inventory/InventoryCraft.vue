<template>
    <tr @click="toggleCraft()">
        <td :colspan="colspan" class="h-32 pl-3 cursor-pointer subpixel-antialiased text-2xl font-bold">
            <div class="flex flex-row items-center gap-x-3">
                <span class="">{{ craft.name }}</span>
                <IconChevronUp v-if="craftShown" class="w-5 h-5"/>
                <IconChevronDown v-else class="w-5 h-5"/>
            </div>
        </td>
    </tr>
    <template v-if="craftShown"
              v-for="(category) in craft.categories">
        <InventoryCategory :colspan="6" :category="category"/>
    </template>
</template>

<script setup>
import InventoryCategory from "@/Pages/Inventory/InventoryCategory.vue";
import {IconChevronDown, IconChevronUp} from "@tabler/icons-vue";
import {ref} from "vue";

const props = defineProps({
    colspan: Number,
    craft: Object
}),
    craftShown = ref(true),
    toggleCraft = () => {
        craftShown.value = !craftShown.value;
    };
</script>

<style scoped>
.fade-enter-active { /* <<transition name>>-<<transition class>> */
    transition: all .3s ease;
}
.fade-leave-active {
    transition: all .8s cubic-bezier(1.0, 0.5, 0.8, 1.0);
}
</style>
