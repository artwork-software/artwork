<template>
    <jet-dialog-modal :show="true" @close="closeModal()">
        <template #content>
            <img alt="Details" src="/Svgs/Overlays/illu_budget_edit.svg" class="-ml-6 -mt-8 mb-4"/>
            <XIcon @click="closeModal()" class="text-secondary h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                   aria-hidden="true"/>
            <div class="mx-4">
                <!--   Heading   -->
                <div>
                    <h1 class="my-1 flex">
                        <div class="flex-grow headline1">
                            Details
                        </div>
                    </h1>
                    <div class="mb-4">
                        <div class="hidden sm:block">
                            <div class="border-gray-200">
                                <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8"
                                     aria-label="Tabs">
                                    <a @click="changeTab(tab)" v-for="tab in tabs" href="#" :key="tab.name"
                                       :class="[tab.current ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium font-semibold']"
                                       :aria-current="tab.current ? 'page' : undefined">
                                        {{ tab.name }}
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <!-- Calculate Tab -->
                    <div v-if="isCalculateTab">
                        <div v-if="this.calculationArray?.length > 0"
                             v-for="(calculation,index) in this.calculationArray">
                            <div class="h-1.5 my-2 bg-silverGray"/>
                            <div class="flex space-x-4 mb-3">
                                <div class="w-1/2">
                                    <input type="text"
                                           v-model="this.calculationNames[index]"
                                           placeholder="Name"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                                <div class="w-1/2">
                                    <input type="text"
                                           v-model="this.calculationValues[index]"
                                           placeholder="Wert"
                                           class="h-12 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                            </div>
                            <textarea placeholder="Kommentar"
                                      v-model="this.calculationDescriptions[index]"
                                      rows="4"
                                      class="inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        </div>
                        <div v-else>
                            <div class="flex space-x-4 mb-3">
                                <div class="w-1/2">
                                    <input type="text"
                                           v-model="this.calculationNames[0]"
                                           placeholder="Name"
                                           class="h-10 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                                <div class="w-1/2">
                                    <input type="text"
                                           v-model="this.calculationValues[0]"
                                           placeholder="Wert"
                                           class="h-10 sDark inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                </div>
                            </div>
                            <textarea placeholder="Kommentar"
                                      v-model="this.calculationDescriptions[0]"
                                      rows="4"
                                      class="inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                        </div>
                        <div @click="addCalculation()"
                             class="bg-secondaryHover h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue cursor-pointer"
                             @mouseover="hoveredBorder = true"
                             @mouseout="hoveredBorder = false">
                            <div v-if="hoveredBorder"
                                 class="uppercase text-buttonBlue text-sm -mt-8">
                                Position
                                <PlusCircleIcon @mouseover="hoveredBorder = true"
                                                @mouseout="hoveredBorder = false" v-if="hoveredBorder"
                                                @click="addCalculation()"
                                                class="h-6 w-6 ml-5 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                            </div>
                        </div>
                        <div class="py-2 bg-silverGray flex justify-between mt-2">
                            <div class="ml-2 sDark">
                                SUM
                            </div>
                            <div class="mr-2 sDark">
                                {{this.calculationValues?.reduce((a, b) => a + b, 0)}}
                            </div>
                        </div>
                        <div class="flex justify-center mt-6">
                            <AddButton @click="saveCalculation()" text="Speichern" class="text-sm ml-0 px-12 py-5 xsWhiteBold"></AddButton>
                        </div>
                    </div>
                    <!-- Link Tab -->
                    <div v-if="isLinkTab">
                        <h2 class="xsLight mb-2 mt-8">
                            Behalte den Überblick über deine Finanzierungsquellen. Du kannst den Wert entweder zur
                            Quelle/-gruppe addieren oder subtrahieren.
                        </h2>
                        <div class="flex items-center justify-start my-6">
                            <input v-model="isLinked" type="checkbox"
                                   class="ring-offset-0 cursor-pointer focus:ring-0 focus:shadow-none h-6 w-6 text-success border-2 border-gray-300"/>
                            <p :class="[isLinked ? 'xsDark' : 'xsLight']"
                               class="ml-4 my-auto text-sm"> Mit Finanzierungsquelle/-gruppe verlinken</p>
                        </div>
                        <div v-if="isLinked" class="flex w-full">
                            <Listbox as="div" v-model="linkedType" id="linked_type">
                                <ListboxButton class="inputMain w-12 h-10 cursor-pointer truncate flex p-2">
                                    <div class="flex-grow xsLight text-left subpixel-antialiased">
                                        {{ linkedType.name }}
                                    </div>
                                    <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </ListboxButton>
                                <ListboxOptions class="w-12 bg-primary max-h-32 overflow-y-auto text-sm absolute">
                                    <ListboxOption v-for="type in linkTypes"
                                                   class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between "
                                                   :key="type.name"
                                                   :value="type"
                                                   v-slot="{ active, selected }">
                                        <div :class="[selected ? 'text-white' : '']">
                                            {{ type.name }}
                                        </div>
                                        <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                    </ListboxOption>
                                </ListboxOptions>
                            </Listbox>
                            <Listbox as="div" v-model="selectedMoneySource" id="room" class="w-full">
                                <ListboxButton class="inputMain h-10 cursor-pointer w-11/12 truncate flex p-2">
                                    <div class="w-full xsLight text-left subpixel-antialiased"
                                         v-if="selectedMoneySource === null">
                                        Quelle wählen*
                                    </div>
                                    <div class="w-full xsDark text-left subpixel-antialiased" v-else>
                                        {{ selectedMoneySource.name }}
                                    </div>
                                    <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                                </ListboxButton>
                                <ListboxOptions class="w-[74%] bg-primary max-h-32 overflow-y-auto text-sm absolute">
                                    <ListboxOption v-for="moneySource in moneySources"
                                                   class="hover:bg-indigo-800 text-secondary cursor-pointer p-2 flex justify-between "
                                                   :key="moneySource.name"
                                                   :value="moneySource"
                                                   v-slot="{ active, selected }">
                                        <div :class="[selected ? 'text-white' : '']">
                                            {{ moneySource.name }}
                                        </div>
                                        <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                    </ListboxOption>
                                </ListboxOptions>
                            </Listbox>
                        </div>
                        <div class="flex justify-center">
                            <AddButton @click="updateMoneySourceLink()"
                                       class="mt-8 py-3 flex" text="Speichern"
                                       mode="modal"></AddButton>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </jet-dialog-modal>

