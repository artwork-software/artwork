<template>
  <div class="space-y-3 select-none">
    <!-- Ganztägige Termine: oben, volle Breite, damit beide Spalten darunter auf gleicher Höhe starten -->
    <div v-if="allDayEvents.length" class="space-y-1 mb-2">
      <div v-for="e in allDayEvents" :key="'allday-'+e.id" class="relative">
        <SingleEventInDailyShiftView
          :event="e"
          :eventTypes="eventTypes"
          :rooms="rooms"
          :first_project_calendar_tab_id="first_project_calendar_tab_id"
          :event-statuses="eventStatuses"
          :has-collision="false"
          @toggle="onEventToggle(e.id, $event)"
        />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <!-- EVENTS (left) -->
      <div class="col-span-1">
        <!-- Zeitblöcke: Events-Spalte -->
        <div v-if="layoutBlocks.length" class="overflow-x-auto">
          <div>
            <div v-for="(block, bIdx) in layoutBlocks" :key="'ev-block-' + bIdx" class="">
              <div class="relative w-full border-l border-gray-200 rounded bg-white/50" :style="getEventBlockStyle(block)">
                <template v-for="item in block.eventItems" :key="item.key">
                  <div class="absolute rounded-b-lg" :style="getEventItemStyle(item, block)">
                    <SingleEventInDailyShiftView
                      v-bind="item.props"
                      @toggle="onEventToggle(item.id, $event)"
                    />
                  </div>
                </template>
              </div>
              <div v-if="block.gapAfter" class="text-center text-[11px] text-gray-500 py-1">~{{ block.gapAfter.human }} Abstand</div>
            </div>
          </div>
        </div>
        <div v-if="!hasAny" class="text-gray-300 text-center mt-2">{{ $t('No events for this day') }}</div>

        <div class="mt-3">
          <BaseUIButton :label="$t('Add Event')" is-add-button :icon="IconCalendarPlus" @click="$emit('addEvent')" />
        </div>
      </div>

      <!-- SHIFTS (right) -->
      <div class="col-span-1">
        <!-- Zeitblöcke: Shifts-Spalte -->
        <div v-if="layoutBlocks.length" class="overflow-x-auto">
          <div>
            <div v-for="(block, bIdx) in layoutBlocks" :key="'sh-block-' + bIdx" class="">
              <div class="relative w-full border-l border-gray-200 rounded bg-white/50" :style="getShiftBlockStyle(block)">
                <template v-for="item in block.shiftItems" :key="item.key">
                  <div class="absolute rounded-b-lg" :style="getShiftItemStyle(item, block)">
                    <SingleShiftInDailyShiftView v-bind="item.props" @toggle="onShiftToggle(item.id, $event)" />
                  </div>
                </template>
              </div>
              <div v-if="block.gapAfter" class="text-center text-[11px] text-gray-500 py-1">~{{ block.gapAfter.human }} Abstand</div>
            </div>
          </div>
        </div>
        <div v-if="!hasAny" class="text-gray-300 text-center mt-2">{{ $t('No shifts for this day') }}</div>

        <div class="mt-3">
          <BaseUIButton :label="$t('Add Shift')" is-add-button :icon="IconCalendarUser" @click="$emit('addShift')" />
        </div>
      </div>
    </div>
  </div>

</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import SingleEventInDailyShiftView from '@/Pages/Shifts/DailyViewComponents/SingleEventInDailyShiftView.vue'
import SingleShiftInDailyShiftView from '@/Pages/Shifts/DailyViewComponents/SingleShiftInDailyShiftView.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import { IconCalendarPlus, IconCalendarUser } from '@tabler/icons-vue'

const props = defineProps({
  day: { type: String, required: true }, // 'YYYY-MM-DD'
  events: { type: Array, default: () => [] },
  shifts: { type: Array, default: () => [] },
  // pass-through props for children
  eventTypes: { type: [Array, Object], required: true },
  rooms: { type: [Array, Object], required: true },
  eventStatuses: { type: [Array, Object], required: true },
  first_project_calendar_tab_id: { type: Number, required: true },
  crafts: { type: [Array, Object], default: () => [] },
  shiftQualifications: { type: [Array, Object], default: () => [] },
  // Visual
  pxPerMin: { type: Number, default: 1.0 },
  gapThresholdMin: { type: Number, default: 90 },
})

