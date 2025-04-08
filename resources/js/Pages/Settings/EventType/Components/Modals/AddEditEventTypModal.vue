<template>
    <BaseModal @closed="$emit('close')">
        <ModalHeader
            :title="!eventType ? $t('New event type') : $t('Edit event type')"
        />
        <form @submit.prevent="addOrUpdateEventType">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="col-span-1">
                    <div class="-mt-1">
                        <div class="xsLight flex items-center justify-start">
                            {{ $t('Color') }}
                        </div>
                       <div class="mt-1 flex items-center justify-center">
                           <ColorPickerComponent @updateColor="addColor" :color="eventTypeForm.hex_code" />
                       </div>
                    </div>
                </div>
                <div class="col-span-4">
                    <TextInputComponent id="name" v-model="eventTypeForm.name" type="text" :label="$t('Event type name*')" required/>
                </div>
                <div class="col-span-full">
                    <TextInputComponent
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
                            <label for="project_mandatory" class="font-medium font-lexend" :class="eventTypeForm.project_mandatory ? 'text-gray-900' : 'text-gray-400'">{{$t('project assignment mandatory')}}</label>
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
                            <label for="individual_name" class="font-medium font-lexend" :class="eventTypeForm.individual_name ? 'text-gray-900' : 'text-gray-400'">{{$t('individual event name mandatory')}}</label>
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
                            <label for="relevant_for_project_period" class="font-medium font-lexend" :class="eventTypeForm.relevant_for_project_period ? 'text-gray-900' : 'text-gray-400'">{{$t('project assignment mandatory')}}</label>
                        </div>
                    </div>
                </div>

                <div class="col-span-full border-t border-gray-200 border-dashed">
                    <div class="mt-4">
                        <h4 class="text-sm/6 font-semibold font-lexend text-gray-900">Verifizierungsmodus</h4>
                        <p class="mt-1 text-sm/6 font-lexend text-gray-600">
                            {{ $t('Wähle den Verifizierungsmodus für den Planungskalender') }}
                        </p>
                        <div class="mt-6 space-y-6 ">
                            <div v-for="notificationMethod in verificationMods" :key="notificationMethod.id" class="flex items-center">
                                <input v-if="eventTypeForm.verification_mode" @change="selectVerificationMode(notificationMethod.id)" :id="notificationMethod.id" name="notification-method" type="radio" :checked="notificationMethod.id === eventTypeForm.verification_mode" class="relative size-4 appearance-none rounded-full input-checklist" />
                                <label :for="notificationMethod.id" class="ml-3 block text-sm/6 font-medium text-gray-900">{{ notificationMethod.title }}</label>
                            </div>
                        </div>
                    </div>

                    <div v-if="eventTypeForm.verification_mode !== 'none'" class="mt-6">
                        <div class="mt-4">
                            <h4 class="text-sm font-bold  font-lexend text-gray-900">
                                {{ $t('Wähle Nutzer für den Verifizierungsprozess im Planungskalender') }}
                            </h4>
                        </div>
                        <div class="pt-2">
                            <UserSearch @userSelected="addUserToEventType" :disabled="checkIfUserSearchMustDisabled" />
                        </div>

                        <div v-if="eventTypeForm.users?.length > 0">
                            <div v-if="eventTypeForm.users?.length > 0" class="flex items-center gap-4 mt-3">
                                <div v-for="(user, index) in eventTypeForm.users" class="group block shrink-0 bg-white w-fit pr-3 rounded-full border border-gray-100">
                                    <div class="flex items-center">
                                        <div class="flex items-center cursor-pointer" @click="makeSpecificVerifier(user.id)">
                                            <div class="relative flex items-center">
                                                <img class="inline-block size-9 rounded-full object-cover" :src="user.profile_photo_url" alt="" />
                                            </div>
                                            <div class="mx-2">
                                                <p class="xsDark">{{ user.name}}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center">
                                            <button type="button" @click="removeUserFromEventType(index)">
                                                <XIcon class="h-4 w-4 text-gray-400 hover:text-error" />
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
            <div class="mt-5 w-full flex justify-center items-center text-center">
                <FormButton
                    type="submit"
                    :disabled="eventTypeForm.name === '' || eventTypeForm.svg_name === '' || eventTypeForm.processing || eventTypeForm.verification_mode === 'specific' && eventTypeForm.specific_verifier_id === null"
                    :text="!eventType ? $t('Create event type') : $t('Save')" />
            </div>
        </form>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import {useForm} from "@inertiajs/vue3";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import {XIcon} from "@heroicons/vue/outline";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import {computed} from "vue";

const props = defineProps({
    eventType: {
        type: Object,
        required: false,
        default: []
    }
})

const emits = defineEmits(['close'])

const verificationMods = [
    { id: 'none', title: 'Keine Verifizierung erforderlich' },
    { id: 'all', title: 'Alle zugewiesenen Nutzer müssen verifizieren' },
    { id: 'any', title: 'Mindestens ein zugewiesener Nutzer muss verifizieren' },
    { id: 'specific', title: 'Ein bestimmter Nutzer muss verifizieren' },
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