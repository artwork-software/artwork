<template>
    <ArtworkBaseModal
        :title="isEdit ? $t('Edit shift preset group') : $t('Create shift preset group')"
        :description="$t('Create or edit a group to organize shift presets.')"
        @close="$emit('close')"
    >
        <form class="space-y-5" @submit.prevent="submit">
            <div class="space-y-1.5">
                <label class="text-xs font-semibold text-zinc-700">{{ $t('Group name') }}</label>

                <div class="relative">
                    <input
                        v-model="groupForm.name"
                        type="text"
                        class="h-11 w-full rounded-2xl border border-zinc-200/70 bg-white/90 px-4 text-sm text-zinc-900
                 shadow-sm outline-none ring-0 transition
                 focus:border-zinc-300 focus:bg-white focus:shadow"
                        :placeholder="$t('e.g. Early shift / Service / Kitchen')"
                        autocomplete="off"
                    />
                </div>
            </div>

            <div class="space-y-2">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <div class="text-xs font-semibold text-zinc-700">{{ $t('Select presets for this group') }}</div>
                        <div class="text-[11px] text-zinc-500">
                            {{ $t('Selected:') }}
                            <span class="font-semibold text-zinc-800">{{ groupForm.preset_ids.length }}</span>
                            / {{ presets.length }}
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button"
                                class="h-9 rounded-xl px-3 text-xs font-semibold text-zinc-700 ring-1 ring-zinc-200/80 hover:bg-zinc-50 active:scale-[0.99] transition"
                                @click="selectAll">
                            {{ $t('All') }}
                        </button>
                        <button type="button"
                                class="h-9 rounded-xl px-3 text-xs font-semibold text-zinc-700 ring-1 ring-zinc-200/80 hover:bg-zinc-50 active:scale-[0.99] transition"
                                @click="clearAll">
                            {{ $t('None') }}
                        </button>
                    </div>
                </div>

                <div class="relative">
                    <input
                        v-model="search"
                        type="text"
                        class="h-11 w-full rounded-2xl border border-zinc-200/70 bg-white/90 pl-10 pr-4 text-sm text-zinc-900
                 shadow-sm outline-none transition focus:border-zinc-300 focus:bg-white"
                        :placeholder="$t('Search presets… (group)')"
                        autocomplete="off"
                    />
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2">
                            <path d="M21 21l-4.3-4.3"/>
                            <circle cx="11" cy="11" r="7"/>
                        </svg>
                    </div>
                </div>

                <div class="max-h-[340px] overflow-auto rounded-2xl border border-zinc-200/70 bg-white/80 shadow-sm">
                    <div v-if="filteredPresets.length === 0" class="p-4 text-sm text-zinc-500">
                        {{ $t('No presets found. (group)') }}
                    </div>

                    <label
                        v-for="p in filteredPresets"
                        :key="p.id"
                        class="group flex items-start gap-3 px-4 py-3 border-b border-zinc-200/50 last:border-b-0
           hover:bg-zinc-50/80 transition cursor-pointer"
                    >
                        <input
                            class="mt-1 h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-0"
                            type="checkbox"
                            :value="p.id"
                            v-model="groupForm.preset_ids"
                        />

                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-zinc-900">
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
                                          <span class="font-semibold text-zinc-700">{{
                                                  presetMeta(p).breakLabel
                                              }}</span>
                                        </span>

                                        <span class="text-zinc-300">•</span>

                                        <span class="inline-flex items-center gap-1">
                                          {{ $t('Craft') }}:
                                          <span class="font-semibold text-zinc-700">{{
                                                  presetMeta(p).craftLabel
                                              }}</span>
                                        </span>
                                    </div>

                                    <div v-if="presetMeta(p).quals.length" class="mt-2 flex flex-wrap gap-1.5">
                                        <span
                                            v-for="q in presetMeta(p).quals.slice(0, 4)"
                                            :key="q.id"
                                            class="inline-flex items-center gap-1 rounded-full bg-zinc-100 text-zinc-800 px-2 py-0.5 text-[11px] font-semibold ring-1 ring-zinc-200/70"
                                        >
                                          {{ q.name }}
                                          <span v-if="q.pivot?.quantity" class="text-zinc-500">×{{
                                                  q.pivot.quantity
                                              }}</span>
                                        </span>

                                        <span v-if="presetMeta(p).quals.length > 4"
                                              class="text-[11px] font-semibold text-zinc-500">
                                          +{{ presetMeta(p).quals.length - 4 }}
                                        </span>
                                    </div>

                                    <div v-if="p.description" class="mt-1 truncate text-xs text-zinc-500">
                                        {{ p.description }}
                                    </div>
                                </div>

                                <div class="shrink-0 text-[11px] text-zinc-400 tabular-nums">
                                    #{{ p.id }}
                                </div>
                            </div>
                        </div>
                    </label>
                </div>


                <p v-if="groupForm.errors.preset_ids" class="text-xs text-red-600">
                    {{ groupForm.errors.preset_ids }}
                </p>

                <div v-if="selectedPresets.length" class="flex flex-wrap gap-2 pt-1">
                    <span
                        v-for="p in selectedPresets"
                        :key="p.id"
                        class="inline-flex items-center gap-2 rounded-full bg-zinc-900 text-white
                               px-3 py-1 text-xs font-semibold shadow-sm"
                    >
                        <span class="max-w-[220px] truncate">
                            {{ p.name ?? p.title ?? `Preset #${p.id}` }}
                        </span>
                        <button
                            type="button"
                            class="rounded-full bg-white/15 hover:bg-white/20 px-1.5 py-0.5 transition"
                            @click="removePreset(p.id)"
                            aria-label="Entfernen"
                        >
                            ✕
                        </button>
                    </span>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <BaseUIButton
                    type="button"
                    is-cancel-button
                    :disabled="groupForm.processing"
                    @click="$emit('close')"
                    :label="$t('Cancel')"
                />

                <BaseUIButton
                    type="submit"
                    is-add-button
                    :disabled="groupForm.processing || !canSubmit"
                    :label="groupForm.processing ? $t('Saving…') : (isEdit ? $t('Save changes') : $t('Create group'))"
                />
            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, ref, watch} from "vue";