// Helpers
const toMin = (hhmm?: string | null) => {
  if (!hhmm) return null
  const [h, m] = hhmm.split(':').map(n => parseInt(n, 10))
  if (Number.isNaN(h) || Number.isNaN(m)) return null
  return h * 60 + m
}
const clampDay = (min: number | null) => {
  if (min === null || Number.isNaN(min)) return null
  return Math.max(0, Math.min(1440, min))
}
const human = (mins: number) => {
  const h = Math.floor(mins / 60)
  const m = mins % 60
  if (h > 0 && m > 0) return `${h} Std. ${m} Min.`
  if (h > 0) return `${h} Std.`
  return `${m} Min.`
}

// Build separate lists for events and shifts with normalized timing
type Item = any

const eventItems = computed<Item[]>(() => {
  const items: Item[] = []
  for (const e of (props.events as any[] || [])) {
    if (e.allDay) continue
    const start = clampDay(toMin(e?.formattedDates?.startTime || e?.start || e?.start_time))
    const endRaw = clampDay(toMin(e?.formattedDates?.endTime || e?.end || e?.end_time))
    if (start === null || endRaw === null) continue
    const end = Math.max(start + 15, endRaw)
    items.push({
      key: `event-${e.id}`,
      id: e.id,
      type: 'event',
      startMin: start,
      endMin: end,
      payload: e,
      props: {
        event: e,
        eventTypes: props.eventTypes,
        rooms: props.rooms,
        first_project_calendar_tab_id: props.first_project_calendar_tab_id,
        eventStatuses: props.eventStatuses,
      },
    })
  }
  return items.sort((a, b) => a.startMin - b.startMin)
})

const shiftItems = computed<Item[]>(() => {
  const items: Item[] = []
  for (const s of (props.shifts as any[] || [])) {
    const start = clampDay(toMin(s?.start))
    let end = clampDay(toMin(s?.end))
    if (start === null || end === null) continue
    if (end <= start) end = 1440
    const endMin = Math.max(start + 15, end)
    items.push({
      key: `shift-${s.id}`,
      id: s.id,
      type: 'shift',
      startMin: start,
      endMin,
      payload: s,
      props: {
        shift: s,
        shiftQualifications: props.shiftQualifications,
        first_project_calendar_tab_id: props.first_project_calendar_tab_id,
        crafts: props.crafts,
      },
    })
  }
  return items.sort((a, b) => a.startMin - b.startMin)
})

const allDayEvents = computed(() => (props.events as any[] || []).filter((e: any) => e.allDay))

// Build unified blocks from both lists to keep vertical alignment between columns
function buildBlocks(evItems: Item[], shItems: Item[]) {
  const all = [...evItems, ...shItems].sort((a, b) => a.startMin - b.startMin)
  const blocks: any[] = []
  if (!all.length) return blocks

  let current: any = { startMin: all[0].startMin, endMin: all[0].endMin, items: [all[0]] }

  for (let i = 1; i < all.length; i++) {
    const it = all[i]
    if (it.startMin - current.endMin > props.gapThresholdMin) {
      finalizeBlock(current)
      blocks.push(current)
      const gapMin = it.startMin - current.endMin
      blocks.push({ type: 'gap', gapAfter: { mins: gapMin, human: human(gapMin) } })
      current = { startMin: it.startMin, endMin: it.endMin, items: [it] }
    } else {
      current.endMin = Math.max(current.endMin, it.endMin)
      current.items.push(it)
    }
  }

  finalizeBlock(current)
  blocks.push(current)

  // move gaps into previous blocks as property
  const compact: any[] = []
  for (let i = 0; i < blocks.length; i++) {
    const b = blocks[i]
    if (b.type === 'gap') {
      const last = compact[compact.length - 1]
      if (last) last.gapAfter = b.gapAfter
    } else {
      compact.push(b)
    }
  }
  return compact
}

