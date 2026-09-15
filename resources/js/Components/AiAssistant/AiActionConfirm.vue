<!--
    Bestaetigungsdialog fuer Schreib-Aktionen (core-ki-bar-confirm; Konzept C4 §7, W2 §7).

    Zeigt genau das, was bestaetigt wird: die Vorschau aus `confirm.request`, Konflikte je Eintrag
    und die verbleibende Frist. Alle Texte kommen aus artwork-ai und werden als Text gerendert,
    nie als HTML (0.3 §10). Das Write-Token holt der Aufrufer (useAiAssistant) — hier gibt es
    keinen Token-Code (C4 §7).
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

                <p v-if="expiresAt" class="mt-3 text-xs text-text-subtle" data-ai-confirm-expiry>
                    {{ expired ? $t('The confirmation window has expired.') : remainingText }}
                </p>

                <div v-if="status === 'pending'" class="mt-4 flex items-center gap-2">
                    <button
                        type="button"
                        data-ai-confirm-accept
                        class="rounded-lg bg-accent-600 px-3 py-1.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="expired"
                        @click="$emit('confirm')"
                    >
                        {{ $t('Confirm') }}
                    </button>
                    <button
                        type="button"
                        data-ai-confirm-cancel
                        class="rounded-lg border border-border-subtle px-3 py-1.5 text-sm text-text"
                        @click="$emit('cancel')"
                    >
                        {{ $t('Cancel') }}
                    </button>
                </div>

                <p v-else-if="status === 'confirming'" class="mt-4 text-xs text-text-subtle" data-ai-confirm-running>
                    {{ $t('The action is being executed...') }}
                </p>

                <p v-else-if="status === 'cancelled'" class="mt-4 text-xs text-text-subtle" data-ai-confirm-cancelled>
                    {{ $t('Action cancelled.') }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
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
    status: {
        type: String,
        default: 'pending',
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

// Der Countdown laeuft in der Komponente, nicht im Zustand: er betrifft nur die Anzeige.
const now = ref(Date.now())
let timer = null

onMounted(() => {
    timer = setInterval(() => {
        now.value = Date.now()
    }, 1000)
})

onBeforeUnmount(() => {
    if (timer !== null) {
        clearInterval(timer)
    }
})

const remainingMs = computed(() => {
    if (!props.expiresAt) {
        return null
    }

    const expiry = Date.parse(props.expiresAt)

    return Number.isNaN(expiry) ? null : expiry - now.value
})

const expired = computed(() => remainingMs.value !== null && remainingMs.value <= 0)

const remainingText = computed(() => {
    if (remainingMs.value === null) {
        return ''
    }

    const totalSeconds = Math.max(0, Math.floor(remainingMs.value / 1000))
    const minutes = Math.floor(totalSeconds / 60)
    const seconds = `${totalSeconds % 60}`.padStart(2, '0')

    return t('Confirm within :time', { time: `${minutes}:${seconds}` })
})
</script>
