<template>
    <ArtworkBaseModal
        :title="$t('Add Shifts by Presets and Groups')"
        :description="$t('Select shift presets and groups to add shifts to the schedule.')"
        @close="$emit('close')"
        modal-size="max-w-5xl"
    >
        <div class="space-y-5">
            <!-- Context pills -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold text-zinc-500">{{ $t('Room') }}</span>
                    <span class="inline-flex items-center rounded-full bg-zinc-900 text-white px-3 py-1 text-xs font-semibold shadow-sm">
                        {{ roomLabel }}
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold text-zinc-500">{{ $t('Day') }}</span>
                    <span class="inline-flex items-center rounded-full bg-white/80 text-zinc-900 px-3 py-1 text-xs font-semibold ring-1 ring-zinc-200/70 shadow-sm">
                        {{ day.fullDay }}
                    </span>
                </div>
            </div>

            <!-- Optional project -->
            <div class="rounded-2xl border border-zinc-200/70 bg-white/80 shadow-sm">
                <div class="px-4 py-3 border-b border-zinc-200/60 flex items-center justify-between gap-3">
                    <div class="text-xs font-semibold text-zinc-600">{{ $t('Project') }}</div>
                </div>
                <div class="p-4">
                    <!-- wie in EventComponent: ProjectSearch + Chip -->
                    <div v-if="selectedProject" class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <span class="inline-flex items-center rounded-full bg-zinc-900 text-white px-3 py-1 text-xs font-semibold shadow-sm truncate">
                                {{ selectedProject.name }}
                            </span>
                        </div>

                        <button
                            type="button"
                            class="text-xs cursor-pointer font-semibold text-zinc-500 hover:text-zinc-700 transition"
                            @click="clearProject"
                            :aria-label="$t('Remove project')"
                        >
                            {{ $t('Remove') }}
                        </button>
                    </div>

                    <ProjectSearch
                        v-else
                        :label="$t('Search for projects')"
                        @project-selected="onProjectSelected"
                    />

                    <LastedProjects
                        v-if="!selectedProject"
                        :limit="10"
                        @select="onProjectSelected"
                    />
                </div>
            </div>

            <!-- Tabs + actions -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-2">
                <div class="inline-flex rounded-2xl bg-zinc-100/70 p-1 ring-1 ring-zinc-200/60">
                    <button
                        type="button"
                        class="h-10 rounded-2xl px-4 text-sm font-extrabold transition active:scale-[0.99]"
                        :class="tab === 'groups'
                            ? 'bg-white text-zinc-900 shadow-sm ring-1 ring-zinc-200/60'
                            : 'text-zinc-600 hover:text-zinc-800'"
                        @click="setTab('groups')"
                    >
                        {{ $t('Groups') }}
                        <span class="ml-2 text-xs font-semibold text-zinc-500">({{ groups.length }})</span>
                    </button>

                    <button
                        type="button"
                        class="h-10 rounded-2xl px-4 text-sm font-extrabold transition active:scale-[0.99]"
                        :class="tab === 'presets'
                            ? 'bg-white text-zinc-900 shadow-sm ring-1 ring-zinc-200/60'
                            : 'text-zinc-600 hover:text-zinc-800'"
                        @click="setTab('presets')"
                    >
                        {{ $t('Single presets') }}
                        <span class="ml-2 text-xs font-semibold text-zinc-500">({{ presets.length }})</span>
                    </button>
                </div>
            </div>

            <!-- Search -->
            <div class="relative">
                <input
                    v-model="search"
                    type="text"
                    class="h-11 w-full rounded-2xl border border-zinc-200/70 bg-white/90 pl-10 pr-4 text-sm text-zinc-900
                           shadow-sm outline-none transition
                           focus:border-zinc-300 focus:bg-white focus:shadow"
                    :placeholder="tab === 'groups' ? $t('Search groups…') : $t('Search presets…')"
                    autocomplete="off"
                />
                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 21l-4.3-4.3" />
                        <circle cx="11" cy="11" r="7" />
                    </svg>
                </div>
            </div>

            <!-- Content -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Left: list -->
                <div class="rounded-2xl border border-zinc-200/70 bg-white/80 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-zinc-200/60">
                        <div class="text-xs font-semibold text-zinc-600">
                            {{ tab === 'groups' ? $t('Select groups') : $t('Select presets') }}
                        </div>
                    </div>

                    <div class="max-h-[380px] overflow-auto">
                        <!-- GROUPS -->
                        <TransitionGroup v-if="tab === 'groups'" name="list" tag="div">
                            <button
                                v-for="g in filteredGroups"
                                :key="`g-${g.id}`"
                                type="button"
                                class="w-full text-left px-4 py-3 border-b border-zinc-200/50 last:border-b-0
                                       hover:bg-zinc-50/80 transition active:scale-[0.995]"
                                @click="toggleGroup(g.id)"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-extrabold text-zinc-900">
                                            {{ g.name }}
                                        </div>

                                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-zinc-500">
      <span class="inline-flex items-center gap-1">
        <span class="font-semibold text-zinc-700">{{ groupSummary(g).totalPresets }}</span>
        {{ $t('presets') }}
      </span>

                                            <span class="text-zinc-300">•</span>

                                            <span class="inline-flex items-center gap-1">
        {{ $t('Time range') }}:
        <span class="font-semibold text-zinc-700">{{ groupSummary(g).timeRange }}</span>
      </span>

                                            <span v-if="groupSummary(g).crafts.length" class="text-zinc-300">•</span>

                                            <div v-if="groupSummary(g).crafts.length" class="flex flex-wrap gap-1.5">
        <span
            v-for="c in groupSummary(g).crafts"
            :key="c"
            class="inline-flex items-center rounded-full bg-zinc-100 text-zinc-800 px-2 py-0.5 text-[11px] font-semibold ring-1 ring-zinc-200/70"
        >
          {{ c }}
        </span>
                                                <span v-if="(g.presets?.length ?? 0) > 0 && groupSummary(g).crafts.length >= 4"
                                                      class="text-[11px] font-semibold text-zinc-400">
          …
        </span>
                                            </div>
                                        </div>

                                        <!-- optional mini preview of first 2 presets -->
                                        <div v-if="g.presets?.length" class="mt-2 flex flex-wrap gap-1.5">
      <span
          v-for="p in g.presets.slice(0,2)"
          :key="p.id"
          class="inline-flex items-center gap-2 rounded-full bg-white text-zinc-900 px-2.5 py-1 text-[11px] font-semibold
               ring-1 ring-zinc-200/70 shadow-sm"
      >
        <span class="text-zinc-500 tabular-nums">{{ fmtTime(p.start_time) }}–{{ fmtTime(p.end_time) }}</span>
        <span class="text-zinc-300">•</span>
        <span class="truncate max-w-[160px]">{{ p.name ?? `${$t('Preset')} #${p.id}` }}</span>
      </span>
                                            <span v-if="g.presets.length > 2" class="text-[11px] font-semibold text-zinc-500">
        +{{ g.presets.length - 2 }}
      </span>
                                        </div>
                                    </div>

                                    <div class="shrink-0">
    <span
        class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 transition"
        :class="selectedGroupIds.has(g.id)
        ? 'bg-zinc-900 text-white ring-zinc-900'
        : 'bg-white text-zinc-700 ring-zinc-200/70'"
    >
      {{ selectedGroupIds.has(g.id) ? $t('Selected') : $t('Select') }}
    </span>
                                    </div>
                                </div>
                            </button>
                        </TransitionGroup>

                        <div v-if="tab === 'groups' && !filteredGroups.length" class="px-4 py-6 text-sm text-zinc-500">
                            {{ $t('No groups found.') }}
                        </div>

                        <!-- PRESETS -->
                        <TransitionGroup v-if="tab === 'presets'" name="list" tag="div">
                            <button
                                v-for="p in filteredPresets"
                                :key="`p-${p.id}`"
                                type="button"
                                class="w-full text-left px-4 py-3 border-b border-zinc-200/50 last:border-b-0
                                       hover:bg-zinc-50/80 transition active:scale-[0.995]"
                                @click="togglePreset(p.id)"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-extrabold text-zinc-900">
                                            {{ p.name ?? p.title ?? `${$t('Preset')} #${p.id}` }}
                                        </div>

                                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-zinc-500">
    <span class="tabular-nums font-semibold text-zinc-700">
      {{ presetMeta(p).start }}–{{ presetMeta(p).end }}
    </span>

                                            <span class="text-zinc-300">•</span>

                                            <span class="inline-flex items-center gap-1">
      {{ $t('Duration') }}:
      <span class="font-semibold text-zinc-700">{{ presetMeta(p).duration }}</span>
    </span>

                                            <span class="text-zinc-300">•</span>

                                            <span class="inline-flex items-center gap-1">
      {{ $t('Break') }}:
      <span class="font-semibold text-zinc-700">{{ presetMeta(p).breakLabel }}</span>
    </span>

                                            <span class="text-zinc-300">•</span>

                                            <span class="inline-flex items-center gap-1">
      {{ $t('Craft') }}:
      <span class="font-semibold text-zinc-700">{{ presetMeta(p).craftLabel }}</span>
    </span>
                                        </div>

                                        <div v-if="presetMeta(p).quals.length" class="mt-2 flex flex-wrap gap-1.5">
    <span
        v-for="q in presetMeta(p).quals.slice(0, 4)"
        :key="q.id"
        class="inline-flex items-center gap-1 rounded-full bg-zinc-100 text-zinc-800 px-2 py-0.5 text-[11px] font-semibold ring-1 ring-zinc-200/70"
    >
      {{ q.name }}
      <span v-if="q.pivot?.quantity" class="text-zinc-500">×{{ q.pivot.quantity }}</span>
    </span>

                                            <span v-if="presetMeta(p).quals.length > 4" class="text-[11px] font-semibold text-zinc-500">
      +{{ presetMeta(p).quals.length - 4 }}
    </span>
                                        </div>

                                        <div v-if="p.description" class="mt-1 truncate text-xs text-zinc-500">
                                            {{ p.description }}
                                        </div>
                                    </div>

                                </div>
                            </button>
                        </TransitionGroup>

                        <div v-if="tab === 'presets' && !filteredPresets.length" class="px-4 py-6 text-sm text-zinc-500">
                            {{ $t('No presets found.') }}
                        </div>
                    </div>
                </div>

                <!-- Right: selection -->
                <div class="rounded-2xl border border-zinc-200/70 bg-white/80 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-zinc-200/60 flex items-center justify-between">
                        <div class="text-xs font-semibold text-zinc-600">{{ $t('Selection') }}</div>
                        <div class="text-xs text-zinc-500">
                            {{ selectedCount }} {{ $t('selected') }}
                        </div>
                    </div>

                    <div class="p-4 space-y-4">
                        <!-- selected groups -->
                        <div>
                            <div class="text-xs font-semibold text-zinc-700 mb-2">{{ $t('Groups') }}</div>

                            <TransitionGroup name="pill" tag="div" class="flex flex-wrap gap-2">
                                <span
                                    v-for="g in selectedGroups"
                                    :key="`sg-${g.id}`"
                                    class="inline-flex items-center gap-2 rounded-full bg-zinc-900 text-white px-3 py-1 text-xs font-semibold shadow-sm"
                                >
                                    {{ g.name }}
                                    <button
                                        type="button"
                                        class="rounded-full bg-white/15 hover:bg-white/20 px-1.5 py-0.5 transition"
                                        @click="toggleGroup(g.id)"
                                        :aria-label="$t('Remove')"
                                    >
                                        ✕
                                    </button>
                                </span>
                            </TransitionGroup>

                            <div v-if="!selectedGroups.length" class="text-sm text-zinc-500">
                                {{ $t('No groups selected.') }}
                            </div>
                        </div>

                        <!-- selected presets -->
                        <div>
                            <div class="text-xs font-semibold text-zinc-700 mb-2">{{ $t('Single presets') }}</div>

                            <TransitionGroup name="pill" tag="div" class="flex flex-wrap gap-2">
                                <span
                                    v-for="p in selectedPresets"
                                    :key="`sp-${p.id}`"
                                    class="inline-flex items-center gap-2 rounded-full bg-white text-zinc-900 px-3 py-1 text-xs font-semibold
                                           ring-1 ring-zinc-200/70 shadow-sm"
                                >
                                    {{ p.name ?? p.title ?? `${$t('Preset')} #${p.id}` }}
                                    <button
                                        type="button"
                                        class="rounded-full bg-black/5 hover:bg-black/10 px-1.5 py-0.5 transition"
                                        @click="togglePreset(p.id)"
                                        :aria-label="$t('Remove')"
                                    >
                                        ✕
                                    </button>
                                </span>
                            </TransitionGroup>

                            <div v-if="!selectedPresets.length" class="text-sm text-zinc-500">
                                {{ $t('No presets selected.') }}
                            </div>
                        </div>

                        <div class="pt-1 text-[11px] text-zinc-500">
                            {{ $t('Tip: You can combine multiple groups and presets — duplicates are removed automatically.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 mt-5 justify-between">
            <BaseUIButton
                type="button"
                is-cancel-button
                @click="clearSelection"
                :disabled="selectedPresetIds.size === 0 && selectedGroupIds.size === 0"
                :label="$t('Clear')"
            />

            <BaseUIButton
                type="button"
                is-add-button
                :disabled="!canApply || processing"
                @click="apply"
                :label="processing ? $t('Adding…') : $t('Add shifts')"
            />
        </div>
    </ArtworkBaseModal>
</template>


<script setup>
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import LastedProjects from "@/Artwork/LastedProjects.vue";

const emit = defineEmits(["close", "added"]);

const props = defineProps({
    room: [String, Number, Object],
    rooms: { type: [Array, Object], default: () => [] },
    day: Object,
    presetGroups: { type: [Array, Object], required: true },
    singleShiftPresets: { type: [Array, Object], required: true },
    projects: { type: [Array, Object], default: () => [] },
    initialProjectId: { type: [Number, String, null], default: null },
});

const tab = ref("groups");
const search = ref("");
const processing = ref(false);

const selectedProjectId = ref(props.initialProjectId != null && props.initialProjectId !== ''
    ? Number(props.initialProjectId)
    : null);

const projectsList = computed(() => {
    const v = props.projects;
    if (Array.isArray(v)) return v;
    return v ? Object.values(v) : [];
});

const selectedProject = ref(null);

if (selectedProjectId.value != null) {
    const existing = projectsList.value.find((p) => Number(p?.id) === Number(selectedProjectId.value));
    selectedProject.value = existing
        ? { id: existing.id, name: existing.name }
        : { id: selectedProjectId.value, name: `Project #${selectedProjectId.value}` };
}

const groups = computed(() => {
    const v = props.presetGroups;
    if (Array.isArray(v)) return v;
    return v ? Object.values(v) : [];
});

const presets = computed(() => {
    const v = props.singleShiftPresets;
    if (Array.isArray(v)) return v;
    return v ? Object.values(v) : [];
});

const selectedGroupIds = ref(new Set());
const selectedPresetIds = ref(new Set());

const filteredGroups = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return groups.value;
    return groups.value.filter((g) => (g?.name ?? "").toLowerCase().includes(q) || String(g?.id ?? "").includes(q));
});

const filteredPresets = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return presets.value;
    return presets.value.filter((p) => {
        const name = (p?.name ?? p?.title ?? "").toString().toLowerCase();
        const desc = (p?.description ?? "").toString().toLowerCase();
        const id = String(p?.id ?? "");
        return name.includes(q) || desc.includes(q) || id.includes(q);
    });
});