function finalizeBlock(block: any) {
  // split items per type
  const ev = block.items.filter((x: any) => x.type === 'event')
  const sh = block.items.filter((x: any) => x.type === 'shift')

  assignLanes(ev)
  assignLanes(sh)

  // Kollisionen markieren: hat ein Item zeitliche Überschneidung mit mind. einem anderen in derselben Spalte?
  markCollisions(ev)
  markCollisions(sh)

  block.eventItems = ev
  block.shiftItems = sh
  block.eventLaneCount = Math.max(1, maxLaneIndex(ev) + 1)
  block.shiftLaneCount = Math.max(1, maxLaneIndex(sh) + 1)
  block.pixelHeight = Math.max(48, Math.round((block.endMin - block.startMin) * props.pxPerMin))
}

function assignLanes(items: Item[]) {
  // Greedy interval partitioning for overlapping items
  const laneEnds: number[] = []
  items.sort((a, b) => a.startMin - b.startMin)
  for (const it of items) {
    let placed = false
    for (let i = 0; i < laneEnds.length; i++) {
      if (it.startMin >= laneEnds[i]) {
        it.laneIndex = i
        laneEnds[i] = it.endMin
        placed = true
        break
      }
    }
    if (!placed) {
      it.laneIndex = laneEnds.length
      laneEnds.push(it.endMin)
    }
  }
}

function maxLaneIndex(items: Item[]) {
  return items.reduce((m: number, it: any) => Math.max(m, it.laneIndex ?? 0), 0)
}

const blocks = computed(() => buildBlocks(eventItems.value, shiftItems.value))

// Expand/Collapse-State der Kindkarten tracken
const eventsExpanded = ref<Record<number, boolean>>({})
const shiftsExpanded = ref<Record<number, boolean>>({})

function onEventToggle(id: number, state: boolean) {
  eventsExpanded.value[id] = state
}
function onShiftToggle(id: number, state: boolean) {
  shiftsExpanded.value[id] = state
}

// Initialzustände anhand der Kind-Defaults ableiten
watch(eventItems, (list) => {
  for (const it of list) {
    const e = it.payload
    if (eventsExpanded.value[it.id] === undefined) {
      // Default wie im Kind: expanded wenn Timelines existieren
      eventsExpanded.value[it.id] = Array.isArray(e?.timelines) && e.timelines.length > 0
    }
  }
}, { immediate: true, deep: true })

watch(shiftItems, (list) => {
  for (const it of list) {
    if (shiftsExpanded.value[it.id] === undefined) {
      // Default wie im Kind: Schichten starten expanded
      shiftsExpanded.value[it.id] = true
    }
  }
}, { immediate: true, deep: true })

// Min-Höhen in Pixel (geschätzt): collapsed = Headerhöhe, expanded = Details sichtbar
const COLLAPSED_MIN_EVENT = 56
const EXPANDED_MIN_EVENT = 140
const COLLAPSED_MIN_SHIFT = 56
// Angepasst: größere Basis für expandierte Schichten, damit Buttons unten nicht überdeckt werden
const EXPANDED_MIN_SHIFT = 220
// Mindestbreite pro Lane (ca. Tailwind min-w-64 = 16rem = 256px)
const LANE_MIN_WIDTH_PX = 256

// Heuristik: geschätzte Zeilenhöhe für eine Entity/Drop-Zeile in der aufgeklappten Schicht
const SHIFT_ROW_PX = 36
const SHIFT_ROW_GAP_PX = 4 // space-y-1 bzw. mt-1

