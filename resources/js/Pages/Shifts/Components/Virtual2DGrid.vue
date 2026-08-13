<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = withDefaults(defineProps<{
    rows: any[]
    cols: any[]
    rowHeight: number
    colWidth: number
    colWidths?: number[]
    stickyColWidth: number
    overscanRows?: number
    overscanCols?: number
    /** z.B. Höhe deiner Sticky-Toolbar im Grid (optional) */
    topPadding?: number
    /** > 0 rendert eine Kopfzeile über dem Scrollbereich (Slot #colHeader), horizontal synchronisiert */
    headerHeight?: number
}>(), {
    overscanRows: 6,
    overscanCols: 3,
    topPadding: 0,
    headerHeight: 0,
})

function getColWidth(c: number): number {
    return props.colWidths?.[c] ?? props.colWidth
}

/** DAS ist der Scroll-Container */
const viewportEl = ref<HTMLElement | null>(null)

const st = ref(0)
const sl = ref(0)
const vw = ref(0)
const vh = ref(0)

function onScroll() {
    const v = viewportEl.value
    if (!v) return
    st.value = v.scrollTop
    sl.value = v.scrollLeft
}

let ro: ResizeObserver | null = null
onMounted(() => {
    const v = viewportEl.value
    if (!v) return

    const update = () => {
        vw.value = v.clientWidth
        vh.value = v.clientHeight
    }
    update()

    ro = new ResizeObserver(update)
    ro.observe(v)

    v.addEventListener('scroll', onScroll, { passive: true })
})

onBeforeUnmount(() => {
    const v = viewportEl.value
    if (v) v.removeEventListener('scroll', onScroll)
    ro?.disconnect()
})

defineExpose({
    viewportEl,
    getViewportEl: () => viewportEl.value,
})

// Precompute cumulative column offsets for variable column widths
const colOffsets = computed(() => {
    const offsets: number[] = []
    let x = 0
    for (let c = 0; c < props.cols.length; c++) {
        offsets.push(x)
        x += getColWidth(c)
    }
    offsets.push(x) // sentinel: total width of all cols
    return offsets
})

const totalW = computed(() => props.stickyColWidth + (colOffsets.value[props.cols.length] ?? 0))
const totalH = computed(() => props.topPadding + props.rows.length * props.rowHeight)

const scrollY = computed(() => Math.max(0, st.value - props.topPadding))

const r0 = computed(() =>
    Math.max(0, Math.floor(scrollY.value / props.rowHeight) - props.overscanRows)
)
const r1 = computed(() =>
    Math.min(
        props.rows.length - 1,
        Math.ceil((scrollY.value + vh.value) / props.rowHeight) + props.overscanRows
    )
)

// Binary search for first col visible
function findFirstCol(scrollLeft: number): number {
    const offs = colOffsets.value
    let lo = 0, hi = props.cols.length - 1
    while (lo < hi) {
        const mid = (lo + hi + 1) >> 1
        if (offs[mid] <= scrollLeft) lo = mid
        else hi = mid - 1
    }
    return lo
}

const c0 = computed(() =>
    Math.max(0, findFirstCol(sl.value) - props.overscanCols)
)
const c1 = computed(() => {
    const rightX = sl.value + vw.value
    const offs = colOffsets.value
    let c = findFirstCol(rightX)
    while (c < props.cols.length - 1 && offs[c] < rightX) c++
    return Math.min(props.cols.length - 1, c + props.overscanCols)
})

const visibleRows = computed(() => {
    const out: any[] = []
    for (let r = r0.value; r <= r1.value; r++) {
        out.push({
            r,
            row: props.rows[r],
            top: props.topPadding + r * props.rowHeight,
        })
    }
    return out
})

const visibleCols = computed(() => {
    const out: Array<{ c: number; col: any; left: number; width: number }> = []
    const offs = colOffsets.value
    for (let c = c0.value; c <= c1.value; c++) {
        out.push({ c, col: props.cols[c], left: props.stickyColWidth + offs[c], width: getColWidth(c) })
    }
    return out
})
</script>

<template>
    <div class="flex h-full w-full flex-col">
        <!-- Kopfzeile (z.B. KW-Labels), horizontal mit dem Scrollbereich synchronisiert;
             pt-14 hält sie unter der fixed Toolbar des User-Overview-Panels -->
        <div v-if="headerHeight > 0" class="shrink-0 pt-14">
            <div class="relative overflow-hidden" :style="{ height: headerHeight + 'px' }">
                <div class="absolute inset-y-0 left-0 right-0" :style="{ transform: `translateX(${-sl}px)` }">
                    <div
                        v-for="vc in visibleCols"
                        :key="`h_${vc.col.fullDay ?? vc.c}`"
                        class="absolute top-0"
                        :style="{ left: vc.left + 'px', width: vc.width + 'px', height: headerHeight + 'px' }"
                    >
                        <slot name="colHeader" :day="vc.col" :colIndex="vc.c" />
                    </div>
                </div>
                <!-- Abdeckung über der Sticky-Spalte, damit Header-Labels darunter durchscrollen -->
                <div
                    class="absolute left-0 top-0 z-10 bg-surface-inverse"
                    :style="{ width: stickyColWidth + 'px', height: headerHeight + 'px' }"
                ></div>
            </div>
        </div>

        <div
            ref="viewportEl"
            class="relative w-full overflow-auto pointer-events-auto"
            :class="headerHeight > 0 ? 'min-h-0 flex-1' : 'h-full pt-10'"
        >
        <!-- Spacer erzeugt echte Scrollbars -->
        <div class="relative" :style="{ width: totalW + 'px', height: totalH + 'px' }">
            <!-- Spalten-Overlay (z.B. durchgehende KW-Trennlinie): exakt an der Spaltenkante,
                 ohne das p-0.5 der Zellen — Zellen-Borders wären 2px versetzt und pro Zeile unterbrochen -->
            <div
                v-for="vc in visibleCols"
                :key="`ov_${vc.col.fullDay ?? vc.c}`"
                class="pointer-events-none absolute top-0"
                :style="{ left: vc.left + 'px', width: vc.width + 'px', height: totalH + 'px' }"
            >
                <slot name="colOverlay" :day="vc.col" :colIndex="vc.c" />
            </div>
            <div
                v-for="vr in visibleRows"
                :key="vr.row.key"
                class="absolute left-0"
                :style="{ top: vr.top + 'px', height: rowHeight + 'px', width: totalW + 'px' }"
            >
                <div class="relative h-full w-full">
                    <!-- Sticky left column -->
                    <div
                        class="sticky left-0 z-10 h-full flex items-center bg-surface-inverse"
                        :style="{ width: stickyColWidth + 'px' }"
                    >
                        <slot name="rowHeader" :row="vr.row" :rowIndex="vr.r" />
                    </div>

                    <!-- Visible cols only -->
                    <div
                        v-for="vc in visibleCols"
                        :key="vc.col.fullDay ?? vc.c"
                        class="absolute top-0 flex-none p-0.5"
                        :style="{ left: vc.left + 'px', width: vc.width + 'px', height: rowHeight + 'px' }"
                    >
                        <slot name="cell" :row="vr.row" :day="vc.col" :rowIndex="vr.r" :colIndex="vc.c" />
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</template>
