<template>
    <!-- Kein Konflikt -->
    <div v-if="!vacation.has_conflicts" class="bg-white rounded border border-zinc-200 text-sm group relative mb-2">
        <!-- Hover-Actions (Edit/Delete) -->
        <div
            class="hidden group-hover:flex absolute inset-0 items-center justify-center gap-2"
            v-if="canManage"
        >
            <button
                type="button"
                @click="openShowEditVacationModal"
                class="ui-button"
                aria-label="Edit"
            >
                <component :is="IconEdit" class="w-4 h-4 " />
            </button>
            <button
                type="button"
                @click="checkIfVacationIsSeries"
                class="ui-button"
                aria-label="Delete"
            >
                <component :is="IconTrash" class="w-4 h-4 text-red-500" />
            </button>
        </div>

        <!-- Content -->
        <div class="py-4 px-3 text-zinc-700">
            <div class="flex items-center">
                <div class="font-medium">{{ vacation.date_casted }}</div>
                <div v-if="!vacation.full_day" class="ml-1">
                    , {{ vacation.start_time }} - {{ vacation.end_time }}
                </div>
                <div v-if="vacation.is_series" class="flex items-center ml-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18.212" height="12.893" viewBox="0 0 18.212 12.893" class="text-zinc-600">
                        <g transform="translate(-4.5 -8.439)">
                            <path d="M17.429,10.217,15.835,8.632a.664.664,0,0,0-.645-.171.567.567,0,0,0-.166.071.662.662,0,0,0-.323.621.7.7,0,0,0,.2.432l.759.754H8.2a3.708,3.708,0,0,0-3.7,3.695v.759a.666.666,0,0,0,.664.664h0a.666.666,0,0,0,.664-.664v-.759A2.38,2.38,0,0,1,8.2,11.663h7.37l-.759.754a.673.673,0,0,0-.2.413.669.669,0,0,0,.664.73.656.656,0,0,0,.47-.194l1.688-1.679a1.026,1.026,0,0,0,.308-.735A1.057,1.057,0,0,0,17.429,10.217Z" fill="currentColor"/>
                            <path d="M24.449,17.156h0a.666.666,0,0,0-.664.664v.759a2.38,2.38,0,0,1-2.371,2.371h-7.37L14.8,20.2a.673.673,0,0,0,.2-.417.669.669,0,0,0-.664-.73.656.656,0,0,0-.47.194l-1.688,1.679a1.031,1.031,0,0,0,0,1.47l1.594,1.584a.664.664,0,0,0,.645.171.567.567,0,0,0,.166-.071.662.662,0,0,0,.323-.621.7.7,0,0,0-.2-.432l-.759-.754h7.465a3.7,3.7,0,0,0,3.7-3.7v-.759A.664.664,0,0,0,24.449,17.156Z" transform="translate(-2.401 -2.837)" fill="currentColor"/>
                        </g>
                    </svg>
                </div>
            </div>
            <p v-if="vacation.comment" class="mt-1 text-zinc-600">
                &bdquo;{{ vacation.comment === 'OFF_WORK' || vacation.comment === 'NOT_AVAILABLE' ? $t(vacation.comment) : vacation.comment }}&rdquo;
            </p>
        </div>
    </div>

    <!-- Mit Konflikt -->
    <div v-else>
        <div class="bg-amber-50 rounded border border-amber-200 text-sm group relative mb-2">
            <!-- Hover-Actions -->
            <div
                class="hidden group-hover:flex absolute inset-0 items-center justify-center gap-2"
                v-if="canManage"
            >
                <button
                    type="button"
                    @click="openShowEditVacationModal"
                    class="ui-button"
                    aria-label="Edit"
                >
                    <component :is="IconEdit" class="w-4 h-4 " />
                </button>
                <button
                    type="button"
                    @click="checkIfVacationIsSeries"
                    class="ui-button"
                    aria-label="Delete"
                >
                    <component :is="IconTrash" class="w-4 h-4 text-red-500" />
                </button>
            </div>

            <div class="py-4 px-3">
                <div class="flex items-center gap-1 font-semibold text-amber-800">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14">
                        <rect width="14" height="14" fill="#f59e0b" />
                        <path d="M8.571,3.015,13.6,13.037H3.542L8.571,3.015Zm0-1.087a.867.867,0,0,0-.713.523L2.735,12.66c-.392.7-.059,1.268.742,1.268H13.664c.8,0,1.134-.571.742-1.268h0L9.284,2.451A.867.867,0,0,0,8.571,1.928Zm.75,9.75a.75.75,0,1,1-.75-.75A.75.75,0,0,1,9.321,11.678Zm-.75-1.5a.75.75,0,0,1-.75-.75V7.178a.75.75,0,1,1,1.5,0v2.25A.75.75,0,0,1,8.571,10.178Z"
                              transform="translate(-1.571 -0.928)" fill="#fff" stroke="#fff" stroke-width="0.2"/>
                    </svg>
                    {{ $t('Conflict with your shift!') }}
                </div>

                <div class="mt-1 text-zinc-700">
                    <span class="font-medium">{{ vacation.date_casted }}</span>
                    <span v-if="!vacation.full_day">, {{ vacation.start_time }} - {{ vacation.end_time }}</span>
                    <span v-if="vacation.is_series" class="inline-flex items-center ml-2 text-zinc-700">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18.212" height="12.893" viewBox="0 0 18.212 12.893">
                      <g transform="translate(-4.5 -8.439)" fill="#52525b">
                        <path d="M17.429,10.217,15.835,8.632a.664.664,0,0,0-.645-.171.567.567,0,0,0-.166.071.662.662,0,0,0-.323.621.7.7,0,0,0,.2.432l.759.754H8.2a3.708,3.708,0,0,0-3.7,3.695v.759a.666.666,0,0,0,.664.664h0a.666.666,0,0,0,.664-.664v-.759A2.38,2.38,0,0,1,8.2,11.663h7.37l-.759.754a.673.673,0,0,0-.2.413.669.669,0,0,0,.664.73.656.656,0,0,0,.47-.194l1.688-1.679a1.026,1.026,0,0,0,.308-.735A1.057,1.057,0,0,0,17.429,10.217Z"/>
                        <path d="M24.449,17.156h0a.666.666,0,0,0-.664.664v.759a2.38,2.38,0,0,1-2.371,2.371h-7.37L14.8,20.2a.673.673,0,0,0,.2-.417.669.669,0,0,0-.664-.73.656.656,0,0,0-.47.194l-1.688,1.679a1.031,1.031,0,0,0,0,1.47l1.594,1.584a.664.664,0,0,0,.645.171.567.567,0,0,0,.166-.071.662.662,0,0,0,.323-.621.7.7,0,0,0-.2-.432l-.759-.754h7.465a3.7,3.7,0,0,0,3.7-3.7v-.759A.664.664,0,0,0,24.449,17.156Z" transform="translate(-2.401 -2.837)"/>
                      </g>
                    </svg>
                  </span>
                </div>

                <p v-if="vacation.comment" class="mt-1 text-zinc-600">
                    &bdquo;{{ vacation.comment === 'OFF_WORK' || vacation.comment === 'NOT_AVAILABLE' ? $t(vacation.comment) : vacation.comment }}&rdquo;
                </p>
            </div>
        </div>

        <!-- Konflikt-Details -->
        <div class="my-2.5 text-sm text-zinc-700">
            <p v-for="conflict in vacation.conflicts" :key="conflictKey(conflict)">
                {{ $t('{username} has scheduled you on {date} {start} - {end}, contrary to your original entry.', { username: conflict.user_name, date: conflict.date_casted, start: conflict.start_time, end: conflict.end_time }) }}
            </p>
        </div>
    </div>

    <!-- Edit Modal -->
    <AddEditVacationsModal
        v-if="showEditVacationModal"
        :createShowDate="createShowDate"
        :edit-vacation="vacation"
        :user="user"
        :vacationSelectCalendar="vacationSelectCalendar"
        @closed="showEditVacationModal = false"
    />

    <!-- Delete Modal -->
    <ConfirmDeleteModal
        v-if="showDeleteSeriesConfirmModal"
        :title="modalTexts.title"
        :button="modalTexts.button"
        :description="modalTexts.description"
        :is-series-delete="isSeries"
        :is_budget="false"
        @closed="showDeleteSeriesConfirmModal = false"
        @complete_delete="deleteCompleteSeries"
        @delete="deleteAvailabilityOrVacation"
    />
