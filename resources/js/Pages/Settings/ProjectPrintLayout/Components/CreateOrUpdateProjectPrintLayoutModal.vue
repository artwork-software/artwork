<template>
    <BaseModal @closed="$emit('close')">
        <div>
            <ModalHeader
                :title="projectPrintLayout ? $t('Edit Print Layout') : $t('Create Print Layout')"
                :description="projectPrintLayout ? $t('Edit the print layout') : $t('Create a new print layout')"
            />
        </div>

        <form @submit.prevent="createOrUpdate">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="col-span-full">
                    <BaseInput
                        id="name"
                        v-model="createOrUpdateForm.name"
                        :label="$t('Name')"
                    />
                </div>
                <div class="col-span-full">
                    <BaseInput
                        id="description"
                        v-model="createOrUpdateForm.description"
                        :label="$t('Description')"
                    />
                </div>
                <div>
                    <label for="columns_header" class="mb-2 text-sm font-medium text-gray-900 dark:text-white flex items-center justify-between">
                        <span>
                            {{ $t('Header columns') }}
                        </span>
                        <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                            {{ createOrUpdateForm.columns_header }}
                        </span>
                    </label>
                    <input id="columns_header" :disabled="projectPrintLayout" type="range" min="1" max="5" v-model="createOrUpdateForm.columns_header" step="1" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700 text-artwork-buttons-create">
                </div>
                <div>
                    <label for="columns_footer" class="mb-2 text-sm font-medium text-gray-900 dark:text-white flex items-center justify-between">
                        <span>
                            {{ $t('Footer columns') }}
                        </span>
                        <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                            {{ createOrUpdateForm.columns_footer }}
                        </span>
                    </label>
                    <input id="columns_footer" :disabled="projectPrintLayout" type="range" min="1" max="5" v-model="createOrUpdateForm.columns_footer" step="1" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
                </div>
                <div>
                    <label for="columns_body" class="mb-2 text-sm font-medium text-gray-900 dark:text-white flex items-center justify-between">
                        <span>
                            {{ $t('Body columns') }}
                        </span>
                        <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                            {{ createOrUpdateForm.columns_body }}
                        </span>
                    </label>
                    <input id="columns_body" :disabled="projectPrintLayout" type="range" min="1" max="3" v-model="createOrUpdateForm.columns_body" step="1" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
                </div>
                <div class="col-span-full my-3">
                    <div v-if="parseInt(createOrUpdateForm.columns_body) > 1" class="mb-2">
                        <AlertComponent type="error" :text="$t('The columns of the main part are set to more than 1, which makes the print layout less legible and it is no longer possible to add the components marked as “special components”.')" />
                    </div>
                    <hr class="border-gray-200 dark:border-gray-700">
                </div>
                <div class="col-span-full">
                    <SwitchGroup as="div" class="flex items-center">
                        <Switch v-model="createOrUpdateForm.is_active" :class="[createOrUpdateForm.is_active ? 'bg-artwork-buttons-create' : 'bg-gray-200', 'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create focus:ring-offset-2']">
                            <span class="sr-only">Use setting</span>
                            <span :class="[createOrUpdateForm.is_active ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none relative inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                              <span :class="[createOrUpdateForm.is_active ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex size-full items-center justify-center transition-opacity']" aria-hidden="true">
                                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                                  <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                              </span>
                              <span :class="[createOrUpdateForm.is_active ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex size-full items-center justify-center transition-opacity']" aria-hidden="true">
                                <svg class="size-3 text-indigo-600" fill="currentColor" viewBox="0 0 12 12">
                                  <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                                </svg>
                              </span>
                            </span>
                        </Switch>
                        <SwitchLabel as="span" class="ml-3 text-sm">
                            <span class="font-medium text-gray-900">{{ $t('Should this print layout be available?') }}</span>
                        </SwitchLabel>
                    </SwitchGroup>
                </div>
            </div>

            <div class="flex items-center justify-center mt-10">
                <FormButton type="submit" :text="projectPrintLayout ? $t('Update') : $t('Create')"  />
            </div>
        </form>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import {useForm} from "@inertiajs/vue3";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import {Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

const props = defineProps({
    projectPrintLayout: {
        type: Object,
        required: false,
        default: null,
    },
})

const emits = defineEmits([
    'close',
])

/**
 * Form for creating or updating a project print layout
 * @type {InertiaForm<{name: (*|string), description: (*|string), columns_header: (number), columns_footer: (number), columns_body: (number), is_active: (boolean)}>}
 */
const createOrUpdateForm = useForm({
    id: props.projectPrintLayout ? props.projectPrintLayout.id : null,
    name: props.projectPrintLayout ? props.projectPrintLayout.name : '',
    description: props.projectPrintLayout ? props.projectPrintLayout.description : '',
    columns_header: props.projectPrintLayout ? props.projectPrintLayout.columns_header : 1,
    columns_footer: props.projectPrintLayout ? props.projectPrintLayout.columns_footer : 1,
    columns_body: props.projectPrintLayout ? props.projectPrintLayout.columns_body : 1,
    is_active: props.projectPrintLayout ? props.projectPrintLayout.is_active : true,
})

const createOrUpdate = () => {
    if (props.projectPrintLayout) {
        createOrUpdateForm.patch(route('project-print-layout.update', props.projectPrintLayout.id), {
            preserveScroll: true,
            onSuccess: () => {
                emits('close')
            }
        })
    } else {
        createOrUpdateForm.post(route('project-print-layout.store'), {
            preserveScroll: true,
            onSuccess: () => {
                emits('close')
            }
        })
    }
}

</script>

<style scoped>

</style>