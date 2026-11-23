<template>
    <AppLayout :title="serviceProvider.provider_name + ' ' + $t('edit')">
        <ExternalWorkerPage
            :name="serviceProvider.provider_name ?? ''"
            :photo-url="serviceProvider.profile_photo_url"
            :tabs="tabs"
            default-tab="data"
            :can-manage="!checkCanEdit"
            :delete-href="route('service_provider.destroy', { serviceProvider: serviceProvider.id })"
            delete-title="Delete service provider"
        >
                <template #plan>
                <div>
                    <UserShiftPlan type="service_provider"
                                   :total-planned-working-hours="totalPlannedWorkingHours"
                                   :crafts="crafts"
                                   :days-with-data="daysWithData"
                                   :date-value="dateValue"
                                   :whole-week-date-period="wholeWeekDatePeriod"
                                   :events-with-total-planned-working-hours="eventsWithTotalPlannedWorkingHours"
                                   :projects="projects"
                                   :event-types="eventTypes"
                                   :rooms="rooms"
                                   :shift-qualifications="shiftQualifications"
                                   :firstProjectShiftTabId="firstProjectShiftTabId"
                                   :user-to-edit-id="serviceProvider.id"/>
                </div>
                </template>
                <template #terms>
                <div>
                    <UserTermsTab user_type="service_provider" :user_to_edit="serviceProvider"></UserTermsTab>
                </div>
                </template>
                <!-- Persönliche Daten -->
                <template #data>
                <div>
                    <div class="rounded-2xl border border-border-subtle bg-white p-5 shadow-sm">
                    <!-- Profilbild, Name, Nachname -->
                    <div class="grid grid-cols-1 sm:grid-cols-8 gap-4">
                        <div class="col-span-1">
                            <input
                                ref="photoInput"
                                type="file"
                                class="hidden"
                                @change="updatePhotoPreview"
                            >

                            <!-- Current Profile Photo -->
                            <div v-show="! photoPreview" class="mt-2">
                                <img :src="serviceProvider.profile_photo_url" :alt="serviceProvider.provider_name" @click="selectNewPhoto" :title="checkCanEdit ? '' : $t('Change profile picture')" class="rounded-full h-20 w-20 object-cover" :class="checkCanEdit ? '' : 'cursor-pointer'">
                            </div>

                            <!-- New Profile Photo Preview -->
                            <div v-show="photoPreview" class="mt-2" @click="selectNewPhoto">
                                <span
                                    class="block rounded-full w-20 h-20 bg-cover bg-no-repeat bg-center"
                                    :style="'background-image: url(\'' + photoPreview + '\');'"
                                />
                            </div>

                        </div>
                        <div class="col-span-7">
                            <div>
                                <BaseInput v-model="providerData.provider_name" @focusout="saveProvider" :disabled="checkCanEdit" :readonly="checkCanEdit" name="provider_name" id="provider_name" :maxlength="255" :error="providerData.errors.provider_name" :class="checkCanEdit ? 'bg-border-subtle' : ''" :label="$t('Company name')" />
                            </div>
                        </div>
                    </div>

                    <div class="max-w-lg">
                        <VisualFeedback :show-save-success="saved" text="The changes have been saved successfully." />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-5">
                        <div class="col-span-1">
                            <BaseInput type="text" :maxlength="255" :error="providerData.errors.street" v-model="providerData.street" @focusout="saveProvider" :disabled="checkCanEdit" :readonly="checkCanEdit" name="street" id="street" :class="checkCanEdit ? 'bg-border-subtle' : ''" :label="$t('Street')" />
                        </div>
                        <div class="col-span-1">
                            <BaseInput type="text" :maxlength="255" :error="providerData.errors.zip_code" v-model="providerData.zip_code" @focusout="saveProvider" :disabled="checkCanEdit" :readonly="checkCanEdit" name="zip_code" id="zip_code" :class="checkCanEdit ? 'bg-border-subtle' : ''" :label="$t('Zip code')" />
                        </div>
                        <div class="col-span-1">
                            <BaseInput type="text" :maxlength="255" :error="providerData.errors.location" v-model="providerData.location" @focusout="saveProvider" :disabled="checkCanEdit" :readonly="checkCanEdit" name="location" id="location" :class="checkCanEdit ? 'bg-border-subtle' : ''" :label="$t('Location')" />
                        </div>
                        <div class="col-span-1">
                            <BaseInput type="email" :maxlength="255" :error="providerData.errors.email" v-model="providerData.email" @focusout="saveProvider" :disabled="checkCanEdit" :readonly="checkCanEdit" name="email" id="email" :class="checkCanEdit ? 'bg-border-subtle' : ''" :label="$t('Email')" />
                        </div>
                        <div class="col-span-1">
                            <BaseInput type="tel" :maxlength="255" :error="providerData.errors.phone_number" v-model="providerData.phone_number" @focusout="saveProvider" :disabled="checkCanEdit" :readonly="checkCanEdit" name="phone_number" id="phone_number" :class="checkCanEdit ? 'bg-border-subtle' : ''" :label="$t('Phone number')" />
                        </div>
                        <div class="col-span-full">
                            <BaseTextarea rows="4" v-model="providerData.note" @focusout="saveProvider" :disabled="checkCanEdit" :readonly="checkCanEdit" name="note" id="note" :label="$t('Note')" :maxlength="500" show-counter :error="providerData.errors.note" :class="checkCanEdit ? 'bg-border-subtle' : ''" />
                        </div>
                    </div>
                    </div>

                    <div class="mt-10">
                        <div>
                            <div class="flex items-center justify-between mb-5">
                                <PageTitle :title="$t('Contacts')" :description="$t('You can view and edit all Service Provider contacts here')" />
                                <BaseUIButton v-if="!checkCanEdit" size="sm" variant="primary" type="button" hide-icon @click="showCreateOrUpdateContactModal = true">
                                    {{ $t('Add Contact') }}
                                </BaseUIButton>
                            </div>


                            <div v-if="serviceProvider.contacts.length > 0">
                                <ul role="list" class="grid grid-cols-1 gap-x-6 gap-y-8 lg:grid-cols-3 xl:gap-x-8">
                                    <li v-for="contact in serviceProvider.contacts" :key="contact.id" class="overflow-hidden rounded-xl border border-border-subtle shadow-overlay">
                                        <ArtworkSingleContact :contact="contact" />
                                    </li>
                                </ul>
                            </div>

                            <div v-else>
                                <BaseAlertComponent message="No contacts found for this Service Provider." use-translation type="info" class="mt-4" />
                            </div>

                        </div>
                    </div>
                </div>
                </template>
                <template #profile>
                <div>
                    <WorkProfileTab user-type="serviceProvider"
                                    :user="serviceProvider"
                                    :shift-qualifications="shiftQualifications"
                    />
                </div>
                </template>
        </ExternalWorkerPage>

        <GeneralCreateOrUpdateContactModal
            model="provider"
            :model-id="serviceProvider.id"
            title="Add Service Provider contact"
            description="Add a new contact to the Service Provider"
            v-if="showCreateOrUpdateContactModal"
            @close="showCreateOrUpdateContactModal = false"
        />
    </AppLayout>
