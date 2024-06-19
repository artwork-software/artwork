<template>
    <BaseModal v-if="show" @closed="close">
        <div class="flex flex-col mx-4 gap-y-2">
            <h1 class="headline1">
                Neue Spalte erstellen
            </h1>
            <div id="new-column-form"
                 class="flex flex-col gap-y-2">
                <TextInputComponent id="new-column-name"
                                    v-model="newColumnForm.name"
                                    label="Spaltenname"
                />
                <SelectComponent id="new-column-type"
                                 v-model="newColumnForm.type"
                                 label="Spaltentyp"
                                 :options="getTypeOptions()"
                                 :getter-for-options-to-display="(type) => type.value"
                                 selected-property-to-display="value"
                />
                <div v-if="newColumnForm.type?.id === NEW_COLUMN_TYPE.SELECT.id">
                    Auswahlbox-Optionen:
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import BaseModal from "@/Components/Modals/BaseModal.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import {ref} from "vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import SelectComponent from "@/Components/Inputs/SelectComponent.vue";

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
    showNewColumnFormNameError = ref(false),
    newColumnFormNameErrorText = ref(''),
    showNewColumnFormTypeOptionsError = ref(false),
    newColumnFormTypeOptionsErrorText = ref(''),
    newColumnForm = useForm({
        name: null,
        type: null
    }),
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
    validateNewColumn = () => {
        if (newColumnForm.name.length === 0) {
            showNewColumnFormNameError.value = true;
            newColumnFormNameErrorText.value = 'Es muss ein Spaltenname angegeben werden.'
        } else {
            showNewColumnFormNameError.value = false;
            newColumnFormNameErrorText.value = '';
        }

        // if (newColumnForm.typeOptions.length > 1) {
        //     showNewColumnFormNameError.value = true;
        //     newColumnFormNameErrorText.value = 'Es muss mindestens eine Auswahloption hinzugefügt werden.';
        // } else {
        //     showNewColumnFormNameError.value = false;
        //     newColumnFormNameErrorText.value = '';
        // }
        //
        // if (newColumnForm.type === 'select') {
        //     if (newColumnForm.typeOptions.hasDefault) {
        //         //validate if default is selected
        //     }
        // }
    },
    saveNewColumn = () => {
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
