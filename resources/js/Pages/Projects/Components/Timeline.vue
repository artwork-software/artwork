<template>
    <div class="w-60">
        <!-- Header -->
        <div
            class="h-10 rounded-xl mb-2 flex items-center justify-between px-3 text-white shadow-sm ring-1 ring-inset ring-black/5
             bg-artwork-buttons-hover/80"
        >
            <div class="flex items-center gap-2">
                <div class="uppercase text-xs font-semibold tracking-wide">
                    {{ $t('Timeline') }}
                </div>
                <!-- Counter -->
                <span
                    v-if="timeLine?.length"
                    class="inline-flex items-center rounded-full border border-white/30 bg-white/10 px-2 py-0.5 text-[11px] leading-none"
                    :title="$t('Entries')"
                >
          <span class="tabular-nums">{{ timeLine.length }}</span>
        </span>
            </div>

            <div class="flex items-center gap-2">
                <ToolTipComponent
                    v-if="canEditComponent"
                    :icon="IconClipboard"
                    icon-size="w-5 h-5"
                    white-icon
                    :tooltip-text="$t('Copy timeline to clipboard')"
                    @click="copyTimelineToClipboard"
                    direction="bottom"
                />
                <ToolTipComponent
                    v-if="canEditComponent"
                    :icon="IconWand"
                    icon-size="w-5 h-5"
                    white-icon
                    :tooltip-text="$t('Create new timeline')"
                    @click="openTimelineModal(false)"
                    direction="bottom"
                />
                <BaseMenu v-if="canEditComponent" white-menu-background has-no-offset white-icon>
                    <BaseMenuItem white-menu-background title="Read from template" :icon="IconFileImport" @click="showSearchTimelinePresetModal = true" />
                    <BaseMenuItem white-menu-background title="Save as template" :icon="IconFileExport" @click="showCreateTimelinePresetModal = true" />
                    <BaseMenuItem white-menu-background title="Edit" :icon="IconEdit" @click="openTimelineModal(true)" />
                </BaseMenu>
            </div>
        </div>

        <!-- Inhalt -->
        <div class="space-y-1">
            <template v-for="(time, idx) in timeLine" :key="time?.id ?? `${time.start}-${time.end}-${idx}`">
                <NewSingleTimeline
                    :canEditComponent="canEditComponent"
                    :time="time"
                    :event="event"
                    @wantsFreshPlacements="$emit('wantsFreshPlacements')"
                />
            </template>

            <!-- Add Button Card -->
            <div v-if="canEditComponent">
                <button
                    type="button"
                    class="group w-full rounded-xl border border-dashed border-zinc-300 bg-white/70
                 hover:border-zinc-500 hover:bg-artwork-navigation-color/20
                 focus:outline-none focus-visible:ring-2 focus-visible:ring-artwork-buttons-hover/50
                 transition px-3 py-4 flex items-center justify-center cursor-pointer"
                    @click="addEmptyTimeline"
                >
                    <component
                        :is="IconCircleDashedPlus"
                        class="h-6 w-6 text-artwork-buttons-context/40 group-hover:text-artwork-buttons-hover transition-colors"
                        stroke-width="1.5"
                        aria-hidden="true"
                    />
                    <span class="ml-2 text-xs font-medium text-zinc-600 group-hover:text-artwork-buttons-hover">
            {{ $t('Add timeline row') }}
          </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <transition name="fade" appear>
        <div
            v-show="successCopied"
            class="fixed inset-x-0 top-5 z-50 sm:flex sm:justify-center sm:px-6 lg:px-8"
        >
            <div class="pointer-events-auto flex items-center justify-between gap-x-6 bg-zinc-900/95 text-white
                  px-5 py-2.5 sm:rounded-xl shadow-lg ring-1 ring-inset ring-white/10">
                <p class="text-sm">{{ $t('Timeline copied to clipboard') }}</p>
                <button type="button" class="-m-1.5 p-1.5" @click="successCopied = false" aria-label="Dismiss">
                    <component :is="IconX" class="size-5" aria-hidden="true" />
                </button>
            </div>
        </div>
    </transition>

    <!-- Modals -->
    <AddEditTimelineModal
        v-if="showAddTimeLineModal"
        :timeline-to-edit="addTimelineToEdit ? timeLine : null"
        :event="event"
        @close="closeModal"
    />

    <SearchTimelinePresetModal
        v-if="showSearchTimelinePresetModal"
        :event="event"
        @close="closeSearchTimelinePresetModal"
    />

    <CreateTimelinePresetFormEvent
        v-if="showCreateTimelinePresetModal"
        :event="event"
        @close="closeModalShowCreateTimelinePresetModal"
    />
</template>

<script setup>
import { ref, getCurrentInstance } from 'vue'
import { router } from '@inertiajs/vue3'
import NewSingleTimeline from '@/Pages/Projects/Components/TimelineComponents/NewSingleTimeline.vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue'
import AddEditTimelineModal from '@/Pages/Projects/Components/TimelineComponents/AddEditTimelineModal.vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import SearchTimelinePresetModal from '@/Pages/Projects/Components/TimelineComponents/SearchTimelinePresetModal.vue'
import CreateTimelinePresetFormEvent from '@/Pages/Projects/Components/TimelineComponents/CreateTimelinePresetFormEvent.vue'
import {is, can} from "laravel-permission-to-vuejs";
import {
    IconCircleDashedPlus,
    IconClipboard,
    IconEdit,
    IconFileExport,
    IconFileImport,
    IconWand,
    IconX
} from "@tabler/icons-vue";

defineOptions({ name: 'Timeline' })

const props = defineProps({
    timeLine: { type: Array, required: true },
    event: { type: Object, required: true },
    canEditComponent: { type: Boolean, default: false }
})

const emit = defineEmits(['wantsFreshPlacements'])

const showAddTimeLineModal = ref(false)
const addTimelineToEdit = ref(false)
const successCopied = ref(false)
const showSearchTimelinePresetModal = ref(false)
const showCreateTimelinePresetModal = ref(false)

const { proxy } = getCurrentInstance()

function closeModal() {
    emit('wantsFreshPlacements')
    showAddTimeLineModal.value = false
}

function openTimelineModal(edit) {
    // Edit erlaubt, wenn canEditComponent ODER (globale Rechte vorhanden)
    const canPlan = can?.('can plan shifts')
    const isAdmin = is('artwork admin')
    if (!(props.canEditComponent || canPlan || isAdmin)) return

    showAddTimeLineModal.value = true
    addTimelineToEdit.value = !!edit
}

function addEmptyTimeline() {
    router.post(
        route('add.timeline.row', { event: props.event.id }),
        {},
        {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => emit('wantsFreshPlacements')
        }
    )
}

function closeSearchTimelinePresetModal() {
    emit('wantsFreshPlacements')
    showSearchTimelinePresetModal.value = false
}

function closeModalShowCreateTimelinePresetModal() {
    emit('wantsFreshPlacements')
    showCreateTimelinePresetModal.value = false
}

function copyTimelineToClipboard() {
    let text = ''
    ;(props.timeLine || []).forEach((t) => {
        text += `${t.start} - ${t.end} ${t.description ?? ''}\n`
    })
    navigator.clipboard?.writeText?.(text)
    successCopied.value = true
    setTimeout(() => (successCopied.value = false), 1200)
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from,
.fade-leave-to { opacity: 0; }

/* ruhige Zahlenbreite */
.tabular-nums { font-variant-numeric: tabular-nums; }
</style>
