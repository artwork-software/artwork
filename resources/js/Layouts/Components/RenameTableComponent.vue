<template>
    <jet-dialog-modal :show="true" @close="closeModal()">
        <template #content>
            <img alt="Vorlage speichern" src="/Svgs/Overlays/illu_budget_edit.svg" class="-ml-6 -mt-8 mb-4"/>
            <XIcon @click="closeModal()" class="text-secondary h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                   aria-hidden="true"/>
            <div class="mx-4">
                <!--   Heading   -->
                <div>
                    <h1 class="my-1 flex">
                        <div class="flex-grow headline1">
                            Tabelle umbenennen
                        </div>
                    </h1>
                    <h2 class="xsLight mb-2 mt-8">
                        Wähle einen aussagekräftigen Namen für deine Vorlage. So kann sie von allen Nutzer*innen einfach gefunden werden.
                    </h2>
                    <div class="flex items-center w-full mr-2">
                        <div class="w-full">
                            <inputComponent v-model="this.tableName" placeholder="Name der Vorlage?*"/>
                        </div>
                    </div>
                    <div class="flex justify-center">
                        <AddButton @click="createBudgetTemplate()" :disabled="tableName === ''"
                                   :class="tableName === '' ? 'bg-secondary hover:bg-secondary cursor-pointer-none' : ''"
                                   class="mt-8 py-3 flex" text="Umbenennen"
                                   mode="modal"></AddButton>
                    </div>
                </div>
            </div>
        </template>
    </jet-dialog-modal>

</template>

<script>

import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";


import JetDialogModal from "@/Jetstream/DialogModal";
import {XIcon, CheckIcon, ChevronDownIcon} from '@heroicons/vue/outline';
import AddButton from "@/Layouts/Components/AddButton.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import {XCircleIcon} from "@heroicons/vue/solid";

export default {
    name: 'RenameTableComponent',

    components: {
        AddButton,
        JetDialogModal,
        XIcon,
        CheckIcon,
        ChevronDownIcon,
        InputComponent,
        XCircleIcon
    },

    data() {
        return {
            tableName: this.table?.name,
        }
    },


    props: ['table'],

    emits: ['closed'],

    methods: {
        openModal() {
        },

        closeModal(bool) {
            this.$emit('closed', bool);
        },
        createBudgetTemplate() {
            this.$inertia.patch(route('project.budget.table.update-name'), {
                table_id: this.table.id,
                table_name: this.tableName
            });
            this.closeModal(true);
        }
    },
}
</script>

<style scoped></style>
