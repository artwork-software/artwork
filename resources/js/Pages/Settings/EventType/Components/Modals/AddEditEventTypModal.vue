<template>
    <BaseModal @closed="$emit('close')">
        <ModalHeader
            :title="!eventType ? $t('New event type') : $t('Edit event type')"
        />
        <form @submit.prevent="addOrUpdateEventType">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="col-span-1">
                    <div class="-mt-1">
                        <div class="text-sm/5 font-bold text-text-subtle flex items-center justify-start">
                            {{ $t('Color') }}
                        </div>
                       <div class="mt-1 flex items-center justify-center">
                           <ColorPickerComponent @updateColor="addColor" :color="eventTypeForm.hex_code" />
                       </div>
                    </div>
                </div>
                <div class="col-span-4">
                    <BaseInput id="name" v-model="eventTypeForm.name" type="text" :label="$t('Event type name*')" required/>
                </div>
                <div class="col-span-full">
                    <BaseInput
                        :label="$t('Abbreviation of the event type') + '*'"
                        v-model="eventTypeForm.abbreviation"
                        required
                        :maxlength="4"
                        id="abbreviation"
                    />
                </div>
                <div class="col-span-full">
                    <div class="flex gap-x-3">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input id="project_mandatory" v-model="eventTypeForm.project_mandatory" type="checkbox" checked="" class="col-start-1 row-start-1 input-checklist" />
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="project_mandatory" class="font-medium font-lexend" :class="eventTypeForm.project_mandatory ? 'text-text' : 'text-text-subtle'">{{$t('project assignment mandatory')}}</label>
                        </div>
                    </div>
                </div>
                <div class="col-span-full">
                    <div class="flex gap-x-3">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input id="individual_name" v-model="eventTypeForm.individual_name" type="checkbox" checked="" class="col-start-1 row-start-1 input-checklist" />
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="individual_name" class="font-medium font-lexend" :class="eventTypeForm.individual_name ? 'text-text' : 'text-text-subtle'">{{$t('individual event name mandatory')}}</label>
                        </div>
                    </div>
                </div>
                <div class="col-span-full">
                    <div class="flex gap-x-3">
                        <div class="flex h-6 shrink-0 items-center">
                            <div class="group grid size-4 grid-cols-1">
                                <input id="relevant_for_project_period" v-model="eventTypeForm.relevant_for_project_period" type="checkbox" checked="" class="col-start-1 row-start-1 input-checklist" />
                            </div>
                        </div>
                        <div class="text-sm/6">
                            <label for="relevant_for_project_period" class="font-medium font-lexend" :class="eventTypeForm.relevant_for_project_period ? 'text-text' : 'text-text-subtle'">{{$t('Relevant for project period')}}</label>
                        </div>
                    </div>
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

                <div class="col-span-full border-t border-border-subtle border-dashed">
                    <div class="mt-4">
                        <h4 class="text-sm/6 font-semibold font-lexend text-text">{{ $t('Verification mode') }}</h4>
                        <p class="mt-1 text-sm/6 font-lexend text-text-muted">
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
                        <div class="mt-6 space-y-6 ">
                            <div v-for="notificationMethod in verificationModes" :key="notificationMethod.id" class="flex items-center">
                                <input
                                    @change="selectVerificationMode(notificationMethod.id)"
                                    :id="notificationMethod.id"
                                    name="notification-method"
                                    type="radio"
                                    :checked="notificationMethod.id === eventTypeForm.verification_mode"
                                    class="size-5 text-text border-border focus:ring-surface-inverse"
                                />
                                <label :for="notificationMethod.id" class="ml-3 block text-sm/6 font-medium text-text">{{ $t(notificationMethod.title) }}</label>
                            </div>
                        </div>
                    </div>

                    <div v-if="eventTypeForm.verification_mode !== 'none'" class="mt-6">
                        <div class="mt-4">
                            <h4 class="text-sm font-bold  font-lexend text-text">
                                {{ $t('Choose users for the verification process in the planning calendar') }}
                            </h4>
                        </div>
                        <div class="pt-2">
                            <UserSearch @userSelected="addUserToEventType" :disabled="checkIfUserSearchMustDisabled" label="Search for users" />
                        </div>

                        <div v-if="eventTypeForm.users?.length > 0">
                            <div v-if="eventTypeForm.users?.length > 0" class="flex items-center gap-4 mt-3">
                                <div v-for="(user, index) in eventTypeForm.users" class="group block shrink-0 bg-white w-fit pr-3 rounded-full border border-border-subtle">
                                    <div class="flex items-center">
                                        <div class="flex items-center cursor-pointer" @click="makeSpecificVerifier(user.id)">
                                            <div class="relative flex items-center">
                                                <img class="inline-block size-9 rounded-full object-cover" :src="user.profile_photo_url" alt="" />
                                            </div>
                                            <div class="mx-2">
                                                <p class="text-sm/5 font-semibold text-text">{{ user.name}}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center">
                                            <button type="button" @click="removeUserFromEventType(index)">
                                                <IconX class="h-4 w-4 text-text-subtle hover:text-danger" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="mt-4">
                            <BaseAlertComponent message="No users selected" type="info" :useTranslation="true" />
                        </div>
                        <div v-if="eventTypeForm.verification_mode === 'specific' && eventTypeForm.specific_verifier_id === null && eventTypeForm.users.length > 0" class="mt-4">
                            <BaseAlertComponent message="Please select a user who needs to verify this event type. To do this, click on the name of a user" type="error" :useTranslation="true" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-5 w-full flex justify-end items-center text-center">
                <BaseUIButton
                    type="submit"
                    :disabled="eventTypeForm.name === '' || eventTypeForm.svg_name === '' || eventTypeForm.processing || eventTypeForm.verification_mode === 'specific' && eventTypeForm.specific_verifier_id === null"
                    :label="!eventType ? $t('Create event type') : $t('Save')" is-add-button />
            </div>
        </form>
    </BaseModal>