</template>

<script setup>
import { ref, computed, getCurrentInstance } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AddEditVacationsModal from '@/Pages/Users/Components/AddEditVacationsModal.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import {can, is} from "laravel-permission-to-vuejs";
import {IconEdit, IconTrash} from "@tabler/icons-vue";

// Props unverändert
const props = defineProps({
    vacation: { type: Object, required: true },
    user: { type: Object, required: true },
    type: { type: String, default: '' },
    createShowDate: { type: Array, default: [] },
    vacationSelectCalendar: { type: [Boolean, Array], default: false },
})

// State
const showEditVacationModal = ref(false)
const showDeleteSeriesConfirmModal = ref(false)
const modalTexts = ref({
    title: 'Urlaub Löschen?',
    description: 'Bist du sicher, dass du den ausgewählten Urlaub löschen möchtest?',
    button: 'Löschen',
})
const isSeries = ref(false)

// globale Helfer wie vorher (Permissions-Mixin-Ersatz)
const { proxy } = getCurrentInstance()
const page = usePage()
const canManage = computed(() =>
    can('can manage workers') ||
    is('artwork admin') ||
    props.user?.id === page.props?.auth?.user?.id ||
    can('can manage availability')
)

// Keys
const conflictKey = (c) => `${c?.date_casted}-${c?.start_time}-${c?.end_time}-${c?.user_name}`

