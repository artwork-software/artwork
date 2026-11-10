<template>
    <ArtworkBaseModal
        :title="isEditing ? 'Edit event type' : 'New event type'"
        modal-size="sm:max-w-2xl"
        @close="closeModal"
    >
        <form id="eventTypeForm" class="space-y-5" @submit.prevent="addOrUpdateEventType">
            <div class="flex items-end gap-x-4">
                <div class="shrink-0">
                    <span class="mb-1 block font-lexend text-xs font-medium text-text-muted">{{ $t('Color') }}</span>
                    <ColorPickerComponent :color="eventTypeForm.hex_code" @update-color="addColor" />
                </div>
                <div class="w-full">
                    <BaseInput
                        id="eventTypeName"
                        v-model="eventTypeForm.name"
                        label="Name"
                        required
                        :error="eventTypeForm.errors.name"
                        autofocus
                    />
                </div>
            </div>
            <BaseInput
                id="eventTypeAbbreviation"
                v-model="eventTypeForm.abbreviation"
                label="Abbreviation of the event type"
                required
                :maxlength="4"
                :error="eventTypeForm.errors.abbreviation"
            />

            <div class="space-y-3">
                <BaseCheckbox
                    id="eventTypeProjectMandatory"
                    v-model="eventTypeForm.project_mandatory"
                    :label="$t('project assignment mandatory')"
                />
                <BaseCheckbox
                    id="eventTypeIndividualName"
                    v-model="eventTypeForm.individual_name"
                    :label="$t('individual event name mandatory')"
                />
                <div>
                    <BaseCheckbox
                        id="eventTypeRelevantForProjectPeriod"
                        v-model="eventTypeForm.relevant_for_project_period"
                        :label="$t('Relevant for project period')"
                    />
                    <SettingsGuideBanner
                        class="mt-2"
                        variant="static"
                        title="Effect on the project period"
                        :paragraphs="[
                            'Only events of types marked as relevant define the start and end of the project period — shown on the project page, in print layouts and for day assignments.',
                            'If no type is marked as relevant, all events of the project count instead.',
                        ]"
                    />
                </div>
                <div v-if="$page.props.ticketing?.active">
                    <BaseCheckbox
                        id="eventTypeRelevantForTicketing"
                        v-model="eventTypeForm.relevant_for_ticketing"
                        :label="$t('Can sell tickets')"
                    />
                    <SettingsGuideBanner
                        class="mt-2"
                        variant="static"
                        title="Effect on Artwork-Tickets"
                        :paragraphs="[
                            'Only events of these types appear in the ticketing component of a project, where they get their prices and are released for sale in Artwork-Tickets.',
                            'Rehearsals, set-ups and other internal types stay out.',
                        ]"
                    />
                </div>
            </div>

            <!-- "Termine immer direkt buchbar": Verifizierung ist instanzweit abgeschaltet, Konfiguration bleibt gespeichert -->
            <div v-if="directBookingOnly" class="border-t border-border-subtle pt-5">
                <h3 class="font-lexend text-sm font-semibold text-text">{{ $t('Verification mode') }}</h3>
                <BaseAlertComponent class="mt-2" type="info" :use-translation="true" message="Event verification is switched off by the setting 'Events always bookable directly' (Settings → Events → Standard values). The stored verification mode has no effect until the setting is switched off." />
            </div>
            <div v-else class="space-y-4 border-t border-border-subtle pt-5">
                <div>
                    <h3 id="eventTypeVerificationHeading" class="font-lexend text-sm font-semibold text-text">{{ $t('Verification mode') }}</h3>
                    <p class="mt-1 text-sm text-text-muted">
                        {{ $t('Choose the verification mode for the planning calendar') }}
                    </p>
                    <SettingsGuideBanner
                        class="mt-3"
                        variant="static"
                        title="Verification in the planning calendar"
                        :paragraphs="[
                            'The verification mode controls who has to confirm events of this type in the planning calendar before they become binding.',
                        ]"
                    />
                </div>
                <div class="grid gap-2 sm:grid-cols-2" role="radiogroup" aria-labelledby="eventTypeVerificationHeading">
                    <label
                        v-for="verificationMode in verificationModes"
                        :key="verificationMode.id"
                        :for="'eventTypeVerificationMode_' + verificationMode.id"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border px-3.5 py-3 transition-colors duration-150 motion-reduce:transition-none"
                        :class="verificationMode.id === eventTypeForm.verification_mode ? 'border-accent-600 bg-accent-50' : 'border-border-subtle bg-surface hover:bg-surface-hover'"
                    >
                        <input
                            :id="'eventTypeVerificationMode_' + verificationMode.id"
                            type="radio"
                            name="eventTypeVerificationMode"
                            class="sr-only"
                            :value="verificationMode.id"
                            :checked="verificationMode.id === eventTypeForm.verification_mode"
                            @change="selectVerificationMode(verificationMode.id)"
                        />
                        <span
                            class="box-border size-4 shrink-0 rounded-full border bg-surface"
                            :class="verificationMode.id === eventTypeForm.verification_mode ? 'border-[5px] border-accent-600' : 'border-border'"
                            aria-hidden="true"
                        />
                        <span class="font-lexend text-[13px] font-medium text-text">{{ $t(verificationMode.title) }}</span>
                    </label>
                </div>

                <div v-if="eventTypeForm.verification_mode !== 'none'" class="space-y-3">
                    <h4 class="font-lexend text-sm font-semibold text-text">
                        {{ $t('Choose users for the verification process in the planning calendar') }}
                    </h4>
                    <UserSearch label="Search for users" :disabled="checkIfUserSearchMustDisabled" @user-selected="addUserToEventType" />

                    <ul
                        v-if="eventTypeForm.users?.length > 0"
                        class="max-h-72 divide-y divide-border-subtle overflow-y-auto rounded-lg border border-border bg-surface"
                    >
                        <li
                            v-for="(user, index) in eventTypeForm.users"
                            :key="user.id"
                            class="flex items-center justify-between gap-x-3 px-3 py-2"
                            :class="isSpecificVerifier(user) ? 'bg-accent-50' : ''"
                        >
                            <button
                                type="button"
                                class="flex min-w-0 items-center gap-x-3 rounded-md text-left"
                                :class="eventTypeForm.verification_mode === 'specific' ? 'cursor-pointer' : 'cursor-default'"
                                :aria-pressed="eventTypeForm.verification_mode === 'specific' ? String(isSpecificVerifier(user)) : undefined"
                                @click="makeSpecificVerifier(user.id)"
                            >
                                <img class="size-8 shrink-0 rounded-full object-cover" :src="user.profile_photo_url" alt="" />
                                <span class="truncate text-sm font-medium text-text">{{ user.name }}</span>
                                <PropertyIcon
                                    v-if="isSpecificVerifier(user)"
                                    name="IconShieldCheck"
                                    class="size-4 shrink-0 text-accent-700"
                                    stroke-width="1.5"
                                    aria-hidden="true"
                                />
                            </button>
                            <button
                                type="button"
                                class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 hover:bg-surface-hover hover:text-danger motion-reduce:transition-none"
                                :aria-label="$t('Remove user')"
                                @click="removeUserFromEventType(index)"
                            >
                                <PropertyIcon name="IconX" class="size-4" stroke-width="1.5" aria-hidden="true" />
                            </button>
                        </li>
                    </ul>
                    <BaseAlertComponent v-else message="No users selected" type="info" :use-translation="true" />
                    <BaseAlertComponent
                        v-if="eventTypeForm.verification_mode === 'specific' && eventTypeForm.specific_verifier_id === null && eventTypeForm.users.length > 0"
                        message="Please select a user who needs to verify this event type. To do this, click on the name of a user"
                        type="error"
                        :use-translation="true"
                    />
                </div>
            </div>
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="eventTypeForm.processing"
                @click="closeModal"
            />
            <BaseUIButton
                type="submit"
                form="eventTypeForm"
                :label="isEditing ? 'Save' : 'Create event type'"
                variant="primary"
                :icon="isEditing ? 'IconDeviceFloppy' : 'IconCirclePlus'"
                :processing="eventTypeForm.processing"
                :disabled="!canSubmit"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed} from 'vue'
