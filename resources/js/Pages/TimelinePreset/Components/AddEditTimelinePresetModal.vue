<template>
    <BaseModal @closed="$emit('close')">
        <div>
            <ModalHeader
                :title="createOrUpdateForm.id ? $t('Edit timeline preset') : $t('Add timeline preset')"
            />
        </div>
        <form @submit.prevent="updateOrCreate" class="w-full">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="col-span-full">
                    <BaseInput
                        id="name"
                        v-model="createOrUpdateForm.name"
                        :label="$t('Name of the template*')"
                        required
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
                        :label="$t('Enter your times here. Each line is interpreted as a separate entry.')"
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

            <TinyPageHeadline
                :title="$t('Preview')"
                :description="$t('This is how your entries will look like')"
            />
            <div class="flex flex-wrap gap-2 mt-5 w-full">

                <div v-for="(line, index) in dataset" :key="index" class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded-full border border-blue-200" v-if="dataset.length > 0">
                    {{ line.start }} – {{ line.end }} {{ line.description }}
                    <button @click="removeEntry(index)" class="hover:text-red-500 transition-colors duration-300 ease-in-out cursor-pointer focus:outline-none">
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
                <FormButton :text="createOrUpdateForm.id ? $t('Update') : $t('Create')" type="submit"/>
            </div>
        </form>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import {computed, onMounted, ref} from "vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import {useForm} from "@inertiajs/vue3";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";

const props = defineProps({
    presetToEdit: {
        type: Object,
        default: null
    }
})

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

    for (const line of lines) {
        if (/(ab|from)\s*(\d{1,2}[:h.]\d{2})\s*(bis|to)\s*(\d{1,2}[:h.]\d{2})\s*(.*)/i.test(line)) {
            // Format: "ab 14:45 bis 16:00 Beschreibung"
            const matches = line.match(/(ab|from)\s*(\d{1,2}[:h.]\d{2})\s*(bis|to)\s*(\d{1,2}[:h.]\d{2})\s*(.*)/i);
            const start = matches[2].replace(/[h.]/g, ":");
            const end = matches[4].replace(/[h.]/g, ":");

            if (isValidTime(start) && isValidTime(end)) {
                dataset.push({ start, end, description: matches[5].trim() });
            }
        } else if (/(\d{1,2}[:h.]\d{2})\s*[–\-]\s*(\d{1,2}[:h.]\d{2})\s*(.*)/i.test(line)) {
            // Format: "14:45 - 16:00 Beschreibung"
            const matches = line.match(/(\d{1,2}[:h.]\d{2})\s*[–\-]\s*(\d{1,2}[:h.]\d{2})\s*(.*)/i);
            const start = matches[1].replace(/[h.]/g, ":");
            const end = matches[2].replace(/[h.]/g, ":");

            if (isValidTime(start) && isValidTime(end)) {
                dataset.push({ start, end, description: matches[3].trim() });
            }
        } else if (/(ab|from)\s*(\d{1,2}[:h.]\d{2})\s*(.*)/i.test(line)) {
            // Format: "ab 14:45 Beschreibung"
            const matches = line.match(/(ab|from)\s*(\d{1,2}[:h.]\d{2})\s*(.*)/i);
            const start = matches[2].replace(/[h.]/g, ":");

            if (isValidTime(start)) {
                dataset.push({ start, end: null, description: matches[3].trim() });
            }
        } else if (/(bis|to|-)\s*(\d{1,2}[:h.]\d{2})\s*(.*)?/i.test(line)) {
            // Format: "bis 16:00 Beschreibung" oder "- 16:00 Beschreibung"
            const matches = line.match(/(bis|to|-)\s*(\d{1,2}[:h.]\d{2})\s*(.*)?/i);
            const end = matches[2].replace(/[h.]/g, ":");

            if (isValidTime(end)) {
                dataset.push({ start: null, end, description: matches[3]?.trim() || "" });
            }
        } else if (/^(\d{1,2}[:h.]\d{2})\s+(.*)$/i.test(line)) {
            // Format: "14:45 Beschreibung" (interpretiert als "ab 14:45 Beschreibung")
            const matches = line.match(/^(\d{1,2}[:h.]\d{2})\s+(.*)$/i);
            const start = matches[1].replace(/[h.]/g, ":");

            if (isValidTime(start)) {
                dataset.push({ start, end: null, description: matches[2].trim() });
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
        rawText.value = props.presetToEdit.times.map((time) => {
            return `${time.start ? `ab ${time.start}` : ''} ${time.end ? `bis ${time.end}` : ''} ${time.description ? time.description : ''}`
        }).join('\n');
    }
});
</script>

<style scoped>

</style>