// Methods 1:1 (nur Composition API)
const openShowEditVacationModal = () => {
    router.reload({
        data: { vacationMonth: props.vacation.date },
        onFinish: () => { showEditVacationModal.value = true },
    })
}

const deleteCompleteSeries = () => {
    if (props.vacation.type === 'available') {
        router.delete(route('delete.availability.series', props.vacation.series_id), {
            preserveScroll: true,
            onFinish: () => { showDeleteSeriesConfirmModal.value = false },
        })
    }
    if (props.vacation.type === 'vacation') {
        router.delete(route('delete.vacation.series', props.vacation.series_id), {
            preserveScroll: true,
            onFinish: () => { showDeleteSeriesConfirmModal.value = false },
        })
    }
}

const checkIfVacationIsSeries = () => {
    if (props.vacation.is_series) {
        modalTexts.value.title = 'Serieneintrag löschen'
        modalTexts.value.description = 'Möchtest Du nur diesen Eintrag löschen oder die ganze Serie?'
        modalTexts.value.button = 'Einzeleintrag löschen'
        isSeries.value = true
        showDeleteSeriesConfirmModal.value = true
    } else {
        isSeries.value = false
        showDeleteSeriesConfirmModal.value = true
    }
}

const deleteAvailabilityOrVacation = () => {
    if (props.vacation.type === 'available') {
        router.delete(route('delete.availability', props.vacation.id), {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => { /* Modal schließt sich über Parent-Reload */ },
        })
    }
    if (props.vacation.type === 'vacation') {
        router.delete(route('delete.vacation', props.vacation.id), {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => { /* Modal schließt sich über Parent-Reload */ },
        })
    }
}
</script>