import {useForm, usePage} from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import SettingsGuideBanner from '@/Artwork/Guide/SettingsGuideBanner.vue'
import ColorPickerComponent from '@/Components/Globale/ColorPickerComponent.vue'
import UserSearch from '@/Components/SearchBars/UserSearch.vue'
import BaseAlertComponent from '@/Components/Alerts/BaseAlertComponent.vue'

defineOptions({name: 'AddEditEventTypeModal'})

const props = defineProps({
    eventType: {
        type: Object,
        required: false,
        default: null,
    },
})

const emit = defineEmits(['close'])

const isEditing = computed(() => Boolean(props.eventType?.id))

// Instanz-Setting "Termine immer direkt buchbar": keine Verifizierung
const directBookingOnly = computed(() => !!usePage().props.event_direct_booking_only)

const verificationModes = [
    {id: 'none', title: 'No verification needed'},
    {id: 'all', title: 'All assigned users must verify'},
    {id: 'any', title: 'At least one assigned user must verify'},
    {id: 'specific', title: 'A specific user must verify'},
]

const eventTypeForm = useForm({
    id: props.eventType?.id ?? null,
    name: props.eventType?.name ?? '',
    project_mandatory: props.eventType?.project_mandatory ?? false,
    individual_name: props.eventType?.individual_name ?? false,
    abbreviation: props.eventType?.abbreviation ?? '',
    hex_code: props.eventType?.hex_code ?? '#EC7A3D',
    relevant_for_project_period: props.eventType?.relevant_for_project_period ?? false,
    relevant_for_ticketing: props.eventType?.relevant_for_ticketing ?? false,
    svg_name: props.eventType?.svg_name ?? 'IconPhoto',
    users: [...(props.eventType?.users ?? [])],
    verification_mode: props.eventType?.verification_mode ?? 'none',
    specific_verifier_id: props.eventType?.specific_verifier_id ?? null,
})

