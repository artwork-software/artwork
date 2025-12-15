<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = withDefaults(defineProps<{
    rows: Array<{ key: string | number; data: any }>
    cols: Array<{ key: string | number; data: any }>

    rowHeight: number
    colWidth: number

    stickyColWidth: number
    headerHeight: number

    overscanRows?: number
    overscanCols?: number
}>(), {
    overscanRows: 6,
    overscanCols: 3,
})

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

const topPadding = computed(() => props.headerHeight)

const totalW = computed(() => props.stickyColWidth + props.cols.length * props.colWidth)
const totalH = computed(() => topPadding.value + props.rows.length * props.rowHeight)

const scrollY = computed(() => Math.max(0, st.value - topPadding.value))

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
    const out: Array<{ r: number; row: any; top: number }> = []
    for (let r = r0.value; r <= r1.value; r++) {
        out.push({
            r,
            row: props.rows[r],
            top: topPadding.value + r * props.rowHeight,
        })
    }
    return out
})

const visibleCols = computed(() => {
    const out: Array<{ c: number; col: any }> = []
    for (let c = c0.value; c <= c1.value; c++) out.push({ c, col: props.cols[c] })
    return out
})

const colsLeft = computed(() => props.stickyColWidth + c0.value * props.colWidth)
</script>

<template>
    <div ref="viewportEl" class="relative w-full overflow-auto pointer-events-auto">
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
                    class="absolute top-0 flex"
                    :style="{ left: colsLeft + 'px', height: headerHeight + 'px' }"
                >
                    <div
                        v-for="vc in visibleCols"
                        :key="vc.col.key"
                        class="flex-none"
                        :style="{ width: colWidth + 'px', height: headerHeight + 'px' }"
                    >
                        <slot name="colHeader" :day="vc.col.data" :colIndex="vc.c" />
                    </div>
                </div>
            </div>

            <!-- Body (virtual rows + cols) -->
            <div
                v-for="vr in visibleRows"
                :key="vr.row.key"
                class="absolute left-0"
                :style="{ top: vr.top + 'px', height: rowHeight + 'px', width: totalW + 'px' }"
            >
                <div class="relative h-full w-full">
                    <!-- Sticky left column -->
                    <div
                        class="sticky left-0 z-30 h-full flex items-center bg-white"
                        :style="{ width: stickyColWidth + 'px' }"
                    >
                        <slot name="rowHeader" :room="vr.row.data" :rowIndex="vr.r" />
                    </div>

                    <!-- Visible cells -->
                    <div class="absolute top-0 h-full flex" :style="{ left: colsLeft + 'px' }">
                        <div
                            v-for="vc in visibleCols"
                            :key="vc.col.key"
                            class="flex-none"
                            :style="{ width: colWidth + 'px', height: rowHeight + 'px' }"
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
    </div>
</template>
