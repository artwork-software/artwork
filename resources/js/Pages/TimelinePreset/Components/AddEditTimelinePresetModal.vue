<template>
    <ArtworkBaseModal
        :title="createOrUpdateForm.id ? 'Edit timeline preset' : 'Add timeline preset'"
        modal-size="sm:max-w-2xl"
        @close="emit('close')"
    >
        <form id="addEditTimelinePresetForm" class="space-y-4" @submit.prevent="updateOrCreate">
            <BaseInput
                id="timeline_preset_name"
                v-model="createOrUpdateForm.name"
                label="Name"
                required
                :error="createOrUpdateForm.errors.name"
            />
            <div>
                <div class="flex justify-end">
                    <button
                        type="button"
                        class="cursor-pointer text-xs font-medium text-accent-700 underline underline-offset-2 hover:text-accent-800"
                        :aria-expanded="showExamples"
                        @click="showExamples = !showExamples"
                    >
                        {{ showExamples ? $t('Hide examples') : $t('Show examples') }}
                    </button>
                </div>
                <div v-if="showExamples" class="mt-2 flex flex-wrap gap-2">
                    <button
                        v-for="(example, index) in exampleText"
                        :key="index"
                        type="button"
                        class="cursor-pointer rounded-full border border-border-subtle bg-surface-sunken px-2 py-0.5 text-[11px] text-text transition-colors duration-150 hover:bg-surface-hover motion-reduce:transition-none"
                        @click="addExample(example)"
                    >
                        {{ example }}
                    </button>
                </div>
            </div>
            <BaseTextarea
                id="rawText"
                v-model="rawText"
                label="Enter your times here. Each line is interpreted as a separate entry."
                rows="15"
                :error="createOrUpdateForm.errors.dataset"
            />
            <BaseAlertComponent
                type="warning"
                title="Important"
                message="The times must be entered in the format HH:MM or HH.MM. If the time is not displayed in the preview, it is not formatted correctly."
                use-translation
                class="!mb-0"
            />

            <div>
                <h3 class="font-lexend text-sm font-medium text-text">{{ $t('Preview') }}</h3>
                <p class="text-xs text-text-muted">{{ $t('This is how your entries will look like') }}</p>
                <ul v-if="previewEntries.length > 0" class="mt-3 flex flex-wrap gap-2">
                    <li
                        v-for="entry in previewEntries"
                        :key="entry.lineIndex"
                        class="flex items-center gap-x-1.5 rounded-full border border-accent-200 bg-accent-50 py-0.5 pr-1 pl-3 text-sm text-accent-700"
                    >
                        <span class="tabular-nums">{{ formatEntryTimes(entry) }}</span>
                        <span v-if="entry.description">{{ entry.description }}</span>
                        <button
                            type="button"
                            class="inline-flex size-6 cursor-pointer items-center justify-center rounded-full transition-colors duration-150 hover:bg-accent-100 hover:text-danger motion-reduce:transition-none"
                            :aria-label="$t('Remove')"
                            @click="removeEntry(entry.lineIndex)"
                        >
                            <PropertyIcon name="IconX" class="size-3.5" stroke-width="1.5" aria-hidden="true" />
                        </button>
                    </li>
                </ul>
                <p v-else class="mt-3 rounded-lg border border-dashed border-border px-3 py-2 text-center text-xs text-text-muted">
                    {{ $t('No entries yet') }}
                </p>
            </div>
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="createOrUpdateForm.processing"
                @click="emit('close')"
            />
            <BaseUIButton
                type="submit"
                form="addEditTimelinePresetForm"
                :label="createOrUpdateForm.id ? 'Update' : 'Create'"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="createOrUpdateForm.processing"
                :disabled="!String(createOrUpdateForm.name ?? '').trim()"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, onMounted, ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";

defineOptions({name: 'AddEditTimelinePresetModal'})

const props = defineProps({
    presetToEdit: {
        type: Object,
        default: null
    }
})

const { t } = useI18n();

