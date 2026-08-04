<template>
    <BaseModal @closed="closeModal(false)" v-if="true" modal-image="/Svgs/Overlays/illu_budget_edit.svg">
        <div class="mx-4">
            <!--   Heading   -->
            <div>
                <h1 class="my-1 flex">
                    <div class="flex-grow font-lexend font-black text-[clamp(24px,3vw,30px)]/[34px] text-text">
                        {{ $t('Save as template') }}
                    </div>
                </h1>
                <h2 class="text-sm/5 font-bold text-text-subtle mb-2 mt-8">
                    {{ $t('Save your calculation and make it available to all users as a template.') }}
                </h2>
                <div class="flex items-center w-full mr-2">
                    <div class="w-full">
                        <input type="text"
                               :placeholder="$t('Name of the template*')"
                               v-model="this.templateName"
                               class="h-10 border border-border placeholder:text-sm/5 font-bold text-text-subtle placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-text-subtle focus:border-1 w-full border-border"/>
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
import {IconCheck, IconChevronDown, IconCircleX, IconX} from "@tabler/icons-vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import Input from "@/Layouts/Components/InputComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: 'AddBudgetTemplateComponent',
    mixins: [Permissions],
    components: {
        BaseModal,
        FormButton,
        Input,
        JetDialogModal,
        IconX,
        IconCheck,
        IconChevronDown,
        InputComponent,
        IconCircleX
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
