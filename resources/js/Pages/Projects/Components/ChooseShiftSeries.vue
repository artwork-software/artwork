<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closed">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform overflow-hidden bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6 rounded-lg">
                            <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8 mb-4"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500" @click="closed">
                                    <span class="sr-only">Close</span>
                                    <XIcon class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </div>
                            <div class="relative z-40">
                                <div class="font-black font-lexend text-primary text-3xl my-2">
                                    {{ $t('Shift repetition') }}
                                </div>
                                <p class="xsLight">
                                    {{ $t('You have assigned a shift to a repeat event. Specify the period in which the shift is to be repeated.') }}
                                </p>
                                <SwitchGroup as="div" class="flex items-center mt-4">
                                    <SwitchLabel as="span" class="mr-3 text-sm" :class="bufferForReturn.onlyThisDay ? 'text-gray-400' : 'font-bold'">
                                        {{ $t('On this and other days') }}
                                    </SwitchLabel>
                                    <Switch v-model="bufferForReturn.onlyThisDay" :class="[bufferForReturn.onlyThisDay ? 'bg-artwork-buttons-create' : 'bg-artwork-buttons-create', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none']">
                                        <span aria-hidden="true" :class="[bufferForReturn.onlyThisDay  ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                                    </Switch>
                                    <SwitchLabel as="span" class="ml-3 text-sm" :class="bufferForReturn.onlyThisDay ? 'font-bold' : 'text-gray-400'">
                                        {{ $t('For this day only') }}
                                    </SwitchLabel>
                                </SwitchGroup>
                                <div v-if="!bufferForReturn.onlyThisDay">
                                    <p class="text-xs my-2 flex items-center text-artwork-buttons-create">
                                        <IconInfoCircle class="h-4 w-4 mr-1"/>
                                        {{ $t('Period of the repeat appointment: {0} - {1}', [event.dates_for_series_event.formatted_start, event.dates_for_series_event.formatted_end]) }}
                                    </p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-4">
                                        <div>
                                            <input :type="bufferForReturn.start ? 'date' : 'text'" onfocus="(this.type='date')"
                                                   :placeholder="$t('Start')"
                                                   v-model="bufferForReturn.start"
                                                   class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                                                   required
                                            />
                                        </div>
                                        <div>
                                            <input :type="bufferForReturn.end ? 'date' : 'text'" onfocus="(this.type='date')"
                                                   :placeholder="$t('End')"
                                                   v-model="bufferForReturn.end"
                                                   maxlength="3"
                                                   required
                                                   class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                                        </div>
                                    </div>
                                </div>
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
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "ChooseShiftSeries",
    mixins: [Permissions, IconLib],
    components: {
        FormButton,
        DialogPanel,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon, Switch, SwitchGroup, SwitchLabel
    },
    data(){
        return {
            open: true,
            clickedAll: false,
            clickedSingle: false,
            bufferForReturn: {
                onlyThisDay: this.buffer ? this.buffer.onlyThisDay : false,
                start: this.event.dates_for_series_event ? this.event.dates_for_series_event.start : this.buffer ? this.buffer.start : null,
                end: this.event.dates_for_series_event ? this.event.dates_for_series_event.end : this.buffer ? this.buffer.end : null,
                cameFormBuffer: true
            }
        }
    },
    props: ['event', 'buffer'],
    emits: ['close-modal', 'returnBuffer'],
    methods: {
        closed(){
            this.$emit('close-modal')
        },
        returnBuffer(){
            this.$emit('returnBuffer', this.bufferForReturn)
        }
    }
}
</script>
