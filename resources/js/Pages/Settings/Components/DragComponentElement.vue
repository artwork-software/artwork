<script setup>
import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";
import DropComponentsToolTip from "@/Components/ToolTips/DropComponentsToolTip.vue";
import { EventListenerForDragging } from "@/Composeables/EventListenerForDragging.js";
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";

const props = defineProps({
    component: { type: Object, required: true },
});

const { t } = useI18n();
const { dispatchEventStart, dispatchEventEnd } = EventListenerForDragging();

const isDragging = ref(false);

const payload = computed(() => ({
    id: props.component.id,
    type: props.component.type,
    name: props.component.name,
    drop_type: "component",
    sidebar_enabled: props.component.sidebar_enabled,
    special: props.component.special,
}));

const tooltipText = computed(() =>
    props.component.special ? t(props.component.name) : props.component.name
);

function onDragStart(event) {
    try {
        const json = JSON.stringify(payload.value);
        event.dataTransfer?.setData("application/json", json);
        // Fallback für manche Browser/Integrationen
        event.dataTransfer?.setData("text/plain", json);
        event.dataTransfer.effectAllowed = "copyMove";
    } catch (e) {
        // no-op
    }
    isDragging.value = true;
    dispatchEventStart();
}

function onDragEnd() {
    isDragging.value = false;
    dispatchEventEnd();
}

// Meta-Infos sicher prüfen
const hasHeight = computed(() => props.component?.data?.height !== undefined);
const hasTitleSize = computed(
    () => props.component?.data?.title_size !== undefined
);
const showLine = computed(() => props.component?.data?.showLine === true);

// Übersetzter Name
const displayName = computed(() => t(props.component.name));
</script>

<template>
    <DropComponentsToolTip :top="true" :tooltip-text="tooltipText">
        <div
            class="group relative w-full select-none"
            draggable="true"
            @dragstart="onDragStart"
            @dragend="onDragEnd"
            :aria-grabbed="isDragging"
        >
            <!-- Karte -->
            <div
                class="flex items-center gap-3 rounded-2xl border bg-white/70 backdrop-blur px-3 py-3 shadow-sm transition
               focus-within:ring-2 focus-within:ring-blue-400/50 active:scale-[0.99]"
                :class="[
          isDragging
            ? 'ring-2 ring-emerald-400/40 border-emerald-300/60 bg-emerald-50/50 shadow rounded-lg'
            : 'border-zinc-200/80'
        ]"
                role="button"
                tabindex="0"
            >
                <!-- Icon -->
                <div
                    class="shrink-0 grid place-items-center size-10 rounded-xl border bg-white/60"
                    :class="isDragging ? 'border-emerald-300/70' : 'border-zinc-200/80'"
                    aria-hidden="true"
                >
                    <ComponentIcons :type="component.type" />
                </div>

                <!-- Text & Meta -->
                <div class="min-w-0">
                    <div class="text-sm font-semibold truncate">
                        {{ displayName }}
                    </div>

                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                        <!-- Höhe -->
                        <span v-if="hasHeight"
                            class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] leading-4 text-zinc-600"
                            :class="isDragging ? 'border-emerald-300/70 bg-emerald-50/50' : 'border-zinc-200 bg-white/70'">
                          {{ component.data.height }} px
                        </span>

                        <!-- Separator -->
                        <span v-if="showLine"
                            class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] leading-4 text-zinc-600"
                            :class="isDragging ? 'border-emerald-300/70 bg-emerald-50/50' : 'border-zinc-200 bg-white/70'">
                            {{ t('Show a separator line') }}
                        </span>

                        <!-- Title Size -->
                        <span
                            v-if="hasTitleSize"
                            class="inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] leading-4 text-zinc-600"
                            :class="isDragging ? 'border-emerald-300/70 bg-emerald-50/50' : 'border-zinc-200 bg-white/70'">
                          {{ component.data.title_size }} px
                        </span>
                    </div>
                </div>

                <!-- (Optional) dezenter Drag-Hinweis rechts -->
                <div
                    class="ml-auto h-8 w-5 rounded-md border"
                    :class="isDragging ? 'border-emerald-300/70 bg-emerald-50/50 rounded-lg' : 'border-zinc-200 bg-white/60'"
                    aria-hidden="true"
                >
                    <div class="h-full w-full grid place-items-center text-[10px] text-zinc-400">⋮⋮</div>
                </div>
            </div>
        </div>
    </DropComponentsToolTip>
</template>

<style scoped>
/* Keine extra Hover-Effekte – Fokus & Drag sind ausreichend hervorgehoben */
</style>
