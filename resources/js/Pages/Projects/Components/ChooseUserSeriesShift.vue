<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closed">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>
            <div class="fixed inset-0 z-50">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
                            <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8 mb-4"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500" @click="closed">
                                    <span class="sr-only">Close</span>
                                    <XIcon class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </div>
                            <div class="relative z-40">
                                <div class="font-black font-lexend text-primary text-3xl my-2">
                                    {{ $t('Shift staffing period') }}
                                </div>
                                <p class="xsLight">
                                    {{ $t('You have filled a repeat shift. Determine the period for which you want to fill the employee.') }}
                                </p>
                                <SwitchGroup as="div" class="flex items-center mt-4">
                                    <SwitchLabel as="span" class="mr-3 text-sm" :class="bufferForReturn.onlyThisDay ? 'text-gray-400' : 'font-bold'">
                                        {{ $t('On this and other days') }}
                                    </SwitchLabel>
                                    <Switch v-model="bufferForReturn.onlyThisDay " :class="[bufferForReturn.onlyThisDay ? 'bg-artwork-buttons-create' : 'bg-artwork-buttons-create', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none']">
                                        <span aria-hidden="true" :class="[bufferForReturn.onlyThisDay  ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                                    </Switch>
                                    <SwitchLabel as="span" class="ml-3 text-sm" :class="bufferForReturn.onlyThisDay ? 'font-bold' : 'text-gray-400'">
                                        {{ $t('For this day only') }}
                                    </SwitchLabel>
                                </SwitchGroup>

                                <div v-if="!bufferForReturn.onlyThisDay">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-4 mt-4">
                                        <div>
                                            <input type="text" onfocus="(this.type='date')"
                                                   :placeholder="$t('Start')"
                                                   v-model="bufferForReturn.start"
                                                   class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                                                   required
                                            />
                                        </div>
                                        <div>
                                            <input type="text" onfocus="(this.type='date')"
                                                   :placeholder="$t('End')"
                                                   v-model="bufferForReturn.end"
                                                   maxlength="3"
                                                   required
                                                   class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>

                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <Listbox as="div" v-model="selectedDay">
                                            <div class="relative">
                                                <ListboxButton class="w-full h-10 border-gray-300 inputMain xsDark placeholder-secondary disabled:border-none flex-grow">
                                                    <span class="block truncate text-left pl-3">{{ selectedDay.name ?? $t('Every Monday')}} </span>
                                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                                            <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                                        </span>
                                                </ListboxButton>
                                                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                                    <ListboxOptions class="absolute z-50 mt-1 max-h-28 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                                        <ListboxOption as="template" v-for="weekday in weekdays" :key="weekday.id" :value="weekday" v-slot="{ active, selected }">
                                                            <li :class="[active ? 'bg-artwork-buttons-create text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                                                <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ weekday.name }}</span>
                                                                <span v-if="selected" :class="[active ? 'text-white' : 'text-artwork-buttons-create', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                                        <CheckIcon class="h-5 w-5" aria-hidden="true" />
                                                                    </span>
                                                            </li>
                                                        </ListboxOption>
                                                    </ListboxOptions>
                                                </transition>
                                            </div>
                                        </Listbox>
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
    SwitchLabel,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Listbox
} from '@headlessui/vue'
import {
    CheckIcon,
    XIcon
} from "@heroicons/vue/solid";
import Permissions from "@/Mixins/Permissions.vue";
import {ChevronDownIcon} from "@heroicons/vue/outline";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
export default {
    name: "ChooseUserSeriesShift",
    mixins: [Permissions],
    components: {
        FormButton,
        ChevronDownIcon, CheckIcon,
        DialogPanel,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon,
        Switch,
        SwitchGroup,
        SwitchLabel,
        ListboxButton,
        ListboxOption,
        ListboxOptions,
        Listbox
    },
    data(){
        return {
            open: true,
            bufferForReturn: {
                onlyThisDay: this.buffer ? this.buffer.onlyThisDay : false,
                start: this.buffer ? this.buffer.start : null,
                end: this.buffer ? this.buffer.end : null,
                dayOfWeek: null
            },
            weekdays: [
                {name: this.$t('Every day'), id: 'all'},
                {name: this.$t('Every Monday'), id: 1},
                {name: this.$t('Every Tuesday'), id: 2},
                {name: this.$t('Every Wednesday'), id: 3},
                {name: this.$t('Every Thursday'), id: 4},
                {name: this.$t('Every Friday'), id: 5},
                {name: this.$t('Every Saturday'), id: 6},
                {name: this.$t('Every Sunday'), id: 0},
            ],
            selectedDay: {name: this.$t('Every day'), id: 'all'}
        }
    },
    props: [
        'event',
        'buffer'
    ],
    emits: [
        'close-modal',
        'returnBuffer'
    ],
    methods: {
        closed(){
            this.$emit('close-modal')
        },
        returnBuffer(){
            this.bufferForReturn.dayOfWeek = this.selectedDay.id
            this.$emit('returnBuffer', this.bufferForReturn)
        }
    }
}
</script>