</template>


<script setup>
import { computed, ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { can, is } from 'laravel-permission-to-vuejs'
import AppLayout from '@/Layouts/AppLayout.vue'
import ExternalWorkerPage from '@/Pages/Components/ExternalWorkerPage.vue'
import { useAutosaveForm } from '@/Composeables/useAutosaveForm.js'
import UserTermsTab from '@/Pages/Users/Tabs/UserTermsTab.vue'
import UserShiftPlan from '@/Layouts/Components/ShiftPlanComponents/UserShiftPlan.vue'
import WorkProfileTab from '@/Pages/Components/WorkProfileTab.vue'
import VisualFeedback from '@/Components/Feedback/VisualFeedback.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseAlertComponent from '@/Components/Alerts/BaseAlertComponent.vue'
import PageTitle from '@/Artwork/Titles/PageTitle.vue'
import ArtworkSingleContact from '@/Artwork/Contact/ArtworkSingleContact.vue'
import GeneralCreateOrUpdateContactModal from '@/Components/Modals/GeneralCreateOrUpdateContactModal.vue'

const props = defineProps({
    serviceProvider: Object,
    dateValue: Array,
    wholeWeekDatePeriod: Array,
    eventsWithTotalPlannedWorkingHours: Array,
    rooms: Array,
    eventTypes: Array,
    projects: Array,
    totalPlannedWorkingHours: [String, Number],
    shiftQualifications: Array,
    firstProjectShiftTabId: [String, Number],
    daysWithData: Object,
    crafts: Array,
})

const canManage = can('can manage workers') || can('can manage external workers') || is('artwork admin')
const checkCanEdit = computed(() => !canManage)

const tabs = [
    { key: 'plan', name: 'Operational plan', visible: can('can plan shifts') || is('artwork admin') },
    { key: 'terms', name: 'Conditions', visible: canManage },
    { key: 'data', name: 'Company data', visible: true },
    { key: 'profile', name: 'Work profile', visible: canManage },
]

const providerData = useForm({
    input: 'Dienstleister (extern)',
    provider_name: props.serviceProvider.provider_name,
    email: props.serviceProvider.email,
    phone_number: props.serviceProvider.phone_number,
    street: props.serviceProvider.street,
    zip_code: props.serviceProvider.zip_code,
    location: props.serviceProvider.location,
    note: props.serviceProvider.note,
    type_of_provider: props.serviceProvider.type_of_provider,
})

const { save: saveProvider, saved, flashSaved } = useAutosaveForm(
    providerData,
    () => route('service_provider.update', props.serviceProvider.id),
)

const photoInput = ref(null)
const photoPreview = ref(null)
const showCreateOrUpdateContactModal = ref(false)

const selectNewPhoto = () => {
    if (canManage) {
        photoInput.value?.click()
    }
}

const updatePhotoPreview = () => {
    const photo = photoInput.value?.files?.[0]
    if (!photo) return

    const reader = new FileReader()
    reader.onload = (e) => {
        photoPreview.value = e.target.result
    }
    reader.readAsDataURL(photo)

    router.post(route('service_provider.change.profile-image', props.serviceProvider.id), {
        profileImage: photo,
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => flashSaved(),
    })
}
</script>
