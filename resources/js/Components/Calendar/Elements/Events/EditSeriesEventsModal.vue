<template>
    <BaseModal @closed="closeModal" modal-image="/Svgs/Overlays/illu_appointment_edit.svg">
        <div class="mx-4">
            <!--   Heading   -->
            <ModalHeader
                :title="$t('Edit all series events')"
                :description="$t('Would you like to move all events of this series to another room or by a certain period of time?')"
            />
            <div class="w-full">
                <div class="mb-2">
                    <Listbox as="div" class="sm:col-span-3" v-model="selectedRoom">
                        <div class="relative">
                            <ListboxButton class="menu-button">
                                <span v-if="selectedRoom === null">{{ $t('No room displacement')}}</span>
                                <div v-else> {{ selectedRoom?.name }}</div>
                                <div class="mr-3">
                                    <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                </div>
                            </ListboxButton>
                            <ListboxOptions class="absolute w-full bg-artwork-navigation-background shadow-lg max-h-32 overflow-y-scroll rounded-md focus:outline-none z-10">
                                <ListboxOption as="template" class="p-2 text-sm"
                                               :value="null"
                                               v-slot="{ active, selected }">
                                    <li :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'rounded-md cursor-pointer flex justify-between']">
                                        <div :class="[selected ? 'xsWhiteBold' : '', 'truncate']">
                                            {{ $t('No room displacement')}}
                                        </div>
                                        <div v-if="selected">
                                            <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                        </div>
                                    </li>
                                </ListboxOption>
                                <ListboxOption as="template" class="p-2 text-sm"
                                               v-for="room in rooms"
                                               :key="room.id"
                                               :value="room"
                                               v-slot="{ active, selected }">
                                    <li :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'rounded-md cursor-pointer flex justify-between']">
                                        <div :class="[selected ? 'xsWhiteBold' : '', 'truncate']">
                                            {{ room.name }}
                                        </div>
                                        <div v-if="selected">
                                            <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                        </div>
                                    </li>
                                </ListboxOption>
                            </ListboxOptions>
                        </div>
                    </Listbox>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-8 mb-2 gap-x-2">
                    <div class="mb-2 col-span-1">
                        <Listbox as="div" class="" v-model="selectedCalculationType">
                            <div class="relative">
                                <ListboxButton class="menu-button">
                                    <div> {{ selectedCalculationType.type }}</div>
                                    <div class="mr-3">
                                        <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                    </div>
                                </ListboxButton>
                                <ListboxOptions class="absolute w-full bg-artwork-navigation-background shadow-lg max-h-32 overflow-y-scroll rounded-md focus:outline-none z-10">
                                    <ListboxOption as="template" class="p-2 text-sm"
                                                   v-for="calculation in calculationTypes"
                                                   :key="calculation.id"
                                                   :value="calculation"
                                                   v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'rounded-md cursor-pointer flex justify-between']">
                                            <div :class="[selected ? 'xsWhiteBold' : '', 'truncate']">
                                                {{ calculation.type }}
                                            </div>
                                            <div v-if="selected">
                                                <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                            </div>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </div>
                        </Listbox>
                    </div>
                    <div class="mb-2 col-span-3">
                        <input type="number"
                               v-model="editValue"
                               id="seriesEditValue"
                               min="0" max="999"
                               class="input h-12"/>
                    </div>
                    <div class="mb-2 col-span-4">
                        <Listbox as="div" v-model="selectedTimeType">
                            <div class="relative">
                                <ListboxButton class="menu-button">
                                    <div> {{ selectedTimeType.value }}</div>
                                    <div class="mr-3">
                                        <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true"/>
                                    </div>
                                </ListboxButton>
                                <ListboxOptions class="absolute bg-artwork-navigation-background shadow-lg max-h-32 overflow-y-scroll rounded-md focus:outline-none z-10">
                                    <ListboxOption as="template" class="p-2 text-sm"
                                                   v-for="time in timeTypes"
                                                   :key="time.id"
                                                   :value="time"
                                                   v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'rounded-md cursor-pointer flex justify-between']">
                                            <div :class="[selected ? 'xsWhiteBold' : '', 'truncate']">
                                                {{ time.value }}
                                            </div>
                                            <div v-if="selected">
                                                <CheckIcon v-if="selected" class="h-5 w-5 text-success" aria-hidden="true"/>
                                            </div>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </div>
                        </Listbox>
                    </div>
                </div>
                <div class="w-full flex justify-center">
                    <FormButton :text="$t('Edit all series events')" @click="saveSeriesEdit"/>
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { ChevronDownIcon } from "@heroicons/vue/outline";
import { CheckIcon } from "@heroicons/vue/solid";
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
} from "@headlessui/vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import axios from "axios";

const { t } = useI18n();

const props = defineProps({
    event: { type: Object, required: true },
    rooms: { type: [Object, Array], required: true },
});

const emits = defineEmits(["close"]);

const calculationTypes = [
    { id: 1, type: "+" },
    { id: 2, type: "-" },
];

const timeTypes = [
    { id: 1, value: t("Hour(s)") },
    { id: 2, value: t("Day(s)") },
    { id: 3, value: t("Week(s)") },
    { id: 4, value: t("Month(s)") },
    { id: 5, value: t("Year(s)") },
];

const selectedCalculationType = ref({ id: 1, type: "+" });
const selectedTimeType = ref({ id: 1, value: t("Hour(s)") });
const selectedRoom = ref(null);
const editValue = ref(0);

const closeModal = () => {
    emits("close");
};

const saveSeriesEdit = () => {
    axios
        .patch(route("events.series.update", props.event.id), {
            newRoomId: selectedRoom.value?.id ?? null,
            calculationType: selectedCalculationType.value.id,
            value: editValue.value,
            type: selectedTimeType.value.id,
        })
        .then(() => {
            closeModal();
            location.reload();
        })
        .catch((error) => {
            console.error("Series edit save failed:", error);
        });
};
</script>