const emit = defineEmits(['close'])

const showExamples = ref(false);
const exampleText = ref([]);

const rawText = ref('');

const createOrUpdateForm = useForm({
    id: props.presetToEdit ? props.presetToEdit.id : null,
    name: props.presetToEdit ? props.presetToEdit.name : '',
    dataset: props.presetToEdit ? props.presetToEdit.times : [],
})

const isValidTime = (time) => {
    const match = time.match(/^(\d{1,2}):(\d{2})$/);
    if (!match) return false;

    const hour = parseInt(match[1], 10);
    const minute = parseInt(match[2], 10);
    return hour >= 0 && hour < 24 && minute >= 0 && minute < 60;
};

const parseTextToDataset = (text) => {
    const lines = text.split("\n").map((line) => line.trim());
    const dataset = [];

    for (const [lineIndex, line] of lines.entries()) {
        if (/(ab|from)\s*(\d{1,2}[:h.]\d{2})\s*(bis|to)\s*(\d{1,2}[:h.]\d{2})\s*(.*)/i.test(line)) {
            // Format: "ab 14:45 bis 16:00 Beschreibung"
            const matches = line.match(/(ab|from)\s*(\d{1,2}[:h.]\d{2})\s*(bis|to)\s*(\d{1,2}[:h.]\d{2})\s*(.*)/i);
            const start = matches[2].replace(/[h.]/g, ":");
            const end = matches[4].replace(/[h.]/g, ":");

            if (isValidTime(start) && isValidTime(end)) {
                dataset.push({ lineIndex, start, end, description: matches[5].trim() });
            }
        } else if (/(\d{1,2}[:h.]\d{2})\s*[–\-]\s*(\d{1,2}[:h.]\d{2})\s*(.*)/i.test(line)) {
            // Format: "14:45 - 16:00 Beschreibung"
            const matches = line.match(/(\d{1,2}[:h.]\d{2})\s*[–\-]\s*(\d{1,2}[:h.]\d{2})\s*(.*)/i);
            const start = matches[1].replace(/[h.]/g, ":");
            const end = matches[2].replace(/[h.]/g, ":");

            if (isValidTime(start) && isValidTime(end)) {
                dataset.push({ lineIndex, start, end, description: matches[3].trim() });
            }
        } else if (/(ab|from)\s*(\d{1,2}[:h.]\d{2})\s*(.*)/i.test(line)) {
            // Format: "ab 14:45 Beschreibung"
            const matches = line.match(/(ab|from)\s*(\d{1,2}[:h.]\d{2})\s*(.*)/i);
            const start = matches[2].replace(/[h.]/g, ":");

            if (isValidTime(start)) {
                dataset.push({ lineIndex, start, end: null, description: matches[3].trim() });
            }
        } else if (/(bis|to|-)\s*(\d{1,2}[:h.]\d{2})\s*(.*)?/i.test(line)) {
            // Format: "bis 16:00 Beschreibung" oder "- 16:00 Beschreibung"
            const matches = line.match(/(bis|to|-)\s*(\d{1,2}[:h.]\d{2})\s*(.*)?/i);
            const end = matches[2].replace(/[h.]/g, ":");

            if (isValidTime(end)) {
                dataset.push({ lineIndex, start: null, end, description: matches[3]?.trim() || "" });
            }
        } else if (/^(\d{1,2}[:h.]\d{2})\s+(.*)$/i.test(line)) {
            // Format: "14:45 Beschreibung" (interpretiert als "ab 14:45 Beschreibung")
            const matches = line.match(/^(\d{1,2}[:h.]\d{2})\s+(.*)$/i);
            const start = matches[1].replace(/[h.]/g, ":");

            if (isValidTime(start)) {
                dataset.push({ lineIndex, start, end: null, description: matches[2].trim() });
            }
        }
    }

    return dataset;
};


