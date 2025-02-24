<template>
    <BaseModal @closed="$emit('close')" modal-size="max-w-7xl">
        <div>
            <ModalHeader
                :title="$t('Configure column width')"
                :description="$t('Configure the width of the columns in the bulk view.')"
            />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div v-for="(column, index) in columnSizeForm.bulk_column_size">
                <div>
                    <label for="columns_header" class="mb-2 text-sm font-medium text-gray-900 dark:text-white flex items-center justify-between">
                        <span>
                            {{ $t('Column') + ' ' + (index) }}
                        </span>
                        <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                            {{ columnSizeForm.bulk_column_size[index] + ' Pixel' }}
                        </span>
                    </label>
                    <input id="columns_header" type="range" :min="columnSizeMinMax[index].min" :max="columnSizeMinMax[index].max" v-model="columnSizeForm.bulk_column_size[index]" step="1" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700 text-artwork-buttons-create">
                </div>
            </div>
        </div>

        <div class="my-4">
            <TinyPageHeadline title="Vorschau" description="" />
        </div>
        <form @submit.prevent="submit">
            <div class="my-4 py-4 overflow-x-scroll">
                <div class="flex items-center gap-4 mb-3 text-gray-400 text-sm print:xsDark">
                    <div class="font-bold" :style="getColumnSize(1)">
                        {{ $t('Event Status') }}
                    </div>
                    <div class="font-bold" :style="getColumnSize(2)">
                        {{ $t('Event type') }}
                    </div>
                    <div class="font-bold" :style="getColumnSize(3)">
                        {{ $t('Event name') }}
                    </div>
                    <div class="font-bold" :style="getColumnSize(4)">
                        {{ $t('Room') }}
                    </div>
                    <div class="font-bold print:col-span-2" :style="getColumnSize(5)">
                        {{ $t('Day') }}
                    </div>
                    <div class="font-bold col-span-1" :style="getColumnSize(6)">
                        <div class="flex items-center gap-x-4">
                            {{ $t('Period') }}
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4 mb-3 text-gray-400 text-sm print:xsDark">
                    <div v-for="(columnSize, index) in columnSizeForm.bulk_column_size" class="font-bold border-2 border-dashed rounded-lg cursor-default" :style="getColumnSize(index)">
                        <div class="px-3 py-1.5 cursor-default"  >
                            {{ columnSize + ' Pixel' }}
                        </div>
                    </div>

                </div>
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <!-- Reset Button -->
                    <FormButton :text="$t('Reset to default')" type="button" @click="setColumnSizeToDefault" />
                </div>

                <div>
                    <!-- Save Button -->
                    <FormButton :text="$t('Speichern')" type="submit" />
                </div>
            </div>
        </form>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

const props = defineProps({})


const emits = defineEmits(['close'])


const columnSizeForm = useForm({
    bulk_column_size: usePage().props.user.bulk_column_size
})

const columnSizeMinMax = {
    1: {
        min: 100,
        max: 450
    },
    2: {
        min: 100,
        max: 450
    },
    3: {
        min: 100,
        max: 450
    },
    4: {
        min: 100,
        max: 450
    },
    5: {
        min: 146,
        max: 450
    },
    6: {
        min: 195,
        max: 450
    }
}

const getColumnSize = (column) => {
    return {
        minWidth: columnSizeForm.bulk_column_size[column] + 'px',
        width: columnSizeForm.bulk_column_size[column] + 'px',
        maxWidth: columnSizeForm.bulk_column_size[column] + 'px'
    }
}


const setColumnSizeToDefault = () => {
   columnSizeForm.bulk_column_size = {
         1: 146,
         2: 146,
         3: 146,
         4: 146,
         5: 146,
         6: 308,
   }

   submit();
}

const submit = () => {
    columnSizeForm.patch(route('user.bulk-column-size.update', usePage().props.user.id), {
        preserveScroll: true,
        onSuccess: () => {
            emits('close')
        }
    })
}
</script>

<style scoped>

</style>