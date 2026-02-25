<template>
    <BaseModal @closed="$emit('close')">
        <div>
            <ModalHeader
                :title="$t('Export artist residencies')"
                :description="$t('Export the artist residencies for this project.')"
            />
        </div>
        <div class="mb-5">
            <fieldset>
                <legend class="text-sm/6 font-semibold text-gray-900">{{ $t('Export Type') }}</legend>
                <p class="mt-1 text-sm/6 text-gray-600">
                    {{ $t('Select the export type for the artist residencies.') }}
                </p>
                <div class="mt-3 space-y-6 sm:flex sm:items-center sm:space-x-10 sm:space-y-0">
                    <div v-for="exportMethod in exportMethods" :key="exportMethod.id" class="flex items-center">
                        <input :id="exportMethod.id" name="export-method" type="radio" v-model="selectedExportMode"  :value="exportMethod" class="relative size-4 appearance-none rounded-full border border-gray-300 bg-white before:absolute before:inset-1 before:rounded-full before:bg-white checked:border-indigo-600 checked:bg-indigo-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:before:bg-gray-400 forced-colors:appearance-auto forced-colors:before:hidden [&:not(:checked)]:before:hidden" />
                        <label :for="exportMethod.id" class="ml-3 block text-sm/6 font-medium text-gray-900">{{ $t(exportMethod.title) }}</label>
                    </div>
                </div>
            </fieldset>
        </div>
        <div class="mb-10">
            <fieldset>
                <legend class="text-sm/6 font-semibold text-gray-900">{{ $t('Language') }}</legend>
                <p class="mt-1 text-sm/6 text-gray-600">
                    {{ $t('Select the language for the export.') }}
                </p>
                <div class="mt-3 space-y-6 sm:flex sm:items-center sm:space-x-10 sm:space-y-0">
                    <div v-for="languageMethod in languageMethods" :key="languageMethod.id" class="flex items-center">
                        <input :id="languageMethod.id" name="language-method" type="radio" v-model="selectedLanguage"  :value="languageMethod" class="relative size-4 appearance-none rounded-full border border-gray-300 bg-white before:absolute before:inset-1 before:rounded-full before:bg-white checked:border-indigo-600 checked:bg-indigo-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:border-gray-300 disabled:bg-gray-100 disabled:before:bg-gray-400 forced-colors:appearance-auto forced-colors:before:hidden [&:not(:checked)]:before:hidden" />
                        <label :for="languageMethod.id" class="ml-3 block text-sm/6 font-medium text-gray-900">{{ $t(languageMethod.title) }}</label>
                    </div>
                </div>
            </fieldset>
        </div>

        <div>
            <div class="my-4 flex items-center justify-center">
                <FormButton
                    :text="$t('Export artist residencies')"
                    @click="exportArtistResidencies"
                />
            </div>
        </div>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import {ref} from "vue";
import {router} from "@inertiajs/vue3";

const props = defineProps({
    project: {
        type: Object,
        required: true
    }
})

const emits = defineEmits(['close'])

const languageMethods = [
    { id: 'de', title: 'German' },
    { id: 'en', title: 'English' },
]

const exportMethods = [
    { id: 'pdf', title: 'PDF-Export' },
    { id: 'excel', title: 'Excel-Export' },
    { id: 'per-diem-pdf', title: 'Per Diem Export' },
]

const selectedLanguage = ref(languageMethods[0])
const selectedExportMode = ref(exportMethods[0])

const exportArtistResidencies = () => {
    if(selectedExportMode.value.id === 'pdf') {
        router.post(route('artist-residencies.export-pdf', {
            project: props.project.id,
            language: selectedLanguage.value.id
        }), {}, {
           preserveScroll: true
        })
    } else if(selectedExportMode.value.id === 'per-diem-pdf') {
        router.post(route('artist-residencies.export-per-diem-pdf', {
            project: props.project.id,
            language: selectedLanguage.value.id
        }), {}, {
           preserveScroll: true
        })
    } else {
        window.open(route(
            'artist-residencies.export-excel',
            {
                project: props.project.id,
                language: selectedLanguage.value.id
            },
        ), '_blank', 'noopener');
    }
}

</script>

<style scoped>

</style>
