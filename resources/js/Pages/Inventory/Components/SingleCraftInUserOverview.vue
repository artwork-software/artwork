<template>
    <tr class="pl-2 h-full cursor-pointer w-full xsLight pb-1" @click="craft.closed = !craft.closed">
        <td>
            <div class="py-2.5 px-2 bg-gray-50/30 w-full" :class="craft.closed ? 'rounded-lg' : 'rounded-t-lg'">
                <div class="flex stickyYAxisNoMarginLeft w-48 items-center gap-x-2">
                    {{craft.name}}
                    <ChevronDownIcon
                        :class="craft.closed ? '' : 'rotate-180 transform'"
                        class="h-4 w-4 mt-0.5"
                    />
                </div>
            </div>
        </td>
    </tr>
    <SingleCategoryInCraft v-for="category in craft.filtered_inventory_categories"
                           :multi-edit="multiEdit"
                           :category="category" :days="days" v-if="!craft.closed"/>
</template>

<script setup>

import {ChevronDownIcon} from "@heroicons/vue/outline";
import {ref} from "vue";
import SingleCategoryInCraft from "@/Pages/Inventory/Components/SingleCategoryInCraft.vue";

const props = defineProps({
    craft: {
        type: Object,
        required: true
    },
    days: {
        type: Object,
        required: true
    },
    multiEdit: {
        type: Boolean,
        required: false,
        default: false
    }
})

</script>

<style scoped>
.stickyYAxisNoMarginLeft {
    position: sticky;
    align-self: flex-start;
    position: -webkit-sticky;
    left: 0.6rem;
    z-index: 22;
}
</style>