</template>

<script setup>
import {IconX} from "@tabler/icons-vue";

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import {useForm} from "@inertiajs/vue3";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import {computed} from "vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";

const props = defineProps({
    eventType: {
        type: Object,
        required: false,
        default: []
    }
})

const emits = defineEmits(['close'])

const verificationModes = [
    { id: 'none', title: 'No verification needed' },
    { id: 'all', title: 'All assigned users must verify' },
    { id: 'any', title: 'At least one assigned user must verify' },
    { id: 'specific', title: 'A specific user must verify' },
]

const eventTypeForm = useForm({
    id: props.eventType ? props.eventType.id : null,
    name: props.eventType ? props.eventType.name : '',
    project_mandatory: props.eventType ? props.eventType.project_mandatory : false,
    individual_name: props.eventType ? props.eventType.individual_name : false,
    abbreviation: props.eventType ? props.eventType.abbreviation : '',
    hex_code: props.eventType ? props.eventType.hex_code : '#EC7A3D',
    relevant_for_project_period: props.eventType ? props.eventType.relevant_for_project_period : false,
    svg_name: props.eventType ? props.eventType.svg_name : 'IconPhoto',
    users: props.eventType ? props.eventType.users ?? [] : [],
    verification_mode: props.eventType ? props.eventType.verification_mode ?? 'none' : 'none',
    specific_verifier_id: props.eventType ? props.eventType.specific_verifier_id ?? null : null,
})

const addOrUpdateEventType = () => {
    if (props.eventType?.id){
        eventTypeForm.patch(route('event_types.update', props.eventType.id), {
            onSuccess: () => {
                eventTypeForm.reset()
                emits('close')
            }
        })
    } else {
        eventTypeForm.post(route('event_types.store'), {
            onSuccess: () => {
                eventTypeForm.reset()
                emits('close')
            }
        })
    }
}

const addColor = (color) => {
    eventTypeForm.hex_code = color
}

const addUserToEventType = (user) => {
    const userExists = eventTypeForm.users.find(u => u.id === user.id)
    if (userExists) {
        return false;
    } else {
        eventTypeForm.users.push(user)
    }

    if (eventTypeForm.verification_mode === 'specific' && eventTypeForm.specific_verifier_id === null) {
        eventTypeForm.specific_verifier_id = user.id
    }
}

const removeUserFromEventType = (index) => {
    const foundedUserByIndexBeforeDelete = eventTypeForm.users[index]
    eventTypeForm.users.splice(index, 1)

    if(eventTypeForm.users.length === 0) {
        eventTypeForm.specific_verifier_id = null
        eventTypeForm.verification_mode = 'none'
    }

    if (eventTypeForm.specific_verifier_id === foundedUserByIndexBeforeDelete.id) {
        eventTypeForm.specific_verifier_id = null
    }
}

const selectVerificationMode = (mode) => {
    eventTypeForm.verification_mode = mode

    if(eventTypeForm.verification_mode !== 'specific' && eventTypeForm.specific_verifier_id !== null) {
        eventTypeForm.specific_verifier_id = null
    }
}

const makeSpecificVerifier = (userId) => {
    eventTypeForm.specific_verifier_id = userId
}

const checkIfUserSearchMustDisabled = computed(() => {
    return eventTypeForm.verification_mode === 'none' || eventTypeForm.verification_mode === 'specific' && eventTypeForm.specific_verifier_id !== null
})
</script>

<style scoped>

</style>
