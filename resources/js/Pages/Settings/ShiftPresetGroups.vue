<template>
    <ShiftSettingsHeader
        :title="$t('Shift preset groups')"
        :description="$t('Groups that collect multiple time presets')"
    >
        <template #actions>
            <BaseUIButton
                @click="openCreateModal"
                :label="$t('New group')"
                is-add-button
            />
        </template>

        <div class="rounded-2xl border border-zinc-200/70 bg-white/85 backdrop-blur px-5 py-5 shadow-sm">
            <div>
                <TransitionGroup v-if="groups?.length" name="list" tag="ul" class="space-y-2">
                    <li
                        v-for="g in groups"
                        :key="g.id"
                        class="group flex items-center justify-between gap-4 rounded-2xl border border-zinc-200/70 bg-white/70 px-4 py-3
                               hover:bg-zinc-50/80 hover:shadow-sm transition active:scale-[0.995]"
                    >
                        <div class="min-w-0">
                            <div class="truncate text-sm font-extrabold text-zinc-900">
                                {{ g.name }}
                            </div>

                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                                <span class="inline-flex items-center gap-1">
                                  <span class="font-semibold text-zinc-700">{{ groupSummary(g).total }}</span>
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
                                </div>
                            </div>

                            <div v-if="g.presets?.length" class="mt-2 flex flex-wrap gap-1.5">
                                <span
                                    v-for="p in g.presets.slice(0,2)"
                                    :key="p.id"
                                    class="inline-flex items-center gap-2 rounded-full bg-white text-zinc-900 px-2.5 py-1 text-[11px] font-semibold
                                         ring-1 ring-zinc-200/70 shadow-sm"
                                >
                                  <span class="text-zinc-500 tabular-nums">{{
                                          fmtTime(p.start_time)
                                      }}–{{ fmtTime(p.end_time) }}</span>
                                  <span class="text-zinc-300">•</span>
                                  <span class="truncate max-w-[160px]">{{ p.name ?? `#${p.id}` }}</span>
                                </span>

                                <span v-if="g.presets.length > 2" class="text-[11px] font-semibold text-zinc-500">
                                  +{{ g.presets.length - 2 }}
                                </span>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl px-2.5 py-2 ring-1 ring-zinc-200/80
                                       bg-white/60 hover:bg-white transition active:scale-[0.99]"
                                @click="openEditModal(g)"
                                :aria-label="$t('Edit')"
                            >
                                <PropertyIcon name="IconEdit" class="text-zinc-700"/>
                            </button>

                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl px-2.5 py-2 ring-1 ring-zinc-200/80
                                       bg-white/60 hover:bg-red-50 transition active:scale-[0.99]"
                                @click="confirmDelete(g)"
                                :aria-label="$t('Delete')"
                            >
                                <PropertyIcon name="IconTrash" class="text-red-600"/>
                            </button>
                        </div>
                    </li>
                </TransitionGroup>

                <AlertComponent v-else type="info" :text="$t('No groups yet.')"/>
            </div>
        </div>
        <ConfirmDeleteModal
            v-if="toDelete"
            :title="$t('Delete group')"
            :description="$t('Delete group and unassign its presets?')"
            @closed="toDelete = null"
            @delete="deleteGroup"
        />
        <ShiftPresetGroupAddEditModal
            v-if="modalOpen"
            :single-shift-presets="presets"
            :preset-group="editGroupModel"
            @close="closeModal"
            @saved="closeModal"
        />
    </ShiftSettingsHeader>
</template>

<script setup>
import {computed, ref} from "vue";
import {router} from "@inertiajs/vue3";

import ShiftSettingsHeader from "@/Pages/Settings/Components/ShiftSettingsHeader.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import ShiftPresetGroupAddEditModal from "@/Pages/Settings/ShiftPresetGroupAddEditModal.vue";

defineProps({
    groups: {type: Array, required: true},
    presets: {type: Array, required: true},
});

const modalOpen = ref(false);
const edit = ref(null);
const toDelete = ref(null);

const editGroupModel = computed(() => edit.value);

function openCreateModal() {
    edit.value = null;
    modalOpen.value = true;
}

function openEditModal(group) {
    edit.value = group;
    modalOpen.value = true;
}

function closeModal() {
    modalOpen.value = false;
    edit.value = null;
}

function confirmDelete(group) {
    toDelete.value = group;
}

function deleteGroup() {
    if (!toDelete.value?.id) return;

    router.delete(route("shift-preset-groups.destroy", toDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            toDelete.value = null;
        },
    });
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

function presetCraft(p) {
    return p?.craft ?? null;
}
function groupSummary(g) {
    const ps = g?.presets ?? [];
    if (!ps.length) return {timeRange: "—", crafts: [], total: 0};

    const starts = ps.map(p => toMinutes(p?.start_time)).filter(v => v != null);
    const ends = ps.map(p => toMinutes(p?.end_time)).filter(v => v != null);

    const minStart = starts.length ? Math.min(...starts) : null;
    const maxEnd = ends.length ? Math.max(...ends) : null;

    const timeRange =
        minStart == null || maxEnd == null
            ? "—"
            : `${String(Math.floor(minStart / 60)).padStart(2, "0")}:${String(minStart % 60).padStart(2, "0")}–${String(Math.floor(maxEnd / 60)).padStart(2, "0")}:${String(maxEnd % 60).padStart(2, "0")}`;

    const crafts = Array.from(new Set(
        ps.map(p => presetCraft(p)?.abbreviation ?? presetCraft(p)?.name).filter(Boolean)
    )).slice(0, 4);

    return {timeRange, crafts, total: ps.length};
}
</script>

<style scoped>
.list-enter-active,
.list-leave-active {
    transition: all 0.16s ease;
}

.list-enter-from {
    opacity: 0;
    transform: translateY(6px);
}

.list-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}
</style>
