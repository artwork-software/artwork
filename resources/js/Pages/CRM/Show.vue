<template>
    <AppLayout :title="contact.display_name">
        <div class="mt-5 mx-auto container pb-20">
            <!-- Back link -->
            <div class="mb-4">
                <Link :href="route('crm.index', { type: contact.contact_type?.slug })" class="inline-flex items-center text-sm font-medium text-artwork-buttons-hover hover:text-artwork-buttons-hover/80">
                    <component :is="IconArrowLeft" class="h-4 w-4 mr-2" />
                    {{ $t('Back to CRM') }}
                </Link>
            </div>

            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-1"
            >
                <div v-if="successMessage" class="mb-4 rounded-md bg-green-50 p-3">
                    <p class="text-sm font-medium text-green-800">{{ successMessage }}</p>
                </div>
            </Transition>

            <!-- Header -->
            <div class="flex items-center gap-6 mb-8">
                <div class="size-20 shrink-0">
                    <img :src="contact.profile_photo_url" alt="" class="size-20 rounded-full object-cover" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ contact.display_name }}</h1>
                    <span class="inline-flex items-center rounded-full px-3 py-0.5 text-sm font-medium mt-1"
                          :style="contact.contact_type?.color
                              ? { backgroundColor: contact.contact_type.color + '15', color: contact.contact_type.color }
                              : { backgroundColor: '#eef2ff', color: '#3730a3' }">
                        <PropertyIcon v-if="contact.contact_type?.icon" :name="contact.contact_type?.icon" class="mr-1.5 h-4 w-4" />
                        {{ $t(contact.contact_type?.name) }}
                    </span>
                </div>
                <div class="ml-auto flex items-center gap-2" v-if="!isReadOnly">
                    <button class="ui-button-add" @click="toggleEditing">
                        <component :is="editing ? IconCheck : IconEdit" stroke-width="1" class="size-5" />
                        {{ editing ? $t('Save changes') : $t('Edit') }}
                    </button>
                </div>
            </div>

            <!-- Property Groups -->
            <div class="space-y-6">
                <CrmPropertyGroupSection
                    v-for="group in visibleGroups"
                    :key="group.id"
                    :group="group"
                    :contact="contact"
                    :editing="editing && !isReadOnly && group.can_edit"
                    :errors="validationErrors"
                    @update-value="updatePropertyValue"
                    @clear-error="(id) => delete validationErrors[id]"
                />
            </div>

            <!-- Room Types (for Accommodation type) -->
            <div v-if="contact.contact_type?.slug === 'accommodation'" class="mt-8">
                <h2 class="text-lg font-semibold mb-4">{{ $t('Room types') }}</h2>

                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('Room type') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('Cost per night') }}</th>
                                <th v-if="!isReadOnly" class="relative px-6 py-3">
                                    <span class="sr-only">{{ $t('Actions') }}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <!-- Existing room types -->
                            <tr v-for="rt in selectedRoomTypes" :key="rt.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <BaseInput
                                        v-if="!isReadOnly"
                                        :id="`name_${rt.id}`"
                                        type="text"
                                        v-model="rt.name"
                                        no-margin-top
                                        @focusout="updateRoomTypeName(rt)"
                                    />
                                    <span v-else>{{ $t(rt.name) }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <div v-if="!isReadOnly" class="w-32">
                                        <BaseInput
                                            :id="`cost_${rt.id}`"
                                            type="number"
                                            :step="0.01"
                                            :max="50000"
                                            v-model="roomTypeCosts[rt.id]"
                                            placeholder="0.00"
                                            no-margin-top
                                            @focusout="saveRoomTypes"
                                        />
                                    </div>
                                    <span v-else>{{ rt.pivot?.cost_per_night ?? '-' }}</span>
                                </td>
                                <td v-if="!isReadOnly" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button
                                        type="button"
                                        class="text-red-600 hover:text-red-800"
                                        @click="removeRoomType(rt.id)"
                                    >
                                        <component :is="IconTrash" class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>

                            <!-- New row for adding -->
                            <tr v-if="!isReadOnly && showNewRow" class="bg-gray-50">
                                <td class="px-6 py-4">
                                    <BaseInput
                                        id="new_room_type_name"
                                        type="text"
                                        v-model="newRoomTypeName"
                                        :placeholder="$t('Name')"
                                        no-margin-top
                                        @keyup.enter="createRoomType"
                                    />
                                </td>
                                <td class="px-6 py-4">
                                    <div class="w-32">
                                        <BaseInput
                                            id="new_room_type_cost"
                                            type="number"
                                            :step="0.01"
                                            v-model="newRoomTypeCost"
                                            placeholder="0.00"
                                            no-margin-top
                                            @keyup.enter="createRoomType"
                                        />
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" class="text-green-600 hover:text-green-800" @click="createRoomType" :disabled="!newRoomTypeName.trim()">
                                            <component :is="IconCheck" class="h-5 w-5" />
                                        </button>
                                        <button type="button" class="text-gray-400 hover:text-gray-600" @click="showNewRow = false; newRoomTypeName = ''; newRoomTypeCost = 0">
                                            <component :is="IconX" class="h-5 w-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="!isReadOnly && !showNewRow" class="mt-3">
                    <button type="button" class="ui-button-add" @click="showNewRow = true">
                        <component :is="IconPlus" class="h-4 w-4 mr-1" />
                        {{ $t('Add room type') }}
                    </button>
                </div>
            </div>

            <!-- Read-only notice for User-type contacts -->
            <div v-if="isReadOnly" class="mt-6 rounded-md bg-blue-50 p-4">
                <div class="flex">
                    <component :is="IconInfoCircle" class="h-5 w-5 text-blue-400" />
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            {{ $t('This contact is linked to a user account. Changes must be made in the user profile.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import CrmPropertyGroupSection from '@/Pages/CRM/Components/CrmPropertyGroupSection.vue'
import { IconArrowLeft, IconEdit, IconCheck, IconInfoCircle, IconTrash, IconPlus, IconX } from '@tabler/icons-vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import { useTranslation } from '@/Composeables/Translation.js'

const props = defineProps({
    contact: { type: Object, required: true },
    propertyGroups: { type: Array, required: true },
})

const $t = useTranslation()

const editing = ref(false)
const validationErrors = ref({})
const successMessage = ref('')
const showSuccess = (msg) => {
    successMessage.value = msg
    setTimeout(() => { successMessage.value = '' }, 3000)
}

const getPropertyValue = (propertyId) => {
    const pv = props.contact.property_values?.find(v => v.crm_property_id === propertyId)
    return pv?.value ?? ''
}

const validateRequiredFields = () => {
    const errors = {}
    for (const group of visibleGroups.value) {
        if (group.is_confidential && !group.can_edit) continue

        for (const prop of group.properties) {
            if (!prop.pivot?.is_required) continue
            const value = getPropertyValue(prop.id)
            if (!value || (typeof value === 'string' && value.trim() === '')) {
                errors[prop.id] = $t('This is a mandatory field.')
            }
        }
    }
    return errors
}

const toggleEditing = () => {
    if (editing.value) {
        const errors = validateRequiredFields()
        if (Object.keys(errors).length > 0) {
            validationErrors.value = errors
            return
        }
        validationErrors.value = {}
        editing.value = false
        showSuccess($t('Changes saved'))
    } else {
        validationErrors.value = {}
        editing.value = true
    }
}

const isReadOnly = computed(() => {
    return ['user', 'freelancer', 'service_provider'].includes(props.contact.contact_type?.slug)
})

// Build pivot map from contact type properties (contains is_required etc.)
const contactTypePivotMap = computed(() => {
    const map = {}
    for (const p of (props.contact.contact_type?.properties ?? [])) {
        map[p.id] = p.pivot ?? {}
    }
    return map
})

// Filter groups to only show those that have properties assigned to this contact type
const visibleGroups = computed(() => {
    const typePropertyIds = new Set(
        (props.contact.contact_type?.properties ?? []).map(p => p.id)
    )

    return props.propertyGroups
        .map(group => ({
            ...group,
            properties: group.properties
                .filter(p => typePropertyIds.has(p.id))
                .map(p => ({ ...p, pivot: contactTypePivotMap.value[p.id] ?? {} })),
        }))
        .filter(group => group.properties.length > 0)
})

const updatePropertyValue = ({ propertyId, value }) => {
    router.patch(route('crm.contacts.update', props.contact.id), {
        property_values: { [propertyId]: value },
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

/* ----- Room Types (Accommodation) ----- */
const selectedRoomTypes = ref(
    (props.contact.room_types ?? []).map(rt => ({
        id: rt.id,
        name: rt.name,
    }))
)

const roomTypeCosts = ref(
    Object.fromEntries(
        (props.contact.room_types ?? []).map(rt => [rt.id, rt.pivot?.cost_per_night ?? 0])
    )
)

const showNewRow = ref(false)
const newRoomTypeName = ref('')
const newRoomTypeCost = ref(0)

const saveRoomTypes = () => {
    router.patch(route('crm.contacts.room-types.update', props.contact.id), {
        room_types: selectedRoomTypes.value.map(rt => rt.id),
        room_type_costs: roomTypeCosts.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const createRoomType = () => {
    const name = newRoomTypeName.value.trim()
    if (!name) return

    router.post(route('crm.contacts.room-types.store', props.contact.id), {
        name: name,
        cost_per_night: newRoomTypeCost.value || 0,
    }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            // Refresh from server data
            const updatedContact = page.props.contact
            selectedRoomTypes.value = (updatedContact.room_types ?? []).map(rt => ({
                id: rt.id,
                name: rt.name,
            }))
            roomTypeCosts.value = Object.fromEntries(
                (updatedContact.room_types ?? []).map(rt => [rt.id, rt.pivot?.cost_per_night ?? 0])
            )
            newRoomTypeName.value = ''
            newRoomTypeCost.value = 0
            showNewRow.value = false
        },
    })
}

const updateRoomTypeName = (rt) => {
    if (!rt.name.trim()) return
    router.patch(route('crm.contacts.room-types.update-name', rt.id), {
        name: rt.name.trim(),
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const removeRoomType = (roomTypeId) => {
    router.delete(route('crm.contacts.room-types.destroy', {
        crmContact: props.contact.id,
        roomType: roomTypeId,
    }), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            selectedRoomTypes.value = selectedRoomTypes.value.filter(rt => rt.id !== roomTypeId)
            delete roomTypeCosts.value[roomTypeId]
        },
    })
}
</script>
