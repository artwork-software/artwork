<template>
  <div class="space-y-3 select-none">
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

// ---- Datums-Helfer: alles auf "YYYY-MM-DD" normalisieren -------------------
function normalizeToYmd(raw: any): string | null {
    if (!raw) return null
    const s = String(raw).trim()
    const datePart = s.split(' ')[0] // "YYYY-MM-DD HH:MM" -> "YYYY-MM-DD", "09.11.2025 00:00" -> "09.11.2025"

    // ISO: 2025-11-09
    if (/^\d{4}-\d{2}-\d{2}$/.test(datePart)) return datePart

    // Deutsch: 09.11.2025
    if (/^\d{2}\.\d{2}\.\d{4}$/.test(datePart)) {
        const [d, m, y] = datePart.split('.')
        return `${y}-${m}-${d}` // -> 2025-11-09
    }

    return null
}

// Aktueller Tag der Daily-Ansicht in Normalform
const currentDayYmd = computed(() => normalizeToYmd(props.day))

const eventItems = computed<Item[]>(() => {

    const items: Item[] = []
    const dayYmd = currentDayYmd.value

    for (const e of (props.events as any[] || [])) {
        // Event hat IMMER raw start/end im Format "YYYY-MM-DD HH:MM"
        const [rawStartDate, rawStartTime] = String(e.start || '').split(' ')
        const [rawEndDate, rawEndTime]     = String(e.end   || '').split(' ')

        const startDate = rawStartDate        // 2025-10-19
        const endDate   = rawEndDate          // 2025-10-20


        const start = clampDay(toMin(rawStartTime)) // z.B. 18:00 → 1080
        const endRaw = clampDay(toMin(rawEndTime))  // z.B. 02:00 → 120

        if (start === null || endRaw === null) continue

        const isMultiDay = !!startDate && !!endDate && startDate !== endDate

        let adjustedStart = start
        let adjustedEnd = endRaw
        let dayRole: 'single' | 'start' | 'middle' | 'end' = 'single'

        if (isMultiDay) {
            if (!dayYmd) continue
            // Tag nur anzeigen, wenn innerhalb [startDate, endDate]
            if (dayYmd < startDate || dayYmd > endDate!) continue

            if (dayYmd === startDate) {
                // Starttag: Original-Start → 24:00
                adjustedStart = start
                adjustedEnd   = 1440
                dayRole = 'start'
            } else if (dayYmd === endDate) {
                // Endtag: 00:00 → Endzeit
                adjustedStart = 0
                // Treat 23:59 (minute 1439) as end of day (1440) for proper collision detection
                let endTime = endRaw
                if (endTime === 1439) endTime = 1440
                adjustedEnd   = Math.max(15, endTime)
                dayRole = 'end'
            } else {
                // Mitteltag(e): 00:00 → 24:00
                adjustedStart = 0
                adjustedEnd   = 1440
                dayRole = 'middle'
            }
        } else {
            // Eintages-Event: nur am Starttag anzeigen (falls Datum gesetzt)
            if (startDate && dayYmd && dayYmd !== startDate) continue

            let end = endRaw
            if (end <= start) end = 1440
            // Treat 23:59 (minute 1439) as end of day (1440) for all-day events to ensure proper collision detection
            if (end === 1439) end = 1440
            adjustedStart = start
            adjustedEnd   = Math.max(start + 15, end)
        }
        items.push({
            key: `event-${e.id}`,
            id: e.id,
            type: 'event',
            startMin: adjustedStart,
            endMin: adjustedEnd,
            isMultiDay,
            dayRole,
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
    const dayYmd = currentDayYmd.value

    for (const s of (props.shifts as any[] || [])) {
        const startDate = normalizeToYmd(
            s.startDate ??
            s.start_date ??
            s.date ??
            s?.formattedDates?.startDate ??
            s?.formattedDates?.start
        )
        const endDate = normalizeToYmd(
            s.endDate ??
            s.end_date ??
            s?.formattedDates?.endDate ??
            s?.formattedDates?.end
        )

        const start  = clampDay(toMin(s?.start))
        const endRaw = clampDay(toMin(s?.end))
        if (start === null || endRaw === null) continue

        let adjustedStart = start
        let adjustedEnd   = Math.max(start + 15, endRaw)

        const isMultiDay = !!startDate && !!endDate && startDate !== endDate
        let dayRole: 'single' | 'start' | 'middle' | 'end' = 'single'

        if (isMultiDay) {
            if (!dayYmd) continue
            if (dayYmd < startDate || dayYmd > endDate) continue

            if (dayYmd === startDate) {
                // Starttag: original Start -> 24:00
                adjustedStart = start
                adjustedEnd   = 1440
                dayRole = 'start'
            } else if (dayYmd === endDate) {
                // Endtag: 00:00 -> Endzeit
                adjustedStart = 0
                // Treat 23:59 (minute 1439) as end of day (1440) for proper collision detection
                let endTime = endRaw
                if (endTime === 1439) endTime = 1440
                adjustedEnd   = Math.max(15, endTime)
                dayRole = 'end'
            } else {
                // Mitteltag
                adjustedStart = 0
                adjustedEnd   = 1440
                dayRole = 'middle'
            }
        } else {
            // Eintags-Schicht nur am Starttag anzeigen, falls Datum vorhanden
            if (startDate && dayYmd && dayYmd !== startDate) continue

            let end = endRaw
            if (end <= start) end = 1440
            // Treat 23:59 (minute 1439) as end of day (1440) for proper collision detection
            if (end === 1439) end = 1440
            adjustedStart = start
            adjustedEnd   = Math.max(start + 15, end)
        }

        items.push({
            key: `shift-${s.id}`,
            id: s.id,
            type: 'shift',
            startMin: adjustedStart,
            endMin: adjustedEnd,
            isMultiDay,
            dayRole,
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
  // Sort items: all-day events first (leftmost), then by earliest start time, then by longest duration
  items.sort((a, b) => {
    // All-day events always come first (leftmost lanes)
    const aIsAllDay = a.payload?.allDay === true
    const bIsAllDay = b.payload?.allDay === true
    if (aIsAllDay && !bIsAllDay) return -1
    if (!aIsAllDay && bIsAllDay) return 1

    // Sort by earliest start time
    if (a.startMin !== b.startMin) {
      return a.startMin - b.startMin
    }

    // For same start time, sort by longest duration first
    const aDuration = a.endMin - a.startMin
    const bDuration = b.endMin - b.startMin
    return bDuration - aDuration
  })

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
      // Anforderung: Termine in der Daily-Ansicht standardmäßig aufgeklappt lassen
      // (damit „Create new timeline“ sofort sichtbar ist).
      eventsExpanded.value[it.id] = true
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

// Schätzung für expandierte Mindesthöhe eines Events anhand seiner Timelines
function estimateEventExpandedMinPx(event: any): number {
  if (!event) return EXPANDED_MIN_EVENT
  const timelines = Array.isArray(event?.timelines) ? event.timelines.length : 0
  const headerPx = COLLAPSED_MIN_EVENT
  const rowsPx = timelines * SHIFT_ROW_PX
  const gapsPx = Math.max(0, timelines - 1) * SHIFT_ROW_GAP_PX + SHIFT_ROW_GAP_PX
  const estimated = headerPx + rowsPx + gapsPx
  return Math.max(EXPANDED_MIN_EVENT, estimated)
}

function getDailyTimeHeight(item: any): number {
    const duration = item.endMin - item.startMin
    return Math.max(24, Math.round(duration * props.pxPerMin))
}





function getEventItemHeightPx(item: any, block: any) {
    const timeHeight = getDailyTimeHeight(item)

    // Multi-Day: keine riesigen Expanded-Höhen erzwingen,
    // nur mind. Headerhöhe
    if (item.isMultiDay) {
        return Math.max(timeHeight, COLLAPSED_MIN_EVENT)
    }

    const expanded = !!eventsExpanded.value[item.id]
    const minH = expanded ? EXPANDED_MIN_EVENT : COLLAPSED_MIN_EVENT
    return Math.max(timeHeight, minH)
}

function getShiftItemHeightPx(item: any, block: any) {
    const timeHeight = getDailyTimeHeight(item)

    if (item.isMultiDay) {
        return Math.max(timeHeight, COLLAPSED_MIN_SHIFT)
    }

    const expanded = !!shiftsExpanded.value[item.id]
    const minH = expanded ? estimateShiftExpandedMinPx(item?.payload) : COLLAPSED_MIN_SHIFT
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
    // Ab jetzt IMMER zeitproportionale Segmente, damit zeitliche Relativität erhalten bleibt
    const heightPx = Math.max(1, Math.round((m.e - m.s) * props.pxPerMin))
    segments.push({ startMin: m.s, endMin: m.e, baseTopPx: y, heightPx, proportional: true })
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
  // In proportionalen Segmenten: rein zeitbasierter Top-Offset
  if (seg.proportional) return topWithinBlock(block, item.startMin)
  // Fallback (sollte nicht mehr vorkommen): Start oben im Segment
  return seg.baseTopPx
}

// Blöcke um Layout-Höhe erweitern (Pixelhöhe = max Bottom beider Spalten)
const layoutBlocks = computed(() => {
  return (blocks.value || []).map((b: any) => {
    // Kompakt-Segmente vorbereiten (immer neu berechnen, da Min-Höhen dynamisch sind)
    b.compactSegments = buildCompactSegments(b)

      const computeVisualMetrics = (arr: any[], type: 'event' | 'shift') => {
          for (const it of arr) {
              const top = getTopForItem(it, b)
              const vH = type === 'shift'
                  ? getShiftItemHeightPx(it, b)
                  : getEventItemHeightPx(it, b)
              ;(it as any)._vTop = top
              ;(it as any)._vHeight = vH
              ;(it as any)._vBottom = top + vH
          }
      }
      computeVisualMetrics(b.eventItems || [], 'event')
      computeVisualMetrics(b.shiftItems || [], 'shift')


      // Visuelle Lanes pro Spalte (Events/Schichten) basierend auf Rechteck-Überlappung zuweisen
    const assignVisualLanesByRect = (arr: any[]) => {
      const items = (arr || []).slice().sort((a, b) => (a._vTop ?? 0) - (b._vTop ?? 0) || (a.id ?? 0) - (b.id ?? 0))
      const laneBottoms: number[] = []
      for (const it of items) {
        let placed = false
        for (let i = 0; i < laneBottoms.length; i++) {
          if ((it._vTop ?? 0) >= laneBottoms[i]) {
            it._vLaneIndex = i
            laneBottoms[i] = Math.max(laneBottoms[i], it._vBottom ?? ((it._vTop ?? 0) + (it._vHeight ?? 0)))
            placed = true
            break
          }
        }
        if (!placed) {
          it._vLaneIndex = laneBottoms.length
          laneBottoms.push(it._vBottom ?? ((it._vTop ?? 0) + (it._vHeight ?? 0)))
        }
      }
      return Math.max(1, laneBottoms.length)
    }

    b.eventVisualLaneCount = assignVisualLanesByRect(b.eventItems || [])
    b.shiftVisualLaneCount = assignVisualLanesByRect(b.shiftItems || [])

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
  // Breite/Position für Events basierend auf VISUELLER Überlappung (Rechtecke schneiden sich?).
  // Dadurch werden Events nebeneinander dargestellt, wenn ihre ausgeklappten Höhen kollidieren –
  // auch ohne direkte Zeitüberschneidung.
  const ensureVisualMetrics = (it: any) => {
    if (it._vTop === undefined || it._vHeight === undefined || it._vBottom === undefined) {
      const top = getTopForItem(it, block)
      const timeH = Math.max(24, Math.round((it.endMin - it.startMin) * props.pxPerMin))
      const vH = Math.max(timeH, EXPANDED_MIN_EVENT)
      it._vTop = top
      it._vHeight = vH
      it._vBottom = top + vH
    }
  }
  const arr: any[] = Array.isArray(block?.eventItems) ? block.eventItems : []
  for (const it of arr) ensureVisualMetrics(it)
  ensureVisualMetrics(item)
  const overlapsRect = (a: any, b: any) => (a._vTop < b._vBottom) && (a._vBottom > b._vTop)
  const overlapping = arr.filter(it => overlapsRect(it, item))
  const activeLaneIndices = Array.from(new Set(overlapping.map(it => (it._vLaneIndex ?? 0)))).sort((a,b)=>a-b)
  const activeCount = Math.max(1, activeLaneIndices.length)
  const rank = Math.max(0, activeLaneIndices.indexOf(item._vLaneIndex ?? 0))
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
  // Breite/Position für Schichten basierend auf VISUELLER Überlappung (Rechtecke schneiden sich?).
  const ensureVisualMetrics = (it: any) => {
    if (it._vTop === undefined || it._vHeight === undefined || it._vBottom === undefined) {
      const top = getTopForItem(it, block)
      const timeH = Math.max(24, Math.round((it.endMin - it.startMin) * props.pxPerMin))
      const vH = Math.max(timeH, estimateShiftExpandedMinPx(it?.payload))
      it._vTop = top
      it._vHeight = vH
      it._vBottom = top + vH
    }
  }
  const arr: any[] = Array.isArray(block?.shiftItems) ? block.shiftItems : []
  for (const it of arr) ensureVisualMetrics(it)
  ensureVisualMetrics(item)
  const overlapsRect = (a: any, b: any) => (a._vTop < b._vBottom) && (a._vBottom > b._vTop)
  const overlapping = arr.filter(it => overlapsRect(it, item))
  const activeLaneIndices = Array.from(new Set(overlapping.map(it => (it._vLaneIndex ?? 0)))).sort((a,b)=>a-b)
  const activeCount = Math.max(1, activeLaneIndices.length)
  const rank = Math.max(0, activeLaneIndices.indexOf(item._vLaneIndex ?? 0))
  const widthPct = 100 / activeCount
  const leftPct = widthPct * rank
  const topPx = getTopForItem(item, block)
  const heightPx = getShiftItemHeightPx(item, block)
  // Wichtig laut Vorgabe: Schicht-Styling ändert sich nicht bei Kollisionen; daher kein hasCollision-Prop.
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
  const lanes = Math.max(1, block.eventVisualLaneCount || block.eventLaneCount || 1)
  const minW = Math.max(LANE_MIN_WIDTH_PX, lanes * LANE_MIN_WIDTH_PX)
  return {
    height: block.pixelHeight + 'px',
    minWidth: minW + 'px',
  }
}

function getShiftBlockStyle(block: any) {
  const lanes = Math.max(1, block.shiftVisualLaneCount || block.shiftLaneCount || 1)
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
