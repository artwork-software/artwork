<template>
    <AppLayout :title="$t('Freelancer') + ' ' + freelancer.first_name + ' ' + freelancer.last_name + ' ' + $t('edit')">
        <div class="mx-auto max-w-7xl mt-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <!-- Avatar / Skeleton -->
                    <div class="relative">
                        <div v-if="showSidebar" class="size-16 rounded-full bg-zinc-200 animate-pulse" />
                        <img
                            v-else
                            :src="freelancer.profile_photo_url"
                            :alt="freelancer.first_name"
                            class="size-16 rounded-full object-cover ring-2 ring-white shadow-sm"
                        />
                    </div>
                    <div>
                        <div v-if="showSidebar" class="h-5 w-48 rounded bg-zinc-200 animate-pulse" />
                        <h1 v-else class="text-xl font-semibold text-zinc-900">
                            {{ freelancer.first_name }} {{ freelancer.last_name }}
                        </h1>
                    </div>
                </div>
            </div>

            <div class="w-full my-8">
                <div class="overflow-x-auto">
                    <nav class="flex gap-2">
                        <template v-for="tab in tabs" :key="tab.name">
                            <button
                                v-if="tab.has_permission"
                                @click="changeTab(tab.id)"
                                :aria-current="tab.current ? 'page' : undefined"
                                :class="[
                                  tab.current
                                    ? 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20'
                                    : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50',
                                  'inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium'
                                ]"
                            >
                                <PropertyIcon v-if="tab?.icon" :name="tab.icon" class="size-4" />
                                {{ $t(tab.name) }}
                            </button>
                        </template>
                    </nav>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-7xl my-8">
            <!-- Einsatzplan -->
            <div v-if="currentTab === 1" class="grid gap-8">
                <UserShiftPlan
                    type="freelancer"
                    :totalPlannedWorkingHours="totalPlannedWorkingHours"
                    :date-value="dateValue"
                    :whole-week-date-period="wholeWeekDatePeriod"
                    :events-with-total-planned-working-hours="eventsWithTotalPlannedWorkingHours"
                    :projects="projects"
                    :event-types="eventTypes"
                    :rooms="rooms"
                    :vacations="vacations"
                    :shift-qualifications="shiftQualifications"
                    :firstProjectShiftTabId="firstProjectShiftTabId"
                    :user-to-edit-whole-week-date-period-vacations="freelancer_to_edit_whole_week_date_period_vacations"
                    :user-to-edit-id="freelancer.id"
                />
                <Availability
                    type="freelancer"
                    :create-show-date="createShowDate"
                    :show-vacations-and-availabilities-date="showVacationsAndAvailabilitiesDate"
                    :vacation-select-calendar="vacationSelectCalendar"
                    :calendar-data="calendarData"
                    :date-to-show="dateToShow"
                    :user="freelancer"
                    :vacations="vacations"
                    :availabilities="availabilities"
                />
            </div>

            <!-- Persönliche Daten -->
            <div v-if="currentTab === 2" class="grid gap-6">
                <!-- Card: Basic info -->
                <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-8">
                        <div class="sm:col-span-1">
                            <input ref="photoInput" type="file" class="hidden" @change="updatePhotoPreview" />
                            <div class="mt-1">
                                <div v-if="photoPreview" @click="selectNewPhoto" class="cursor-pointer">
                  <span
                      class="block size-20 rounded-full bg-cover bg-center ring-2 ring-zinc-200"
                      :style="`background-image: url('${photoPreview}');`"
                  />
                                </div>
                                <img
                                    v-else
                                    :src="freelancer.profile_photo_url"
                                    :alt="freelancer.first_name"
                                    @click="selectNewPhoto"
                                    class="size-20 cursor-pointer rounded-full object-cover ring-2 ring-zinc-200"
                                />
                            </div>
                        </div>

                        <div class="sm:col-span-3">
                            <TextInputComponent
                                id="first_name"
                                v-model="freelancerData.first_name"
                                @focusout="saveFreelancer"
                                :label="$t('First name')"
                            />
                        </div>
                        <div class="sm:col-span-4">
                            <TextInputComponent
                                id="last_name"
                                v-model="freelancerData.last_name"
                                @focusout="saveFreelancer"
                                :label="$t('Last name')"
                            />
                        </div>
                    </div>

                    <div class="mt-4">
                        <VisualFeedback
                            v-show="showSuccessModal"
                            :text="$t('The changes have been saved successfully.')"
                        />
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <TextInputComponent
                            class="sm:col-span-1"
                            readonly
                            disabled
                            id="textFreelancer"
                            v-model="freelancerData.placeholder"
                            label=""
                        />

                        <TextInputComponent
                            class="sm:col-span-1"
                            id="position"
                            name="position"
                            v-model="freelancerData.position"
                            @focusout="saveFreelancer"
                            :disabled="checkCanEdit"
                            :readonly="checkCanEdit"
                            :label="$t('Position')"
                        />

                        <TextInputComponent
                            class="sm:col-span-1"
                            id="email"
                            type="email"
                            name="email"
                            v-model="freelancerData.email"
                            @focusout="saveFreelancer"
                            :disabled="checkCanEdit"
                            :readonly="checkCanEdit"
                            :label="$t('Email')"
                        />

                        <TextInputComponent
                            class="sm:col-span-1"
                            id="phone_number"
                            type="text"
                            name="phone_number"
                            v-model="freelancerData.phone_number"
                            @focusout="saveFreelancer"
                            :disabled="checkCanEdit"
                            :readonly="checkCanEdit"
                            :label="$t('Phone number')"
                        />

                        <TextInputComponent
                            class="sm:col-span-1"
                            id="street"
                            type="text"
                            name="street"
                            v-model="freelancerData.street"
                            @focusout="saveFreelancer"
                            :disabled="checkCanEdit"
                            :readonly="checkCanEdit"
                            :label="$t('Street')"
                        />

                        <div class="sm:col-span-1" />

                        <TextInputComponent
                            class="sm:col-span-1"
                            id="zip_code"
                            type="text"
                            name="zip_code"
                            v-model="freelancerData.zip_code"
                            @focusout="saveFreelancer"
                            :disabled="checkCanEdit"
                            :readonly="checkCanEdit"
                            :label="$t('Zip code')"
                        />

                        <TextInputComponent
                            class="sm:col-span-1"
                            id="location"
                            type="text"
                            name="location"
                            v-model="freelancerData.location"
                            @focusout="saveFreelancer"
                            :disabled="checkCanEdit"
                            :readonly="checkCanEdit"
                            :label="$t('Location')"
                        />

                        <TextareaComponent
                            class="sm:col-span-2"
                            id="note"
                            rows="4"
                            name="note"
                            v-model="freelancerData.note"
                            @focusout="saveFreelancer"
                            :disabled="checkCanEdit"
                            :readonly="checkCanEdit"
                            :label="$t('Note')"
                        />
                    </div>
                </div>
            </div>

            <!-- Conditions -->
            <div v-if="currentTab === 3">
                <UserTermsTab user_type="freelancer" :user_to_edit="freelancer" />
            </div>

            <!-- Work profile -->
            <div v-if="currentTab === 4">
                <WorkProfileTab user-type="freelancer" :user="freelancer" :shift-qualifications="shiftQualifications" />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, getCurrentInstance } from 'vue'
