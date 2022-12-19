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
                    HIER KOMMEN NOCH TABS

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
                                    {{linkedType.name}}
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
                                <div class="w-full xsLight text-left subpixel-antialiased" v-if="selectedMoneySource === null">
                                    Quelle wählen*
                                </div>
                                <div class="w-full xsLight text-left subpixel-antialiased" v-else>
                                    {{ selectedMoneySource.name}}
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
        </template>
    </jet-dialog-modal>

</template>

<script>

import {Listbox, ListboxButton, ListboxOption, ListboxOptions, RadioGroup, RadioGroupOption} from "@headlessui/vue";

import JetDialogModal from "@/Jetstream/DialogModal";
import {XIcon, CheckIcon, ChevronDownIcon} from '@heroicons/vue/outline';
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
        ChevronDownIcon
    },

    data() {
        return {
            isLinked: this.column.pivot.linked_money_source_id !== null,
            linkedType: this.column.pivot.linked_type === 'COST' ? linkTypes[1] : linkTypes[0],
            selectedMoneySource: this.isLinked ? this.moneySources.find(moneySource => moneySource.id === this.column.pivot.linked_money_source_id) : null,
            linkTypes,
        }
    },

    props: ['column', 'moneySources'],

    emits: ['closed'],

    watch: {},

    methods: {
        openModal() {
        },

        closeModal(bool) {
            this.$emit('closed', bool);
        },
        updateMoneySourceLink(){
            this.$inertia.patch(route('project.budget.cell-source.update'),{cell_id: this.column.pivot.id , linked_type: this.linkedType, money_source_id: this.selectedMoneySource.id});
        }
    },
}
</script>

<style scoped></style>
