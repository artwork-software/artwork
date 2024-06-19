<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closeModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100"
                             leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"/>
            </TransitionChild>
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300"
                                     enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200"
                                     leave-from="opacity-100 translate-y-0 sm:scale-100"
                                     leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel
                            class="relative transform bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl sm:p-6">
                            <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-6 mb-4"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500"
                                        @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <XIcon class="h-6 w-6" aria-hidden="true"/>
                                </button>
                            </div>
                            <div class="relative z-40">
                                <div class="font-black font-lexend text-primary text-3xl my-2">
                                    {{ $t('Import shift template') }}
                                </div>
                                <p class="xsLight subpixel-antialiased">
                                    {{ $t('Would you like to apply the template to all appointments of this type in this project?') }}
                                </p>
                                <div class="mt-10">
                                    <SwitchGroup as="div" class="flex items-center font-bold mb-3">
                                        <SwitchLabel as="span" class="mr-3 text-sm">
                                            <span :class="presetForm.all === false ?
                                                    'font-bold text-gray-900' :
                                                    'font-normal text-gray-500'"
                                            >
                                                {{ $t('Only for this date') }}
                                            </span>
                                        </SwitchLabel>
                                        <Switch v-model="presetForm.all"
                                                :class="[
                                                    presetForm.all ?
                                                        'bg-artwork-buttons-create' :
                                                        'bg-gray-200',
                                                    'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create focus:ring-offset-2'
                                                ]"
                                        >
                                            <span aria-hidden="true"
                                                  :class="[
                                                      presetForm.all ?
                                                          'translate-x-3' :
                                                          'translate-x-0',
                                                      'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                                  ]"
                                            />
                                        </Switch>
                                        <SwitchLabel as="span" class="ml-3 text-sm">
                                            <span :class="presetForm.all === true ?
                                                    'font-bold text-gray-900' :
                                                    'font-normal text-gray-500'"
                                            >
                                                {{ $t('Apply to all ({0})', [event_type.abbreviation]) }}
                                            </span>
                                        </SwitchLabel>
                                    </SwitchGroup>
                                    <div class="mb-2">
                                        <div class="relative w-full">
                                            <div class="w-full">
                                                <input id="userSearch"
                                                       v-model="template_query"
                                                       type="text"
                                                       autocomplete="off"
                                                       :placeholder="$t('Which template?*')"
                                                       class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
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
                                <FormButton :text="$t('Import template')" @click="importTemplate"/>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
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

export default defineComponent({
    name: "ImportShiftTemplate",
    components: {
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
