<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = withDefaults(defineProps<{
    rows: any[]
    cols: any[]
    rowHeight: number
    colWidth: number
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

/** DAS ist der Scroll-Container */
const viewportEl = ref<HTMLElement | null>(null)

const st = ref(0)
const sl = ref(0)
const vw = ref(0)
const vh = ref(0)

let raf = 0
function onScroll() {
    const v = viewportEl.value
    if (!v) return
    if (raf) cancelAnimationFrame(raf)
    raf = requestAnimationFrame(() => {
        st.value = v.scrollTop
        sl.value = v.scrollLeft
    })
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
    if (raf) cancelAnimationFrame(raf)
})

defineExpose({
    viewportEl,
    getViewportEl: () => viewportEl.value,
})

const totalW = computed(() => props.stickyColWidth + props.cols.length * props.colWidth)
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

const c0 = computed(() =>
    Math.max(0, Math.floor(sl.value / props.colWidth) - props.overscanCols)
)
const c1 = computed(() =>
    Math.min(
        props.cols.length - 1,
        Math.ceil((sl.value + vw.value) / props.colWidth) + props.overscanCols
    )
)

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
    const out: any[] = []
    for (let c = c0.value; c <= c1.value; c++) {
        out.push({ c, col: props.cols[c] })
    }
    return out
})

const colsLeft = computed(() => props.stickyColWidth + c0.value * props.colWidth)
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
                    <div class="absolute top-0 h-full flex" :style="{ left: colsLeft + 'px' }">
                        <div
                            v-for="vc in visibleCols"
                            :key="vc.col.fullDay ?? vc.c"
                            class="flex-none p-0.5"
                            :style="{ width: colWidth + 'px', height: rowHeight + 'px' }"
                        >
                            <slot name="cell" :row="vr.row" :day="vc.col" :rowIndex="vr.r" :colIndex="vc.c" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
