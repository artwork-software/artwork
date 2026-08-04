<script setup>

import InventorySettingsHeader from "@/Pages/InventorySetting/Components/InventorySettingsHeader.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
import SingleArticleStatus from "@/Pages/InventorySetting/Components/SingleArticleStatus.vue";
import draggable from "vuedraggable";
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { IconGripVertical } from "@tabler/icons-vue";

const props = defineProps({
    statuses: {
        type: [Array, Object],
        required: true
    }
})

const toArray = (value) => Array.isArray(value) ? [...value] : Object.values(value ?? {})

// Local, drag-sortable copy. Kept in sync when the server sends fresh data.
const localStatuses = ref(toArray(props.statuses))
watch(() => props.statuses, (value) => {
    localStatuses.value = toArray(value)
})

// Ref 1.29: persist the new status order after a drag.
const persistOrder = () => {
    router.post(route('inventory.article-status.reorder'), {
        ids: localStatuses.value.map((status) => status.id),
    }, {
        preserveScroll: true,
        preserveState: true,
    })
}
</script>

<template>
    <InventorySettingsHeader
        :title="$t('Status Settings')"
        :description="$t('Manage your article status settings here.')"
    >
        <SettingsGuideBanner
            class="mb-4"
            storage-key="settings-guide.inventory.status"
            title="How does this area work?"
            :paragraphs="[
                'The status catalog is fixed: you can adjust the color and order of the statuses, but you cannot create or delete statuses.',
                'The statuses appear in the status overview of the inventory overview and on the detailed articles.',
            ]"
        />
        <div class="mb-10 rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5">


            <div class="mt-8 flow-root">
                <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                        <table class="min-w-full divide-y divide-border">
                            <thead>
                            <tr class="divide-x divide-border-subtle">
                                <th scope="col" class="w-8 py-3.5 pl-4 sm:pl-0"></th>
                                <th scope="col" class="py-3.5 pr-4 pl-6 text-left text-sm font-semibold text-text">Name</th>
                                <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-text">
                                    {{ $t('Color') }}
                                </th>
                                <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-text sm:pr-0">
                                    {{ $t('Actions') }}
                                </th>
                            </tr>
                            </thead>
                            <draggable
                                tag="tbody"
                                v-model="localStatuses"
                                item-key="id"
                                handle=".drag-handle"
                                ghost-class="opacity-50"
                                class="divide-y divide-border-subtle bg-white"
                                @end="persistOrder"
                            >
                                <template #item="{ element: status }">
                                    <tr :key="status.id" class="divide-x divide-border-subtle">
                                        <td class="py-4 pl-4 sm:pl-0 text-text-subtle align-middle">
                                            <component :is="IconGripVertical" class="size-4 cursor-grab drag-handle" />
                                        </td>
                                        <SingleArticleStatus :status="status" />
                                    </tr>
                                </template>
                            </draggable>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </InventorySettingsHeader>
</template>

<style scoped>

</style>
