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
                <div v-if="contact.room_types?.length" class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('Room type') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('Cost per night') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="rt in contact.room_types" :key="rt.id">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $t(rt.name) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ rt.pivot?.cost_per_night ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-gray-500">{{ $t('No room types assigned.') }}</p>
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
import { IconArrowLeft, IconEdit, IconCheck, IconInfoCircle } from '@tabler/icons-vue'
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
</script>