const canSubmit = computed(() => (
    String(eventTypeForm.name ?? '').trim() !== ''
    && eventTypeForm.svg_name !== ''
    && !eventTypeForm.processing
    && !(eventTypeForm.verification_mode === 'specific' && eventTypeForm.specific_verifier_id === null)
))

function closeModal() {
    emit('close')
}

function addOrUpdateEventType() {
    if (!canSubmit.value) {
        return
    }
    const options = {
        onSuccess: () => {
            eventTypeForm.reset()
            closeModal()
        },
    }
    if (isEditing.value) {
        eventTypeForm.patch(route('event_types.update', props.eventType.id), options)
        return
    }
    eventTypeForm.post(route('event_types.store'), options)
}

function addColor(color) {
    eventTypeForm.hex_code = color
}

function addUserToEventType(user) {
    if (eventTypeForm.users.some((existingUser) => existingUser.id === user.id)) {
        return
    }
    eventTypeForm.users.push(user)

    if (eventTypeForm.verification_mode === 'specific' && eventTypeForm.specific_verifier_id === null) {
        eventTypeForm.specific_verifier_id = user.id
    }
}

function removeUserFromEventType(index) {
    const removedUser = eventTypeForm.users[index]
    eventTypeForm.users.splice(index, 1)

    if (eventTypeForm.users.length === 0) {
        eventTypeForm.specific_verifier_id = null
        eventTypeForm.verification_mode = 'none'
    }

    if (removedUser && eventTypeForm.specific_verifier_id === removedUser.id) {
        eventTypeForm.specific_verifier_id = null
    }
}

function selectVerificationMode(mode) {
    eventTypeForm.verification_mode = mode

    if (mode !== 'specific' && eventTypeForm.specific_verifier_id !== null) {
        eventTypeForm.specific_verifier_id = null
    }
}

// Nur im Modus "bestimmte Person" gibt es eine Verifizierer*in
function makeSpecificVerifier(userId) {
    if (eventTypeForm.verification_mode !== 'specific') {
        return
    }
    eventTypeForm.specific_verifier_id = userId
}

function isSpecificVerifier(user) {
    return eventTypeForm.verification_mode === 'specific' && eventTypeForm.specific_verifier_id === user.id
}

const checkIfUserSearchMustDisabled = computed(() => (
    eventTypeForm.verification_mode === 'none'
    || (eventTypeForm.verification_mode === 'specific' && eventTypeForm.specific_verifier_id !== null)
))
</script>
