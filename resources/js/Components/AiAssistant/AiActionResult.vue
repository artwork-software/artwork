<!--
    Ergebnis einer ausgefuehrten Aktion (core-ki-bar-confirm; Konzept W3 §3).

    Zeigt die Links auf die betroffenen Objekte und „Rueckgaengig" mit Restzeit. Nach Ablauf der
    Frist oder nach der Ruecknahme verschwindet der Knopf. Die Rueckfrage ist ein zweiter Klick im
    selben Bereich — ein Browser-Dialog waere hier weder gestaltbar noch testbar.
-->
<template>
    <div class="rounded-xl border border-border-subtle bg-surface p-3" data-ai-result>
        <p v-if="links.length > 0" class="text-xs text-text-subtle">
            {{ $t('Affected entries') }}:
            <a
                v-for="(link, index) in links"
                :key="link.id"
                :href="link.url"
                class="underline hover:text-text"
                data-ai-result-link
            >{{ index + 1 }}<span v-if="index < links.length - 1">, </span></a>
        </p>

        <p v-if="status === 'reverted'" class="mt-1 text-xs text-text-subtle" data-ai-result-reverted>
            {{ $t('The action was undone.') }}
        </p>

        <template v-else-if="undoAvailable">
            <div class="mt-2 flex items-center gap-2">
                <button
                    v-if="!askingForConfirmation"
                    type="button"
                    data-ai-undo
                    class="text-xs font-semibold text-accent-600 underline"
                    :disabled="status === 'reverting'"
                    @click="askingForConfirmation = true"
                >
                    {{ $t('Undo (:time left)', { time: remainingText }) }}
                </button>

                <template v-else>
                    <span class="text-xs text-text">{{ $t('Really undo?') }}</span>
                    <button
                        type="button"
                        data-ai-undo-confirm
                        class="rounded-lg bg-accent-600 px-2 py-1 text-xs font-semibold text-white"
                        :disabled="status === 'reverting'"
                        @click="$emit('revert')"
                    >
                        {{ $t('Yes, undo') }}
                    </button>
                    <button
                        type="button"
                        class="text-xs text-text-subtle underline"
                        @click="askingForConfirmation = false"
                    >
                        {{ $t('Keep') }}
                    </button>
                </template>
            </div>
        </template>

        <p v-if="error" class="mt-2 text-xs text-danger" data-ai-undo-error>
            {{ error.status === 409 ? $t('Undo is no longer possible — entries were changed.') : $t('Undo failed.') }}
        </p>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
    undoUntil: {
        type: String,
        default: null,
    },
    links: {
        type: Array,
        default: () => [],
    },
    status: {
        type: String,
        default: 'executed',
    },
    error: {
        type: Object,
        default: null,
    },
})

defineEmits(['revert'])

const { t } = useI18n()

const askingForConfirmation = ref(false)
const now = ref(Date.now())
let timer = null

onMounted(() => {
    timer = setInterval(() => {
        now.value = Date.now()
    }, 30000)
})

onBeforeUnmount(() => {
    if (timer !== null) {
        clearInterval(timer)
    }
})

const remainingMs = computed(() => {
    if (!props.undoUntil) {
        return 0
    }

    const until = Date.parse(props.undoUntil)

    return Number.isNaN(until) ? 0 : until - now.value
})

const undoAvailable = computed(() => remainingMs.value > 0)

const remainingText = computed(() => {
    const totalMinutes = Math.max(0, Math.floor(remainingMs.value / 60000))
    const hours = Math.floor(totalMinutes / 60)
    const minutes = `${totalMinutes % 60}`.padStart(2, '0')

    return hours > 0 ? `${hours}:${minutes} h` : t(':count min', { count: totalMinutes })
})
</script>
