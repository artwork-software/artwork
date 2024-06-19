<template>
    <BaseModal @closed="closeModal(false)" v-if="true" modal-image="/Svgs/Overlays/illu_budget_edit.svg">
            <div class="mx-4">
                <!--   Heading   -->
                <div>
                    <h1 class="my-1 flex">
                        <div class="flex-grow headline1">
                            {{ $t('New column') }}
                        </div>
                    </h1>
                    <h2 class="xsLight mb-2 mt-8">
                        {{ $t('Create a new, empty column. Alternatively, you can also create a function column (sum/difference).') }}
                    </h2>
                    <radio-group v-model="selectedType" class="mt-4">
                        <legend class="sr-only">{{ $t('Column type') }}</legend>
                        <div class="space-y-3">
                            <radio-group-option :value="columnType.type" v-for="columnType in columnTypes"
                                                :key="columnType.type" class="flex items-center">
                                <input :id="columnType.type" name="notification-method" type="radio"
                                       :checked="columnType.type === 'empty'"
                                       class="h-5 w-5 border-gray-300 text-success focus:ring-success"/>
                                <label :for="columnType.type"
                                       :class="[selectedType === columnType.type ? 'xsDark' : 'xsLight']"
                                       class="ml-3 block">{{ columnType.title }}</label>
                            </radio-group-option>
                        </div>
                    </radio-group>
                    <div v-if="selectedType !== 'empty'" class="bg-backgroundGray -mx-10 pb-8">
                        <h2 v-if="selectedType === 'sum'" class="xsLight ml-12 mb-4 pt-4 mt-6">
                            {{ $t('What amount would you like to receive?') }}
                        </h2>
                        <h2 v-if="selectedType === 'difference'" class="xsLight ml-12 mb-4 pt-4 mt-6">
                            {{ $t('What difference do you want to get?') }}
                        </h2>
                        <div class="flex ml-12 w-full pr-24">
                            <Listbox as="div" class="flex h-12 mr-2 w-1/2" v-model="selectedFirstColumn"
                                     id="firstColumn">
                                <ListboxButton
                                    class="pl-3 h-12 inputMain w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm">
                                    <div class="flex items-center my-auto">
                                        <span class="block truncate items-center ml-3 flex" v-if="selectedFirstColumn">
                                            <span>{{ selectedFirstColumn?.name }}</span>
                                        </span>
                                        <span class="block truncate items-center ml-3 flex" v-else>
                                            <span>{{ $t('Select column') }}</span>
                                        </span>
                                        <span
                                            class="ml-2 right-0 absolute inset-y-0 flex items-center pr-2 pointer-events-none">
                                            <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                        </span>
                                    </div>
                                </ListboxButton>
                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions
                                        class="absolute w-72 z-10 mt-12 bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class="max-h-8"
                                                       v-for="column in table.columns.slice(3)"
                                                       :key="column.id"
                                                       :value="column"
                                                       v-slot="{ active, selected }">
                                            <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                <div class="flex">
                                                    <span
                                                        :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                                        {{ column.name }}
                                                    </span>
                                                </div>
                                                <span :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                    <IconCheck stroke-width="1.5" v-if="selected"
                                                               class="h-5 w-5 flex text-success"
                                                               aria-hidden="true"
                                                    />
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </Listbox>
                            <div class="ml-2 mr-4 xsDark my-auto" v-if="selectedType === 'sum'">+</div>
                            <div class="ml-2 mr-4 xsDark my-auto" v-if="selectedType === 'difference'">-</div>
                            <Listbox as="div" class="flex h-12 mr-2 w-1/2" v-model="selectedSecondColumn"
                                     v-if="selectedType !== 'empty'"
                                     id="secondColumn">
                                <ListboxButton
                                    class="pl-3 h-12 inputMain w-full bg-white relative font-semibold py-2 text-left cursor-pointer focus:outline-none sm:text-sm">
                                    <div class="flex items-center my-auto">
                                        <span class="block truncate items-center ml-3 flex" v-if="selectedSecondColumn">
                                            <span>{{ selectedSecondColumn?.name }}</span>
                                        </span>
                                        <span class="block truncate items-center ml-3 flex" v-else>
                                            <span>{{ $t('Select column') }}</span>
                                        </span>
                                        <span
                                            class="ml-2 right-0 absolute inset-y-0 flex items-center pr-2 pointer-events-none">
                                     <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </span>
                                    </div>
                                </ListboxButton>
                                <transition leave-active-class="transition ease-in duration-100"
                                            leave-from-class="opacity-100" leave-to-class="opacity-0">
                                    <ListboxOptions
                                        class="absolute w-72 z-10 mt-12 bg-primary shadow-lg max-h-32 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                                        <ListboxOption as="template" class="max-h-8"
                                                       v-for="column in table.columns.slice(3)"
                                                       :key="column.id"
                                                       :value="column"
                                                       v-slot="{ active, selected }">
                                            <li :class="[active ? ' text-white' : 'text-secondary', 'group hover:border-l-4 hover:border-l-success cursor-pointer flex justify-between items-center py-2 pl-3 pr-9 text-sm subpixel-antialiased']">
                                                <div class="flex">
                                                    <span
                                                        :class="[selected ? 'xsWhiteBold' : 'font-normal', 'ml-4 block truncate']">
                                                        {{ column.name }}
                                                    </span>
                                                </div>
                                                <span :class="[active ? ' text-white' : 'text-secondary', ' group flex justify-end items-center text-sm subpixel-antialiased']">
                                                      <IconCheck stroke-width="1.5" v-if="selected"
                                                                 class="h-5 w-5 flex text-success"
                                                                 aria-hidden="true"
                                                      />
                                                </span>
                                            </li>
                                        </ListboxOption>
                                    </ListboxOptions>
                                </transition>
                            </Listbox>
                        </div>
                    </div>
                    <div class="flex justify-center mt-8">
                        <FormButton
                            @click="addColumn"
                            :disabled="selectedType !== 'empty' && ((selectedFirstColumn === null || selectedSecondColumn === null) || (selectedFirstColumn === selectedSecondColumn))"
                            :text="$t('Create column')"
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
    ListboxOptions,
    RadioGroup,
    RadioGroupOption
} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {
    XIcon,
    CheckIcon,
    ChevronDownIcon
} from '@heroicons/vue/outline';
import Permissions from "@/Mixins/Permissions.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    name: 'AddColumnComponent',
    mixins: [Permissions, IconLib],
    components: {
        BaseModal,
        FormButton,
        ListboxOptions,
        ListboxOption,
        ListboxButton,
        Listbox,
        RadioGroupOption,
        RadioGroup,
        JetDialogModal,
        XIcon,
        CheckIcon,
        ChevronDownIcon
    },
    data() {
        return {
            columnTypes: [
                {type: 'empty', title: this.$t('Empty column')},
                {type: 'sum', title: this.$t('Sum column')},
                {type: 'difference', title: this.$t('Difference column')},
            ],
            selectedType: 'empty',
            selectedFirstColumn: null,
            selectedSecondColumn: null,
        }
    },
    props: [
        'project',
        'table'
    ],
    emits: ['closed'],
    methods: {
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        addColumn(){
            if (this.selectedType === 'empty') {
                this.$inertia.post(
                    route('project.budget.column.add'),
                    {
                        column_type: this.selectedType,
                        table_id: this.table.id
                    }
                );
            } else {
                this.$inertia.post(
                    route('project.budget.column.add'),
                    {
                        first_column_id: this.selectedFirstColumn.id,
                        second_column_id: this.selectedSecondColumn.id,
                        column_type: this.selectedType,
                        table_id: this.table.id
                    }
                );
            }
            this.closeModal(true);
        }
    },
}
</script>