function estimateShiftExpandedMinPx(shift: any): number {
  if (!shift) return EXPANDED_MIN_SHIFT
  // Personen zählen
  const usersCount = Array.isArray(shift.users) ? shift.users.length : 0
  const freelancersCount = Array.isArray(shift.freelancer) ? shift.freelancer.length : 0
  const serviceProvidersCount = Array.isArray(shift.serviceProviders) ? shift.serviceProviders.length : 0
  const peopleCount = usersCount + freelancersCount + serviceProvidersCount

  // Drop-Zeilen zählen (wie im Kind: eine Zeile pro Qualifikation mit Restbedarf)
  let dropRows = 0
  const sqs: any[] = Array.isArray(shift.shifts_qualifications) ? shift.shifts_qualifications : []
  for (const sq of sqs) {
    const qid = sq.shift_qualification_id
    const assignedUsers = (Array.isArray(shift.users) ? shift.users : []).filter((u: any) => u?.pivot?.shift_qualification_id === qid).length
    const assignedFreelancers = (Array.isArray(shift.freelancer) ? shift.freelancer : []).filter((f: any) => f?.pivot?.shift_qualification_id === qid).length
    const assignedService = (Array.isArray(shift.serviceProviders) ? shift.serviceProviders : []).filter((s: any) => s?.pivot?.shift_qualification_id === qid).length
    const totalAssigned = assignedUsers + assignedFreelancers + assignedService
    const remaining = (sq?.value ?? 0) - totalAssigned
    if (remaining > 0) dropRows++
  }

  const totalRows = peopleCount + dropRows
  if (totalRows <= 0) return EXPANDED_MIN_SHIFT

  // Basis: Kopfzeilenhöhe (wie COLLAPSED_MIN_SHIFT), plus Inhalt
  const headerPx = COLLAPSED_MIN_SHIFT
  const rowsPx = totalRows * SHIFT_ROW_PX
  const gapsPx = Math.max(0, totalRows - 1) * SHIFT_ROW_GAP_PX + SHIFT_ROW_GAP_PX // plus mt-1
  const estimated = headerPx + rowsPx + gapsPx
  // Mindestens EXPANDED_MIN_SHIFT
  return Math.max(EXPANDED_MIN_SHIFT, estimated)
}

function getEventItemHeightPx(item: any, block: any) {
  const timeHeight = Math.max(24, Math.round((item.endMin - item.startMin) * props.pxPerMin))
  const expanded = !!eventsExpanded.value[item.id]
  const minH = expanded ? EXPANDED_MIN_EVENT : COLLAPSED_MIN_EVENT
  // Anpassung laut Issue: Jeder Termin soll immer – egal ob mit oder ohne Kollision –
  // eine zur Dauer passende Höhe besitzen. Daher immer zeitproportional, aber nicht
  // kleiner als die jeweilige Minimalhöhe (collapsed/expanded).
  return Math.max(timeHeight, minH)
}
function getShiftItemHeightPx(item: any, block: any) {
  const timeHeight = Math.max(24, Math.round((item.endMin - item.startMin) * props.pxPerMin))
  const expanded = !!shiftsExpanded.value[item.id]
  const minH = expanded ? estimateShiftExpandedMinPx(item?.payload) : COLLAPSED_MIN_SHIFT
  // Immer zeitproportionale Höhe berücksichtigen, damit Schichten relativ zu Terminen skaliert werden
  // (Minimum bleibt je nach Expand-Status erhalten)
  return Math.max(timeHeight, minH)
}

// Kompakt-Mapping innerhalb eines Blocks:
// Nur Zeiten, in denen mindestens ein Item aktiv ist, werden zeitlich proportional dargestellt.
// Lücken zwischen diesen aktiven Segmenten werden auf einen kleinen, festen Divider reduziert.
const SEGMENT_GAP_PX = 8
const STACK_GAP_PX = 6

type CompactSegment = {
  startMin: number,
  endMin: number,
  baseTopPx: number,
  heightPx: number,
  proportional: boolean,
  eventStack?: Record<number, number>,
  shiftStack?: Record<number, number>,
}

