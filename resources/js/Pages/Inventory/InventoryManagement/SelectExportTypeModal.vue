<template>
    <BaseModal v-if="show" @closed="close(false)">
        <div class="flex flex-col mx-4 gap-y-5">
            <h1 class="headline1">
                {{ $t('Bestandsexport') }}
            </h1>
            <span>{{ $t('Bitte wählen in welchem Format der Export erstellt werden soll')}}</span>
            <div class="flex flex-row gap-3 items-center">
                <input id="rb-pdf"
                       value="pdf"
                       name="rb-export-type"
                       type="radio"
                       class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-600"
                       v-model="type"/>
                <label for="rb-pdf" class="ml-0">{{ $t('PDF') }}</label>
                <input id="rb-csv"
                       value="xlsx"
                       name="rb-export-type"
                       type="radio"
                       class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-600"
                       v-model="type"/>
                <label for="rb-csv">{{ $t('XLSX') }}</label>
            </div>
            <div class="flex flex-row justify-between items-center">
                <button :disabled="type.length === 0" type="button"
                        :class="[type.length === 0 ? 'cursor-not-allowed bg-gray-600 hover:bg-gray-800' : 'cursor-pointer', 'flex flex-row p-2 px-3 items-center border border-transparent rounded-lg shadow-sm text-white focus:outline-none bg-artwork-buttons-create hover:bg-artwork-buttons-hover']"
                        @click="close(true)">
                    <IconFileExport stroke-width="2" class="h-4 w-4 mr-2"/>
                    <p class="text-sm">{{ $t('Export') }}</p>
                </button>
                <span class="cursor-pointer xsLight" @click="close()">{{ $t('Close') }}</span>
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
import { useI18n } from "vue-i18n";
const { t } = useI18n(),
    $t = t;

const emits = defineEmits(['closed']),
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
            route('inventory-management.inventory.export.cacheExportData'),
            {
                data: props.craftsToExport.map(
                    (craft) => {
                        return {
                            craftId: craft.id,
                            craftName: craft.name,
                            abbreviation: craft.abbreviation,
                            filteredInventoryCategories: craft.filtered_inventory_categories
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
        }).catch(() => page.props.flash.error = $t('Export konnte nicht erzeugt werden. Bitte erneut versuchen.'));

        emits.call(this, 'closed');
    }
</script>
