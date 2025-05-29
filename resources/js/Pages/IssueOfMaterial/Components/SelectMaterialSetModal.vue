<template>
    <ArtworkBaseModal title="Select Material Set"  description="Select a material set to issue." @close="$emit('close')">
        <div class="space-y-4">
            <p class="text-sm text-gray-600">{{ $t('Please select a material set to issue.') }}</p>

            <div class="h-min-96 h-96 overflow-y-auto">
                <ul role="list" class="divide-y divide-gray-100">
                    <li v-for="set in materialSets" :key="set.id" class="flex justify-between gap-x-6 p-3 rounded hover:bg-gray-50" @click="$emit('add-material-set', set)">
                        <div class="flex min-w-0 gap-x-4">
                            <div class="min-w-0 flex-auto">
                                <p class="text-sm/6 font-semibold text-gray-900">{{ set.name }}</p>
                                <p class="mt-1 truncate text-xs/5 text-gray-500">{{ set.description }}</p>
                            </div>
                        </div>
                        <div class="hidden shrink-0 sm:flex sm:flex-col sm:items-end">
                            <ToolTipWithTextComponent :text="set.items?.length + ' ' + $t('Items in this set.')" classes="text-xs whitespace-nowrap" tooltip-width="whitespace-nowrap" direction="left" :tooltip-text="createToolTipTextByItems(set)" />
                        </div>
                    </li>
                </ul>
            </div>

        </div>

        <div class="flex items-center justify-end mt-4">
            <ArtworkBaseModalButton @click="$emit('close')">{{$t('Close')}}</ArtworkBaseModalButton>
        </div>

    </ArtworkBaseModal>
</template>

<script setup>

import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import {computed, inject} from "vue";
import ToolTipWithTextComponent from "@/Components/ToolTips/ToolTipWithTextComponent.vue";

const props = defineProps({})

const materialSets = inject("materialSets");

const emit = defineEmits(['close', 'add-material-set']);


const createToolTipTextByItems = (set) => {
    // return item name with count
    return set.items.map(item => item.quantity + 'x ' + item.name).join(', ')  + ' (' + set.items.length + ' Artikel)';
};
</script>

<style scoped>

</style>