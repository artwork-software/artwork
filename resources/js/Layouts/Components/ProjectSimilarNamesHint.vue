<template>
    <div
        v-if="matches.length > 0"
        class="mt-2 rounded-[8px] border border-warning-border bg-warning-surface px-3 py-2.5"
        role="status"
        aria-live="polite"
        data-testid="project-similar-names-hint"
    >
        <div class="flex items-start gap-x-2">
            <IconAlertTriangle class="size-5 shrink-0 text-warning" aria-hidden="true" />
            <div class="min-w-0 flex-1">
                <p class="text-[13px] font-semibold text-warning">
                    {{ hasIdenticalMatch ? t('A project or project group with this name already exists') : t('There are projects or project groups with a similar name') }}
                </p>
                <p class="text-[13px] text-warning">
                    {{ t('Check whether you mean one of them. You can still create it.') }}
                </p>
                <ul class="mt-2 space-y-1">
                    <li v-for="match in matches" :key="match.id" class="flex items-center gap-x-2 text-[13px] text-text">
                        <span class="inline-block size-1.5 shrink-0 rounded-full" :class="match.is_group ? 'bg-success' : 'bg-accent-500'"></span>
                        <a
                            v-if="match.url"
                            :href="match.url"
                            target="_blank"
                            rel="noopener"
                            class="truncate font-medium underline decoration-border hover:decoration-text"
                        >{{ match.name }}</a>
                        <span v-else class="truncate font-medium">{{ match.name }}</span>
                        <span v-if="periodLabel(match)" class="shrink-0 text-[11px] text-text-subtle">{{ periodLabel(match) }}</span>
                        <div class="ml-auto flex shrink-0 items-center gap-1.5">
                            <span
                                v-if="match.match === 'identical'"
                                class="inline-flex items-center rounded-full bg-warning-surface px-2 py-0.5 text-[11px] font-medium text-warning ring-1 ring-warning-border"
                            >{{ t('Same name') }}</span>
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1"
                                :class="match.is_group ? 'bg-success-surface text-success ring-success-border' : 'bg-surface-sunken text-text-muted ring-border-subtle'"
                            >{{ match.is_group ? t('Project group') : t('Project') }}</span>
                            <span
                                v-if="match.marked_as_done"
                                class="inline-flex items-center rounded-full bg-surface-sunken px-2 py-0.5 text-[11px] font-medium text-text-muted ring-1 ring-border-subtle"
                            >{{ t('Done') }}</span>
                        </div>
                    </li>
                </ul>
                <p v-if="creationHeld" class="mt-2 text-[13px] font-semibold text-warning">
                    {{ t('Click again to create it anyway.') }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import axios from 'axios';
import { IconAlertTriangle } from '@tabler/icons-vue';
import { useTranslation } from '@/Composeables/Translation.js';
import {
    projectPeriodLabel,
    shouldCheckSimilarNames,
    shouldHoldCreation,
    similarNameKey,
} from '@/Helper/projectSimilarNames.js';

const props = defineProps({
    name: {
        type: String,
        default: '',
    },
});

const t = useTranslation();
const DEBOUNCE_MS = 400;

const matches = ref([]);
// Für welche Eingabe die angezeigten Treffer gelten und ob sie schon beim Tippen zu sehen waren
const checkedKey = ref(null);
const visibleKey = ref(null);
const creationHeld = ref(false);
let debounceTimer = null;
let requestSequence = 0;
let pendingCheck = null;

const hasIdenticalMatch = computed(() => matches.value.some((match) => match.match === 'identical'));

const periodLabel = (match) => projectPeriodLabel(match.first_event_date, match.last_event_date);

const runCheck = (name) => {
    const key = similarNameKey(name);
    const sequence = ++requestSequence;
    if (!shouldCheckSimilarNames(name)) {
        matches.value = [];
        checkedKey.value = key;
        pendingCheck = Promise.resolve();
        return pendingCheck;
    }

    pendingCheck = axios.get(route('projects.similar-names'), { params: { name }, skipErrorToast: true })
        .then(({ data }) => {
            if (sequence !== requestSequence) {
                return;
            }
            matches.value = Array.isArray(data?.matches) ? data.matches : [];
            checkedKey.value = key;
        })
        .catch(() => {
            // Der Hinweis ist eine Hilfe: ein Fehler darf die Anlage nicht blockieren
            if (sequence === requestSequence) {
                matches.value = [];
                checkedKey.value = key;
            }
        });

    return pendingCheck;
};

const checkWhileTyping = async (name) => {
    await runCheck(name);
    if (checkedKey.value === similarNameKey(name)) {
        visibleKey.value = checkedKey.value;
    }
};

watch(() => props.name, (name) => {
    creationHeld.value = false;
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => checkWhileTyping(name), DEBOUNCE_MS);
});

// Beim Umschalten Projekt/Projektgruppe steht der Name schon da
onMounted(() => {
    if (shouldCheckSimilarNames(props.name)) {
        checkWhileTyping(props.name);
    }
});

onBeforeUnmount(() => clearTimeout(debounceTimer));

/**
 * Vor dem Anlegen: Prüfung für die aktuelle Eingabe nachholen. Tauchen Treffer erst jetzt auf,
 * hält der erste Klick an, damit sie gesehen werden; der nächste Klick legt trotzdem an.
 *
 * @returns {Promise<boolean>} true = anlegen
 */
const confirmBeforeCreate = async () => {
    const currentKey = similarNameKey(props.name);
    if (checkedKey.value !== currentKey) {
        clearTimeout(debounceTimer);
        await runCheck(props.name);
    } else if (pendingCheck) {
        await pendingCheck;
    }

    if (shouldHoldCreation({ matches: matches.value, visibleKey: visibleKey.value, currentKey })) {
        visibleKey.value = currentKey;
        creationHeld.value = true;
        return false;
    }

    return true;
};

defineExpose({ confirmBeforeCreate });
</script>
