<template>
    <BaseModal @closed="closeModal" v-if="open">
        <div class="font-black font-lexend text-primary text-3xl my-2">
            {{ $t('New shift template') }}
        </div>
        <p class="xsLight subpixel-antialiased">
            {{ $t('What kind of shift template would you like to create?') }}
        </p>
        <div class="mt-10">
            <!--
            <Listbox as="div" class="flex h-12" v-model="selectedEventType" id="eventType">
                <ListboxButton class="pl-3 h-12 inputMain w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm">
                    <div class="flex items-center my-auto">
                        <div>
                            <div class="block w-5 h-5 rounded-full" :style="{'backgroundColor' : selectedEventType?.hex_code }" />
                        </div>
                        <span class="block truncate items-center ml-3 flex">
                                                    <span>{{ selectedEventType?.name }}</span>
                                                </span>
                        <span class="ml-2 right-0 absolute inset-y-0 flex items-center pr-2 pointer-events-none">
                                                     <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                                </span>
                    </div>
                </ListboxButton>
                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <ListboxOptions class="absolute w-full z-10 mt-12 bg-artwork-navigation-background shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                        <ListboxOption as="template" class="max-h-8" v-for="eventType in event_types" :key="eventType.name" :value="eventType" v-show="eventType.id !== 1" v-slot="{ active, selected }">
                            <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 text-sm subpixel-antialiased']">
                                <div class="flex">
                                    <div>
                                        <div class="block w-3 h-3 rounded-full" :style="{'backgroundColor' : eventType?.hex_code }" />
                                    </div>
                                    <span :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                                                {{ eventType.name }}
                                                            </span>
                                </div>
                                <span :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                            <CheckIcon v-if="selected" class="h-5 w-5 flex text-success" aria-hidden="true"/>
                                                        </span>
                            </li>
                        </ListboxOption>
                    </ListboxOptions>
                </transition>
            </Listbox>
            -->
            <div class="w-full mb-3 mt-3">
                <BaseInput
                    v-model="presetForm.name"
                    id="changeEndTime"
                    type="text"
                    required
                    label="Name of the template*"/>
            </div>
        </div>
        <div class="flex justify-center">
            <FormButton
                :text="preset ? $t('Save template') : $t('Create template')"
                @click="savePreset"
                class="mt-3"
                :disabled="presetForm.processing"
            />
        </div>
    </BaseModal>
</template>

<script>
import {defineComponent} from 'vue'
import {CheckIcon, XIcon} from "@heroicons/vue/solid";
import {ChevronDownIcon, PlusCircleIcon} from "@heroicons/vue/outline";
import SingleTimeLine from "@/Pages/Projects/Components/SingleTimeLine.vue";
import Input from "@/Jetstream/Input.vue";
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    Listbox,
    ListboxButton, ListboxOption, ListboxOptions,
    TransitionChild,
    TransitionRoot
} from "@headlessui/vue";
import {useForm} from "@inertiajs/vue3";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

export default defineComponent({
    name: "AddShiftPresetModal",
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
    },
    emits: ['closed'],
    props: ['event_types', 'eventId', 'preset','event_type_id'],
    data() {
        return {
            open: true,
            showAddTimeLine: false,
            selectedEventType: this.preset ?
                this.event_types[this.preset.event_type_id - 1] :
                this.event_types.find(event_type => event_type.id === this.event_type_id),
            presetForm: useForm({
                name: this.preset ? this.preset.name : null,
                event_type_id: null,
            })
        }
    },
    methods: {
        closeModal() {
            this.$emit('closed')
        },
        savePreset(){
            //this.presetForm.event_type_id = this.selectedEventType.id;
            if (this.eventId !== null && this.eventId !== undefined) {
                this.presetForm.post(
                    route('shift-presets.store', { event: this.eventId }),
                    {
                        preserveScroll: true,
                        onSuccess: () => {
                            this.closeModal()
                        }
                    }
                )
            } else if (this.preset?.id !== null && this.preset?.id !== undefined) {
                this.presetForm.patch(
                    route('update.shift.preset', { shiftPreset: this.preset.id }),
                    {
                        preserveScroll: true,
                        onSuccess: () => {
                            this.closeModal()
                        }
                    }
                )
            } else {
                this.presetForm.post(
                    route('empty.presets.store'),
                    {
                        preserveScroll: true,
                        onSuccess: () => {
                            this.closeModal()
                        }
                    }
                )
            }
        },
    },
})
</script>
