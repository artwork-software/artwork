<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closed">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform overflow-hidden bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
                            <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8 mb-4"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500" @click="closed">
                                    <span class="sr-only">Close</span>
                                    <XIcon class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </div>
                            <div class="relative z-40">
                                <div class="font-black font-lexend text-primary text-3xl my-2">
                                    {{ $t('Delete shift staffing') }}
                                </div>
                                <p class="xsLight">
                                    {{ $t('You want to delete the occupation of a repeat shift. Specify whether this only applies to one or all further shifts.') }}
                                </p>
                                <SwitchGroup as="div" class="flex items-center mt-4">
                                    <SwitchLabel as="span" class="mr-3 text-sm" :class="this.removeFromSingleShift ? 'text-gray-400' : 'font-bold'">
                                        {{ $t('For this and all subsequent shifts') }}
                                    </SwitchLabel>
                                    <Switch v-model="this.removeFromSingleShift" :class="[this.removeFromSingleShift ? 'bg-artwork-buttons-create' : 'bg-artwork-buttons-create', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none']">
                                        <span aria-hidden="true" :class="[this.removeFromSingleShift  ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                                    </Switch>
                                    <SwitchLabel as="span" class="ml-3 text-sm" :class="this.removeFromSingleShift ? 'font-bold' : 'text-gray-400'">
                                        {{ $t('Only for this shift') }}
                                    </SwitchLabel>
                                </SwitchGroup>
                                <div class="flex items-center justify-center">
                                    <FormButton :text="$t('Save')" @click="returnBuffer" class="mt-4"/>
                                </div>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script>
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
    Switch,
    SwitchGroup,
    SwitchLabel
} from '@headlessui/vue'
import {XIcon} from "@heroicons/vue/solid";
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

export default {
    name: "ChooseDeleteUserShiftModal",
    mixins: [Permissions],
    components: {
        FormButton,
        DialogPanel,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon,
        Switch,
        SwitchGroup,
        SwitchLabel
    },
    data(){
        return {
            open: true,
            clickedAll: false,
            clickedSingle: false,
            removeFromSingleShift: false,
        }
    },
    emits: [
        'close-modal',
        'returnBuffer'
    ],
    methods: {
        closed(){
            this.$emit('close-modal')
        },
        returnBuffer(){
            this.$emit('returnBuffer', this.removeFromSingleShift)
        }
    }
}
</script>
