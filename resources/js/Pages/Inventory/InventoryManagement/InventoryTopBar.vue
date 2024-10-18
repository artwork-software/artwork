<template>
    <div class="inventory-top-bar-container">
        <BaseFilter :only-icon="true">
            <div class="inventory-top-bar-filter-container">
                <div class="reset-filter-container">
                    <span>{{ $t('Filter') }}</span>
                    <span class="reset-btn" @click="updatesCraftFilters()">
                        {{ $t('Reset') }}
                    </span>
                </div>
                <div class="title">{{ $t('Crafts') }}</div>
                <div class="craft-checkbox-filter">
                    <BaseFilterCheckboxList :list="craftFilters"
                                            filter-name="inventory-management-crafts-filter"
                                            @change-filter-items="updatesCraftFilters"/>
                </div>
            </div>
        </BaseFilter>
        <input v-show="searchOpened"
               class="inventory-top-bar-search-input"
               type="text"
               ref="searchBarInput"
               aria-label="ajax search text input"
               :placeholder="$t('Search')"
               v-model="searchValue"
               @update:model-value="updateSearchValue()"
        />
        <IconSearch v-if="!searchOpened"
                    class="inventory-top-bar-search-icon"
                    @click="toggleSearch()"/>
        <IconX v-else
               class="inventory-top-bar-search-close-icon"
               @click="toggleSearch(true)"/>
        <IconFileExport class="inventory-top-bar-export-icon"
                        @click="openSelectExportTypeModal()"/>
        <button type="button"
                class="inventory-top-bar-new-column-btn"
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
import {nextTick, ref} from "vue";
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
    searchBarInput = ref(null),

    searchValue = ref(''),
    toggleSearch = (close = false) => {
        if (close) {
            searchValue.value = '';
            emits.call(this, 'updatesSearchValue', '');
        }
        searchOpened.value = !searchOpened.value;
        nextTick(() => {
            if (searchOpened.value) {
                searchBarInput.value.focus()
            }
        });
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