import {router, useForm} from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import UserTermsTab from '@/Pages/Users/Tabs/UserTermsTab.vue'
import Availability from '@/Pages/Users/Components/Availability.vue'
import UserShiftPlan from '@/Layouts/Components/ShiftPlanComponents/UserShiftPlan.vue'
import WorkProfileTab from '@/Pages/Components/WorkProfileTab.vue'
import TextInputComponent from '@/Components/Inputs/TextInputComponent.vue'
import TextareaComponent from '@/Components/Inputs/TextareaComponent.vue'
import VisualFeedback from '@/Components/Feedback/VisualFeedback.vue'

import { is, can } from 'laravel-permission-to-vuejs'
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

const props = defineProps({
    freelancer: Object,
    shifts: Array,
    calendarData: Array,
    dateToShow: Array,
    wholeWeekDatePeriod: Array,
    eventsWithTotalPlannedWorkingHours: Array,
    vacations: Array,
    dateValue: Array,
    daysWithEvents: Array,
    rooms: Array,
    eventTypes: Array,
    projects: Array,
    totalPlannedWorkingHours: [String, Number],
    vacationSelectCalendar: Array,
    createShowDate: Array,
    showVacationsAndAvailabilitiesDate: String,
    availabilities: Array,
    shiftQualifications: Array,
    freelancer_to_edit_whole_week_date_period_vacations: Array,
    firstProjectShiftTabId: [String, Number],
})

const { proxy } = getCurrentInstance()

/* UI state */
const showSuccessModal = ref(false)
const currentTab = ref(2)
const photoPreview = ref(null)
const photoInput = ref(null)
const showSidebar = ref(false)

/* Tabs */
const tabs = reactive([
    {
        id: 1,
        name: proxy.$t('Operational plan'),
        current: false,
        has_permission: can('can plan shifts') || is('artwork admin'),
    },
    {
        id: 2,
        name: proxy.$t('Personal data'),
        current: true,
        has_permission: true,
    },
    {
        id: 3,
        name: proxy.$t('Conditions'),
        current: false,
        has_permission: can('can edit external users conditions') || is('artwork admin'),
    },
    {
        id: 4,
        name: proxy.$t('Work profile'),
        current: false,
        has_permission: can('can manage workers') || is('artwork admin'),
    },
])

/* Form */
const freelancerData = useForm({
    placeholder: 'Freelancer (extern)',
    first_name: props.freelancer.first_name,
    last_name: props.freelancer.last_name,
    position: props.freelancer.position,
    email: props.freelancer.email,
    phone_number: props.freelancer.phone_number,
    street: props.freelancer.street,
    zip_code: props.freelancer.zip_code,
    location: props.freelancer.location,
    note: props.freelancer.note,
})

/* Computed */
const checkCanEdit = computed(() => !(can('can manage workers') || is('artwork admin')))

/* Lifecycle */
onMounted(() => {
    showSidebar.value = true
    setTimeout(() => (showSidebar.value = false), 800)
})

/* Methods */
const changeTab = (id) => {
    tabs.forEach((t) => (t.current = t.id === id))
    currentTab.value = id
}

const openSuccessModal = () => {
    showSuccessModal.value = true
    setTimeout(() => closeSuccessModal(), 2000)
}
const closeSuccessModal = () => (showSuccessModal.value = false)

const saveFreelancer = () => {
    if (freelancerData.isDirty) {
        freelancerData.patch(route('freelancer.update', props.freelancer.id), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => openSuccessModal(),
        })
    }
}

const selectNewPhoto = () => {
    if (can('can manage workers') || is('artwork admin')) {
        photoInput.value?.click()
    }
}

const updatePhotoPreview = () => {
    const file = photoInput.value?.files?.[0]
    if (!file) return

    const reader = new FileReader()
    reader.onload = (e) => {
        photoPreview.value = e.target?.result
    }
    reader.readAsDataURL(file)

    router.post(
        route('freelancer.change.profile-image', props.freelancer.id),
        { profileImage: file },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => openSuccessModal(),
        }
    )
}
</script>
