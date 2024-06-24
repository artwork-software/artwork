<script>
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    Switch,
    SwitchGroup,
    SwitchLabel,
    TransitionChild,
    TransitionRoot
} from "@headlessui/vue";
import {XIcon} from "@heroicons/vue/solid";
import {useForm} from "@inertiajs/vue3";
import {
    Listbox,
    ListboxButton,
    ListboxOptions,
    ListboxOption,
    ListboxLabel
} from '@headlessui/vue'
export default {
    name: "SelectTabsModal",
    mixins: [IconLib],
    components: {
        SwitchLabel,
        Switch,
        SwitchGroup,
        FormButton,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon, DialogPanel,
        Listbox,
        ListboxButton,
        ListboxOptions,
        ListboxOption,
        ListboxLabel
    },
    emits: ['close', 'openTab'],
    props: ['tabs', 'currentTab', 'componentData', 'position'],
    data(){
        return {
            open: true,
            selected: [],
            enabled: false
        }
    },
    mounted() {
        this.selected = [this.currentTab]
    },
    methods: {
        closeModal(bool){
            this.$emit('close', bool)
        },
        addComponentWithScope() {
            if(!this.enabled){
                this.selected = [this.currentTab]
            }
            this.$inertia.post(route('tab.add.component.with.scopes', {projectTab: this.currentTab.id}), {
                component_id: this.componentData.id,
                order: this.position,
                scope: this.selected.map((tab) => tab.id)
            }, {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    this.closeModal(false);
                    this.$emit('openTab', this.currentTab.id)
                }
            });
        }
    }
}
</script>

<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="closeModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                        <DialogPanel class="relative transform bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl sm:p-6">
                            <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-8 mb-4"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500" @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <IconX stroke-width="1.5" class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </div>
                            <div class="relative z-40 px-4">
                                <div class="mt-8 headline1">
                                   Wähle Tabs aus
                                </div>
                                <p class="xsLight my-6">
                                    Welche Tabs sollen in dieser Komponente einbezogen werden ?
                                </p>

                                <div class="mb-3">
                                    <SwitchGroup as="div" class="flex items-center gap-x-4">
                                        <SwitchLabel as="span" class="text-sm">
                                            <span class="font-medium text-gray-900">Nur aktuellen Tab einbeziehen</span>
                                        </SwitchLabel>
                                        <Switch v-model="enabled" :class="[enabled ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-artwork-buttons-hover focus:ring-offset-2']">
                                            <span aria-hidden="true" :class="[enabled ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                                        </Switch>
                                        <SwitchLabel as="span" class="text-sm">
                                            <span class="font-medium text-gray-900">Tabs auswählen</span>
                                        </SwitchLabel>
                                    </SwitchGroup>
                                </div>
                                <div v-if="enabled">
                                    <Listbox as="div" v-model="selected" multiple>
                                        <ListboxLabel class="block text-sm font-medium leading-6 text-gray-900">Ausgewählte Tabs</ListboxLabel>
                                        <div class="relative mt-2">
                                            <ListboxButton class="relative w-full cursor-default bg-white h-10 py-1.5 pl-3 pr-10 text-left text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:outline-none focus:ring-2 focus:ring-artwork-buttons-hover sm:text-sm sm:leading-6">
                                                <span class="block truncate"> {{ selected.map((tab) => tab.name).join(', ') }}</span>
                                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                                  <IconChevronDown class="h-5 w-5 text-gray-400" aria-hidden="true" />
                                                </span>
                                            </ListboxButton>

                                            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                                <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                                    <ListboxOption as="template" v-for="tab in tabs" :key="tab.id" :value="tab" v-slot="{ active, selected }">
                                                        <li :class="[active ? 'bg-artwork-buttons-hover text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                                            <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ tab.name }}</span>

                                                            <span v-if="selected" :class="[active ? 'text-white' : 'text-artwork-buttons-hover', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                                <IconCircleCheck class="h-5 w-5" aria-hidden="true" />
                                                              </span>
                                                        </li>
                                                    </ListboxOption>
                                                </ListboxOptions>
                                            </transition>
                                        </div>
                                    </Listbox>
                                </div>
                                <div class="flex justify-between mt-5 items-center pr-4">
                                    <FormButton
                                        @click="addComponentWithScope(true)" :text="$t('Save')" />
                                    <p class="cursor-pointer text-sm mt-3 text-secondary" @click="closeModal">
                                        {{ $t('No, not really') }}
                                    </p>
                                </div>
                            </div>

                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<style scoped>

</style>
