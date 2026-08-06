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
            <span
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
</script>
