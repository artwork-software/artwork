<template>
    <ArtworkBaseModal
        :title="preset ? 'Edit timeline preset' : (timelineToEdit ? 'Edit timeline' : 'Add timeline')"
        description="Define the shift-relevant times. You can create shifts along this timeline."
        modal-size="sm:max-w-2xl"
        @close="emit('close')"
    >
        <form id="addEditTimelineForm" class="space-y-4" @submit.prevent="updateOrCreate">
            <BaseInput
                v-if="preset"
                id="preset_name"
                v-model="presetName"
                label="Name"
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
            />
            <BaseAlertComponent
                type="warning"
                title="Important"
                message="The times must be entered in the format HH:MM or HH.MM. If the time is not displayed in the preview, it is not formatted correctly."
                use-translation
                class="!mb-0"
            />
            <!-- Abnahme CAL-01 Ref. 3.23: erklärt, wofür die Beschreibung dient und wo sie sichtbar wird -->
            <div class="flex items-start gap-x-1.5 text-xs text-text-muted">
                <PropertyIcon name="IconInfoCircle" class="mt-px size-4 shrink-0" stroke-width="1.5" aria-hidden="true" />
                <span>{{ $t('The description appears next to the time in the schedule of the event (shifts tab) and in the daily view of the shift plan — visible to everyone who can see the schedule.') }}</span>
            </div>

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

            <div v-if="saveError" class="rounded-md border border-danger-border bg-danger-surface px-3 py-2" role="alert" aria-live="assertive">
                <p class="text-xs text-danger">{{ saveError }}</p>
            </div>
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="isSaving"
                @click="emit('close')"
            />
            <BaseUIButton
                type="submit"
                form="addEditTimelineForm"
                :label="preset || timelineToEdit ? 'Save' : 'Create'"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="isSaving"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, onMounted, ref} from "vue";
import axios from "axios";
import { useI18n } from "vue-i18n";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";

defineOptions({name: 'AddEditTimelineModal'})

