<template>
    <ArtworkBaseModal
        title="Mehrfacheintrag"
        description=""
        modal-size="sm:max-w-xl"
        @close="$emit('close', { saved: false })"
    >
        <div class="space-y-6 text-sm">
            <!-- Save-Hinweis -->
            <div
                v-show="showSaveSuccess"
                class="my-1.5 inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs text-emerald-800 border border-emerald-100"
            >
                <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                <span>{{ $t('Saved. The changes have been successfully applied.') }}</span>
            </div>

            <!-- Verfügbarkeit -->
            <section class="space-y-3 rounded-xl border border-zinc-100 bg-zinc-50/80 px-3.5 py-3">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <h3 class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">
                            {{ $t('Availability') }}
                        </h3>
                        <p class="text-[11px] text-zinc-400 mt-0.5">
                            {{ $t('Set a unified availability status for all selected cells.') }}
                        </p>
                    </div>
                </div>

                <Listbox as="div" v-model="multiEditCellForm.vacation_type" class="w-full relative mt-2">
                    <ListboxButton class="menu-button flex items-center justify-between">
                        <div class="flex items-center gap-2 truncate">
                            <span
                                class="inline-flex h-1.5 w-1.5 rounded-full"
                                :class="currentVacationDotClass"
                            ></span>
                            <span class="truncate">
                                {{ multiEditCellForm.vacation_type.name }}
                            </span>
                        </div>
                        <PropertyIcon name="ChevronDownIcon" class="h-5 w-5 text-primary" aria-hidden="true" />
                    </ListboxButton>
                    <ListboxOptions
                        class="absolute mt-1 w-full z-10 bg-artwork-navigation-background shadow-lg rounded-md max-h-40 pr-2 pt-2 pb-2 text-base ring-1 ring-black ring-opacity-5 overflow-y-scroll focus:outline-none sm:text-sm"
                    >
                        <ListboxOption
                            v-for="type in vacationTypes"
                            :key="type.type"
                            :value="type"
                            v-slot="{ selected }"
                            class="cursor-pointer rounded-md p-2 mb-0.5 flex justify-between items-center text-secondary"
                        >
                            <div class="flex items-center gap-2 truncate">
                                <span
                                    class="inline-flex h-1.5 w-1.5 rounded-full"
                                    :class="dotClassForVacationType(type)"
                                ></span>
                                <span :class="[selected ? 'xsWhiteBold' : 'xsLight', 'truncate']">
                                    {{ type.name }}
                                </span>
                            </div>
                            <PropertyIcon name="CheckIcon"
                                v-if="selected"
                                class="h-5 w-5 text-success"
                                aria-hidden="true"
                            />
                        </ListboxOption>
                    </ListboxOptions>
                </Listbox>

                <!-- Hinweis zu "Keine Änderung" -->
                <p class="text-[11px] text-zinc-400 mt-1">
                    {{ $t('If you do not select a status, the availability will not be changed.') }}
                </p>
            </section>

            <!-- Individuelle Zeiten -->
            <section class="space-y-3 rounded-xl border border-zinc-100 bg-white px-3.5 py-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">
                            {{ $t('Individual time') }}
                        </h4>
                        <p class="text-[11px] text-zinc-400 mt-0.5">
                            {{ $t('Optional: define custom times that will be applied to all selected cells.') }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="hidden sm:inline-flex items-center gap-1 rounded-full border border-zinc-200 bg-white px-2.5 py-1 text-[11px] text-zinc-600 hover:border-artwork-buttons-hover hover:text-artwork-buttons-hover transition-colors"
                        @click="addIndividualTime"
                    >
                        <PropertyIcon name="IconCirclePlus" class="h-3.5 w-3.5" stroke-width="2" />
                        <span>{{ $t('Add time') }}</span>
                    </button>
                </div>

                <div v-if="multiEditCellForm.individual_times.length" class="text-sm mt-2 xsLight">
                    <!-- Kopfzeile -->
                    <div class="hidden md:block text-[11px] text-zinc-500">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-1 px-1">
                            <span>{{ $t('Title') }}</span>
                            <span class="col-span-2">{{ $t('Period') }}</span>
                            <span class="text-right">{{ $t('Actions') }}</span>
                        </div>
                    </div>

                    <!-- Einträge -->
                    <div
                        v-for="(individual_time, index) in multiEditCellForm.individual_times"
                        :key="index"
                        class="mb-2 rounded-lg border border-zinc-100 bg-zinc-50 px-3 py-3 group"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 mb-1 items-center">
                            <BaseInput
                                id="title"
                                v-model="individual_time.title"
                                :label="$t('Title')"
                                :show-label="false"
                                no-margin-top
                            />
                            <div class="flex items-center col-span-2 gap-1">
                                <BaseInput
                                    type="time"
                                    id="start_time"
                                    v-model="individual_time.start_time"
                                    classes="rounded-r-none"
                                    :label="$t('Start time')"
                                    :show-label="false"
                                    no-margin-top
                                />
                                <BaseInput
                                    type="time"
                                    id="end_time"
                                    v-model="individual_time.end_time"
                                    classes="border-l-0 rounded-l-none"
                                    :label="$t('End time')"
                                    :show-label="false"
                                    no-margin-top
                                />
                            </div>
                            <div class="flex items-center justify-end">
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-md p-1.5 text-zinc-400 hover:text-red-500 hover:bg-red-50 transition-colors"
                                    @click="deleteIndividualTimeByIndex(index)"
                                >
                                    <PropertyIcon name="IconTrash" class="h-4 w-4" stroke-width="1.5" />
                                </button>
                            </div>
                        </div>
                        <div v-if="individual_time.error" class="text-xs text-red-500 mt-1">
                            {{ individual_time.error }}
                        </div>
                    </div>

                    <!-- Mobile "Zeit hinzufügen" -->
                    <div class="mt-1 sm:hidden">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 text-xs xsLight hover:text-artwork-buttons-hover transition-colors"
                            @click="addIndividualTime"
                        >
                            <PropertyIcon name="IconCirclePlus" class="h-5 w-5" stroke-width="2" />
                            <span>{{ $t('Add time') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Noch keine Zeiten -->
                <div
                    v-else
                    class="cursor-pointer mt-2"
                    @click="addIndividualTime"
                >
                    <div class="w-full px-3 py-4 bg-blue-400/8 hover:bg-blue-400/16 border border-dashed border-blue-200/70 rounded-lg transition-colors">
                        <AlertComponent
                            text="Es wurden noch keine Zeiten festgelegt. Klicke hier um Zeiten zu erstellen"
                            show-icon
                            icon-size="h-4 w-4"
                        />
                    </div>
                </div>
            </section>

            <!-- Kommentar -->
            <section class="space-y-3 rounded-xl border border-zinc-100 bg-white px-3.5 py-3">
                <div>
                    <h4 class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">
                        {{ $t('Comment') }}
                    </h4>
                    <p class="text-[11px] text-zinc-400 mt-0.5">
                        {{ $t('Optional note that will be saved for all affected cells.') }}
                    </p>
                </div>
                <BaseTextarea
                    id="shift_comment"
                    v-model="multiEditCellForm.comment"
                    :show-label="false"
                    no-margin-top
                    label="Comment"
                />
            </section>

            <!-- Footer -->
            <div class="flex justify-end pt-2 border-t border-zinc-100">
                <BaseUIButton
                    :label="$t('Save')"
                    is-add-button
                    :disabled="multiEditCellForm.processing"
                    @click="submitForm"
                />
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed } from 'vue';
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
} from '@headlessui/vue';
import { useForm } from '@inertiajs/vue3';

import AlertComponent from '@/Components/Alerts/AlertComponent.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

const props = defineProps({
    multiEditCellByDayAndUser: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close']);

const vacationTypes = ref([
    { name: 'Verfügbar', type: 'AVAILABLE' },
    { name: 'Arbeitsfreier Tag', type: 'OFF_WORK' },
    { name: 'Nicht Verfügbar', type: 'NOT_AVAILABLE' },
]);

const showSaveSuccess = ref(false);

const multiEditCellForm = useForm({
    comment: '',
    vacation_type: {
        name: 'Keine Änderung',
        type: null,
    },
    entities: props.multiEditCellByDayAndUser,
    individual_times: [],
});

// Farbpunkt für aktuellen Vacation-Typ
const currentVacationDotClass = computed(() => {
    const type = multiEditCellForm.vacation_type?.type;
    if (type === 'AVAILABLE') return 'bg-emerald-500';
    if (type === 'OFF_WORK') return 'bg-amber-500';
    if (type === 'NOT_AVAILABLE') return 'bg-rose-500';
    return 'bg-zinc-300';
});

function dotClassForVacationType(type) {
    if (type.type === 'AVAILABLE') return 'bg-emerald-500';
    if (type.type === 'OFF_WORK') return 'bg-amber-500';
    if (type.type === 'NOT_AVAILABLE') return 'bg-rose-500';
    return 'bg-zinc-300';
}

const addIndividualTime = () => {
    multiEditCellForm.individual_times.push({
        title: '',
        start_time: '',
        end_time: '',
    });
};

const deleteIndividualTimeByIndex = (index) => {
    multiEditCellForm.individual_times.splice(index, 1);
};

const submitForm = () => {
    multiEditCellForm.post(route('shift.plan.user.cell.update'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            showSaveSuccess.value = true;
            emit('close', { saved: true });
        },
    });
};
</script>

<style scoped>
/* Optional: Zusätzliche Feintuning-Styles */
</style>
