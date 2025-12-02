<!-- resources/js/Components/Shift/ShiftActivityLogModal.vue -->
<template>
    <BaseModal
        v-if="true"
        modal-image="/Svgs/Overlays/illu_project_history.svg"
        @closed="handleClose"
    >
        <div class="px-4 py-4 space-y-4">
            <div v-if="!sortedLogs.length" class="text-xs text-gray-500">
                {{ t('No history entries available for this shift yet.') }}
            </div>

            <ol v-else class="space-y-3">
                <li
                    v-for="entry in sortedLogs"
                    :key="entry.id"
                    class="flex gap-3 rounded-lg border border-gray-100 bg-white px-3 py-2 text-xs"
                >
                    <!-- Icon-Badge -->
                    <div class="pt-1">
                        <span
                            class="inline-flex h-7 w-7 items-center justify-center rounded-full text-[11px] font-semibold"
                            :class="bubbleClass(entry)"
                        >
                            {{ entry.iconLetter }}
                        </span>
                    </div>

                    <!-- Inhalt -->
                    <div class="flex-1 space-y-1.5">
                        <!-- Nachricht -->
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-[11px] leading-snug text-gray-900">
                                {{ entry.message }}
                            </p>
                        </div>

                        <!-- Meta: Zeit, Kontext, Auslöser -->
                        <div class="flex flex-wrap items-center gap-2 text-[10px] text-gray-500">
                            <span>{{ entry.createdAtFormatted }}</span>

                            <span v-if="entry.contextLabel" class="inline-flex items-center gap-1">
                                ·
                                <span
                                    class="inline-flex items-center rounded-full border px-1.5 py-0.5 text-[9px]"
                                    :class="contextBadgeClass(entry)"
                                >
                                    {{ entry.contextLabel }}
                                </span>
                            </span>

                            <span v-if="entry.causerName" class="inline-flex items-center gap-1">
                                ·
                                <span
                                    class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-gray-100 text-[9px]"
                                >
                                    {{ entry.causerInitials }}
                                </span>
                                <span>{{ entry.causerName }}</span>
                            </span>
                        </div>

                        <!-- Detail-Änderungen (über extractActivityChanges) -->
                        <div
                            v-if="entry.changes.length"
                            class="mt-1.5 rounded-md bg-gray-50/80 p-2"
                        >
                            <table class="w-full border-collapse text-[10px]">
                                <thead>
                                <tr class="text-gray-500 text-[9px]">
                                    <th class="text-left font-medium pb-1">
                                        {{ t('Field') }}
                                    </th>
                                    <th class="text-left font-medium pb-1">
                                        {{ t('Before') }}
                                    </th>
                                    <th class="text-left font-medium pb-1">
                                        {{ t('After') }}
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr
                                    v-for="change in entry.changes"
                                    :key="change.fieldName + '-' + change.index"
                                    class="align-top"
                                >
                                    <td class="pr-2 py-0.5 text-gray-700">
                                        {{ fieldLabel(change.fieldName) }}
                                    </td>
                                    <td class="pr-2 py-0.5 text-gray-500">
                                        {{ formatFieldValue(change.fieldName, change.oldValue) }}
                                    </td>
                                    <td class="py-0.5 text-gray-900">
                                        {{ formatFieldValue(change.fieldName, change.newValue) }}
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </li>
            </ol>
        </div>
    </BaseModal>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseModal from '@/Components/Modals/BaseModal.vue'
import { useShiftPlanRequest } from '../../ShiftPlanRequests/components/useShiftPlanRequest.js'

type AnyRecord = Record<string, any>

type ShiftActivityProperties = {
    translation_key?: string | null
    translation_key_placeholder_values?: any[] | null
    context?: 'normal' | 'in_workflow' | 'post_commit' | string | null
    shift_id?: number | null
    craft_id?: number | null
    project_id?: number | null
    current_request_id?: number | null
    attributes?: AnyRecord
    old?: AnyRecord
    field_changes?: AnyRecord
    [key: string]: any
}

type RawShiftActivity = {
    id: number
    log_name: string
    description: string
    subject_type: string
    event: string
    subject_id: number
    created_at: string
    updated_at: string
    properties: ShiftActivityProperties
    causer: {
        id: number
        first_name: string | null
        last_name: string | null
        full_name: string | null
        profile_photo_url: string | null
        type: string | null
    } | null
}

// Props / Emits
const props = defineProps<{
    logs: RawShiftActivity[]
}>()

const emit = defineEmits<{
    (e: 'close'): void
}>()

const {
    t,
    fieldLabel,
    formatFieldValue,
    formatDateTime,
    activityContext,
    extractActivityChanges,
    activityTranslation,
} = useShiftPlanRequest()

const handleClose = () => {
    emit('close')
}

// Normalisierte Struktur für das Frontend
type NormalizedChange = {
    index: number
    fieldName: string
    oldValue: any
    newValue: any
}

type NormalizedLogEntry = {
    id: number
    message: string
    createdAt: string
    createdAtFormatted: string
    context: string | null
    contextLabel: string | null
    causerName: string | null
    causerInitials: string | null
    iconLetter: string
    level: 'default' | 'success' | 'warning' | 'danger'
    changes: NormalizedChange[]
}