function buildCompactSegments(block: any): CompactSegment[] {
  const items: any[] = Array.isArray(block?.items) ? block.items : []
  if (!items.length) return []
  const intervals = items
    .map(it => ({ s: Math.max(0, it.startMin), e: Math.max(0, it.endMin) }))
    .sort((a, b) => a.s - b.s)

  // Merge zu aktiven Segmenten
  const merged: { s: number, e: number }[] = []
  for (const iv of intervals) {
    if (!merged.length) {
      merged.push({ s: iv.s, e: iv.e })
    } else {
      const last = merged[merged.length - 1]
      if (iv.s <= last.e) {
        last.e = Math.max(last.e, iv.e)
      } else {
        merged.push({ s: iv.s, e: iv.e })
      }
    }
  }

  // In Pixelhöhen und Basistops umrechnen
  const segments: CompactSegment[] = []
  let y = 0
  for (let i = 0; i < merged.length; i++) {
    const m = merged[i]
    // Ermitteln, ob innerhalb dieses Intervalls zeitliche Überschneidungen zwischen IRGENDWELCHEN Items stattfinden
    // (spaltenübergreifend: Events <-> Shifts). Dadurch wird die vertikale Zeitachse
    // auch dann proportional, wenn sich nur ein Event mit einer Schicht überschneidet.
    const itemsInSeg = items.filter(it => it.startMin < m.e && it.endMin > m.s)
    let anyCollision = false
    for (let a = 0; a < itemsInSeg.length && !anyCollision; a++) {
      for (let b = a + 1; b < itemsInSeg.length; b++) {
        const A = itemsInSeg[a]
        const B = itemsInSeg[b]
        if (A.startMin < B.endMin && A.endMin > B.startMin) {
          anyCollision = true
          break
        }
      }
    }

    let heightPx: number
    if (anyCollision) {
      // Zeitproportional abbilden
      heightPx = Math.max(1, Math.round((m.e - m.s) * props.pxPerMin))
    } else {
      // Kein zeitproportionales Mapping nötig: Segmenthöhe = max sichtbare Kartenhöhe in diesem Segment
      const eventStack: Record<number, number> = {}
      const shiftStack: Record<number, number> = {}
      let evY = 0
      let shY = 0
      const evs = itemsInSeg.filter(it => it.type === 'event').sort((a,b)=> a.startMin - b.startMin)
      const shs = itemsInSeg.filter(it => it.type === 'shift').sort((a,b)=> a.startMin - b.startMin)
      for (const it of evs) {
        eventStack[it.id as number] = evY
        evY += getEventItemHeightPx(it, block) + STACK_GAP_PX
      }
      for (const it of shs) {
        shiftStack[it.id as number] = shY
        shY += getShiftItemHeightPx(it, block) + STACK_GAP_PX
      }
      // letzte Lücke abziehen
      if (evY > 0) evY -= STACK_GAP_PX
      if (shY > 0) shY -= STACK_GAP_PX
      heightPx = Math.max(1, Math.round(Math.max(evY, shY, 1)))
      segments.push({ startMin: m.s, endMin: m.e, baseTopPx: y, heightPx, proportional: false, eventStack, shiftStack })
      y += heightPx
      if (i < merged.length - 1) y += SEGMENT_GAP_PX
      continue
    }
    segments.push({ startMin: m.s, endMin: m.e, baseTopPx: y, heightPx, proportional: anyCollision })
    y += heightPx
    if (i < merged.length - 1) y += SEGMENT_GAP_PX
  }
  return segments
}

function topWithinBlock(block: any, minute: number): number {
  const segs: CompactSegment[] = Array.isArray(block?.compactSegments) ? block.compactSegments : []
  if (!segs.length) return 0
  // Vor dem ersten Segment
  if (minute <= segs[0].startMin) return segs[0].baseTopPx
  for (let i = 0; i < segs.length; i++) {
    const seg = segs[i]
    if (minute >= seg.startMin && minute <= seg.endMin) {
      if (seg.proportional) {
        return seg.baseTopPx + Math.round((minute - seg.startMin) * props.pxPerMin)
      }
      // Nicht-proportionaler (kompaktierter) Abschnitt: alle Zeiten innerhalb dieses Segments starten oben
      return seg.baseTopPx
    }
    if (i < segs.length - 1) {
      const next = segs[i + 1]
      if (minute > seg.endMin && minute < next.startMin) {
        // Lücke – auf Beginn des nächsten Segments springen
        return next.baseTopPx
      }
    }
  }
  // Nach dem letzten Segment
  const last = segs[segs.length - 1]
  return last.baseTopPx + last.heightPx
}

function getSegmentForMinute(block: any, minute: number): CompactSegment | null {
  const segs: CompactSegment[] = Array.isArray(block?.compactSegments) ? block.compactSegments : []
  for (const seg of segs) {
    if (minute >= seg.startMin && minute <= seg.endMin) return seg
  }
  return null
}

