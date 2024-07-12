<template>
    <BaseModal v-if="show" @closed="close(false)">
        <div class="select-export-type-modal-container">
            <h1 class="headline1">
                {{ $t('Inventory export') }}
            </h1>
            <span>{{ $t('Please select the format in which the export is to be created.')}}</span>
            <div class="radio-container">
                <input id="rb-pdf"
                       value="pdf"
                       name="rb-export-type"
                       type="radio"
                       class="radio-input"
                       v-model="type"/>
                <label for="rb-pdf" class="ml-0">{{ $t('PDF') }}</label>
                <input id="rb-csv"
                       value="xlsx"
                       name="rb-export-type"
                       type="radio"
                       class="radio-input"
                       v-model="type"/>
                <label for="rb-csv">{{ $t('XLSX') }}</label>
            </div>
            <div class="button-container">
                <button :disabled="type.length === 0" type="button"
                        :class="[type.length === 0 ? 'cursor-not-allowed !bg-gray-600 !hover:bg-gray-800' : 'cursor-pointer', 'export-button']"
                        @click="close(true)">
                    <IconFileExport stroke-width="2" class="icon"/>
                    <p class="text-sm">{{ $t('Export') }}</p>
                </button>
                <span class="close-modal-button" @click="close()">{{ $t('Close') }}</span>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import {ref} from "vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import {IconFileExport} from "@tabler/icons-vue";
import Button from "@/Jetstream/Button.vue";
import {usePage} from "@inertiajs/vue3";
import {useTranslation} from "@/Composeables/Translation.js";

const $t = useTranslation(),
    emits = defineEmits(['closed']),
    page = usePage(),
    props = defineProps({
        show: {
            type: Boolean,
            required: true
        },
        craftsToExport: {
            type: Object,
            required: true
        }
    }),
    type = ref(''),
    close = (closedOnPurpose = false) => {
        if (!closedOnPurpose) {
            emits.call(this, 'closed');
            return;
        }

        axios.post(
            route('inventory-management.inventory.export.saveExportDataInCache'),
            {
                data: props.craftsToExport.map(
                    (craft) => {
                        return {
                            craftId: craft.value.id,
                            craftName: craft.value.name,
                            abbreviation: craft.value.abbreviation,
                            filteredInventoryCategories: craft.value.filtered_inventory_categories
                        }
                    }
                )
            }
        ).then((response) => {
            window.open(
                route(
                    (
                        type.value === 'pdf' ?
                            'inventory-management.inventory.export.download-pdf' :
                            'inventory-management.inventory.export.download-xlsx'
                    ),
                    {
                        cacheToken: response.data
                    }
                )
            );
        }).catch(
            () => page.props.flash.error = $t('Export could not be created. Please try again.')
        );

        emits.call(this, 'closed');
    }
</script>
