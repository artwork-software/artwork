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
}>(), {
    overscanRows: 6,
    overscanCols: 3,
    topPadding: 0,
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
    <div ref="viewportEl" class="relative h-full w-full overflow-auto pointer-events-auto pt-10">
        <!-- Spacer erzeugt echte Scrollbars -->
        <div class="relative" :style="{ width: totalW + 'px', height: totalH + 'px' }">
            <div
                v-for="vr in visibleRows"
                :key="vr.row.key"
                class="absolute left-0"
                :style="{ top: vr.top + 'px', height: rowHeight + 'px', width: totalW + 'px' }"
            >
                <div class="relative h-full w-full">
                    <!-- Sticky left column -->
                    <div
                        class="sticky left-0 z-10 h-full flex items-center bg-artwork-navigation-background"
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
</template>
