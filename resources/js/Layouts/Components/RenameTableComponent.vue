<template>
    <jet-dialog-modal :show="true" @close="closeModal()">
        <template #content>
            <img :alt="$t('Save template')" src="/Svgs/Overlays/illu_budget_edit.svg" class="-ml-6 -mt-8 mb-4"/>
            <XIcon @click="closeModal()" class="text-secondary h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                   aria-hidden="true"/>
            <div class="mx-4">
                <div>
                    <h1 class="my-1 flex">
                        <div class="flex-grow headline1">
                            {{ $t('Rename table') }}
                        </div>
                    </h1>
                    <h2 class="xsLight mb-2 mt-8">
                        {{ $t('Choose a meaningful name for your template. This will make it easy for all users to find.') }}
                    </h2>
                    <div class="flex items-center w-full mr-2">
                        <div class="w-full">
                            <inputComponent v-model="this.tableName" :placeholder="$t('Name of the template*')"/>
                        </div>
                    </div>
                    <div class="flex justify-center">
                        <FormButton @click="updateBudgetTemplateName()"
                                   :disabled="tableName === ''"
                                   :text="$t('Rename')"
                        />
                    </div>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal";
import {XIcon, CheckIcon, ChevronDownIcon} from '@heroicons/vue/outline';
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import {XCircleIcon} from "@heroicons/vue/solid";
import Permissions from "@/mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

export default {
    name: 'RenameTableComponent',
    mixins: [Permissions],
    components: {
        FormButton,
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
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        updateBudgetTemplateName() {
            this.$inertia.patch(
                route('project.budget.table.update-name'),
                {
                    table_id: this.table.id,
                    table_name: this.tableName
                }
            );
            this.closeModal(true);
        }
    },
}
</script>
