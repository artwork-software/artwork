<template>
    <BaseModal @closed="$emit('close')">
        <ModalHeader title="Mehrfacheintrag"  />

        <div v-show="showSaveSuccess" class="my-3 text-xs bg-green-600 px-3 py-1.5 text-white rounded-lg">
            {{ $t('Saved. The changes have been successfully applied.') }}
        </div>

        <section>
            <h3 class="xsDark">Verfügbarkeit</h3>
            <Listbox as="div" v-model="multiEditCellForm.vacation_type" class="w-full relative mt-2">
                <ListboxButton class="menu-button">
                    <div>{{ multiEditCellForm.vacation_type.name }}</div>
                    <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true" />
                </ListboxButton>
                <ListboxOptions class="absolute w-full z-10 bg-artwork-navigation-background shadow-lg rounded-md max-h-40 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm">
                    <ListboxOption
                        v-for="type in vacationTypes"
                        :key="type.type"
                        :value="type"
                        class="text-secondary cursor-pointer p-2 flex justify-between"
                        v-slot="{ selected }"
                    >
                        <div :class="selected ? 'xsWhiteBold' : 'xsLight'">{{ type.name }}</div>
                        <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true" />
                    </ListboxOption>
                </ListboxOptions>
            </Listbox>
        </section>

        <section>
            <h4 class="font-semibold">Individuelle Zeit</h4>
            <div v-if="multiEditCellForm.individual_times.length" class="text-sm mt-3 xsLight mb-3">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    <span>{{ $t('Title') }}</span>
                    <span class="col-span-2">{{ $t('Period') }}</span>
                </div>
                <div v-for="(individual_time, index) in multiEditCellForm.individual_times" :key="index" class="mb-2">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-3 group">
                        <TextInputComponent id="title" v-model="individual_time.title" label="Title" :show-label="false" no-margin-top />
                        <div class="flex items-center col-span-2">
                            <TimeInputComponent id="start_time" v-model="individual_time.start_time" classes="rounded-r-none" label="Startzeit" :show-label="false" no-margin-top />
                            <TimeInputComponent id="end_time" v-model="individual_time.end_time" classes="border-l-0 rounded-l-none" label="Endzeit" :show-label="false" no-margin-top />
                        </div>
                        <component is="IconTrash" class="h-6 w-6 hover:text-red-500 transition-colors duration-300 ease-in-out cursor-pointer" stroke-width="1.5" @click="deleteIndividualTimeByIndex(index)" />
                    </div>
                    <div v-if="individual_time.error" class="text-xs text-red-500 -mt-2">{{ individual_time.error }}</div>
                </div>
                <component is="IconCirclePlus" class="h-6 w-6 xsLight cursor-pointer hover:text-artwork-buttons-hover transition-all duration-300 ease-in-out" stroke-width="2" @click="addIndividualTime" />
            </div>
            <div v-else class="cursor-pointer mt-3" @click="addIndividualTime">
                <div class="w-full px-3 py-4 bg-blue-400/30 rounded-lg">
                    <AlertComponent text="Es wurden noch keine Zeiten festgelegt. Klicke hier um Zeiten zu erstellen" show-icon icon-size="h-4 w-4" />
                </div>
            </div>
        </section>

        <section class="my-2">
            <h4 class="font-semibold">{{ $t('Comment') }}</h4>
            <TextInputComponent id="shift_comment" v-model="multiEditCellForm.comment" :show-label="false" no-margin-top  label=""/>
        </section>

        <div class="flex justify-center mt-5">
            <FormButton :text="$t('Save')" @click="submitForm" :disabled="multiEditCellForm.processing" />
        </div>
    </BaseModal>
</template>

<script setup>
import { reactive, ref } from "vue";
import { Listbox, ListboxButton, ListboxOption, ListboxOptions } from "@headlessui/vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import TimeInputComponent from "@/Components/Inputs/TimeInputComponent.vue";
import {router, useForm} from "@inertiajs/vue3";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import { CheckIcon, ChevronDownIcon } from "@heroicons/vue/solid";

const props = defineProps({
    multiEditCellByDayAndUser: {
        type: Object,
        required: true,
    },
})

const emit = defineEmits(['close']);

const vacationTypes = ref([
    { name: 'Verfügbar', type: 'AVAILABLE' },
    { name: 'Arbeitsfreier Tag', type: 'OFF_WORK' },
    { name: 'Nicht Verfügbar', type: 'NOT_AVAILABLE' },
])

const showSaveSuccess = ref(false);

const multiEditCellForm = useForm({
    comment: '',
    vacation_type: ref({
        name: 'Keine Änderung',
        type: null,
    }),
    entities: props.multiEditCellByDayAndUser,
    individual_times: reactive([]),
})

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
    multiEditCellForm.post(route('shift.plan.user.cell.update'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            showSaveSuccess.value = true;
            setTimeout(() => {
                showSaveSuccess.value = false;
                emit('close', true);
            }, 2500)

        }
    })
}
</script>

<style scoped>
/* Optionale Styles hier hinzufügen */
</style>