function getTopForItem(item: any, block: any): number {
  const seg = getSegmentForMinute(block, item.startMin)
  if (!seg) return 0
  if (seg.proportional) {
    // Falls im proportionalen Segment bereits justierte Tops berechnet wurden, diese verwenden,
    // damit expandierte Karten direkt anliegende nach unten schieben und nichts überlappt.
    if (item.type === 'event') {
      const adj = (seg as any).eventAdjustedTop?.[item.id]
      if (typeof adj === 'number') return adj
    } else {
      const adj = (seg as any).shiftAdjustedTop?.[item.id]
      if (typeof adj === 'number') return adj
    }
    return topWithinBlock(block, item.startMin)
  }
  // nicht-proportional: per Stack-Position
  if (item.type === 'event') {
    const off = seg.eventStack?.[item.id]
    return seg.baseTopPx + (off ?? 0)
  }
  const off = seg.shiftStack?.[item.id]
  return seg.baseTopPx + (off ?? 0)
}

// Blöcke um Layout-Höhe erweitern (Pixelhöhe = max Bottom beider Spalten)
const layoutBlocks = computed(() => {
  return (blocks.value || []).map((b: any) => {
    // Kompakt-Segmente vorbereiten (immer neu berechnen, da Min-Höhen dynamisch sind)
    b.compactSegments = buildCompactSegments(b)

    // Innerhalb proportionaler Segmente sicherstellen, dass direkt anliegende (nicht überlappende)
    // Items mit ihren Mindesthöhen nicht übereinander laufen. Wir justieren pro Spalte (Events/Shifts)
    // die Top-Offsets nach unten und vergrößern ggf. die Segmenthöhe. Nach jeder Segmentanpassung
    // werden die folgenden Segmente entsprechend nach unten verschoben.
    let runningBase = 0
    const pxPerMinLocal = props.pxPerMin
    for (const seg of b.compactSegments) {
      // Basisposition für dieses Segment setzen (inkl. Verschiebungen aus vorherigen Segmenten)
      seg.baseTopPx = (seg.baseTopPx ?? 0) + runningBase

      if (seg.proportional) {
        // Event-Spalte anpassen
        const evsInSeg: any[] = (b.eventItems || []).filter((it: any) => it.startMin < seg.endMin && it.startMin >= seg.startMin)
        evsInSeg.sort((a: any, b: any) => a.startMin - b.startMin || (a.id ?? 0) - (b.id ?? 0))
        let lastChainEndMinEv = seg.startMin
        let chainBottomEv = seg.baseTopPx
        let maxBottomEv = seg.baseTopPx
        seg.eventAdjustedTop = seg.eventAdjustedTop || {}
        for (const it of evsInSeg) {
          const baseTop = seg.baseTopPx + Math.round((it.startMin - seg.startMin) * pxPerMinLocal)
          // nur drücken, wenn nicht-überlappend mit vorheriger Kette
          if (it.startMin >= lastChainEndMinEv) {
            // Kette kann aktualisiert werden
            chainBottomEv = Math.max(chainBottomEv, maxBottomEv)
          }
          const top = Math.max(baseTop, chainBottomEv)
          const h = getEventItemHeightPx(it, b)
          const bottom = top + h
          seg.eventAdjustedTop[it.id as number] = top
          maxBottomEv = Math.max(maxBottomEv, bottom)
          // End-Minuten nur für Ketten ohne Überlappung aktualisieren
          lastChainEndMinEv = Math.max(lastChainEndMinEv, it.endMin)
        }

        // Shift-Spalte anpassen
        const shsInSeg: any[] = (b.shiftItems || []).filter((it: any) => it.startMin < seg.endMin && it.startMin >= seg.startMin)
        shsInSeg.sort((a: any, b: any) => a.startMin - b.startMin || (a.id ?? 0) - (b.id ?? 0))
        let lastChainEndMinSh = seg.startMin
        let chainBottomSh = seg.baseTopPx
        let maxBottomSh = seg.baseTopPx
        seg.shiftAdjustedTop = seg.shiftAdjustedTop || {}
        for (const it of shsInSeg) {
          const baseTop = seg.baseTopPx + Math.round((it.startMin - seg.startMin) * pxPerMinLocal)
          if (it.startMin >= lastChainEndMinSh) {
            chainBottomSh = Math.max(chainBottomSh, maxBottomSh)
          }
          const top = Math.max(baseTop, chainBottomSh)
          const h = getShiftItemHeightPx(it, b)
          const bottom = top + h
          seg.shiftAdjustedTop[it.id as number] = top
          maxBottomSh = Math.max(maxBottomSh, bottom)
          lastChainEndMinSh = Math.max(lastChainEndMinSh, it.endMin)
        }

        // Segmenthöhe ggf. erhöhen, damit justierte Bottoms hinein passen
        const needed = Math.max(maxBottomEv, maxBottomSh) - seg.baseTopPx
        if (needed > seg.heightPx) {
          const delta = needed - seg.heightPx
          seg.heightPx = needed
          runningBase += delta
        }
      }

      // Nach Abschluss dieses Segments Basis für das nächste Segment setzen
      runningBase += 0 // bereits in proportionalen Fällen oben berücksichtigt
      // Für nicht-proportionale Segmente wird heightPx durch buildCompactSegments korrekt gesetzt
    }

    // Gesamthöhe des Blocks auf Basis der tatsächlich verwendeten Tops/Höhen berechnen
    let maxBottom = 0
    for (const it of b.eventItems || []) {
      const top = getTopForItem(it, b)
      const h = getEventItemHeightPx(it, b)
      maxBottom = Math.max(maxBottom, top + h)
    }
    for (const it of b.shiftItems || []) {
      const top = getTopForItem(it, b)
      const h = getShiftItemHeightPx(it, b)
      maxBottom = Math.max(maxBottom, top + h)
    }
    return { ...b, pixelHeight: Math.max(48, maxBottom) }
  })
})