const selectedGroups = computed(() => groups.value.filter((g) => selectedGroupIds.value.has(g.id)));
const selectedPresets = computed(() => presets.value.filter((p) => selectedPresetIds.value.has(p.id)));

const selectedCount = computed(() => selectedGroupIds.value.size + selectedPresetIds.value.size);
const canApply = computed(() => selectedCount.value > 0);

const roomId = computed(() => {
    if (typeof props.room === "object" && props.room) return props.room.roomId ?? props.room.id;
    return props.room;
});
const roomLabel = computed(() => (typeof props.room === "object" ? (props.room?.roomName) : String(props.room ?? "—")));
const setTab = (t) => (tab.value = t)

const t = (key) => (typeof window !== "undefined" && typeof $t === "function" ? $t(key) : key) // optional falls du $t nicht importierst

function toMinutes(hhmm) {
    if (!hhmm) return null;
    const [h, m] = String(hhmm).slice(0, 5).split(":").map(Number);
    if (Number.isNaN(h) || Number.isNaN(m)) return null;
    return h * 60 + m;
}

function fmtTime(hhmm) {
    if (!hhmm) return "—";
    return String(hhmm).slice(0, 5);
}

function durationLabel(start, end) {
    const s = toMinutes(start);
    const e = toMinutes(end);
    if (s == null || e == null) return "—";

    let diff = e - s;
    if (diff <= 0) diff += 24 * 60; // über Mitternacht

    const h = Math.floor(diff / 60);
    const m = diff % 60;

    if (h && m) return `${h}h ${m}m`;
    if (h) return `${h}h`;
    return `${m}m`;
}

