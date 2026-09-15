<!--
    Bestaetigungsdialog fuer Schreib-Aktionen (core-ki-bar-ui, Konzept C4 §7, W2).

    In dieser Version bewusst nur der Vertrag: Props `preview` und `expiresAt`, Events `confirm`
    und `cancel`. Die Knoepfe sind deaktiviert — Fall C wird erst in W2 freigeschaltet, und ohne
    Write-Token gibt es hier nichts auszufuehren. Alle Texte kommen aus artwork-ai und werden
    deshalb als Text gerendert, nie als HTML (0.3 §10).
-->
<template>
    <div class="rounded-xl border border-warning-border bg-warning-surface p-4" data-ai-confirm>
        <div class="flex items-start gap-3">
            <PropertyIcon name="IconAlertTriangle" class="size-5 shrink-0 text-warning" :stroke-width="1.5" aria-hidden="true" />
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-text">{{ $t('Waiting for confirmation') }}</p>
                <p class="mt-1 text-sm text-text break-words">{{ preview?.summary }}</p>

                <ul class="mt-3 space-y-2">
                    <li v-for="(item, index) in preview?.items ?? []" :key="index" class="text-sm text-text">
                        <span class="font-medium">{{ actionLabel(item.action) }}:</span>
                        <span class="break-words">{{ item.label }}</span>
                        <ul v-if="item.conflicts?.length" class="mt-1 ml-4 list-disc text-xs text-danger">
                            <li v-for="(conflict, conflictIndex) in item.conflicts" :key="conflictIndex">{{ conflict }}</li>
                        </ul>
                    </li>
                </ul>

                <p v-if="expiresAt" class="mt-3 text-xs text-text-subtle">
                    {{ $t('Valid until') }}: {{ formattedExpiry }}
                </p>

                <div class="mt-4 flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-lg bg-accent-600 px-3 py-1.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
                        disabled
                        @click="$emit('confirm')"
                    >
                        {{ $t('Confirm') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-border-subtle px-3 py-1.5 text-sm text-text disabled:cursor-not-allowed disabled:opacity-50"
                        disabled
                        @click="$emit('cancel')"
                    >
                        {{ $t('Cancel') }}
                    </button>
                    <span class="text-xs text-text-subtle">{{ $t('Actions are not enabled in this version yet.') }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

const props = defineProps({
    preview: {
        type: Object,
        default: () => ({ summary: '', items: [] }),
    },
    expiresAt: {
        type: String,
        default: null,
    },
})

defineEmits(['confirm', 'cancel'])

const { t } = useI18n()

const ACTION_LABELS = {
    create: 'Create',
    update: 'Update',
    delete: 'Delete',
}

const actionLabel = (action) => t(ACTION_LABELS[action] ?? 'Change')

const formattedExpiry = computed(() => {
    if (!props.expiresAt) {
        return ''
    }

    const date = new Date(props.expiresAt)

    return Number.isNaN(date.getTime()) ? '' : date.toLocaleTimeString()
})
</script>