// Flags für Hinweise
const hasEvents = computed(() => (props.events as any[] || []).length > 0)
const hasShifts = computed(() => (props.shifts as any[] || []).length > 0)
const hasAny = computed(() => hasEvents.value || hasShifts.value)

// style helpers
function getEventItemStyle(item: any, block: any) {
  // Dynamische Breite/Position für Events basierend auf den tatsächlich zeitgleich
  // aktiven Events – und dabei links nach rechts nach Dauer sortiert (längster links).
  const overlapping: any[] = (() => {
    const arr: any[] = Array.isArray(block?.eventItems) ? block.eventItems : []
    return arr.filter((it) => it.startMin < item.endMin && it.endMin > item.startMin)
  })()
  const byDurationDesc = (a: any, b: any) => {
    const durA = (a.endMin - a.startMin)
    const durB = (b.endMin - b.startMin)
    if (durA !== durB) return durB - durA // länger zuerst
    if (a.startMin !== b.startMin) return a.startMin - b.startMin // früherer Start links
    return (a.id ?? 0) - (b.id ?? 0) // stabiler Tie-Breaker
  }
  const ordered = overlapping.slice().sort(byDurationDesc)
  const activeCount = Math.max(1, ordered.length)
  const rank = Math.max(0, ordered.findIndex((it) => it === item))
  const widthPct = 100 / activeCount
  const leftPct = widthPct * rank
  const topPx = getTopForItem(item, block)
  const heightPx = getEventItemHeightPx(item, block)
  // hasCollision an Kinder weitergeben (Dokumentation):
  // SingleEventInDailyShiftView nutzt diese Prop, um kompaktes Design bei Überschneidungen zu aktivieren.
  if (item?.props) item.props.hasCollision = !!item.hasCollision
  // Hintergrundfarbe gemäß EventType (mit schwacher Opacity)
  const typeHex: string | undefined = item?.payload?.eventType?.hex_code || item?.props?.event?.eventType?.hex_code
  const bg = typeHex ? hexToRgba(typeHex, 0.12) : 'transparent'
  return {
    top: topPx + 'px',
    height: heightPx + 'px',
    left: `calc(${leftPct}% + 2px)`,
    width: `calc(${widthPct}% - 4px)`,
    backgroundColor: bg,
  }
}

