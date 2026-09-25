<template>
    <div class="space-y-2">
        <div class="text-sm font-bold text-text">{{ title }}</div>
        <p v-if="contacts.length === 0" class="text-xs text-text-subtle">{{ $t('No entries') }}</p>
        <div
            v-for="contact in contacts"
            :key="contact.id"
            class="break-inside-avoid rounded border border-border-subtle px-3 py-2"
        >
            <div class="flex items-baseline justify-between gap-3">
                <span class="text-sm font-semibold text-text">{{ contact.display_name }}</span>
                <span v-if="contact.contact_type" class="shrink-0 text-[11px] uppercase tracking-wide text-text-subtle">
                    {{ $t(contact.contact_type.name) }}
                </span>
            </div>
            <dl v-if="contact.fields.length" class="mt-1.5 grid grid-cols-2 gap-x-4 gap-y-1">
                <div v-for="field in contact.fields" :key="field.property_id" class="min-w-0">
                    <dt class="text-[10px] uppercase tracking-wide text-text-subtle">{{ $t(field.name) }}</dt>
                    <dd class="whitespace-pre-line break-words text-xs text-text">{{ formatValue(field) }}</dd>
                </div>
            </dl>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useTranslation } from '@/Composeables/Translation.js'

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    component: {
        type: Object,
        required: true,
    },
})

const $t = useTranslation()

const title = computed(() => props.component.data?.title || props.component.name)
const contacts = computed(() => props.project.crm_contact_lists?.[props.component.id] ?? [])

function formatValue(field) {
    if (field.type === 'checkbox') return field.value === '1' ? $t('Yes') : $t('No')
    if (field.type === 'date' && field.value) return new Date(field.value).toLocaleDateString()
    return field.value
}
</script>
