<template>
    <tr class="cursor-pointer w-full xsLight pb-1" @click="group.closed = !group.closed">
        <td>
            <div class="px-2 py-1.5 bg-gray-50/10 w-full" :class="group.closed ? 'rounded-b-lg' : ''">
                <div class="stickyYAxisNoMarginLeft w-48 flex items-center gap-x-1">
                    <component :is="IconCornerDownRight" class="h-4 w-4"/>
                    {{ group.name }}
                    <ChevronDownIcon
                        :class="group.closed ? '' : 'rotate-180 transform'"
                        class="h-4 w-4 mt-0.5"
                    />
                </div>
            </div>
        </td>
    </tr>
    <SingleItemInGroup :inventory_planned_by_all="inventory_planned_by_all" :inventory_planer_ids="inventory_planer_ids" :multi-edit="multiEdit" v-for="item in group.items" :days="days" :item="item" v-if="!group.closed"/>
    <SingleFolderInGroup :inventory_planned_by_all="inventory_planned_by_all" :inventory_planer_ids="inventory_planer_ids" v-for="folder in group.folders" :days="days" :folder="folder" v-if="!group.closed" :multi-edit="multiEdit" />
</template>

<script setup>

import {ChevronDownIcon} from "@heroicons/vue/outline";
import SingleItemInGroup from "@/Pages/Inventory/Components/SingleItemInGroup.vue";
import SingleFolderInGroup from "@/Pages/Inventory/Components/SingleFolderInGroup.vue";
import {IconCornerDownRight} from "@tabler/icons-vue";

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
    },
    inventory_planer_ids: {
        type: Array,
        required: false,
        default: []
    },
    inventory_planned_by_all: {
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