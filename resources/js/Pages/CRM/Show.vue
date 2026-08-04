<template>
    <AppLayout :title="contact.display_name">
        <div class="mt-5 mx-auto container pb-20">
            <!-- Back link -->
            <div class="mb-4">
                <Link :href="route('crm.index', { type: contact.contact_type?.slug })" class="inline-flex items-center text-sm font-medium text-accent-700 hover:text-accent-700/80">
                    <component :is="IconArrowLeft" class="h-4 w-4 mr-2" />
                    {{ $t('Back to CRM') }}
                </Link>
            </div>

            <!-- External access status — Feature vorerst ausgeblendet (noch nicht ausgereift)
            <div v-if="externalAccessStatus" class="mb-4 flex flex-wrap items-center gap-3">
                <span
                    v-if="externalAccessStatus.crm_access_expires_at && !externalAccessStatus.revoked_at"
                    class="inline-flex items-center rounded-full bg-accent-50 px-3 py-1 text-xs font-medium text-accent-700"
                >
                    {{ $t('External access active until {date}', { date: new Date(externalAccessStatus.crm_access_expires_at).toLocaleDateString() }) }}
                </span>
                <Link
                    v-if="externalAccessStatus.has_pending_submission"
                    :href="route('crm.contacts.external-submissions.show', [contact.id, externalAccessStatus.pending_submission_id])"
                    class="inline-flex items-center rounded-full bg-warning-surface px-3 py-1 text-xs font-medium text-warning hover:bg-warning-surface"
                >
                    {{ $t('There is a data update request to review') }}
                </Link>
                <Link
                    v-if="externalAccessStatus.id"
                    :href="route('crm.external-access.show', externalAccessStatus.id)"
                    class="inline-flex items-center rounded-full bg-surface-sunken px-3 py-1 text-xs font-medium text-text-muted hover:bg-border-subtle"
                >
                    {{ $t('Manage external access') }}
                </Link>
            </div>
            -->

            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-1"
            >
                <div v-if="successMessage" class="mb-4 rounded-md bg-success-surface p-3">
                    <p class="text-sm font-medium text-success">{{ successMessage }}</p>
                </div>
            </Transition>

            <!-- Header -->
            <div class="flex items-center gap-6 mb-8">
                <div class="size-20 shrink-0 relative group/avatar">
                    <img :src="contact.profile_photo_url" alt="" class="size-20 rounded-full object-cover" />
                    <label
                        v-if="!isReadOnly"
                        class="absolute inset-0 flex items-center justify-center rounded-full bg-black/40 opacity-0 group-hover/avatar:opacity-100 transition-opacity cursor-pointer"
                        :title="$t('Upload profile image')"
                    >
                        <component :is="IconCamera" class="h-6 w-6 text-white" />
                        <input type="file" accept="image/*" class="sr-only" @change="onProfileImageChange" />
                    </label>
                </div>
                <div>
                    <h1 v-if="!editing" class="text-2xl font-bold text-text">{{ contact.display_name }}</h1>
                    <input
                        v-else
                        v-model="editableDisplayName"
                        type="text"
                        class="text-2xl font-bold text-text border-b-2 border-accent-600 bg-transparent outline-none px-0 py-0.5 w-full max-w-md"
                        :placeholder="$t('Name')"
                    />
                    <span class="inline-flex items-center rounded-full px-3 py-0.5 text-sm font-medium mt-1"
                          :style="contact.contact_type?.color
                              ? { backgroundColor: contact.contact_type.color + '15', color: contact.contact_type.color }
                              : { backgroundColor: '#eef2ff', color: '#3730a3' }">
                        <PropertyIcon v-if="contact.contact_type?.icon" :name="contact.contact_type?.icon" class="mr-1.5 h-4 w-4" />
                        {{ $t(contact.contact_type?.name) }}
                    </span>
                    <p v-if="profileImageError" class="mt-1 text-sm text-danger">{{ profileImageError }}</p>
                </div>
                <div class="ml-auto flex items-center gap-2" v-if="!isReadOnly && activeTab === 'info'">
                    <button v-if="canChangeType && !editing" class="ui-button" @click="showChangeTypeModal = true">
                        <component :is="IconSwitchHorizontal" stroke-width="1" class="size-5" />
                        {{ $t('Change type') }}
                    </button>
                    <button class="ui-button-add" @click="toggleEditing">
                        <component :is="editing ? IconCheck : IconEdit" stroke-width="1" class="size-5" />
                        {{ editing ? $t('Save changes') : $t('Edit') }}
                    </button>
                </div>
            </div>

            <!-- Tabs (Projekte-Reiter nur, wenn Verknüpfungen existieren) -->
            <div v-if="linkedProjects.length > 0" class="border-b border-border-subtle mb-6">
                <nav class="-mb-px flex gap-x-6">
                    <button
                        type="button"
                        class="whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors"
                        :class="activeTab === 'info' ? 'border-accent-600 text-accent-600'
                            : 'border-transparent text-text-subtle hover:text-text-muted hover:border-border'"
                        @click="activeTab = 'info'"
                    >
                        {{ $t('Information') }}
                    </button>
                    <button
                        type="button"
                        class="whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors"
                        :class="activeTab === 'projects' ? 'border-accent-600 text-accent-600'
                            : 'border-transparent text-text-subtle hover:text-text-muted hover:border-border'"
                        @click="activeTab = 'projects'"
                    >
                        {{ $t('Projects') }}
                        <span class="ml-1 rounded-full bg-surface-sunken px-2 py-0.5 text-xs text-text-muted">
                            {{ linkedProjects.length }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- Projects tab -->
            <div v-if="activeTab === 'projects'" class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                <table class="min-w-full divide-y divide-border">
                    <thead class="bg-surface-sunken">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase">{{ $t('Project') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase">{{ $t('Linked via') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase">{{ $t('Period') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase">{{ $t('Linked on') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-border-subtle">
                        <tr v-for="project in linkedProjects" :key="project.id" class="hover:bg-surface-sunken">
                            <td class="px-6 py-4 text-sm">
                                <Link
                                    v-if="firstProjectTabId"
                                    :href="route('projects.tab', { project: project.id, projectTab: firstProjectTabId })"
                                    class="font-medium text-accent-600 hover:underline"
                                >
                                    {{ project.name }}
                                </Link>
                                <span v-else class="font-medium text-text">{{ project.name }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-text-subtle">
                                <div class="flex flex-col gap-1.5">
                                    <span v-if="(project.sources ?? []).includes('artist')" class="inline-flex w-fit items-center rounded-full bg-special-violet-surface px-2.5 py-0.5 text-xs font-medium text-special-violet">
                                        {{ $t('Artist linking') }}
                                    </span>
                                    <span v-if="(project.sources ?? []).includes('team')" class="inline-flex w-fit items-center rounded-full bg-accent-50 px-2.5 py-0.5 text-xs font-medium text-accent-700">
                                        {{ $t('Project team') }}<template v-if="project.team_roles?.length">:&nbsp;{{ project.team_roles.join(', ') }}</template>
                                    </span>
                                    <span v-if="(project.sources ?? []).includes('residency')" class="inline-flex w-fit items-center rounded-full bg-success-surface px-2.5 py-0.5 text-xs font-medium text-success">
                                        {{ $t('Artist residency') }}<template v-if="project.residency_summary">:&nbsp;{{ residencySummaryText(project.residency_summary) }}</template>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-text-subtle">
                                <template v-if="project.first_event_date">
                                    {{ dateOnly(project.first_event_date) }} – {{ dateOnly(project.last_event_date) }}
                                </template>
                                <template v-else>-</template>
                            </td>
                            <td class="px-6 py-4 text-sm text-text-subtle">
                                {{ project.linked_at ?? '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Property Groups -->
            <div v-show="activeTab === 'info'" class="space-y-6">
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
            <div v-if="contact.contact_type?.slug === 'accommodation' && activeTab === 'info'" class="mt-8">
                <h2 class="text-lg font-semibold mb-4">{{ $t('Room types') }}</h2>

                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
                    <table class="min-w-full divide-y divide-border">
                        <thead class="bg-surface-sunken">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase">{{ $t('Room type') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase">{{ $t('Cost per night') }}</th>
                                <th v-if="!isReadOnly" class="relative px-6 py-3">
                                    <span class="sr-only">{{ $t('Actions') }}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-border-subtle">
                            <!-- Existing room types -->
                            <tr v-for="rt in selectedRoomTypes" :key="rt.id" class="hover:bg-surface-sunken">
                                <td class="px-6 py-4 text-sm text-text">
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
                                <td class="px-6 py-4 text-sm text-text-subtle">
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
                                        class="text-danger hover:text-danger"
                                        @click="removeRoomType(rt.id)"
                                    >
                                        <component :is="IconTrash" class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>

                            <!-- New row for adding -->
                            <tr v-if="!isReadOnly && showNewRow" class="bg-surface-sunken">
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
                                        <button type="button" class="text-success hover:text-success" @click="createRoomType" :disabled="!newRoomTypeName.trim()">
                                            <component :is="IconCheck" class="h-5 w-5" />
                                        </button>
                                        <button type="button" class="text-text-subtle hover:text-text-muted" @click="showNewRow = false; newRoomTypeName = ''; newRoomTypeCost = 0">
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
                        <component :is="IconCirclePlus" class="h-4 w-4 mr-1" />
                        {{ $t('Add room type') }}
                    </button>
                </div>
            </div>

            <!-- Read-only notice for User-type contacts -->
            <div v-if="isReadOnly && activeTab === 'info'" class="mt-6 rounded-md bg-accent-50 p-4">
                <div class="flex">
                    <component :is="IconInfoCircle" class="h-5 w-5 text-accent-500" />
                    <div class="ml-3">
                        <p class="text-sm text-accent-700">
                            {{ $t('This contact is linked to a user account. Changes must be made in the user profile.') }}
                            <Link v-if="sourceProfileUrl" :href="sourceProfileUrl" class="font-medium underline hover:text-accent-700">
                                {{ $t('Open profile') }}
                            </Link>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Change Contact Type Modal -->
        <ChangeContactTypeModal
            v-if="showChangeTypeModal"
            :contact="contact"
            :contact-types="contactTypes"
            @close="showChangeTypeModal = false"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import CrmPropertyGroupSection from '@/Pages/CRM/Components/CrmPropertyGroupSection.vue'
import ChangeContactTypeModal from '@/Pages/CRM/Components/ChangeContactTypeModal.vue'
import {
    IconArrowLeft, IconEdit, IconCheck, IconInfoCircle, IconTrash, IconCirclePlus, IconX,
    IconCamera, IconSwitchHorizontal,
} from '@tabler/icons-vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import { useTranslation } from '@/Composeables/Translation.js'
import debounce from 'lodash.debounce'

const props = defineProps({
    contact: { type: Object, required: true },
    propertyGroups: { type: Array, required: true },
    externalAccessStatus: { type: Object, default: null },
    linkedProjects: { type: Array, default: () => [] },
    firstProjectTabId: { type: Number, default: null },
    sourceProfileUrl: { type: String, default: null },
    contactTypes: { type: Array, default: () => [] },
    canChangeType: { type: Boolean, default: false },
})

const $t = useTranslation()

const activeTab = ref('info')

// Backend liefert "d.m.Y H:i" – im Projektprotokoll nur das Datum anzeigen
const dateOnly = (value) => (value ? String(value).split(' ')[0] : '-')

// Zusammenfassung der Aufenthalte: Zeitraum + Anzahl (bei mehr als einem Aufenthalt)
const residencySummaryText = (summary) => {
    const period = summary.from && summary.to ? `${summary.from} – ${summary.to}` : (summary.from ?? summary.to ?? '')
    return summary.count > 1 ? `${period} (${summary.count})` : period
}

const editing = ref(false)
const editableDisplayName = ref(props.contact.display_name ?? '')
const validationErrors = ref({})
const successMessage = ref('')
const showSuccess = (msg) => {
    successMessage.value = msg
    setTimeout(() => { successMessage.value = '' }, 3000)
}

const getPropertyValue = (propertyId) => {
    // Noch nicht gespeicherte (debounced) Eingaben haben Vorrang vor dem Serverstand
    if (propertyId in pendingPropertyValues.value) {
        return pendingPropertyValues.value[propertyId] ?? ''
    }
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

        // Ausstehende (debounced) Wertänderungen sofort speichern
        debouncedFlush.cancel()
        flushPropertyUpdates()

        // Save display_name if changed
        const trimmedName = editableDisplayName.value.trim()
        if (trimmedName && trimmedName !== props.contact.display_name) {
            router.patch(route('crm.contacts.update', props.contact.id), {
                display_name: trimmedName,
            }, {
                preserveState: true,
                preserveScroll: true,
            })
        }

        editing.value = false
        showSuccess($t('Changes saved'))
    } else {
        editableDisplayName.value = props.contact.display_name ?? ''
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

// Filter groups to only show those that have properties assigned to this contact type,
// ordered by the type's own group order (pivot sort_order via contact_type.properties)
const visibleGroups = computed(() => {
    const typeProperties = props.contact.contact_type?.properties ?? []
    const typePropertyIds = new Set(typeProperties.map(p => p.id))

    const groupOrder = new Map()
    typeProperties.forEach((p, index) => {
        if (!groupOrder.has(p.crm_property_group_id)) {
            groupOrder.set(p.crm_property_group_id, index)
        }
    })

    return props.propertyGroups
        .map(group => ({
            ...group,
            properties: group.properties
                .filter(p => typePropertyIds.has(p.id))
                .map(p => ({ ...p, pivot: contactTypePivotMap.value[p.id] ?? {} })),
        }))
        .filter(group => group.properties.length > 0)
        .sort((a, b) =>
            (groupOrder.get(a.id) ?? Number.MAX_SAFE_INTEGER) - (groupOrder.get(b.id) ?? Number.MAX_SAFE_INTEGER)
        )
})

// Eingaben werden gesammelt und gebündelt gespeichert statt pro Tastendruck
// einen PATCH zu feuern (Request-Flut + Race-Conditions).
const pendingPropertyValues = ref({})

const flushPropertyUpdates = () => {
    const values = { ...pendingPropertyValues.value }
    pendingPropertyValues.value = {}
    if (Object.keys(values).length === 0) return

    router.patch(route('crm.contacts.update', props.contact.id), {
        property_values: values,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const debouncedFlush = debounce(flushPropertyUpdates, 600)

const updatePropertyValue = ({ propertyId, value }) => {
    pendingPropertyValues.value[propertyId] = value
    debouncedFlush()
}

const showChangeTypeModal = ref(false)

const profileImageError = ref('')

const onProfileImageChange = (e) => {
    const file = e.target.files?.[0]
    e.target.value = ''
    if (!file) return

    profileImageError.value = ''
    router.post(route('crm.contacts.profile-image', props.contact.id), {
        profile_image: file,
    }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => showSuccess($t('Profile image updated')),
        onError: (errors) => {
            profileImageError.value = errors.profile_image ?? $t('Upload failed')
        },
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