function getShiftItemStyle(item: any, block: any) {
  // Dynamische Breite pro Item abhängig von den tatsächlich zeitgleich aktiven Shift-Lanes.
  // Dadurch erhält eine Schicht volle Breite, wenn sie zum betreffenden Zeitpunkt alleine ist –
  // auch wenn später im Block mehrere parallele Schichten stattfinden.
  const activeLaneIndices: number[] = (() => {
    const set = new Set<number>()
    const items: any[] = Array.isArray(block?.shiftItems) ? block.shiftItems : []
    for (const it of items) {
      const lane = (it?.laneIndex ?? 0) as number
      // Überschneidung: strikt wie in markCollisions
      const overlaps = it.startMin < item.endMin && it.endMin > item.startMin
      if (overlaps) set.add(lane)
    }
    // Sicherstellen, dass mindestens die eigene Lane berücksichtigt wird
    set.add((item?.laneIndex ?? 0) as number)
    return Array.from(set).sort((a, b) => a - b)
  })()

  const activeCount = Math.max(1, activeLaneIndices.length)
  const rank = Math.max(0, activeLaneIndices.indexOf(item?.laneIndex ?? 0))
  const widthPct = 100 / activeCount
  const leftPct = widthPct * rank
  const topPx = getTopForItem(item, block)
  const heightPx = getShiftItemHeightPx(item, block)
  // hasCollision an Kinder weitergeben (Dokumentation):
  // SingleShiftInDailyShiftView nutzt diese Prop, um kompaktes Design bei Überschneidungen zu aktivieren.
  if (item?.props) item.props.hasCollision = !!item.hasCollision
  // Hintergrundfarbe gemäß Gewerk-Farbe (mit schwacher Opacity) – analog zu getEventItemStyle
  const craftHex: string | undefined = item?.payload?.craft?.color || item?.props?.shift?.craft?.color
  const bg = craftHex ? hexToRgba(craftHex, 0.12) : 'transparent'
  return {
    top: topPx + 'px',
    height: heightPx + 'px',
    left: `calc(${leftPct}% + 2px)`,
    width: `calc(${widthPct}% - 4px)`,
    backgroundColor: bg,
  }
}

// Block-Container-Stile mit Mindestbreite pro Lane
function getEventBlockStyle(block: any) {
  const lanes = Math.max(1, block.eventLaneCount || 1)
  const minW = Math.max(LANE_MIN_WIDTH_PX, lanes * LANE_MIN_WIDTH_PX)
  return {
    height: block.pixelHeight + 'px',
    minWidth: minW + 'px',
  }
}

function getShiftBlockStyle(block: any) {
  const lanes = Math.max(1, block.shiftLaneCount || 1)
  const minW = Math.max(LANE_MIN_WIDTH_PX, lanes * LANE_MIN_WIDTH_PX)
  return {
    height: block.pixelHeight + 'px',
    minWidth: minW + 'px',
  }
}

// Hilfsfunktion: markiert per O(n^2) innerhalb eines Blocks Überschneidungen
function markCollisions(items: Item[]) {
  if (!items || items.length < 2) return
  const n = items.length
  for (let i = 0; i < n; i++) {
    const a: any = items[i]
    a.hasCollision = false
  }
  for (let i = 0; i < n; i++) {
    for (let j = i + 1; j < n; j++) {
      const a: any = items[i]
      const b: any = items[j]
      // Überschneidung, wenn Zeitintervalle sich schneiden (strikt)
      if (a.startMin < b.endMin && a.endMin > b.startMin) {
        a.hasCollision = true
        b.hasCollision = true
      }
      // Break-Frühstück: wenn nächstes Item nach a.endMin startet und Liste sortiert ist, können wir weitergehen
      if (b.startMin >= a.endMin) break
    }
  }
}

// Kleine Hilfsfunktion: Hex-Farbe in rgba umrechnen, mit Alpha
function hexToRgba(hex: string, alpha = 1): string {
  try {
    let h = hex.trim()
    if (h.startsWith('#')) h = h.slice(1)
    if (h.length === 3) {
      h = h.split('').map(c => c + c).join('')
    }
    // Falls 8-stellig (#RRGGBBAA), Alpha aus Hex ignorieren und eigenes alpha verwenden
    if (h.length === 8) {
      h = h.slice(0, 6)
    }
    if (h.length !== 6) return 'transparent'
    const r = parseInt(h.slice(0, 2), 16)
    const g = parseInt(h.slice(2, 4), 16)
    const b = parseInt(h.slice(4, 6), 16)
    const a = Math.max(0, Math.min(1, alpha))
    return `rgba(${r}, ${g}, ${b}, ${a})`
  } catch (e) {
    return 'transparent'
  }
}
</script>

<style scoped>

</style>