import {useForm} from "@inertiajs/vue3";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const emit = defineEmits(["close", "saved"]);

const props = defineProps({
    singleShiftPresets: {
        type: [Object, Array],
        required: true,
    },
    presetGroup: {
        type: Object,
        default: null,
    },
});

const isEdit = computed(() => !!props.presetGroup?.id);

const presets = computed(() => {
    const v = props.singleShiftPresets;
    if (Array.isArray(v)) return v;
    if (!v) return [];
    return Object.values(v);
});

const groupForm = useForm({
    name: props.presetGroup?.name ?? "",
    preset_ids: props.presetGroup?.presets?.map((p) => p.id) ?? [],
});

watch(
    () => props.presetGroup,
    (pg) => {
        groupForm.clearErrors();
        groupForm.name = pg?.name ?? "";
        groupForm.preset_ids = pg?.presets?.map((p) => p.id) ?? [];
        search.value = "";
    }
);

const search = ref("");

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

const selectedPresets = computed(() => {
    const ids = new Set(groupForm.preset_ids);
    return presets.value.filter((p) => ids.has(p.id));
});

const canSubmit = computed(() => {
    return groupForm.name.trim().length > 0;
});

function selectAll() {
    groupForm.preset_ids = presets.value.map((p) => p.id);
}

function clearAll() {
    groupForm.preset_ids = [];
}

function removePreset(id) {
    groupForm.preset_ids = groupForm.preset_ids.filter((x) => x !== id);
}

function submit() {
    const storeUrl = route("shift-preset-groups.store");

    const onSuccess = () => {
        emit("close");
    };

    if (isEdit.value) {
        if (!props.presetGroup?.id) return;
        const updateUrl = route("shift-preset-groups.update", props.presetGroup.id);
        groupForm.patch(updateUrl, {preserveScroll: true, onSuccess});
        return;
    }

    groupForm.post(storeUrl, {preserveScroll: true, onSuccess});
}

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
    if (diff <= 0) diff += 24 * 60;
    const h = Math.floor(diff / 60);
    const m = diff % 60;
    if (h && m) return `${h}h ${m}m`;
    if (h) return `${h}h`;
    return `${m}m`;
}

function presetQuals(p) {
    return p?.shifts_qualifications ?? p?.shiftsQualifications ?? [];
}

function presetCraft(p) {
    return p?.craft ?? null;
}

function presetMeta(p) {
    const brk = Number(p?.break_duration ?? 0);
    const craft = presetCraft(p);
    return {
        start: fmtTime(p?.start_time),
        end: fmtTime(p?.end_time),
        duration: durationLabel(p?.start_time, p?.end_time),
        breakLabel: brk ? `${brk}m` : "—",
        craftLabel: craft?.abbreviation ?? craft?.name ?? "—",
        craftColor: craft?.color ?? null,
        quals: presetQuals(p),
    };
}

</script>

<style scoped></style>
