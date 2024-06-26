<template>
    <tr class="pl-2 cursor-pointer w-full xsLight pb-1" @click="group.closed = !group.closed">
        <td>
            <div class="px-2 py-1.5 bg-gray-50/10 w-full" :class="group.closed ? 'rounded-b-lg' : ''">
                <div class="ml-5 stickyYAxisNoMarginLeft w-48 flex items-center gap-x-1">
                    {{ group.name }}
                    <ChevronDownIcon
                        :class="group.closed ? '' : 'rotate-180 transform'"
                        class="h-4 w-4 mt-0.5"
                    />
                </div>
            </div>
        </td>
    </tr>
    <SingleItemInGroup :multi-edit="multiEdit" v-for="item in group.items" :days="days" :item="item" v-if="!group.closed"/>
</template>

<script setup>

import {ChevronDownIcon} from "@heroicons/vue/outline";
import SingleItemInGroup from "@/Pages/Inventory/Components/SingleItemInGroup.vue";

const props = defineProps({
    group: {
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
    left: 1.8rem;
    z-index: 22;
}
</style>