// Causer-Name + Initialen wie im Drawer
const getCauserName = (log: RawShiftActivity): { name: string | null; initials: string | null } => {
    const causer = log.causer
    if (!causer) {
        return {
            name: t('System'),
            initials: 'S',
        }
    }

    const name =
        causer.full_name ||
        [causer.first_name, causer.last_name].filter(Boolean).join(' ') ||
        null

    if (!name) {
        return {
            name: t('Unknown user'),
            initials: '?',
        }
    }

    const initials = name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p.charAt(0))
        .join('')
        .toUpperCase()

    return {
        name,
        initials: initials || name.charAt(0).toUpperCase(),
    }
}

// Level anhand von description / event / translation_key erkennen (nur fürs Styling)
const detectLevel = (log: RawShiftActivity): NormalizedLogEntry['level'] => {
    const desc = log.description || ''
    const ev = log.event || ''
    const key = log.properties?.translation_key || ''

    if (
        desc.includes('assigned') ||
        ev === 'assigned' ||
        key.includes('added to shift') ||
        key.includes('assigned_to_shift')
    ) {
        return 'success'
    }

    if (
        desc.includes('removed') ||
        ev === 'removed' ||
        key.includes('removed from shift') ||
        key.includes('removed_from_shift')
    ) {
        return 'danger'
    }

    if (
        desc.includes('updated') ||
        ev.includes('updated') ||
        key.includes('updated') ||
        key === 'shift_updated'
    ) {
        return 'warning'
    }

    if (key === 'committed_shift_change_reverted' || desc.includes('reverted')) {
        return 'warning'
    }

    return 'default'
}

// Icon-Buchstabe für das Badge
const iconForLevel = (level: NormalizedLogEntry['level']): string => {
    switch (level) {
        case 'success':
            return 'A' // Assigned / Added
        case 'danger':
            return 'R' // Removed
        case 'warning':
            return 'U' // Updated / Reverted
        default:
            return 'H' // History
    }
}

// Label für Kontext (nutzt activityContext aus useShiftPlanRequest)
const getContextLabel = (log: RawShiftActivity): string | null => {
    const label = activityContext(log)
    // activityContext gibt bereits übersetzte Texte zurück (Workflow, Post-commit, Normal)
    return label || null
}

// Detail-Änderungen über extractActivityChanges (wie im Drawer)
const normalizeChanges = (log: RawShiftActivity): NormalizedChange[] => {
    const changes = extractActivityChanges(log) || []

    return changes.map((fc: any, index: number) => ({
        index,
        fieldName: fc.fieldName,
        oldValue: fc.old_label ?? fc.old ?? null,
        newValue: fc.new_label ?? fc.new ?? null,
    }))
}

// Nachrichtentext – **korrekt über i18n-Keys**:
// 1. activityTranslation(activity) → nutzt translation_key + translation_key_placeholder_values
//    z.B. "User {0} removed from shift", "Change reverted ({0})", "{0} removed from shift as {1} for {2} ({3})"
// 2. Fallback auf description / event als i18n-Key
const messageForLog = (log: RawShiftActivity): string => {
    const msgFromTranslationKey = activityTranslation(log)
    if (msgFromTranslationKey) {
        return msgFromTranslationKey
    }

    if (log.description) {
        return t(log.description)
    }

    if (log.event) {
        return t(log.event)
    }

    return t('Change in shift')
}

// Sortierung + Normalisierung
const sortedLogs = computed<NormalizedLogEntry[]>(() => {
    const raw = props.logs || []

    return [...raw]
        .sort((a, b) => (a.created_at > b.created_at ? -1 : 1)) // neueste zuerst
        .map<NormalizedLogEntry>((log) => {
            const level = detectLevel(log)
            const { name: causerName, initials: causerInitials } = getCauserName(log)
            const createdAt = log.created_at
            const createdAtFormatted = formatDateTime(createdAt)
            const context = log.properties?.context || null
            const contextLabel = getContextLabel(log)
            const message = messageForLog(log)
            const changes = normalizeChanges(log)

            return {
                id: log.id,
                message,
                createdAt,
                createdAtFormatted,
                context,
                contextLabel,
                causerName,
                causerInitials,
                iconLetter: iconForLevel(level),
                level,
                changes,
            }
        })
})

// Styling: Icon-Badge
const bubbleClass = (entry: NormalizedLogEntry): string => {
    switch (entry.level) {
        case 'success':
            return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100'
        case 'warning':
            return 'bg-amber-50 text-amber-700 ring-1 ring-amber-100'
        case 'danger':
            return 'bg-rose-50 text-rose-700 ring-1 ring-rose-100'
        default:
            return 'bg-gray-50 text-gray-700 ring-1 ring-gray-100'
    }
}

// Styling: Kontext-Badge
const contextBadgeClass = (entry: NormalizedLogEntry): string => {
    switch (entry.context) {
        case 'in_workflow':
        case 'workflow':
            return 'border-amber-200 bg-amber-50 text-amber-700'
        case 'post_commit':
            return 'border-emerald-200 bg-emerald-50 text-emerald-700'
        case 'normal':
            return 'border-gray-200 bg-gray-50 text-gray-700'
        default:
            return 'border-gray-200 bg-gray-50 text-gray-700'
    }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease-out;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
