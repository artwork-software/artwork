<template>
    <BaseModal v-if="show" @closed="close">
        <div class="add-column-modal-container">
            <h1 class="headline1">
                {{ $t('Create new column') }}
            </h1>
            <div class="new-column-form">
                <TextInputComponent id="new-column-name"
                                    v-model="newColumnForm.name"
                                    :label="$t('Column name*')"
                />
                <span v-if="showNewColumnFormNameError"
                      class="error-text">
                    {{ $t('A name must be entered.') }}
                </span>
                <SelectComponent id="new-column-type"
                                 v-model="newColumnForm.type"
                                 :label="$t('Column type')"
                                 :options="getTypeOptions()"
                                 :getter-for-options-to-display="(type) => type.value"
                                 selected-property-to-display="value"
                />
                <div v-if="newColumnForm.type?.id === NEW_COLUMN_TYPE.SELECT.id"
                     class="new-select-options-container">
                    <div class="add-select-option-container">
                        <div class="layout">
                            <TextInputComponent id="new-select-option-name"
                                                v-model="newColumnNewSelectOptionName"
                                                :label="$t('New selection option')"
                                                @keyup.enter="addNewColumnNewSelectOption()"/>
                            <PlusCircleIcon v-if="newColumnNewSelectOptionName.length > 0"
                                      class="icon"
                                      @click="addNewColumnNewSelectOption()"/>
                        </div>
                    </div>
                    <div class="new-select-options-list-container">
                        <div v-for="(option, index) in newColumnForm.typeOptions" class="new-option-container">
                            <span class="title">{{ $t('Selection option') + (index + 1) }}:</span>
                            <div class="select-option-container">
                                <span class="select-option-name">{{ option }}</span>
                                <input :id="'radio-option-' + index"
                                       name="default-option"
                                       v-model="newColumnForm.defaultOption"
                                       :value="option"
                                       type="radio"/>
                                <label :for="'radio-option-' + index">{{ $t('Default value') }}</label>
                                <TrashIcon class="icon"
                                           @click="removeNewColumnNewSelectOption(index)"/>
                            </div>
                        </div>
                    </div>
                    <span v-if="showNewColumnFormTypeOptionsError"
                          class="select-options-error-text">
                        {{ $t('At least one selection option must be added.') }}
                    </span>
                </div>
                <div class="button-container">
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
import {useTranslation} from "@/Composeables/Translation.js";

const $t = useTranslation(),
    emits = defineEmits(['closed']),
    NEW_COLUMN_TYPE = {
        TEXT: {
            id: 0,
            translation: $t('Text field')
        },
        DATE: {
            id: 1,
            translation: $t('Date field')
        },
        CHECKBOX: {
            id: 2,
            translation: $t('Checkbox')
        },
        SELECT: {
            id: 3,
            translation: $t('Select box')
        },
    },
    props = defineProps({
        show: Boolean
    }),
    newColumnNewSelectOptionName = ref(''),
    newColumnForm = useForm({
        name: '',
        type: null,
        typeOptions: [],
        defaultOption: null
    }),
    showNewColumnFormNameError = ref(false),
    showNewColumnFormTypeOptionsError = ref(false),
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
        showNewColumnFormNameError.value = newColumnForm.name === null || newColumnForm.name.length === 0;
        showNewColumnFormTypeOptionsError.value = newColumnForm.type !== null &&
            newColumnForm.type.id === NEW_COLUMN_TYPE.SELECT.id &&
            newColumnForm.typeOptions.length < 1;

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
