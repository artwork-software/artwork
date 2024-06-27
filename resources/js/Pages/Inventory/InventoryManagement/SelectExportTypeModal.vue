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
                <button :disabled="type.length === 0 ? 'disabled' : ''" type="button"
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

const emits = defineEmits(['closed']),
    props = defineProps({
        show: Boolean
    }),
    type = ref(''),
    close = (closedOnPurpose = false) => {
        if (!closedOnPurpose) {
            emits.call(this, 'closed');
            return;
        }

        //send request
    }
</script>