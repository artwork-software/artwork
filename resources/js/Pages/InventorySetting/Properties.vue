<template>
    <InventorySettingsHeader
        :title="$t('Properties')"
        :description="$t('Define the properties that articles can have.')"
    >
        <template #actions>
            <button class="ui-button-add" @click="showAddEditPropertyModal = true">
                <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                {{ $t('Create Property') }}
            </button>
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
        <div class="card white p-5">
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
            <div class="my-8 flow-root">
                <div class="-mx-4 -my-2 sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                        <div class="table-container">
                            <div class="overflow-x-auto">
                                <div class="inline-flex w-full">
                                    <table class="min-w-full divide-y divide-border flex-grow">
                                        <thead>
                                        <tr class="divide-x divide-border-subtle">
                                            <th scope="col" class="w-8 py-3.5 pl-4 sm:pl-0"></th>
                                            <th scope="col" class="py-3.5 pr-4 pl-6 text-left text-sm font-semibold text-text">{{ $t('Name') }}</th>
                                            <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Tooltip Text') }}</th>
                                            <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Type') }}</th>
                                            <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Filterable') }}</th>
                                            <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-text">{{ $t('In article overview') }}</th>
                                            <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Required field') }}</th>
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
                                                    <SinglePropertyInSettings :property="property" :show-actions="false" />
                                                </tr>
                                            </template>
                                        </draggable>
                                    </table>

                                    <!-- Fixed Actions Column -->
                                    <div class="fixed-actions-column">
                                        <table class="h-full divide-y divide-border">
                                            <thead>
                                            <tr>
                                                <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-text sm:pr-0 bg-white">{{ $t('Actions') }}</th>
                                            </tr>
                                            </thead>
                                            <tbody class="divide-y divide-border-subtle bg-white">
                                            <tr v-for="property in localProperties" :key="property?.id">
                                                <SinglePropertyInSettings :property="property" :show-only-actions="true" />
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
.table-container {
    position: relative;
    overflow: hidden;
}

/* Add a shadow to the fixed actions column */
.fixed-actions-column {
    position: sticky;
    top: 0;
    right: 0;
    height: 100%;
    background-color: white;
    box-shadow: -4px 0 6px -2px rgba(0, 0, 0, 0.05);
    border-left: 1px solid #e5e7eb;
    z-index: 10;
    width: 100px;
    margin-left: auto; /* Push to the right edge in flex container */
}

/* Ensure the actions column has a consistent width */
.actions-column {
    width: 100px;
    min-width: 100px;
}
</style>
