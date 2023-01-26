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
                            Als Vorlage speichern
                        </div>
                    </h1>
                    <h2 class="xsLight mb-2 mt-8">
                        Speichere deine Kalkulation und mache sie allen Usern als Vorlage nutzbar.
                    </h2>
                    <div class="flex items-center w-full mr-2">
                        <div class="w-full">
                            <inputComponent v-model="this.templateName" placeholder="Name der Vorlage?*"/>
                        </div>
                    </div>
                    <div class="flex justify-center">
                        <AddButton @click="createBudgetTemplate()" :disabled="templateName === ''"
                                   :class="templateName === '' ? 'bg-secondary hover:bg-secondary cursor-pointer-none' : ''"
                                   class="mt-8 py-3 flex" text="Als Vorlage speichern"
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
    name: 'AddBudgetTemplateComponent',

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
            templateName: '',
        }
    },


    props: ['projectId'],

    emits: ['closed'],

    methods: {
        openModal() {
        },

        closeModal(bool) {
            this.$emit('closed', bool);
        },
        createBudgetTemplate() {
            this.$inertia.post(route('project.budget.template.create'), {
                project_id: this.projectId,
                template_name: this.templateName
            });
            this.closeModal(true);
        }
    },
}
</script>

<style scoped></style>
