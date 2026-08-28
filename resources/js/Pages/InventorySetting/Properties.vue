<template>
    <InventorySettingsHeader
        :title="$t('Properties')"
        :description="$t('Define the properties that articles can have.')"
    >
        <template #actions>
            <BaseUIButton variant="primary" hide-icon @click="showAddEditPropertyModal = true">
                <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                {{ $t('Create Property') }}
            </BaseUIButton>
        </template>

        <SettingsGuideBanner
            class="mb-4"
            storage-key="settings-guide.inventory.properties"
            title="How does this area work?"
            :paragraphs="[
                'Properties define which fields your articles can have. They take effect once they are assigned to a category or sub-category.',
                'Dragging a row changes the global order of the properties.',
            ]"
        />
        <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5">
            <SettingsGuideBanner
                variant="inline"
                storage-key="settings-guide.inventory.properties.flags"
                title="What do the flags mean?"
                :paragraphs="[
                    'Filterable: the property appears in the filter sidebar of the inventory overview and in the material set modal.',
                    'In article overview: the property gets its own column in the article overview.',
                    'Required field: the property must be filled in when an article is created.',
                    'Cross-article: one shared value for all detailed articles of an article.',
                    'Individual value: the value is not duplicated and not changed via multi-edit, e.g. serial numbers.',
                ]"
                footnote="System properties such as room and manufacturer cannot be deleted."
            />
            <div class="my-8 overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                    <thead>
                    <tr class="divide-x divide-border-subtle">
                        <th scope="col" class="w-8 py-3.5 pl-4 sm:pl-0"></th>
                        <th scope="col" class="py-3.5 pr-4 pl-6 text-left text-sm font-semibold text-text">{{ $t('Name') }}</th>
                        <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Tooltip Text') }}</th>
                        <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Type') }}</th>
                        <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Filterable') }}</th>
                        <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-text">{{ $t('In article overview') }}</th>
                        <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Required field') }}</th>
                        <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-text sm:pr-0 sticky right-0 bg-surface">{{ $t('Actions') }}</th>
                    </tr>
                    </thead>
                    <draggable
                        tag="tbody"
                        v-model="localProperties"
                        item-key="id"
                        handle=".drag-handle"
                        ghost-class="opacity-50"
                        class="divide-y divide-border-subtle bg-white"
                        @end="persistOrder"
                    >
                        <template #item="{ element: property }">
                            <tr :key="property?.id" class="divide-x divide-border-subtle">
                                <td class="py-4 pl-4 sm:pl-0 text-text-subtle align-middle">
                                    <component :is="IconGripVertical" class="size-4 cursor-grab drag-handle" />
                                </td>
                                <SinglePropertyInSettings :property="property" />
                            </tr>
                        </template>
                    </draggable>
                </table>
            </div>

            <BasePaginator
                property-name="properties"
                :entities="properties"
            />
        </div>

        <AddEditArticlePropertyModal
            v-if="showAddEditPropertyModal"
            @close="showAddEditPropertyModal = false"
            :property="null"
            />
    </InventorySettingsHeader>
</template>

<script setup>

import InventorySettingsHeader from "@/Pages/InventorySetting/Components/InventorySettingsHeader.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import {ref, watch} from "vue";
import {router} from "@inertiajs/vue3";
import draggable from "vuedraggable";
import AddEditArticlePropertyModal from "@/Pages/InventorySetting/Components/AddEditArticlePropertyModal.vue";
import SinglePropertyInSettings from "@/Pages/InventorySetting/Components/SinglePropertyInSettings.vue";
import {IconCirclePlus, IconGripVertical} from "@tabler/icons-vue";

const props = defineProps({
    properties: {
        type: Object,
        required: true
    }
})

const showAddEditPropertyModal = ref(false);

// Local, drag-sortable copy of the current page. Re-synced on fresh server data.
const localProperties = ref([...(props.properties?.data ?? [])])
watch(() => props.properties, (value) => {
    localProperties.value = [...(value?.data ?? [])]
})

// Ref 1.41: persist the new global property order. `start` keeps the order
// values globally consistent across paginated pages.
const persistOrder = () => {
    router.post(route('inventory-management.settings.properties.reorder'), {
        ids: localProperties.value.map((property) => property.id),
        start: (props.properties?.from ?? 1) - 1,
    }, {
        preserveScroll: true,
        preserveState: true,
    })
}

</script>

<style scoped>
</style>