function presetQuals(p) {
    // supports snake/camel
    return p?.shifts_qualifications ?? p?.shiftsQualifications ?? [];
}

function presetCraft(p) {
    return p?.craft ?? null;
}

function presetMeta(p) {
    const start = fmtTime(p?.start_time);
    const end = fmtTime(p?.end_time);
    const brk = Number(p?.break_duration ?? 0);
    const craft = presetCraft(p);
    const craftLabel = craft?.abbreviation ?? craft?.name ?? "—";
    const dur = durationLabel(p?.start_time, p?.end_time);

    return {
        start,
        end,
        breakLabel: brk ? `${brk}m` : "—",
        craftLabel,
        duration: dur,
        quals: presetQuals(p),
    };
}

function groupSummary(g) {
    const presets = g?.presets ?? [];
    if (!presets.length) {
        return { timeRange: "—", crafts: [], totalPresets: 0 };
    }

    // time range (min start, max end as minutes, but keep "over midnight" simple)
    const starts = presets.map(p => toMinutes(p?.start_time)).filter(v => v != null);
    const ends = presets.map(p => toMinutes(p?.end_time)).filter(v => v != null);

    const minStart = starts.length ? Math.min(...starts) : null;
    const maxEnd = ends.length ? Math.max(...ends) : null;

    const timeRange =
        minStart == null || maxEnd == null
            ? "—"
            : `${String(Math.floor(minStart / 60)).padStart(2, "0")}:${String(minStart % 60).padStart(2, "0")}–${String(Math.floor(maxEnd / 60)).padStart(2, "0")}:${String(maxEnd % 60).padStart(2, "0")}`;

    // crafts unique (abbreviation)
    const crafts = Array.from(
        new Set(
            presets
                .map(p => presetCraft(p)?.abbreviation ?? presetCraft(p)?.name)
                .filter(Boolean)
        )
    ).slice(0, 4);

    return {
        timeRange,
        crafts,
        totalPresets: presets.length
    };
}

