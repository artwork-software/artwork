<template>
    <div
        ref="cellRef"
        :style="containerStyle"
        :class="[isFullscreen ? 'stickyRemarksNoMarginLeft' : 'stickyRemarks', editable ? 'cursor-pointer' : '']"
        class="group/remark relative bg-warning-surface border-r border-warning-border text-left overflow-hidden"
        @click.stop="openEditor"
        @mouseenter="hovering = true"
        @mouseleave="hovering = false"
    >
        <div :style="stickyTop !== null ? { position: 'sticky', top: stickyTop + 'px' } : {}" class="px-1.5 py-1">
            <p
                v-if="remark?.text"
                class="text-[11px] leading-[14px] text-text whitespace-pre-line break-words remark-clamp"
                :style="clampStyle"
            >
                {{ remark.text }}
            </p>
            <!-- Leer + Editierrecht: dezente Hinzufügen-Affordanz erst bei Hover -->
            <div
                v-else-if="editable"
                class="flex items-center gap-x-1 text-text-subtle opacity-0 group-hover/remark:opacity-100 transition-opacity"
            >
                <component :is="IconPencilPlus" class="size-3.5 shrink-0" stroke-width="1.5" />
                <span class="text-[10px] leading-tight">{{ $t('Add remark') }}</span>
            </div>
        </div>
        <!-- Stift-Overlay bei bestehendem Text und Editierrecht -->
        <div
            v-if="editable && remark?.text"
            class="absolute top-0.5 right-0.5 rounded-sm bg-surface/80 p-0.5 text-text-muted opacity-0 group-hover/remark:opacity-100 transition-opacity"
        >
            <component :is="IconPencil" class="size-3" stroke-width="1.5" />
        </div>
    </div>

    <!-- Voller Text beim Hover (nur wenn geclampt); beim Editieren unterdrückt -->
    <DayRemarkHoverTooltip
        v-if="hovering && !editing && remark?.text"
        :remark="remark"
        :anchor="cellRef"
    />

    <!-- Inline-Editor: teleportiert + fixed, damit overflow-hidden/sticky der
         Zelle und Zeilen ihn nicht abschneiden. Esc bricht ab, Klick außerhalb
         oder Speichern-Button übernimmt. -->
    <Teleport to="body">
        <div
            v-if="editing"
            ref="editorRef"
            class="fixed z-[999] rounded-lg border border-warning-border bg-surface shadow-overlay p-2 space-y-1.5"
            :style="editorStyle"
            @click.stop
        >
            <div class="text-[11px] font-semibold text-text flex items-center justify-between gap-x-3">
                <span class="tabular-nums">{{ $t('Day remark') }} · {{ day.fullDay }}</span>
                <span class="tabular-nums" :class="draftText.length >= maxLength ? 'font-semibold text-danger' : 'font-normal text-text-subtle'">
                    {{ draftText.length }}/{{ maxLength }}
                </span>
            </div>
            <BaseTextarea
                :id="`day-remark-inline-${day.withoutFormat}`"
                v-model="draftText"
                :rows="4"
                :placeholder="$t('Add remark')"
            />
            <div class="flex items-center justify-between">
                <span v-if="remark?.updated_by" class="text-[10px] text-text-subtle truncate mr-2">
                    {{ $t('Last edited by {0} on {1}', [remark.updated_by, remark.updated_at]) }}
                </span>
                <span v-else></span>
                <div class="flex items-center gap-x-1.5 shrink-0">
                    <button
                        type="button"
                        class="text-[11px] text-text-muted hover:text-text px-1.5 py-0.5"
                        @click="closeEditor"
                    >
                        {{ $t('Cancel') }}
                    </button>
                    <BaseUIButton :label="$t('Save')" is-add-button is-small :disabled="saving" @click="saveDraft" />
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { IconPencil, IconPencilPlus } from '@tabler/icons-vue'
import { useCalendarZoom } from '@/Composeables/useCalendarZoom.js'
import { useDayRemarks, DAY_REMARK_COLUMN_WIDTH, DAY_REMARK_MAX_LENGTH } from '@/Composeables/useDayRemarks.js'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import DayRemarkHoverTooltip from '@/Components/Calendar/Elements/DayRemarkHoverTooltip.vue'

const props = defineProps({
    day: { type: Object, required: true },
    editable: { type: Boolean, default: false },
    isFullscreen: { type: Boolean, default: false },
    stickyTop: { type: Number, default: null },
})

