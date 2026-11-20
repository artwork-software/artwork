<template>
    <AppLayout :title="$t('Freelancer') + ' ' + freelancer.first_name + ' ' + freelancer.last_name + ' ' + $t('edit')">
        <ExternalWorkerPage
            :name="freelancer.first_name + ' ' + freelancer.last_name"
            :photo-url="freelancer.profile_photo_url"
            :tabs="tabs"
            default-tab="data"
            :can-manage="!checkCanEdit"
            :delete-href="route('freelancer.destroy', { freelancer: freelancer.id })"
            delete-title="Delete freelancer"
        >
            <!-- Einsatzplan -->
            <template #plan>
            <div class="grid gap-8">
                <UserShiftPlan
                    type="freelancer"
                    :totalPlannedWorkingHours="totalPlannedWorkingHours"
                    :crafts="crafts"
                    :days-with-data="daysWithData"
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
            </template>

            <!-- Persönliche Daten -->
            <template #data>
            <div class="grid gap-6">
                <!-- Card: Basic info -->
                <div class="rounded-2xl border border-border-subtle bg-white p-5 shadow-sm">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-8">
                        <div class="sm:col-span-1">
                            <input ref="photoInput" type="file" class="hidden" @change="updatePhotoPreview" />
                            <div class="mt-1">
                                <div v-if="photoPreview" @click="selectNewPhoto" class="cursor-pointer">
                  <span
                      class="block size-20 rounded-full bg-cover bg-center ring-2 ring-border-subtle"
                      :style="`background-image: url('${photoPreview}');`"
                  />
                                </div>
                                <img
                                    v-else
                                    :src="freelancer.profile_photo_url"
                                    :alt="freelancer.first_name"
                                    @click="selectNewPhoto"
                                    :title="checkCanEdit ? '' : $t('Change profile picture')"
                                    :class="checkCanEdit ? '' : 'cursor-pointer'"
                                    class="size-20 rounded-full object-cover ring-2 ring-border-subtle"
                                />
                            </div>
                        </div>

                        <div class="sm:col-span-3">
                            <BaseInput
                                id="first_name"
                                v-model="freelancerData.first_name"
                                @focusout="saveFreelancer"
                                :disabled="checkCanEdit"
                                :readonly="checkCanEdit"
                                :label="$t('First name')"
                            :maxlength="255"
                            :error="freelancerData.errors.first_name"
                        />
                        </div>
                        <div class="sm:col-span-4">
                            <BaseInput
                                id="last_name"
                                v-model="freelancerData.last_name"
                                @focusout="saveFreelancer"
                                :disabled="checkCanEdit"
                                :readonly="checkCanEdit"
                                :label="$t('Last name')"
                            :maxlength="255"
                            :error="freelancerData.errors.last_name"
                        />
                        </div>
                    </div>

                    <div class="mt-4">
                        <VisualFeedback :show-save-success="saved" text="The changes have been saved successfully." />
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <BaseInput
                            class="sm:col-span-1"
                            id="business"
                            name="business"
                            v-model="freelancerData.business"
                            @focusout="saveFreelancer"
                            :disabled="checkCanEdit"
                            :readonly="checkCanEdit"
                            :label="$t('Company')"
                            :maxlength="255"
                            :error="freelancerData.errors.business"
                        />

                        <BaseInput
                            class="sm:col-span-1"
                            id="position"
                            name="position"
                            v-model="freelancerData.position"
                            @focusout="saveFreelancer"
                            :disabled="checkCanEdit"
                            :readonly="checkCanEdit"
                            :label="$t('Position')"
                            :maxlength="255"
                            :error="freelancerData.errors.position"
                        />

                        <BaseInput
                            class="sm:col-span-1"
                            id="email"
                            type="email"
                            name="email"
                            v-model="freelancerData.email"
                            @focusout="saveFreelancer"
                            :disabled="checkCanEdit"
                            :readonly="checkCanEdit"
                            :label="$t('Email')"
                            :maxlength="255"
                            :error="freelancerData.errors.email"
                        />

                        <BaseInput
                            class="sm:col-span-1"
                            id="phone_number"
                            name="phone_number"
                            v-model="freelancerData.phone_number"
                            @focusout="saveFreelancer"
                            :disabled="checkCanEdit"
                            :readonly="checkCanEdit"
                            :label="$t('Phone number')"
                            type="tel"
                            :maxlength="255"
                            :error="freelancerData.errors.phone_number"
                        />

                        <BaseInput
                            class="sm:col-span-1"
                            id="street"
                            name="street"
                            v-model="freelancerData.street"
                            @focusout="saveFreelancer"
                            :disabled="checkCanEdit"
                            :readonly="checkCanEdit"
                            :label="$t('Street')"
                            :maxlength="255"
                            :error="freelancerData.errors.street"
                        />

                        <div class="sm:col-span-1" />

                        <BaseInput
                            class="sm:col-span-1"
                            id="zip_code"
                            name="zip_code"
                            v-model="freelancerData.zip_code"
                            @focusout="saveFreelancer"
                            :disabled="checkCanEdit"
                            :readonly="checkCanEdit"
                            :label="$t('Zip code')"
                            :maxlength="255"
                            :error="freelancerData.errors.zip_code"
                        />

                        <BaseInput
                            class="sm:col-span-1"
                            id="location"
                            name="location"
                            v-model="freelancerData.location"
                            @focusout="saveFreelancer"
                            :disabled="checkCanEdit"
                            :readonly="checkCanEdit"
                            :label="$t('Location')"
                            :maxlength="255"
                            :error="freelancerData.errors.location"
                        />

                        <div class="sm:col-span-2">
                            <BaseTextarea
                            id="note"
                            rows="4"
                            name="note"
                            v-model="freelancerData.note"
                            @focusout="saveFreelancer"
                            :disabled="checkCanEdit"
                            :readonly="checkCanEdit"
                            :label="$t('Note')"
                            :maxlength="500"
                            show-counter
                            :error="freelancerData.errors.note"
                        />
                        </div>
                    </div>
                </div>
            </div>
            </template>

            <!-- Conditions -->
            <template #terms>
            <div>
                <UserTermsTab user_type="freelancer" :user_to_edit="freelancer" />
            </div>
            </template>

            <!-- Work profile -->
            <template #profile>
            <div>
                <WorkProfileTab user-type="freelancer" :user="freelancer" :shift-qualifications="shiftQualifications" />
            </div>
            </template>
        </ExternalWorkerPage>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import {router, useForm} from '@inertiajs/vue3'
