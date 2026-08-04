<template>
    <tr class="pl-2 cursor-pointer w-full text-sm/5 font-bold text-text-subtle pb-1" @click="folder.closed = !folder.closed">
        <td>
            <div class="px-2 py-1.5 bg-surface-sunken w-full" :class="folder.closed ? 'rounded-b-lg' : ''">
                <div class="ml-5 stickyYAxisNoMarginLeft w-48 flex items-center gap-x-1">
                    <component :is="IconFolderSymlink" class="h-4 w-4"/>
                    {{ folder.name }}
                    <IconChevronDown
                        :class="folder.closed ? '' : 'rotate-180 transform'"
                        class="h-4 w-4 mt-0.5"
                    />
                </div>
            </div>
        </td>
    </tr>
    <SingleItemInGroup :inventory_planned_by_all="inventory_planned_by_all" :inventory_planer_ids="inventory_planer_ids" :multi-edit="multiEdit" v-for="item in folder.items" :days="days" :item="item" v-if="!folder.closed"/>
</template>

<script setup>

import SingleItemInGroup from "@/Pages/Inventory/Components/SingleItemInGroup.vue";
import {IconChevronDown, IconFolderSymlink} from "@tabler/icons-vue";

const props = defineProps({
    folder: {
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
    },
})

</script>

<style scoped>

</style>