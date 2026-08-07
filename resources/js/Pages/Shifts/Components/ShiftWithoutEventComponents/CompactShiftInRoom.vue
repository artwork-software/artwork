<template>
    <!-- Bewusst schlanke Kompaktkarte für Zoom < 100 %: bei doppelter Tagesdichte sind
         sehr viele Zellen sichtbar — kein Drag&Drop, keine Qualifikationszeilen, keine
         Worker-Liste. Klick öffnet wie gewohnt das Schicht-Modal. -->
    <div
        class="flex items-center justify-between gap-x-1 px-1 py-0.5 text-[10px]  cursor-pointer select-none"
        :data-shift-id="shift?.id"
        :title="`${craftAbbreviation} ${shift.start} - ${shift.end} (${usedWorkerCount}/${maxWorkerCount})`"
        @click="emit('clickOnEdit', shift)"
    >
        <span class="flex items-center gap-x-1 min-w-0">
            <PropertyIcon
                v-if="shift.isCommitted"
                name="IconLock"
                class="size-3 shrink-0 text-black"
                :stroke-width="2"
            />
            <!-- Nur Startzeit — die volle Zeitspanne steht im title-Tooltip und im Modal -->
            <span class="truncate">
                {{ craftAbbreviation }} {{ shift.start }}
            </span>
        </span>
        <span class="flex items-center shrink-0 tabular-nums">
            ({{ usedWorkerCount }}/{{ maxWorkerCount }})
            <!-- Eingeplante Person nicht (mehr) verfügbar: Warndreieck statt Besetzungs-Punkt,
                 rot bei festgeschriebener Schicht (identische Logik wie ShiftDropElement) -->
            <svg
                v-if="hasUnavailableWorkers"
                class="ml-1 h-2.5 w-2.5 shrink-0"
                :class="shift.isCommitted ? 'text-danger' : 'text-warning'"
                fill="currentColor" viewBox="0 0 20 20"
            >
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span
                v-else
                class="ml-1 inline-block h-2 w-2 rounded-full"
                :class="{
                    'bg-danger': usedWorkerCount === 0 && maxWorkerCount !== 0,
                    'bg-warning': usedWorkerCount !== 0 && usedWorkerCount < maxWorkerCount,
                    'bg-success': usedWorkerCount === maxWorkerCount,
                    'bg-warning': usedWorkerCount > maxWorkerCount,
                }"
            ></span>
        </span>
    </div>
</template>

<script setup>
import { computed } from "vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import { useShiftPlanLookups } from "@/Composeables/useShiftPlanLookups.js";

const { resolveCraft } = useShiftPlanLookups();

const props = defineProps({
    shift: { type: Object, required: true },
});

const emit = defineEmits(["clickOnEdit"]);

const craftAbbreviation = computed(() => {
    const craft = props.shift?.craft ?? resolveCraft(props.shift?.craftId) ?? {};
    return craft.abbreviation ?? '';
});

// Besetzt gesamt über alle Funktionen: Bedarf = Σ shifts_qualifications.value,
// belegt = zugewiesene Worker (identische Logik wie ShiftDropElement)
const maxWorkerCount = computed(() => {
    let max = 0;
    props.shift?.shifts_qualifications?.forEach((sq) => {
        max += sq.value;
    });
    return max;
});

const usedWorkerCount = computed(() => (props.shift?.workers || []).length);

const hasUnavailableWorkers = computed(() =>
    (props.shift?.workers || []).some((w) => w?.is_unavailable)
);
</script>
