<template>
    <BaseModal v-if="show" @closed="close">
        <div class="edit-column-select-options-modal-container">
            <h1 class="headline1">
                {{ $t('Edit column options') }}
            </h1>
            <span>{{ $t('If an option is removed and it is used in a cell, the corresponding cell is emptied.') }}</span>
            <div class="new-option-container">
                <div class="layout">
                    <TextInputComponent id="new-select-option-name"
                                        v-model="columnNewSelectOptionName"
                                        :label="$t('New selection option')"
                                        @keyup.enter="addNewColumnNewSelectOption()"/>
                    <PlusCircleIcon v-if="columnNewSelectOptionName.length > 0"
                                    class="icon"
                                    @click="addNewColumnNewSelectOption()"/>
                </div>
            </div>
            <div class="select-options-container">
                <div v-for="(option, index) in selectOptionColumnForm.selectOptions" class="select-option-container">
                    <span class="option-index">{{ $t('Selection option') + (index + 1) }}:</span>
                    <div class="option-container">
                        <span class="option">{{ option }}</span>
                        <TrashIcon class="icon"
                                   @click="removeNewColumnNewSelectOption(index)"/>
                    </div>
                </div>
            </div>
            <span v-if="showNewSelectOptionError"
                  class="error-text">
                {{ $t('At least one selection option must be added.') }}
            </span>
            <div class="button-container">
                <FormButton :text="$t('Save')"
                            @click="updateSelectOptions()"/>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import {PlusCircleIcon, TrashIcon} from "@heroicons/vue/outline";
import { ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

const emits = defineEmits(['closed']),
    props = defineProps({
        show: {
            type: Boolean,
            required: true
        },
        column: {
            type: Object,
            required: true
        }
    }),
    columnNewSelectOptionName = ref(''),
    selectOptionColumnForm = useForm({
        selectOptions: props.column.type_options
    }),
    showNewSelectOptionError = ref(false),
    addNewColumnNewSelectOption = () => {
        if (columnNewSelectOptionName.value.length === 0 || columnNewSelectOptionName.value.trim().length === 0) {
            //just whitespaces are not allowed
            return;
        }
        selectOptionColumnForm.selectOptions.push(columnNewSelectOptionName.value);
        columnNewSelectOptionName.value = '';
        validateSelectOptions();
    },
    removeNewColumnNewSelectOption = (index) => {
        selectOptionColumnForm.selectOptions.splice(index, 1);
    },
    validateSelectOptions = () => {
        showNewSelectOptionError.value = selectOptionColumnForm.selectOptions.length < 1;

        return !showNewSelectOptionError.value;
    },
    updateSelectOptions = () => {
        if (!validateSelectOptions()) {
            return;
        }
        selectOptionColumnForm.patch(
            route(
                'inventory-management.inventory.column.update.type_options',
                {
                    craftsInventoryColumn: props.column.id
                }
            ),
            {
                preserveScroll: true,
                onSuccess: close
            }
        );
    },
    close = () => {
        selectOptionColumnForm.reset();
        emits.call(this, 'closed');
    }
</script>
