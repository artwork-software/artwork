<template>
    <BaseModal v-if="show" @closed="close">
        <div class="flex flex-col mx-4 gap-y-2">
            <h1 class="headline1">
                {{ $t('Neue Spalte erstellen') }}
            </h1>
            <div id="new-column-form"
                 class="flex flex-col gap-y-2">
                <TextInputComponent id="new-column-name"
                                    v-model="newColumnForm.name"
                                    :label="$t('Spaltenname*')"
                />
                <span v-if="showNewColumnFormNameError"
                      class="text-xs subpixel-antialiased text-error">
                    {{ $t('Es muss ein Name angegeben werden.') }}
                </span>
                <SelectComponent id="new-column-type"
                                 v-model="newColumnForm.type"
                                 :label="$t('Spaltentyp')"
                                 :options="getTypeOptions()"
                                 :getter-for-options-to-display="(type) => type.value"
                                 selected-property-to-display="value"
                />
                <div v-if="newColumnForm.type?.id === NEW_COLUMN_TYPE.SELECT.id"
                     class="w-full flex flex-col">

                    <div class="flex flex-row w-full items-start gap-x-2">
                        <div class="flex flex-row w-full items-center gap-x-2">
                        <TextInputComponent id="new-select-option-name"
                                            v-model="newColumnNewSelectOptionName"
                                            :label="$t('Neue Auswahlmöglichkeit')"
                                            @keyup.enter="addNewColumnNewSelectOption()"/>
                        <PlusCircleIcon v-if="newColumnNewSelectOptionName.length > 0"
                                  class="w-8 h-8 bg-artwork-buttons-create hover:bg-artwork-buttons-hover text-white cursor-pointer translate-y-2.5 p-1 subpixel-antialiased rounded-full"
                                  @click="addNewColumnNewSelectOption()"/>
                        </div>

                    </div>
                    <div class="w-full flex flex-col items-center text-xs">
                        <div v-for="(option, index) in newColumnForm.typeOptions" class="w-full flex flex-col border-b py-2 gap-y-2">
                            <span class="text-primary underline">Auswahlmöglichkeit {{ (index + 1) }}:</span>
                            <div class="flex flex-row justify-between">
                                <span class="w-[90%] break-words">{{ option }}</span>
                                <TrashIcon class="w-8 h-8 bg-artwork-buttons-create hover:bg-artwork-buttons-hover text-white cursor-pointer p-1 subpixel-antialiased rounded-full"
                                           @click="removeNewColumnNewSelectOption(index)"/>
                            </div>
                        </div>
                    </div>
                    <span v-if="showNewColumnFormTypeOptionsError"
                          class="text-xs subpixel-antialiased text-error mt-2">
                        {{ $t('Es muss mindestens eine Auswahloption hinzugefügt werden.') }}
                    </span>
                </div>
                <div class="w-full flex flex-row justify-center mt-4">
                    <FormButton :text="$t('Save')"
                                @click="saveNewColumn()"/>
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import {PlusCircleIcon, TrashIcon} from "@heroicons/vue/outline";
import {ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import SelectComponent from "@/Components/Inputs/SelectComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

const emits = defineEmits(['closed']),
    NEW_COLUMN_TYPE = {
        TEXT: {
            id: 0,
            translation: 'Freitextfeld'
        },
        DATE: {
            id: 1,
            translation: 'Datumsfeld'
        },
        CHECKBOX: {
            id: 2,
            translation: 'Checkbox'
        },
        SELECT: {
            id: 3,
            translation: 'Auswahlbox'
        },
    },
    props = defineProps({
        show: Boolean
    }),
    newColumnNewSelectOptionName = ref(''),
    newColumnForm = useForm({
        name: null,
        type: null,
        typeOptions: []
    }),
    showNewColumnFormNameError = ref(false),
    showNewColumnFormTypeOptionsError = ref(false),
    newColumnFormNameErrorText = ref(''),
    newColumnFormTypeOptionsErrorText = ref(''),
    getTypeOptions = () => {
        return [
            {
                id: NEW_COLUMN_TYPE.TEXT.id,
                value: NEW_COLUMN_TYPE.TEXT.translation
            },
            {
                id: NEW_COLUMN_TYPE.DATE.id,
                value: NEW_COLUMN_TYPE.DATE.translation
            },
            {
                id: NEW_COLUMN_TYPE.CHECKBOX.id,
                value: NEW_COLUMN_TYPE.CHECKBOX.translation
            },
            {
                id: NEW_COLUMN_TYPE.SELECT.id,
                value: NEW_COLUMN_TYPE.SELECT.translation
            }
        ];
    },
    addNewColumnNewSelectOption = () => {
        if (newColumnNewSelectOptionName.value.length === 0 || newColumnNewSelectOptionName.value.trim().length === 0) {
            //just whitespaces are not allowed
            return;
        }
        newColumnForm.typeOptions.push(newColumnNewSelectOptionName.value);
        newColumnNewSelectOptionName.value = '';
        validateNewColumn();
    },
    removeNewColumnNewSelectOption = (index) => {
        newColumnForm.typeOptions.splice(index, 1);
    },
    validateNewColumn = () => {
        if (newColumnForm.name === null || newColumnForm.name.length === 0) {
            showNewColumnFormNameError.value = true;
            newColumnFormNameErrorText.value = 'Es muss ein Spaltenname angegeben werden.'
        } else {
            showNewColumnFormNameError.value = false;
            newColumnFormNameErrorText.value = '';
        }

        if (newColumnForm.type !== null && newColumnForm.type.id === NEW_COLUMN_TYPE.SELECT.id && newColumnForm.typeOptions.length < 1) {
            showNewColumnFormTypeOptionsError.value = true;
            newColumnFormTypeOptionsErrorText.value = 'Es muss mindestens eine Auswahloption hinzugefügt werden.';
        } else {
            showNewColumnFormTypeOptionsError.value = false;
            newColumnFormTypeOptionsErrorText.value = '';
        }

        return !showNewColumnFormNameError.value && !showNewColumnFormTypeOptionsError.value;
    },
    saveNewColumn = () => {
        if (!validateNewColumn()) {
            return;
        }
        newColumnForm.post(
            route('inventory-management.inventory.column.create'),
            {
                preserveScroll: true,
                onSuccess: close
            }
        );
    },
    close = () => {
        newColumnForm.reset();
        emits.call(this, 'closed');
    }
</script>

<style scoped>
#new-column-form:deep(ul) {
    max-height: 10rem !important;
}
</style>
