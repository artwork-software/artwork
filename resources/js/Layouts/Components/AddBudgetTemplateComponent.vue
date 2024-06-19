<template>
    <BaseModal @closed="closeModal(false)" v-if="true" modal-image="/Svgs/Overlays/illu_budget_edit.svg">
        <div class="mx-4">
            <!--   Heading   -->
            <div>
                <h1 class="my-1 flex">
                    <div class="flex-grow headline1">
                        {{ $t('Save as template') }}
                    </div>
                </h1>
                <h2 class="xsLight mb-2 mt-8">
                    {{ $t('Save your calculation and make it available to all users as a template.') }}
                </h2>
                <div class="flex items-center w-full mr-2">
                    <div class="w-full">
                        <input type="text"
                               :placeholder="$t('Name of the template*')"
                               v-model="this.templateName"
                               class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                    </div>
                </div>
                <div class="flex justify-center">
                    <FormButton
                        @click="createBudgetTemplate"
                        :disabled="templateName === ''"
                        :text="$t('Save as template')"
                        class="mt-8"
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
import Input from "@/Layouts/Components/InputComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: 'AddBudgetTemplateComponent',
    mixins: [Permissions, IconLib],
    components: {
        BaseModal,
        FormButton,
        Input,
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
    props: ['tableId'],
    emits: ['closed'],
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        createBudgetTemplate() {
            this.$inertia.post(
                route('project.budget.template.create', this.tableId),
                {
                    template_name: this.templateName,
                },
                {
                    onFinish: this.$emit('closed', true)
                }
            );
        }
    },
}
</script>