const generateRandomExamples = () => {
    const activityNames = [
        "Aufbau Bühne",
        "Abbau Technik",
        "Soundcheck",
        "Lichtcheck",
        "Generalprobe",
        "Besprechung Bühnencrew",
        "Planung Catering",
        "Akkreditierung Künstler",
        "Einlass Publikum",
        "Start Veranstaltung",
        "Pausenplanung",
        "Konzertprobe",
        "Abbau Bühne",
        "Planung Sicherheit",
        "Abbau Lichttechnik",
        "Abbau Audiotechnik",
        "Nachbesprechung Event",
        "Logistik für Abbau",
        "Transport Equipment",
        "Planung nächstes Event",
    ];
    const languages = ["de", "en"]; // Unterstützt Deutsch und Englisch

    // Hilfsfunktion: Zufällige Zeit generieren
    const randomTime = () => {
        const hour = Math.floor(Math.random() * 24).toString().padStart(2, "0");
        const minute = Math.floor(Math.random() * 60).toString().padStart(2, "0");
        return `${hour}:${minute}`;
    };

    // Generiere 10 zufällige Beispiele
    for (let i = 0; i < 10; i++) {
        const lang = languages[Math.floor(Math.random() * languages.length)];
        const startTime = randomTime();
        const endTime = randomTime();
        const activity =
            activityNames[Math.floor(Math.random() * activityNames.length)];

        if (lang === "de") {
            const formats = [
                `ab ${startTime} bis ${endTime} ${activity}`,
                `${startTime} - ${endTime} ${activity}`,
                `ab ${startTime} ${activity}`,
                `bis ${endTime} ${activity}`,
            ];
            exampleText.value.push(
                formats[Math.floor(Math.random() * formats.length)]
            );
        } else {
            const formats = [
                `from ${startTime} to ${endTime} ${activity}`,
                `${startTime} - ${endTime} ${activity}`,
                `from ${startTime} ${activity}`,
                `to ${endTime} ${activity}`,
            ];
            exampleText.value.push(
                formats[Math.floor(Math.random() * formats.length)]
            );
        }
    }
};

// Vorschau mit Zeilennummer (leere oder ungültige Zeilen erzeugen keinen Eintrag)
const previewEntries = computed(() => parseTextToDataset(rawText.value));

// Gesendeter Datensatz ohne die Zeilennummer
const dataset = computed(() => previewEntries.value.map(({lineIndex, ...entry}) => entry));

// Entfernt die Zeile, aus der der Eintrag stammt (nicht die n-te Zeile des Textes)
const removeEntry = (lineIndex) => {
    const lines = rawText.value.split("\n");
    lines.splice(lineIndex, 1);
    rawText.value = lines.join("\n");
};

const formatEntryTimes = (entry) => {
    if (entry.start && entry.end) {
        return `${entry.start}–${entry.end}`;
    }
    return entry.start ? `${t('from (time)')} ${entry.start}` : `${t('until')} ${entry.end}`;
};

const addExample = (example) => {
    if (rawText.value.length > 0 && rawText.value[rawText.value.length - 1] !== "\n") {
        rawText.value += "\n";
    }

    rawText.value += example;
};

const updateOrCreate = () => {
    createOrUpdateForm.dataset = dataset.value;
    if(createOrUpdateForm.id) {
        createOrUpdateForm.patch(
            route('timeline-presets.update', createOrUpdateForm.id),
            {
                preserveScroll: true,
                onSuccess: () => {
                    emit('close');
                }
            }
        )
    } else {
        createOrUpdateForm.post(
            route('timeline-presets.store'),
            {
                preserveScroll: true,
                onSuccess: () => {
                    emit('close');
                }
            }
        )
    }
}

onMounted(() => {
    generateRandomExamples();

    if (props.presetToEdit){
        // add presetToEdit.times to rawText
        rawText.value = (props.presetToEdit.times ?? []).map((time) => {
            return `${time.start ? `ab ${time.start}` : ''} ${time.end ? `bis ${time.end}` : ''} ${time.description ? time.description : ''}`
        }).join('\n');
    }
});
</script>
