<!--
    Auswahl-Liste der KI-Bar (core-ki-bar-choice; Protokoll 1.1, contracts/sse-events/choice.request).

    Das Modell fragt nach einem Raum, einer Terminart oder einem Projekt und schickt die Kandidaten
    mit. Gefiltert wird ausschliesslich hier im Browser ueber die mitgelieferte Liste — es gibt
    keinen zweiten Aufruf. Ein Klick sendet das Label als ganz normale Nachricht; das Modell
    erkennt die Antwort aus dem Gespraechskontext (Brief).

    Alle Texte kommen aus artwork-ai und stehen als Text im Template, nie als HTML (0.3 §10).
-->
<template>
    <div class="rounded-xl border border-border-subtle bg-surface-canvas p-4" data-ai-choice>
        <p class="text-sm font-medium text-text break-words" data-ai-choice-question>{{ question }}</p>

        <input
            ref="filterField"
            v-model="filter"
            type="text"
            :placeholder="$t('Narrow down')"
            class="mt-3 w-full rounded-lg border border-border-subtle bg-surface px-3 py-1.5 text-sm text-text placeholder:text-text-subtle focus:border-accent-500 focus:ring-0"
            data-ai-choice-filter
            @keydown.esc.stop.prevent="$emit('dismiss')"
        />

        <ul v-if="matches.length > 0" class="mt-2 max-h-56 space-y-1 overflow-y-auto">
            <li v-for="option in matches" :key="option.id">
                <button
                    type="button"
                    class="w-full rounded-lg px-3 py-2 text-left text-sm text-text hover:bg-surface-sunken"
                    data-ai-choice-option
                    @click="$emit('choose', option)"
                >
                    <span class="break-words">{{ option.label }}</span>
                    <span v-if="option.hint" class="block text-xs text-text-subtle break-words">{{ option.hint }}</span>
                </button>
            </li>
        </ul>

        <p v-else class="mt-2 text-xs text-text-subtle" data-ai-choice-empty>
            {{ $t('No match. Change the filter or just type your answer.') }}
        </p>

        <p v-if="truncated" class="mt-2 text-xs text-text-subtle" data-ai-choice-truncated>
            {{ $t('More matches — narrow your search') }}
        </p>

        <button
            type="button"
            class="mt-3 text-xs text-text-subtle underline"
            data-ai-choice-dismiss
            @click="$emit('dismiss')"
        >
            {{ $t('Cancel') }}
        </button>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from 'vue'

const props = defineProps({
    question: {
        type: String,
        default: '',
    },
    options: {
        type: Array,
        default: () => [],
    },
    truncated: {
        type: Boolean,
        default: false,
    },
})

defineEmits(['choose', 'dismiss'])

const filter = ref('')
const filterField = ref(null)

const matches = computed(() => {
    const needle = filter.value.trim().toLowerCase()

    if (needle === '') {
        return props.options
    }

    return props.options.filter((option) => {
        const haystack = `${option.label ?? ''} ${option.hint ?? ''}`.toLowerCase()

        return haystack.includes(needle)
    })
})

// Der Fokus geht ins Filterfeld: tippen grenzt ein, Enter waehlt nichts aus Versehen aus.
onMounted(async () => {
    await nextTick()
    filterField.value?.focus()
})
</script>
