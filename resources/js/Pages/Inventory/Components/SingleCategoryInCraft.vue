<template>
    <tr class="pl-2 cursor-pointer xsLight pb-1" @click="category.closed = !category.closed">
       <td>
           <div class="px-2 py-1.5 bg-gray-50/20 w-full" :class="category.closed ? 'rounded-b-lg' : ''">
               <div class="ml-3 stickyYAxisNoMarginLeft w-48 flex items-center gap-x-1">
                   {{ category.name }}
                   <ChevronDownIcon
                       :class="category.closed ? '' : 'rotate-180 transform'"
                       class="h-4 w-4 mt-0.5"
                   />
               </div>
           </div>
       </td>
    </tr>
    <SingleGroupInCategory :multi-edit="multiEdit" v-for="group in category.groups" :days="days" :group="group" v-if="!category.closed"/>
</template>

<script setup>

import {ChevronDownIcon} from "@heroicons/vue/outline";
import SingleGroupInCategory from "@/Pages/Inventory/Components/SingleGroupInCategory.vue";

const props = defineProps({
    category: {
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
    left: 1.3rem;
    z-index: 22;
}
</style>