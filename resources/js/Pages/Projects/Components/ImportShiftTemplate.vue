<template>
    <BaseModal @closed="closeModal">
        <div>
            <div class="font-black font-lexend text-primary text-3xl my-2">
                {{ $t('Import shift template') }}
            </div>
            <p class="xsLight subpixel-antialiased">
                {{ $t('Would you like to apply the template to all appointments of this type in this project?') }}
            </p>
            <div class="mt-10">
                <SwitchGroup as="div" class="flex items-center font-bold mb-8">
                    <SwitchLabel as="span" class="mr-3 text-sm">
                        <span :class="presetForm.all === false ? 'font-bold text-gray-900' : 'font-normal text-gray-500'">
                            {{ $t('Only for this date') }}
                        </span>
                    </SwitchLabel>
                    <Switch v-model="presetForm.all" :class="[presetForm.all ?'bg-artwork-buttons-create' :  'bg-gray-200', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create focus:ring-offset-2']">
                        <span aria-hidden="true" :class="[presetForm.all ?'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']"/>
                    </Switch>
                    <SwitchLabel as="span" class="ml-3 text-sm">
                        <span :class="presetForm.all === true ?'font-bold text-gray-900' :'font-normal text-gray-500'">
                            {{ $t('Apply to all ({0})', [event_type.abbreviation]) }}
                        </span>
                    </SwitchLabel>
                </SwitchGroup>
                <div class="mb-2">
                    <div class="relative w-full">
                        <div class="w-full">
                            <BaseInput
                                id="userSearch"
                                v-model="template_query"
                                label="Which template?*"
                            />
                        </div>
                        <transition leave-active-class="transition ease-in duration-100"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0">
                            <div v-if="template_search_result.length > 0 && template_query.length > 0"
                                 class="absolute z-10 mt-1 w-full max-h-60 bg-artwork-navigation-background shadow-lg
                                                        text-base ring-1 ring-black ring-opacity-5
                                                        overflow-auto focus:outline-none sm:text-sm">
                                <div class="border-gray-200">
                                    <div v-for="(template, index) in template_search_result" :key="index"
                                         class="flex items-center cursor-pointer">
                                        <div class="flex-1 text-sm py-4">
                                            <p @click="addTemplate(template)"
                                               class="font-bold px-4 text-white hover:border-l-4 hover:border-l-success">
                                                {{ template.name }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </transition>
                    </div>
                    <div class="mt-3" v-if="selectedTemplate">
                        <div class="flex items-center justify-between">
                            <div>
                                {{ selectedTemplate?.name }}
                            </div>
                            <div>
                                <XIcon class="h-5 w-5 cursor-pointer"
                                       @click="clearSelectedTemplate"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex justify-center">
            <FormButton :text="$t('Import template')" @click="importTemplate" :disabled="presetForm.processing"/>
        </div>
    </BaseModal>
</template>

<script>
import {defineComponent} from 'vue'
import {
    CheckIcon,
    XIcon
} from "@heroicons/vue/solid";
import {
    ChevronDownIcon,
    PlusCircleIcon
} from "@heroicons/vue/outline";
import SingleTimeLine from "@/Pages/Projects/Components/SingleTimeLine.vue";
import Input from "@/Jetstream/Input.vue";
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    TransitionChild,
    TransitionRoot,
    Switch,
    SwitchGroup,
    SwitchLabel
} from "@headlessui/vue";
import {useForm} from "@inertiajs/vue3";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

export default defineComponent({
    name: "ImportShiftTemplate",
    components: {
        BaseInput,
        TextInputComponent,
        BaseModal,
        FormButton,
        CheckIcon,
        ChevronDownIcon,
        SingleTimeLine,
        Input,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon,
        DialogPanel,
        PlusCircleIcon,
        Listbox,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        Switch,
        SwitchGroup,
        SwitchLabel
    },
    emits: ['closed'],
    props: [
        'event_type',
        'eventId'
    ],
    data() {
        return {
            open: true,
            showAddTimeLine: false,
            presetForm: useForm({
                name: null,
                all: false,
                selectedTemplate: null,
            }),
            template_query: null,
            template_search_result: [],
            selectedTemplate: null
        }
    },
    methods: {
        closeModal() {
            this.$emit('closed')
        },
        importTemplate(){
            this.presetForm.post(
                route('shift.preset.import' , {event: this.eventId, shiftPreset: this.selectedTemplate.id}),
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.closeModal()
                    }
                }
            )
        },
        addTemplate(template){
            this.presetForm.selectedTemplate = template.id;
            this.selectedTemplate = template;
            this.template_query = '';
            this.template_search_result = [];
        },
        clearSelectedTemplate(){
            this.presetForm.selectedTemplate = null;
            this.selectedTemplate = null;
        },
    },
    watch: {
        template_query: {
            handler() {
                if (this.template_query.length > 0) {
                    axios.get('/shift/template/search', {
                        params: {query: this.template_query, eventTypeId: this.event_type.id}
                    }).then(response => {
                        this.template_search_result = response.data
                    })
                }
            },
            deep: true
        },
    },

})
</script>
