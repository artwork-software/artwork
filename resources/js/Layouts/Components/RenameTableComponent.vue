<template>
    <BaseModal @closed="closeModal" v-if="true" modal-image="/Svgs/Overlays/illu_budget_edit.svg">
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
    </BaseModal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {XIcon, CheckIcon, ChevronDownIcon} from '@heroicons/vue/outline';
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import {XCircleIcon} from "@heroicons/vue/solid";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: 'RenameTableComponent',
    mixins: [Permissions],
    components: {
        BaseModal,
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
