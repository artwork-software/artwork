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

                    <div class="flex flex-row w-1/2 items-center gap-x-2">
                        <TextInputComponent id="new-select-option-name"
                                            v-model="newColumnNewSelectOptionName"
                                            :label="$t('Neue Auswahlmöglichkeit')"/>
                        <IconPlus v-if="newColumnNewSelectOptionName.length > 0"
                                  class="w-6 h-6 cursor-pointer translate-y-2.5 p-0.5 subpixel-antialiased rounded-full text-white bg-black hover:bg-green-500"
                                  @click="addNewColumnNewSelectOption()"/>
                    </div>
                    <span v-if="showNewColumnFormTypeOptionsError"
                          class="text-xs subpixel-antialiased text-error mt-2">
                        {{ $t('Es muss mindestens eine Auswahloption hinzugefügt werden.') }}
                    </span>
                    <div class="flex flex-col text-xs">
                        <div v-for="(option, index) in newColumnForm.selectOptions"
                            class="flex flex-row items-center gap-x-2 mt-2">
                            <span>{{ option }}</span>
                            <IconTrashXFilled class="w-6 h-6 cursor-pointer p-0.5 subpixel-antialiased rounded-full text-white bg-black hover:bg-red-500"
                                              @click="removeNewColumnNewSelectOption(index)"/>
                        </div>
                    </div>
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
import {IconPlus, IconTrashXFilled} from "@tabler/icons-vue";
import {ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import SelectComponent from "@/Components/Inputs/SelectComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

const emits = defineEmits(['closed']);
const NEW_COLUMN_TYPE = {
        TEXTAREA: {
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
        selectOptions: []
    }),
    showNewColumnFormNameError = ref(false),
    showNewColumnFormTypeOptionsError = ref(false),
    newColumnFormNameErrorText = ref(''),
    newColumnFormTypeOptionsErrorText = ref(''),
    getTypeOptions = () => {
        return [
            {
                id: NEW_COLUMN_TYPE.TEXTAREA.id,
                value: NEW_COLUMN_TYPE.TEXTAREA.translation
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
        newColumnForm.selectOptions.push(newColumnNewSelectOptionName.value);
        newColumnNewSelectOptionName.value = '';
    },
    removeNewColumnNewSelectOption = (index) => {
        newColumnForm.selectOptions.splice(index, 1);
    },
    validateNewColumn = () => {
        if (newColumnForm.name === null || newColumnForm.name.length === 0) {
            showNewColumnFormNameError.value = true;
            newColumnFormNameErrorText.value = 'Es muss ein Spaltenname angegeben werden.'
        } else {
            showNewColumnFormNameError.value = false;
            newColumnFormNameErrorText.value = '';
        }

        if (newColumnForm.type !== null && newColumnForm.type.id === NEW_COLUMN_TYPE.SELECT.id && newColumnForm.selectOptions.length < 1) {
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

        console.debug('validated', newColumnForm);

        //newColumnForm.post(route('#'));
    },
    close = () => {
        emits.call(this, 'closed');
    }
</script>

<style scoped>
#new-column-form:deep(ul) {
    max-height: 10rem !important;
}
</style>