import ExternalWorkerPage from '@/Pages/Components/ExternalWorkerPage.vue'
import { useAutosaveForm } from '@/Composeables/useAutosaveForm.js'
import AppLayout from '@/Layouts/AppLayout.vue'
import UserTermsTab from '@/Pages/Users/Tabs/UserTermsTab.vue'
import Availability from '@/Pages/Users/Components/Availability.vue'
import UserShiftPlan from '@/Layouts/Components/ShiftPlanComponents/UserShiftPlan.vue'
import WorkProfileTab from '@/Pages/Components/WorkProfileTab.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import VisualFeedback from '@/Components/Feedback/VisualFeedback.vue'

import { is, can } from 'laravel-permission-to-vuejs'

const props = defineProps({
    freelancer: Object,
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
    daysWithData: Object,
    crafts: Array,
})

/* UI state */
const photoPreview = ref(null)
const photoInput = ref(null)

const canManage = can('can manage workers') || can('can manage external workers') || is('artwork admin')

/* Tabs */
const tabs = [
    { key: 'plan', name: 'Operational plan', visible: can('can plan shifts') || is('artwork admin') },
    { key: 'data', name: 'Personal data', visible: true },
    { key: 'terms', name: 'Conditions', visible: canManage },
    { key: 'profile', name: 'Work profile', visible: canManage },
]

/* Form */
const freelancerData = useForm({
    first_name: props.freelancer.first_name,
    last_name: props.freelancer.last_name,
    position: props.freelancer.position,
    business: props.freelancer.business,
    email: props.freelancer.email,
    phone_number: props.freelancer.phone_number,
    street: props.freelancer.street,
    zip_code: props.freelancer.zip_code,
    location: props.freelancer.location,
    note: props.freelancer.note,
})

const { save: saveFreelancer, saved, flashSaved } = useAutosaveForm(
    freelancerData,
    () => route('freelancer.update', props.freelancer.id),
)

const checkCanEdit = computed(() => !canManage)

const selectNewPhoto = () => {
    if (canManage) {
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
            onSuccess: () => flashSaved(),
        }
    )
}
</script>
