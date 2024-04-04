<script>
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/mixins/IconLib.vue";
import {Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot, Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions} from "@headlessui/vue";
import {XIcon} from "@heroicons/vue/solid";
import {useForm} from "@inertiajs/inertia-vue3";

export default {
    name: "AddEditSidebarTab",
    mixins: [IconLib],
    components: {
        FormButton,
        Dialog,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
        XIcon, DialogPanel,
        Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions
    },
    emits: ['close'],
    props: ['tabComponentTypes'],
    data(){
        return {
            open: true,
            selectedType: this.tabComponentTypes ? this.tabComponentTypes['TextField'] : {name: 'Select a component type' },
            textData: this.tabComponentTypes ? JSON.parse(JSON.stringify(this.tabComponentTypes['TextField'].availableFields)) : {},
            componentName: '',
            helpTexts: {
                name: ''
            }
        }
    },
    methods: {
        closeModal(bool){
            this.$emit('close', bool)
        },
        addMoreOneOption(){
            this.textData.options.push({value: ''})
        },
        removeOption(index){
            this.textData.options.splice(index, 1)
        },
        saveTab() {
            this.helpTexts.name = ''
            if(this.componentName === ''){
                this.helpTexts.name = this.$t('Please enter a name.')
                return;
            }
            this.$inertia.post(route('component.store'),{
                    name: this.componentName,
                    type: this.selectedType.name,
                    data: this.textData,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        this.closeModal(false);
                    },
                }
            )
        }
    },
    watch: {
        selectedType: {
            handler(){
                // add the selected type to the textData as copy
                this.textData = JSON.parse(JSON.stringify(this.selectedType.availableFields))
            },
            deep: true
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
                        <DialogPanel class="relative transform  bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
                            <img src="/Svgs/Overlays/illu_appointment_edit.svg" class="-ml-6 -mt-6 mb-4"/>
                            <div class="absolute top-0 right-0 hidden pt-4 pr-4 sm:block">
                                <button type="button" class="rounded-md bg-white text-gray-400 hover:text-gray-500" @click="closeModal">
                                    <span class="sr-only">Close</span>
                                    <IconX stroke-width="1.5" class="h-6 w-6" aria-hidden="true" />
                                </button>
                            </div>
                            <div class="relative z-40 px-4">
                                <div class="font-black font-lexend text-primary text-3xl my-2 mb-6">
                                    {{ $t('Create a new component')}}
                                </div>


                                <Listbox as="div" v-model="selectedType">
                                    <ListboxLabel class="block text-sm font-medium leading-6 text-gray-900">{{$t('Component Layout')}}</ListboxLabel>
                                    <div class="relative mt-2">
                                        <ListboxButton class="relative w-full cursor-default rounded-md bg-white py-1.5 pl-3 pr-10 text-left text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                            <span class="block truncate">{{ $t(selectedType?.name) }}</span>
                                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                              <IconChevronDown class="h-5 w-5 text-gray-400" aria-hidden="true" />
                                            </span>
                                        </ListboxButton>

                                        <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                            <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                                <ListboxOption as="template" v-for="componentTyp in tabComponentTypes" :key="componentTyp.name" :value="componentTyp" v-slot="{ active, selected }">
                                                    <li :class="[active ? 'bg-indigo-600 text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                                        <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ $t(componentTyp.name) }}</span>

                                                        <span v-if="selected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                            <IconCircleCheck class="h-5 w-5" aria-hidden="true" />
                                                          </span>
                                                    </li>
                                                </ListboxOption>
                                            </ListboxOptions>
                                        </transition>
                                    </div>
                                </Listbox>
                                <div class="my-3">
                                    <label for="componentName" class="block text-sm font-medium leading-6 text-gray-900">{{$t('Name of the component')}}</label>
                                    <div class="mt-2">
                                        <input type="text" v-model="componentName" id="componentName" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                                    </div>
                                    <span v-show="helpTexts.name" class="mt-1 text-xs text-red-500">
                                        {{ helpTexts.name }}
                                    </span>
                                </div>
                                <div class="my-3 font-bold text-sm">
                                    {{ $t('Edit basic data')}}
                                </div>

                                <div v-for="(text, index) in textData">
                                    <div class="mb-3" v-if="index === 'title'">
                                        <label :for="index" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('Title')}}</label>
                                        <div class="mt-2">
                                            <input type="text" v-model="textData.title" :id="index" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                                        </div>
                                    </div>
                                    <div class="mb-3" v-if="index === 'label'">
                                        <label :for="index" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('label')}}</label>
                                        <div class="mt-2">
                                            <input type="text" v-model="textData.label" :id="index" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                                        </div>
                                    </div>
                                    <div class="mb-3" v-if="index === 'text'">
                                        <label :for="index" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('Text')}}</label>
                                        <div class="mt-2">
                                            <input type="text" v-model="textData.text" :id="index" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                                        </div>
                                    </div>
                                    <div class="mb-3" v-if="index === 'placeholder'">
                                        <label :for="index" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('Placeholder')}}</label>
                                        <div class="mt-2">
                                            <input type="text" v-model="textData.placeholder" :id="index" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                                        </div>
                                    </div>

                                    <div class="mb-3" v-if="index === 'height'">
                                        <label :for="index" class="block text-sm font-medium leading-6 text-gray-900">{{ $t('Height - ({0} pixels)', [textData.height])}}</label>
                                        <div class="mt-2">
                                            <input type="range" v-model="textData.height" min="0" max="150" class="block w-full rounded-md border-0 py-1.5 text-gray-900 ring-0 placeholder:text-gray-400 focus:ring-0 sm:text-sm sm:leading-6  accent-artwork-buttons-create" />
                                        </div>
                                    </div>
                                    <div class="relative flex items-start"  v-if="index === 'checked'">
                                        <div class="flex h-6 items-center">
                                            <input :id="index"  v-model="textData.checked" :checked="textData.checked" aria-describedby="comments-description" name="comments" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" />
                                        </div>
                                        <div class="ml-3 text-sm leading-6">
                                            <label :for="index" class="font-medium text-gray-900">{{ $t('This checkbox is activated by default')}} </label>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="textData.options?.length > 0">
                                    <div class="mb-3" v-for="(field, optionIndex) in textData.options">
                                        <label :for="'option-' + optionIndex" class="text-sm font-medium leading-6 text-gray-900 flex items-center justify-between">Option ({{ optionIndex + 1 }})
                                            <span v-if="optionIndex !== 0" class="text-xs text-end underline underline-offset-2 text-artwork-buttons-create cursor-pointer" @click="removeOption(optionIndex)">
                                                {{ $t('Remove') }}
                                            </span>
                                        </label>
                                        <div class="mt-2">
                                            <input type="text" v-model="textData.options[optionIndex].value" :id="'option-' + optionIndex" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-end mt-3">
                                        <div class="text-xs underline underline-offset-2 text-artwork-buttons-create cursor-pointer" @click="addMoreOneOption">{{ $t('Add another option')}}</div>
                                    </div>

                                    <div  v-if="textData.options[0].value">
                                        <Listbox as="div" v-model="textData.selected">
                                            <ListboxLabel class="block text-sm font-medium leading-6 text-gray-900">{{ $t('Standard Option') }}</ListboxLabel>
                                            <div class="relative mt-2">
                                                <ListboxButton class="relative w-full cursor-default rounded-md bg-white h-10 py-1.5 pl-3 pr-10 text-left text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                                    <span class="block truncate">{{ textData.selected }}</span>
                                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                              <IconChevronDown class="h-5 w-5 text-gray-400" aria-hidden="true" />
                                            </span>
                                                </ListboxButton>

                                                <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                                    <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                                        <ListboxOption as="template" v-for="option in textData.options" :key="option.value" :value="option.value" v-slot="{ active, selected }">
                                                            <li :class="[active ? 'bg-indigo-600 text-white' : 'text-gray-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                                                <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ option.value }}</span>

                                                                <span v-if="selected" :class="[active ? 'text-white' : 'text-indigo-600', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                                            <IconCircleCheck class="h-5 w-5" aria-hidden="true" />
                                                          </span>
                                                            </li>
                                                        </ListboxOption>
                                                    </ListboxOptions>
                                                </transition>
                                            </div>
                                        </Listbox>
                                        <div class="flex items-center justify-end mt-3">
                                            <div class="text-xs underline underline-offset-2 text-artwork-buttons-create cursor-pointer" @click="textData.selected = ''">{{ $t('Remove default option') }}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-between mt-5 items-center pr-4">
                                    <FormButton
                                        @click="saveTab(true)"
                                        :text="$t('Create')" />
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