function toggleGroup(id) {
    const set = selectedGroupIds.value;
    if (set.has(id)) set.delete(id);
    else set.add(id);
    // trigger reactivity
    selectedGroupIds.value = new Set(set);
}

function togglePreset(id) {
    const set = selectedPresetIds.value;
    if (set.has(id)) set.delete(id);
    else set.add(id);
    selectedPresetIds.value = new Set(set);
}

function clearSelection() {
    selectedGroupIds.value = new Set();
    selectedPresetIds.value = new Set();
}

function clearProject() {
    selectedProjectId.value = null;
    selectedProject.value = null;
}

function onProjectSelected(project) {
    selectedProject.value = project;
    const n = Number(project?.id);
    selectedProjectId.value = Number.isFinite(n) ? n : null;
}

function collectPresetIdsFromSelection() {
    const ids = new Set([...selectedPresetIds.value]);

    // aus Gruppen Presets ziehen
    for (const g of selectedGroups.value) {
        const groupPresets = g?.presets ?? [];
        for (const p of groupPresets) ids.add(p.id);
    }

    return Array.from(ids);
}

/**
 * Empfohlen: ein bulk endpoint, damit du nicht 20 Requests schickst.
 */
function apply() {
    const preset_ids = collectPresetIdsFromSelection();
    if (!preset_ids.length) return;

    processing.value = true;

    const url =
        props.bulkCreateRoute ??
        (typeof route === "function"
            ? route("shifts.createFromPresets") // <- passe an
            : "/shifts/create-from-presets");

    router.post(
        url,
        {
            room_id: roomId.value,
            day: props.day.withoutFormat,
            preset_ids,
            project_id: selectedProjectId.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            },
            onSuccess: () => {
                emit("added");
                emit("close");
            },
        }
    );
}

/**
 * Wenn du es doch einzeln willst (nicht empfohlen):
 */
function createShiftBySinglePreset(presetId) {
    selectedPresetIds.value = new Set([presetId]);
    tab.value = "presets";
}

function createShiftsByPresetGroup(groupId) {
    selectedGroupIds.value = new Set([groupId]);
    tab.value = "groups";
}
</script>


<style scoped>
/* Mini spinner */
.spinner{
    width: 14px;
    height: 14px;
    border-radius: 999px;
    border: 2px solid rgba(255,255,255,.35);
    border-top-color: rgba(255,255,255,1);
    animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Smooth list entrance */
.list-enter-active, .list-leave-active { transition: all .16s ease; }
.list-enter-from { opacity: 0; transform: translateY(6px); }
.list-leave-to   { opacity: 0; transform: translateY(-6px); }

/* Pills */
.pill-enter-active, .pill-leave-active { transition: all .14s ease; }
.pill-enter-from { opacity: 0; transform: scale(.96); }
.pill-leave-to   { opacity: 0; transform: scale(.96); }
</style>