const { rowHeight } = useCalendarZoom()
const { remarkForDay, saveDayRemark } = useDayRemarks()

const calendarSettings = computed(() => usePage().props.auth.user.calendar_settings ?? {})

// Live-Store vor Seiten-Payload — reagiert auch auf eigene Saves und Broadcasts,
// selbst wenn das day-Objekt selbst nicht reaktiv ist (computed/v-memo im Parent)
const remark = computed(() => remarkForDay(props.day))

const hovering = ref(false)

const containerStyle = computed(() => ({
    height: calendarSettings.value.expand_days ? '' : rowHeight.value + 'px',
    width: DAY_REMARK_COLUMN_WIDTH + 'px',
    minWidth: DAY_REMARK_COLUMN_WIDTH + 'px',
}))

// Zeilenanzahl an die Zeilenhöhe anpassen (14px Zeile, 8px vertikales Padding);
// bei expand_days wächst die Zeile mit den Raumzellen — dann großzügig clampen,
// damit die Bemerkung die Höhenmessung nie beeinflusst
const clampStyle = computed(() => {
    const lines = calendarSettings.value.expand_days
        ? 8
        : Math.max(1, Math.floor((rowHeight.value - 8) / 14))
    return { '-webkit-line-clamp': String(lines) }
})

// --- Inline-Editor ---
const maxLength = DAY_REMARK_MAX_LENGTH
const cellRef = ref(null)
const editorRef = ref(null)
const editing = ref(false)
const draftText = ref('')
const saving = ref(false)
const editorStyle = ref({})

// BaseTextarea reicht kein maxlength ans <textarea> durch — Limit hier durchsetzen
watch(draftText, (value) => {
    if (value.length > maxLength) {
        draftText.value = value.slice(0, maxLength)
    }
})

const EDITOR_WIDTH = 280
const EDITOR_HEIGHT_ESTIMATE = 190

const openEditor = () => {
    if (!props.editable || editing.value) {
        return
    }
    draftText.value = remark.value?.text ?? ''
    const rect = cellRef.value?.getBoundingClientRect()
    const left = Math.min(rect?.left ?? 100, window.innerWidth - EDITOR_WIDTH - 12)
    const top = Math.min(rect?.top ?? 100, window.innerHeight - EDITOR_HEIGHT_ESTIMATE - 12)
    editorStyle.value = { left: `${Math.max(8, left)}px`, top: `${Math.max(8, top)}px`, width: `${EDITOR_WIDTH}px` }
    editing.value = true
    nextTick(() => {
        editorRef.value?.querySelector('textarea')?.focus()
        document.addEventListener('mousedown', onOutsidePointer, true)
        document.addEventListener('keydown', onKeydown, true)
    })
}

const closeEditor = () => {
    editing.value = false
    document.removeEventListener('mousedown', onOutsidePointer, true)
    document.removeEventListener('keydown', onKeydown, true)
}

const saveDraft = () => {
    if (saving.value) {
        return
    }
    // Nichts geändert → nur schließen, kein Request/Broadcast
    if (draftText.value.trim() === (remark.value?.text ?? '')) {
        closeEditor()
        return
    }
    saving.value = true
    saveDayRemark(props.day.withoutFormat, draftText.value)
        .then(() => closeEditor())
        .finally(() => {
            saving.value = false
        })
}

// Klick außerhalb übernimmt (direktes Inline-Verhalten), Esc verwirft
const onOutsidePointer = (event) => {
    if (editorRef.value && !editorRef.value.contains(event.target)) {
        saveDraft()
    }
}
const onKeydown = (event) => {
    if (event.key === 'Escape') {
        event.stopPropagation()
        closeEditor()
    }
}

onBeforeUnmount(() => closeEditor())
</script>

<style scoped>
.remark-clamp {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Sticky direkt neben der Datumsspalte (90px + 2px Spalten-Gap);
   Offsets analog zu SingleDayInCalendar.stickyDays */
.stickyRemarks {
    position: sticky;
    position: -webkit-sticky;
    left: 92px;
    z-index: 21;
}

@media (min-width: 1024px) {
    .stickyRemarks {
        left: calc(4rem + 92px);
    }
}

.stickyRemarksNoMarginLeft {
    position: sticky;
    position: -webkit-sticky;
    left: 92px;
    z-index: 21;
}
</style>