const props = defineProps({
    timelineToEdit: {
        type: Object,
        default: null
    },
    event: {
        type: Object,
        default: null
    },
    preset: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['close'])

const { t } = useI18n();

const showExamples = ref(false);
const exampleText = ref([]);

const rawText = ref('');

const isValidTime = (time) => {
    const [hours, minutes] = time.split(":").map(Number);
    return hours >= 0 && hours < 24 && minutes >= 0 && minutes < 60;
};

const parseTextToDataset = (text) => {
    const lines = text.split("\n").map((line) => line.trim());
    const dataset = [];

    for (const [lineIndex, line] of lines.entries()) {
        // Entferne "Uhr" unabhängig davon, ob es mit oder ohne Leerzeichen hinter der Zeit steht
        const cleanedLine = line.replace(/(\d{1,2}[:h.]\d{2}|\d{1,2})\s*Uhr\b/gi, "$1").trim();

        if (/(ab|from)\s*(\d{1,2}[:h.]\d{2})\s*(bis|to)\s*(\d{1,2}[:h.]\d{2})\s*(.*)/i.test(cleanedLine)) {
            // Format: "ab 14:45 bis 16:00 Beschreibung"
            const matches = cleanedLine.match(/(ab|from)\s*(\d{1,2}[:h.]\d{2})\s*(bis|to)\s*(\d{1,2}[:h.]\d{2})\s*(.*)/i);
            const start = matches[2].replace(/[h.]/g, ":");
            const end = matches[4].replace(/[h.]/g, ":");
            let description = matches[5].trim();

            if (isValidTime(start) && isValidTime(end)) {
                dataset.push({ lineIndex, start, end, description });
            }
        } else if (/(\d{1,2}[:h.]\d{2}|\d{1,2})\s*[–\-]\s*(\d{1,2}[:h.]\d{2}|\d{1,2})\s*(.*)/i.test(cleanedLine)) {
            // Format: "14:45 - 16:00 Beschreibung" oder "14 - 16 Beschreibung"
            const matches = cleanedLine.match(/(\d{1,2}[:h.]\d{2}|\d{1,2})\s*[–\-]\s*(\d{1,2}[:h.]\d{2}|\d{1,2})\s*(.*)/i);
            const start = formatTime(matches[1]);
            const end = formatTime(matches[2]);
            let description = matches[3].trim();

            if (isValidTime(start) && isValidTime(end)) {
                dataset.push({ lineIndex, start, end, description });
            }
        } else if (/(ab|from)\s*(\d{1,2}[:h.]\d{2}|\d{1,2})\s*(.*)/i.test(cleanedLine)) {
            // Format: "ab 14:45 Beschreibung" oder "ab 14 Beschreibung"
            const matches = cleanedLine.match(/(ab|from)\s*(\d{1,2}[:h.]\d{2}|\d{1,2})\s*(.*)/i);
            const start = formatTime(matches[2]);
            let description = matches[3].trim();

            if (isValidTime(start)) {
                dataset.push({ lineIndex, start, end: null, description });
            }
        } else if (/(bis|to|-)\s*(\d{1,2}[:h.]\d{2}|\d{1,2})\s*(.*)?/i.test(cleanedLine)) {
            // Format: "bis 16:00 Beschreibung" oder "- 16 Beschreibung"
            const matches = cleanedLine.match(/(bis|to|-)\s*(\d{1,2}[:h.]\d{2}|\d{1,2})\s*(.*)?/i);
            const end = formatTime(matches[2]);
            let description = matches[3]?.trim() || "";

            if (isValidTime(end)) {
                dataset.push({ lineIndex, start: null, end, description });
            }
        } else if (/^(\d{1,2}[:h.]\d{2}|\d{1,2})\s+(.*)$/i.test(cleanedLine)) {
            // Format: "14:45 Beschreibung" oder "14 Beschreibung" (interpretiert als "ab 14:45 Beschreibung")
            const matches = cleanedLine.match(/^(\d{1,2}[:h.]\d{2}|\d{1,2})\s+(.*)$/i);
            const start = formatTime(matches[1]);
            let description = matches[2].trim();

            if (isValidTime(start)) {
                dataset.push({ lineIndex, start, end: null, description });
            }
        }
    }

    return dataset;
};


// Hilfsfunktion zur Formatierung der Zeit
const formatTime = (time) => {
    return time.includes(":") ? time.replace(/[h.]/g, ":") : `${time.padStart(2, "0")}:00`;
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

const presetName = ref(props.preset?.name || '');

const isSaving = ref(false);
const saveError = ref('');

const updateOrCreate = async () => {
    // Ohne sichtbare Meldung wirkte ein 403 wie ein toter Button: das Modal blieb
    // offen und der Fehler landete nur in der Konsole.
    saveError.value = '';
    isSaving.value = true;

    try {
        const payload = { dataset: dataset.value };
        // Fehler steht im Modal – kein zusätzlicher globaler Toast
        const requestConfig = { skipErrorToast: true };

        if (props.preset) {
            // Preset-Modus: Update des Timeline-Presets
            await axios.patch(route('timeline-presets.update', {shiftPresetTimeline: props.preset.id}), {
                name: presetName.value,
                dataset: dataset.value
            }, requestConfig);
        } else if(props.timelineToEdit){
            await axios.post(route('edit.timeline.event', {event: props.event.id}), payload, requestConfig);
        } else {
            await axios.post(route('create.timeline.event', {event: props.event.id}), payload, requestConfig);
        }

        emit('close');
    } catch (error) {
        saveError.value = error?.response?.status === 403
            ? t('You are not allowed to edit the timeline of this date.')
            : (error?.response?.data?.message || t('The timeline could not be saved. Please try again.'));
    } finally {
        isSaving.value = false;
    }
}

onMounted(() => {
    generateRandomExamples();

    if (props.preset) {
        // Preset-Modus: Lade die Zeiten des Presets
        const times = props.preset.times || [];
        rawText.value = times.map((time) => {
            const start = time.start ? time.start.substring(0, 5) : '';
            const end = time.end ? time.end.substring(0, 5) : '';
            return `${start} ${end ? `- ${end}` : ''} ${time.description || ''}`;
        }).join('\n');
    } else if (props.timelineToEdit){
        // add presetToEdit.times to rawText
        rawText.value = props.timelineToEdit.map((time) => {
            return `${time.start ? `${time.start}` : ''} ${time.end ? `- ${time.end}` : ''} ${time.description ? time.description : ''}`;
        }).join('\n');
    }
});
</script>