</template>

<script>

import {Listbox, ListboxButton, ListboxOption, ListboxOptions, RadioGroup, RadioGroupOption} from "@headlessui/vue";

import JetDialogModal from "@/Jetstream/DialogModal";
import {XIcon, CheckIcon, ChevronDownIcon, PlusCircleIcon} from '@heroicons/vue/outline';
import AddButton from "@/Layouts/Components/AddButton.vue";

const linkTypes = [
    {name: '+', type: 'EARNING'},
    {name: '-', type: 'COST'}
]

export default {
    name: 'CellDetailComponent',

    components: {
        AddButton,
        ListboxOptions,
        ListboxOption,
        ListboxButton,
        Listbox,
        RadioGroupOption,
        RadioGroup,
        JetDialogModal,
        XIcon,
        CheckIcon,
        ChevronDownIcon,
        PlusCircleIcon
    },

    data() {
        return {
            isLinked: this.column.pivot.linked_money_source_id !== null,
            linkedType: this.column.pivot.linked_type === 'COST' ? linkTypes[1] : linkTypes[0],
            selectedMoneySource: this.column.pivot.linked_money_source_id !== null ? this.moneySources.find(moneySource => moneySource.id === this.column.pivot.linked_money_source_id) : null,
            linkTypes,
            isCalculateTab: true,
            isCommentTab: false,
            isExcludeTab: false,
            isLinkTab: false,
            hoveredBorder: false,
        }
    },

    props: ['column', 'moneySources'],

    emits: ['closed'],

    watch: {},
    computed: {
        tabs() {
            return [
                {name: 'Kalkulation', href: '#', current: this.isCalculateTab},
                {name: 'Kommentar', href: '#', current: this.isCommentTab},
                {name: 'Ausklammern', href: '#', current: this.isExcludeTab},
                {name: 'Verlinkung', href: '#', current: this.isLinkTab},
            ]
        },
        calculationNames() {
            let names = []
            this.column.pivot.calculations?.forEach((calculation) => {
                names.push(calculation.name);
            })
            return names;
        },
        calculationValues() {
            let values = []
            this.column.pivot.calculations?.forEach((calculation) => {
                values.push(calculation.value);
            })
            return values;
        },
        calculationDescriptions() {
            let descriptions = []
            this.column.pivot.calculations?.forEach((calculation) => {
                descriptions.push(calculation.description);
            })
            return descriptions;
        },
        calculationArray() {
            let helperArray = [];
            this.column.pivot.calculations?.forEach((calculation, index) => {
                helperArray[index] = {
                    name: this.calculationNames[index],
                    value: this.calculationValues[index],
                    description: this.calculationDescriptions[index]
                };
            });
            return helperArray;
        },
        calculationSum() {
            return this.calculationValues?.reduce((a, b) => a + b, 0);

        },
    },

    methods: {
        openModal() {
        },
        changeTab(selectedTab) {
            this.isCalculateTab = false;
            this.isCommentTab = false;
            this.isExcludeTab = false;
            this.isLinkTab = false;
            if (selectedTab.name === 'Kalkulation') {
                this.isCalculateTab = true;
            } else if (selectedTab.name === 'Kommentar') {
                this.isCommentTab = true;
            } else if (selectedTab.name === 'Ausklammern') {
                this.isExcludeTab = true;
            } else {
                this.isLinkTab = true;
            }
        },
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        updateMoneySourceLink() {
            this.$inertia.patch(route('project.budget.cell-source.update'), {
                cell_id: this.column.pivot.id,
                linked_type: this.linkedType.type,
                money_source_id: this.selectedMoneySource.id
            });
        },
        addCalculation() {
            if (this.calculationArray?.length > 0) {
                this.calculationArray?.forEach((calculation, index) => {
                    this.calculationArray[index] = {
                        name: this.calculationNames[index],
                        value: this.calculationValues[index],
                        description: this.calculationDescriptions[index]
                    };
                })
                this.calculationArray.push({name: '', value: '', description: ''})
            } else {
                this.calculationArray = [({
                    name: this.calculationNames[0],
                    value: this.calculationValues[0],
                    description: this.calculationDescriptions[0]
                })]
                this.calculationArray.push({name: '', value: '', description: ''})
            }
        },
        saveCalculation(){
            this.$inertia.patch(route('project.budget.cell-calculation.update'),{column_id: this.column.id, calculations: this.calculationArray, sub_position_row_id: this.column.pivot.sub_position_row_id}, {preserveState: true});
        }
    },
}
</script>

<style scoped></style>
