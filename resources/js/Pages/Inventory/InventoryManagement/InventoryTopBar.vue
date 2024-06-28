<template>
    <div class="flex flex-row w-full h-10 items-center gap-x-2 absolute justify-end -top-12 z-30 rounded-t-md subpixel-antialiased">
        <BaseFilter :only-icon="true">
            <div class="flex flex-col w-full gap-y-2">
                <div class="flex justify-between">
                    <span> {{ $t('Filter') }}</span>
                    <span class="xxsLight cursor-pointer text-right w-full" @click="updatesCraftFilters()">
                        {{ $t('Reset') }}
                    </span>
                </div>
                <div class="text-sm border-b">{{ $t('Crafts') }}</div>
                <div class="craft-checkbox-filter">
                    <BaseFilterCheckboxList :list="craftFilters"
                                            filter-name="inventory-management-crafts-filter"
                                            @change-filter-items="updatesCraftFilters"/>
                </div>
            </div>
        </BaseFilter>
        <input v-if="searchOpened"
               class="w-60 h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300"
               type="text"
               id="inventory-search-input"
               aria-label="ajax search text input"
               :placeholder="$t('Search')"
               v-model="searchValue"
               @update:model-value="updateSearchValue()"
        />
        <IconSearch v-if="!searchOpened"
                    class="h-7 w-7 cursor-pointer hover:text-blue-500"
                    @click="toggleSearch()"/>
        <IconX v-else
               class="h-7 w-7 cursor-pointer hover:text-blue-500"
               @click="toggleSearch(true)"/>
        <IconFileExport class="h-7 w-7 cursor-pointer text-artwork-buttons-context" aria-hidden="true"
                        @click="openSelectExportTypeModal()"/>
        <button type="button"
                class="flex p-2 px-3 items-center border border-transparent rounded-lg shadow-sm text-white focus:outline-none bg-artwork-buttons-create hover:bg-artwork-buttons-hover"
                @click="openAddColumnModal()">
            <PlusIcon stroke-width="2" class="h-4 w-4 mr-2"/>
            <p class="text-sm">{{ $t('New column') }}</p>
        </button>
    </div>
    <AddColumnModal v-if="showAddColumnModal"
                    :show="showAddColumnModal"
                    @closed="closeAddColumnModal"/>
    <SelectExportTypeModal v-if="showSelectExportTypeModal"
                           :show="showSelectExportTypeModal"
                           :crafts-to-export="crafts"
                           @closed="closeSelectExportTypeModal"/>
</template>

<script setup>
import {IconX, IconSearch, IconFileExport} from "@tabler/icons-vue";
import BaseFilter from "@/Layouts/Components/BaseFilter.vue";
import BaseFilterCheckboxList from "@/Layouts/Components/BaseFilterCheckboxList.vue";
import {PlusIcon} from "@heroicons/vue/solid";
import {ref} from "vue";
import AddColumnModal from "@/Pages/Inventory/InventoryManagement/AddColumnModal.vue";
import Button from "@/Jetstream/Button.vue";
import SelectExportTypeModal from "@/Pages/Inventory/InventoryManagement/SelectExportTypeModal.vue";

const emits = defineEmits(['updatesSearchValue', 'updatesCraftFilters']),
    props = defineProps({
        craftFilters: {
            type: Array,
            required: true
        },
        crafts: {
            type: Object,
            required: true
        }
    }),
    searchOpened = ref(false),
    showAddColumnModal = ref(false),
    showSelectExportTypeModal = ref(false),
    searchValue = ref(''),
    toggleSearch = (close = false) => {
        if (close) {
            searchValue.value = '';
            emits.call(this, 'updatesSearchValue', '');
        }
        searchOpened.value = !searchOpened.value;
    },
    openAddColumnModal = () => {
        showAddColumnModal.value = true;
    },
    closeAddColumnModal = () => {
        showAddColumnModal.value = false;
    },
    openSelectExportTypeModal = () => {
        showSelectExportTypeModal.value = true;
    },
    closeSelectExportTypeModal = () => {
        showSelectExportTypeModal.value = false;
    },
    updatesCraftFilters = (args) => {
        emits.call(this, 'updatesCraftFilters', args);
    },
    updateSearchValue = () => {
        emits.call(this, 'updatesSearchValue', searchValue.value);
    };
</script>

<style scoped>
.craft-checkbox-filter {
    @apply flex flex-col gap-y-2;
}

.craft-checkbox-filter :deep(div) {
    @apply mb-0;
}
</style>
