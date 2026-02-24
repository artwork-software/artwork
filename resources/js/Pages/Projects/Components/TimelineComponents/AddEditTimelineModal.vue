<template>
    <ArtworkBaseModal @close="$emit('close')"  :title="preset ? $t('Edit timeline preset') : (timelineToEdit ? $t('Edit timeline') : $t('Add timeline'))" :description="$t('Define the shift-relevant times. You can create shifts along this timeline.')">
        <form @submit.prevent="updateOrCreate" class="w-full">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-if="preset" class="col-span-full">
                    <BaseInput
                        v-model="presetName"
                        :label="$t('Name')"
                        id="preset_name"
                    />
                </div>
                <div class="col-span-full">
                    <div class="flex items-center justify-end">
                        <div class="flex items-center gap-x-1 cursor-pointer select-none underline underline-offset-2 text-blue-500 text-xs" @click="showExamples = !showExamples">
                            <span v-if="!showExamples">{{ $t('Show examples') }}</span>
                            <span v-else>{{ $t('Hide examples') }}</span>
                        </div>
                    </div>
                    <div v-if="showExamples" class="mt-2">
                        <div class="flex flex-wrap gap-2 mb-3">
                            <div v-for="example in exampleText" class="px-2 py-0.5 bg-gray-100 text-gray-800 text-[10px] rounded-full border border-gray-200 cursor-pointer" @click="addExample(example)">
                                {{ example }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-full">
                    <BaseTextarea
                        v-model="rawText"
                        id="rawText"
                        label="Enter your times here. Each line is interpreted as a separate entry."
                        rows="15"
                        :max-length="100000"
                    />
                </div>
            </div>
            <div class="text-xs text-red-500 my-2">
                <span class="font-bold">{{ $t('Important')}}</span> <br>
                <span>
                    {{ $t('The times must be entered in the format HH:MM or HH.MM. If the time is not displayed in the preview, it is not formatted correctly.')}}
                </span>
            </div>

            <BasePageTitle
                :title="$t('Preview')"
                :description="$t('This is how your entries will look like')"
            />
            <div class="flex flex-wrap gap-2 mt-5 w-full">

                <div v-for="(line, index) in dataset" :key="index" class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full border border-blue-200" v-if="dataset.length > 0">
                    {{ line.start }} – {{ line.end }} {{ line.description }}
                    <button @click="removeEntry(index)" type="button" class="hover:text-red-500 transition-colors duration-300 ease-in-out cursor-pointer focus:outline-none">
                        ✕
                    </button>
                </div>
                <div v-else class="w-full">
                    <div class="text-xs bg-red-100 text-red-800 border border-red-200 px-3 py-1 w-full rounded-lg text-center">
                        {{ $t('No entries yet') }}
                    </div>
                </div>
            </div>


            <div class="mt-5 flex items-center justify-center">
                <FormButton :text="$t('Create')" type="submit"/>
            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import {computed, onMounted, ref} from "vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BasePageTitle from "@/Artwork/Titles/BasePageTitle.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import axios from "axios";

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

    for (const line of lines) {
        // Entferne "Uhr" unabhängig davon, ob es mit oder ohne Leerzeichen hinter der Zeit steht
        const cleanedLine = line.replace(/(\d{1,2}[:h.]\d{2}|\d{1,2})\s*Uhr\b/gi, "$1").trim();

        if (/(ab|from)\s*(\d{1,2}[:h.]\d{2})\s*(bis|to)\s*(\d{1,2}[:h.]\d{2})\s*(.*)/i.test(cleanedLine)) {
            // Format: "ab 14:45 bis 16:00 Beschreibung"
            const matches = cleanedLine.match(/(ab|from)\s*(\d{1,2}[:h.]\d{2})\s*(bis|to)\s*(\d{1,2}[:h.]\d{2})\s*(.*)/i);
            const start = matches[2].replace(/[h.]/g, ":");
            const end = matches[4].replace(/[h.]/g, ":");
            let description = matches[5].trim();

            if (isValidTime(start) && isValidTime(end)) {
                dataset.push({ start, end, description });
            }
        } else if (/(\d{1,2}[:h.]\d{2}|\d{1,2})\s*[–\-]\s*(\d{1,2}[:h.]\d{2}|\d{1,2})\s*(.*)/i.test(cleanedLine)) {
            // Format: "14:45 - 16:00 Beschreibung" oder "14 - 16 Beschreibung"
            const matches = cleanedLine.match(/(\d{1,2}[:h.]\d{2}|\d{1,2})\s*[–\-]\s*(\d{1,2}[:h.]\d{2}|\d{1,2})\s*(.*)/i);
            const start = formatTime(matches[1]);
            const end = formatTime(matches[2]);
            let description = matches[3].trim();

            if (isValidTime(start) && isValidTime(end)) {
                dataset.push({ start, end, description });
            }
        } else if (/(ab|from)\s*(\d{1,2}[:h.]\d{2}|\d{1,2})\s*(.*)/i.test(cleanedLine)) {
            // Format: "ab 14:45 Beschreibung" oder "ab 14 Beschreibung"
            const matches = cleanedLine.match(/(ab|from)\s*(\d{1,2}[:h.]\d{2}|\d{1,2})\s*(.*)/i);
            const start = formatTime(matches[2]);
            let description = matches[3].trim();

            if (isValidTime(start)) {
                dataset.push({ start, end: null, description });
            }
        } else if (/(bis|to|-)\s*(\d{1,2}[:h.]\d{2}|\d{1,2})\s*(.*)?/i.test(cleanedLine)) {
            // Format: "bis 16:00 Beschreibung" oder "- 16 Beschreibung"
            const matches = cleanedLine.match(/(bis|to|-)\s*(\d{1,2}[:h.]\d{2}|\d{1,2})\s*(.*)?/i);
            const end = formatTime(matches[2]);
            let description = matches[3]?.trim() || "";

            if (isValidTime(end)) {
                dataset.push({ start: null, end, description });
            }
        } else if (/^(\d{1,2}[:h.]\d{2}|\d{1,2})\s+(.*)$/i.test(cleanedLine)) {
            // Format: "14:45 Beschreibung" oder "14 Beschreibung" (interpretiert als "ab 14:45 Beschreibung")
            const matches = cleanedLine.match(/^(\d{1,2}[:h.]\d{2}|\d{1,2})\s+(.*)$/i);
            const start = formatTime(matches[1]);
            let description = matches[2].trim();

            if (isValidTime(start)) {
                dataset.push({ start, end: null, description });
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

// Berechneter Datensatz
const dataset = computed(() => parseTextToDataset(rawText.value));

// Funktion zum Entfernen eines Eintrags
const removeEntry = (index) => {
    const lines = rawText.value.split("\n");
    lines.splice(index, 1); // Entfernt die entsprechende Zeile
    rawText.value = lines.join("\n"); // Aktualisiert den Text
};

const addExample = (example) => {
    if (rawText.value.length > 0 && rawText.value[rawText.value.length - 1] !== "\n") {
        rawText.value += "\n";
    }

    rawText.value += example;
};

const presetName = ref(props.preset?.name || '');

const updateOrCreate = async () => {
    try {
        const payload = { dataset: dataset.value };

        if (props.preset) {
            // Preset-Modus: Update des Timeline-Presets
            await axios.patch(route('timeline-presets.update', {shiftPresetTimeline: props.preset.id}), {
                name: presetName.value,
                dataset: dataset.value
            });
        } else if(props.timelineToEdit){
            await axios.post(route('edit.timeline.event', {event: props.event.id}), payload);
        } else {
            await axios.post(route('create.timeline.event', {event: props.event.id}), payload);
        }

        emit('close');
    } catch (error) {
        console.error('Error saving timeline:', error);
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

<style scoped>

</style>
