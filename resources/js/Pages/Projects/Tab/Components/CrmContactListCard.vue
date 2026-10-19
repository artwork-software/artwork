<template>
    <li class="overflow-hidden rounded-lg border border-border-subtle bg-white">
        <div class="flex items-center gap-x-2 px-3 py-2">
            <img :src="contact.profile_photo_url" :alt="contact.display_name" class="size-7 shrink-0 rounded-full object-cover" />
            <button
                type="button"
                class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-1 text-left"
                :aria-expanded="String(expanded)"
                @click="$emit('toggle')"
            >
                <span class="truncate text-sm font-medium text-text">{{ contact.display_name }}</span>
                <span v-if="contact.contact_type" class="inline-flex shrink-0 items-center rounded-full bg-surface-sunken px-2 py-0.5 text-xs text-text-muted">
                    {{ $t(contact.contact_type.name) }}
                </span>
                <span v-if="contact.is_artist && showArtistBadge" class="inline-flex shrink-0 items-center gap-1 rounded-full bg-accent-50 px-2 py-0.5 text-xs text-accent-700">
                    <IconPalette class="size-3.5" stroke-width="1.5" />
                    {{ $t('Artist') }}
                </span>
                <span
                    v-for="role in (showRoleChips ? contact.roles : [])"
                    :key="role"
                    class="inline-flex shrink-0 items-center rounded-full border border-border-subtle px-2 py-0.5 text-xs text-text-muted"
                >
                    {{ role }}
                </span>
                <span
                    v-if="contact.is_external && !contact.reviewed_at"
                    class="inline-flex shrink-0 items-center gap-1 rounded-full bg-warning-surface px-2 py-0.5 text-xs text-warning"
                    :title="$t('Added externally by {name} – not yet reviewed', { name: contact.created_by_external?.name ?? '' })"
                >
                    <IconUserExclamation class="size-3.5" stroke-width="1.5" />
                    {{ $t('External · not reviewed') }}
                </span>
                <span
                    v-else-if="contact.is_external"
                    class="inline-flex shrink-0 items-center gap-1 rounded-full bg-surface-sunken px-2 py-0.5 text-xs text-text-muted"
                    :title="$t('Added externally by {name}', { name: contact.created_by_external?.name ?? '' })"
                >
                    <IconUserCheck class="size-3.5" stroke-width="1.5" />
                    {{ $t('External') }}
                </span>
                <component :is="expanded ? IconChevronUp : IconChevronDown" class="size-4 shrink-0 text-text-subtle" />
            </button>
            <button v-if="canWrite" type="button" class="shrink-0 text-text-subtle hover:text-text" :title="$t('Roles')" :aria-label="$t('Roles')" @click="$emit('edit-roles')">
                <IconTags class="size-4" stroke-width="1.5" />
            </button>
            <button v-if="contact.can_edit" type="button" class="shrink-0 text-text-subtle hover:text-text" :title="$t('Edit')" :aria-label="$t('Edit')" @click="$emit('edit')">
                <IconPencil class="size-4" stroke-width="1.5" />
            </button>
            <button v-if="contact.can_remove" type="button" class="shrink-0 text-text-subtle hover:text-danger" :title="$t('Remove from list')" :aria-label="$t('Remove from list')" @click="$emit('remove')">
                <IconLinkOff class="size-4" stroke-width="1.5" />
            </button>
        </div>

        <!-- Mögliche Dubletten extern angelegter Kontakte -->
        <div v-if="contact.possible_duplicates?.length" class="border-t border-warning-border bg-warning-surface px-3 py-2 text-xs text-warning">
            <p class="font-medium">{{ $t('Possible duplicate in the CRM') }}</p>
            <div v-for="duplicate in contact.possible_duplicates" :key="duplicate.id" class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                <a v-if="canViewCrm" :href="route('crm.contacts.show', duplicate.id)" target="_blank" class="underline">{{ duplicate.display_name }}</a>
                <span v-else>{{ duplicate.display_name }}</span>
                <button v-if="canMerge" type="button" class="font-medium underline" @click="$emit('merge', duplicate)">
                    {{ $t('Merge into existing contact') }}
                </button>
            </div>
        </div>

        <div v-if="expanded" class="border-t border-border-subtle px-3 py-2">
            <dl v-if="contact.fields.length" class="grid grid-cols-1 gap-x-4 gap-y-1.5 sm:grid-cols-2">
                <div v-for="field in contact.fields" :key="field.property_id">
                    <dt class="text-xs text-text-subtle">{{ $t(field.name) }}</dt>
                    <dd class="whitespace-pre-line break-words text-sm text-text">
                        <a v-if="field.type === 'link' && isSafeHttpUrl(field.value)" :href="field.value" target="_blank" rel="noopener" class="text-accent-600 hover:underline">{{ field.value }}</a>
                        <template v-else>{{ formatValue(field) }}</template>
                    </dd>
                </div>
            </dl>
            <p v-else class="text-xs text-text-subtle">{{ $t('No information available') }}</p>
            <p v-if="contact.is_external" class="mt-2 text-xs text-text-subtle">
                {{ $t('Added externally by {name}', { name: contact.created_by_external?.name ?? '' }) }}
                <template v-if="contact.reviewed_at"> · {{ $t('confirmed by {name}', { name: contact.reviewed_by ?? '' }) }}</template>
            </p>
            <a v-if="canViewCrm" :href="route('crm.contacts.show', contact.id)" target="_blank" class="mt-1.5 inline-flex items-center gap-x-1 text-xs text-accent-600 hover:underline">
                <IconExternalLink class="size-3.5" stroke-width="1.5" />
                {{ $t('Open in CRM') }}
            </a>
        </div>
    </li>
</template>

<script setup>
import { crmPropertyDisplayText } from '@/Helper/CrmPropertyValue.js'
import { isSafeHttpUrl } from '@/Helper/SafeUrl.js'
import { useTranslation } from '@/Composeables/Translation.js'
import {
    IconChevronDown,
    IconChevronUp,
    IconExternalLink,
    IconLinkOff,
    IconPalette,
    IconPencil,
    IconTags,
    IconUserCheck,
    IconUserExclamation,
} from '@tabler/icons-vue'

defineProps({
    contact: { type: Object, required: true },
    expanded: { type: Boolean, default: false },
    canWrite: { type: Boolean, default: false },
    canViewCrm: { type: Boolean, default: false },
    canMerge: { type: Boolean, default: false },
    // in Rollen-Gruppen steht die Rolle schon als Überschrift
    showRoleChips: { type: Boolean, default: true },
    showArtistBadge: { type: Boolean, default: true },
})

defineEmits(['toggle', 'edit', 'edit-roles', 'remove', 'merge'])

const $t = useTranslation()

function formatValue(field) {
    if (field.type === 'checkbox') return field.value === '1' ? $t('Yes') : $t('No')
    if (field.type === 'multiselect') return crmPropertyDisplayText(field.value, field.type)
    if (field.type === 'date' && field.value) return new Date(field.value).toLocaleDateString()
    return field.value
}
</script>
