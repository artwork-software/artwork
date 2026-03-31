<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = withDefaults(defineProps<{
    rows: Array<{ key: string | number; data: any }>
    cols: Array<{ key: string | number; data: any }>

    rowHeight: number
    rowHeights?: number[]
    colWidth: number
    colWidths?: number[]

    stickyColWidth: number
    headerHeight: number

    overscanRows?: number
    overscanCols?: number

    noVirtualize?: boolean
}>(), {
    overscanRows: 6,
    overscanCols: 3,
    noVirtualize: false,
})

function getRowHeight(r: number): number {
    return props.rowHeights?.[r] ?? props.rowHeight
}

function getColWidth(c: number): number {
    return props.colWidths?.[c] ?? props.colWidth
}

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

// defineExpose moved to end of script (after c0 is declared)

const topPadding = computed(() => props.headerHeight)

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

// Precompute cumulative row tops for variable row heights
const rowTops = computed(() => {
    const tops: number[] = []
    let y = 0
    for (let r = 0; r < props.rows.length; r++) {
        tops.push(y)
        y += getRowHeight(r)
    }
    tops.push(y) // sentinel: total height of all rows
    return tops
})

const totalH = computed(() => topPadding.value + (rowTops.value[props.rows.length] ?? 0))

const scrollY = computed(() => Math.max(0, st.value - topPadding.value))

// Binary search for first row visible
function findFirstRow(scrollTop: number): number {
    const tops = rowTops.value
    let lo = 0, hi = props.rows.length - 1
    while (lo < hi) {
        const mid = (lo + hi + 1) >> 1
        if (tops[mid] <= scrollTop) lo = mid
        else hi = mid - 1
    }
    return lo
}

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

const r0 = computed(() =>
    Math.max(0, findFirstRow(scrollY.value) - props.overscanRows)
)
const r1 = computed(() => {
    const bottomY = scrollY.value + vh.value
    const tops = rowTops.value
    let r = findFirstRow(bottomY)
    // include rows that are partially visible
    while (r < props.rows.length - 1 && tops[r] < bottomY) r++
    return Math.min(props.rows.length - 1, r + props.overscanRows)
})

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
    const out: Array<{ r: number; row: any; top: number; height: number }> = []
    const tops = rowTops.value
    for (let r = r0.value; r <= r1.value; r++) {
        out.push({
            r,
            row: props.rows[r],
            top: topPadding.value + tops[r],
            height: getRowHeight(r),
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

// -- noVirtualize: all rows/cols for full render --
const allRows = computed(() =>
    props.rows.map((row, r) => ({ r, row, height: getRowHeight(r) }))
)

const allCols = computed(() =>
    props.cols.map((col, c) => ({ c, col, width: getColWidth(c) }))
)

// First actually visible column (without overscan)
const firstVisibleColIndex = computed(() => findFirstCol(sl.value))

defineExpose({
    viewportEl,
    getViewportEl: () => viewportEl.value,
    firstVisibleColIndex,
})
</script>

<template>
    <!-- ==================== NO-VIRTUALIZE MODE ==================== -->
    <div v-if="noVirtualize" ref="viewportEl" class="relative w-full overflow-auto pointer-events-auto">
        <!-- Sticky Header Row -->
        <div
            class="sticky top-0 z-40 bg-artwork-navigation-background/95 backdrop-blur
                   border-b border-white/10 flex"
            :style="{ height: headerHeight + 'px', width: (stickyColWidth + (colOffsets[cols.length] ?? 0)) + 'px' }"
        >
            <div
                class="sticky left-0 z-50 shrink-0 flex items-stretch
                       bg-artwork-navigation-background/95 backdrop-blur
                       border-r border-white/10"
                :style="{ width: stickyColWidth + 'px', height: headerHeight + 'px' }"
            >
                <slot name="corner" />
            </div>
            <div
                v-for="vc in allCols"
                :key="vc.col.key"
                class="shrink-0"
                :style="{ width: vc.width + 'px', height: headerHeight + 'px' }"
            >
                <slot name="colHeader" :day="vc.col.data" :colIndex="vc.c" />
            </div>
        </div>

        <!-- Body Rows -->
        <div
            v-for="vr in allRows"
            :key="vr.row.key"
            class="relative flex border-b border-dashed border-gray-400"
            :style="{ width: (stickyColWidth + (colOffsets[cols.length] ?? 0)) + 'px' }"
        >
            <!-- Sticky left column -->
            <div
                class="sticky left-0 z-30 shrink-0 flex items-center bg-white"
                :style="{ width: stickyColWidth + 'px' }"
            >
                <slot name="rowHeader" :room="vr.row.data" :rowIndex="vr.r" />
            </div>

            <!-- All cells -->
            <div
                v-for="vc in allCols"
                :key="vc.col.key"
                class="shrink-0 border-r border-gray-200"
                :style="{ width: vc.width + 'px' }"
            >
                <slot
                    name="cell"
                    :room="vr.row.data"
                    :day="vc.col.data"
                    :rowIndex="vr.r"
                    :colIndex="vc.c"
                />
            </div>
        </div>
    </div>

    <!-- ==================== VIRTUAL MODE (default) ==================== -->
    <div v-else ref="viewportEl" class="relative w-full overflow-auto pointer-events-auto">
        <div class="relative" :style="{ width: totalW + 'px', height: totalH + 'px' }">

            <!-- Sticky Header Row -->
            <div
                class="sticky top-0 z-40 bg-artwork-navigation-background/95 backdrop-blur
         border-b border-white/10"
                :style="{ height: headerHeight + 'px' }"
            >
                <div
                    class="sticky left-0 z-50 inline-flex items-stretch
           bg-artwork-navigation-background/95 backdrop-blur
           border-r border-white/10"
                    :style="{ width: stickyColWidth + 'px', height: headerHeight + 'px' }"
                >
                    <slot name="corner" />
                </div>

                <div
                    v-for="vc in visibleCols"
                    :key="vc.col.key"
                    class="absolute top-0 flex-none"
                    :style="{ left: vc.left + 'px', width: vc.width + 'px', height: headerHeight + 'px' }"
                >
                    <slot name="colHeader" :day="vc.col.data" :colIndex="vc.c" />
                </div>
            </div>

            <!-- Body (virtual rows + cols) -->
            <div
                v-for="vr in visibleRows"
                :key="vr.row.key"
                class="absolute left-0"
                :style="{ top: vr.top + 'px', height: vr.height + 'px', width: totalW + 'px' }"
            >
                <!-- Durchgehende gestrichelte Trennlinie zwischen Räumen -->
                <div class="absolute bottom-0 left-0 w-full border-b border-dashed border-gray-400 z-40 pointer-events-none"></div>
                <div class="relative h-full w-full">
                    <!-- Sticky left column -->
                    <div
                        class="sticky left-0 z-30 h-full flex items-center bg-white"
                        :style="{ width: stickyColWidth + 'px' }"
                    >
                        <slot name="rowHeader" :room="vr.row.data" :rowIndex="vr.r" />
                    </div>

                    <!-- Visible cells -->
                    <div
                        v-for="vc in visibleCols"
                        :key="vc.col.key"
                        class="absolute top-0 flex-none"
                        :style="{ left: vc.left + 'px', width: vc.width + 'px', height: vr.height + 'px' }"
                    >
                        <slot
                            name="cell"
                            :room="vr.row.data"
                            :day="vc.col.data"
                            :rowIndex="vr.r"
                            :colIndex="vc.c"
                        />
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
