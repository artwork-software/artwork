<template>
    <BaseModal @closed="$emit('close')">

        <ModalHeader
            title="Mehrfacheintrag"

        />

        <div>
            <h3 class="xsDark">Verfügbarkeit</h3>
            <div class="flex items-center mb-5">
                <Listbox as="div" v-model="multiEditCellForm.vacation_type" class="w-full relative mt-2">
                    <ListboxButton class="menu-button">
                        <div>
                            <div>
                                {{ multiEditCellForm.vacation_type.name }}
                            </div>
                        </div>
                        <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
                    </ListboxButton>
                    <ListboxOptions class="absolute w-full z-10 bg-artwork-navigation-background shadow-lg rounded-md max-h-40 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                        <ListboxOption v-for="type in vacationTypes"
                                       class="text-secondary cursor-pointer p-2 flex justify-between "
                                       :key="type.type"
                                       :value="type"
                                       v-slot="{ active, selected }">
                            <div :class="[selected ? 'xsWhiteBold' : 'xsLight', 'flex']">
                                {{ type.name }}
                            </div>
                            <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                        </ListboxOption>
                    </ListboxOptions>
                </Listbox>
            </div>
        </div>

        <div>
            <div>
                <div>
                    <h4 class="font-semibold">Individuelle Zeit</h4>
                </div>
                <div v-if="multiEditCellForm.individual_times.length > 0">
                    <div class="text-sm mt-3 xsLight mb-3">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                            <div>
                                {{ $t('Title') }}
                            </div>
                            <div class="col-span-2">
                                {{ $t('Period') }}
                            </div>
                        </div>
                    </div>
                    <div v-for="(individual_time, index) in multiEditCellForm.individual_times" class="mb-2">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-3 group">
                            <TextInputComponent id="title" v-model="individual_time.title" label="Title" :show-label="false" no-margin-top />
                            <div class="flex items-center justify-center col-span-2">
                                <TimeInputComponent id="start_time" classes="rounded-r-none" v-model="individual_time.start_time" label="Startzeit" :show-label="false" no-margin-top />
                                <TimeInputComponent id="end_time" v-model="individual_time.end_time" classes="border-l-0 rounded-l-none" label="Endzeit" :show-label="false" no-margin-top />
                            </div>
                            <div class="invisible group-hover:visible flex items-center justify-center">
                                <component is="IconTrash" class="h-6 w-6 hover:text-red-500 transition-colors duration-300 ease-in-out cursor-pointer" stroke-width="1.5" @click="deleteIndividualTimeByIndex(index)" />
                            </div>
                        </div>
                        <div v-if="individual_time.error" class="text-xs text-red-500 -mt-2">
                            {{ individual_time.error }}
                        </div>
                    </div>
                    <div class="mt-5">
                        <component
                            is="IconCirclePlus"
                            class="h-6 w-6 xsLight cursor-pointer hover:text-artwork-buttons-hover transition-all duration-300 ease-in-out"
                            stroke-width="2"
                            @click="addIndividualTime"
                        />
                    </div>
                </div>
                <div v-else class="cursor-pointer" @click="addIndividualTime">
                    <div class="w-full px-3 py-4 bg-blue-400/30 rounded-lg mt-3">
                        <AlertComponent text="Es wurden noch keine Zeiten festgelegt. Klicke hier um Zeiten zu erstellen" show-icon icon-size="h-4 w-4" />
                    </div>
                </div>
            </div>
        </div>

        <div class="my-2">
            <div class="mb-2">
                <h4 class="font-semibold">{{ $t('Comment')}}</h4>
            </div>
            <div>
                <TextInputComponent id="shift_comment" v-model="multiEditCellForm.comment" label="" :show-label="false" no-margin-top />
            </div>
        </div>

        <div class="flex justify-center mt-5">
            <FormButton
                :text="$t('Save')"
                @click="submitForm"
            />
        </div>

    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import {reactive, ref} from "vue";
import {CheckIcon} from "@heroicons/vue/solid";
import {ChevronDownIcon} from "@heroicons/vue/outline";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import TimeInputComponent from "@/Components/Inputs/TimeInputComponent.vue";
import {useForm} from "@inertiajs/vue3";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

const props = defineProps({
    multiEditCellByDayAndUser: {
        type: Object,
        required: true,
    },
})

const vacationTypes = ref([
    { name: 'Verfügbar', type: 'AVAILABLE'},
    { name: 'Arbeitsfreier Tag', type: 'OFF_WORK'},
    { name: 'Nicht Verfügbar', type: 'NOT_AVAILABLE'},
])

const getIndividualTimesByDate = reactive([])

const selectedVacationType = ref(
    {
        name: 'Keine Änderung',
        type: null
    }
)

const multiEditCellForm = useForm({
    comment: '',
    vacation_type: ref({
        name: 'Keine Änderung',
        type: null
    }),
    entities: props.multiEditCellByDayAndUser,
    individual_times: reactive([]),
})

const emit = defineEmits(['close'])


const addIndividualTime = () => {
    multiEditCellForm.individual_times.push({
        title: '',
        start_time: '',
        end_time: '',
    })
}

const deleteIndividualTimeByIndex = (index) => {
    multiEditCellForm.individual_times.splice(index, 1)
}

const submitForm = () => {
    multiEditCellForm.post(route('shift.plan.user.cell.update'))
}
</script>

<style scoped>

</style>