<template>
    <jet-dialog-modal :show="true" @close="closeModal(false)">
        <template #content>
            <img alt="Vorlage speichern" src="/Svgs/Overlays/illu_budget_edit.svg" class="-ml-6 -mt-8 mb-4"/>
            <IconX stroke-width="1.5" @click="closeModal(false)" class="text-secondary h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                   aria-hidden="true"/>
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
        </template>
    </jet-dialog-modal>
</template>

<script>
import JetDialogModal from "@/Jetstream/DialogModal";
import {XIcon, CheckIcon, ChevronDownIcon} from '@heroicons/vue/outline';
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import {XCircleIcon} from "@heroicons/vue/solid";
import Input from "@/Layouts/Components/InputComponent.vue";
import Permissions from "@/mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/mixins/IconLib.vue";

export default {
    name: 'AddBudgetTemplateComponent',
    mixins: [Permissions, IconLib],
    components: {
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
