<template>
    <BaseModal @closed="closeModal" v-if="true" modal-image="/Svgs/Overlays/illu_budget_edit.svg">
            <div class="mx-4">
                <div>
                    <h1 class="my-1 flex">
                        <div class="flex-grow headline1">
                            {{ $t('Import template') }}
                        </div>
                    </h1>
                    <h2 class="xsLight mb-2 mt-8">
                        {{ $t('To make your work easier, use a template.') }}
                    </h2>
                    <Listbox as="div" class="flex h-12 mr-2 w-full" v-model="selectedTemplate">
                        <ListboxButton
                            class="pl-3 h-12 inputMain w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm">
                            <div class="flex items-center my-auto">
                                <span class="block truncate items-center ml-3 flex" v-if="selectedTemplate">
                                    <span>{{ selectedTemplate?.name }}</span>
                                </span>
                                <span class="block truncate items-center ml-3 flex" v-else>
                                    <span>{{ $t('Select template*') }}</span>
                                </span>
                                <span class="ml-2 right-0 absolute inset-y-0 flex items-center pr-2 pointer-events-none">
                                    <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </span>
                            </div>
                        </ListboxButton>
                        <transition leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100" leave-to-class="opacity-0">
                            <ListboxOptions
                                class="absolute w-[90%] z-10 mt-12 bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                <ListboxOption as="template" class="max-h-8"
                                               v-for="template in this.templates"
                                               :key="template.id"
                                               :value="template"
                                               v-slot="{ active, selected }">
                                    <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                        <div class="flex">
                                            <span
                                                :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                                {{ template.name }}
                                            </span>
                                        </div>
                                        <span :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                            <CheckIcon v-if="selected"
                                                       class="h-5 w-5 flex text-success"
                                                       aria-hidden="true"/>
                                        </span>
                                    </li>
                                </ListboxOption>
                            </ListboxOptions>
                        </transition>
                    </Listbox>
                    <div class="flex justify-center">
                        <FormButton @click="useTemplate()" :disabled="selectedTemplate === null"
                                   :text="$t('Import template')"
                        />
                    </div>
                </div>
            </div>
    </BaseModal>
</template>

<script>
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions
} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {
    XIcon,
    CheckIcon,
    ChevronDownIcon
} from '@heroicons/vue/outline';
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: 'UseTemplateComponent',
    mixins: [Permissions],
    components: {
        BaseModal,
        FormButton,
        ListboxOptions,
        ListboxOption,
        ListboxButton,
        Listbox,
        JetDialogModal,
        XIcon,
        CheckIcon,
        ChevronDownIcon
    },
    data() {
        return {
            selectedTemplate: null,
        }
    },
    props: [
        'projectId',
        'templates'
    ],
    emits: ['closed'],
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        useTemplate() {
            this.$inertia.post(
                route('project.budget.template.use', this.selectedTemplate.id),
                {
                    project_id: this.projectId
                }
            );
            this.closeModal(true);
        }
    },
}
</script>
