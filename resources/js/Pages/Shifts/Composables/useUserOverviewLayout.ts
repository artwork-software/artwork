import { ref, computed, onMounted, onUnmounted, type Ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

type UseUserOverviewLayoutOptions = {
    headerHeight?: number // Standard: 100
    topGap?: number       // Standard: 16
    minTop?: number       // Standard: 100
    minMain?: number      // Standard: 200
    initialHeight?: number
    pageProps?: Record<string, any>
}

function debounce<T extends (...args: any[]) => void>(fn: T, wait = 300) {
    let t: number | undefined
    return (...args: Parameters<T>) => {
        if (t) window.clearTimeout(t)
        t = window.setTimeout(() => fn(...args), wait)
    }
}

export function useUserOverviewLayout(
    showUserOverview: Ref<boolean>,
    options: UseUserOverviewLayoutOptions = {},
) {
    const HEADER_H = options.headerHeight ?? 100
    const TOP_GAP = options.topGap ?? 16
    const MIN_TOP = options.minTop ?? 100
    const MIN_MAIN = options.minMain ?? 200
    const pageProps = options.pageProps ?? {}

    const page = usePage() as unknown as {
        props: {
            auth?: { user?: { id: number | string; drawer_height?: number } }
        }
    }

    const clamp = (n: number, min: number, max: number) => Math.min(Math.max(n, min), max)

    // Statt in Hot-Paths direkt window.innerHeight zu lesen, halten wir es in einem Ref aktuell.
    const innerHeight = ref<number>(typeof window !== 'undefined' ? window.innerHeight : 0)

    const startY = ref(0)
    const startHeight = ref(0)

    const windowHeight = ref<number>(innerHeight.value)
    const screenHeight = ref<number>(typeof window !== 'undefined' ? window.screen.height : 0)

    const heightRatio = ref(0.4)

    const userOverviewHeight = ref<number>(
        options.initialHeight ??
        (pageProps.auth?.user?.drawer_height ??
            Math.round(innerHeight.value * heightRatio.value))
    )

    let onMouseMove: ((e: MouseEvent) => void) | null = null
    let onMouseUp: ((e: MouseEvent) => void) | null = null
    let onWindowResize: (() => void) | null = null

    const availableHeight = () => Math.max(0, innerHeight.value - HEADER_H)

    const maxTop = computed(() => {
        const avail = availableHeight()
        return Math.max(MIN_TOP, avail - MIN_MAIN - TOP_GAP)
    })

    // Vermeidet unnötige Reactive-Updates (Performance + weniger Re-Renders)
    function setIfChanged(refObj: { value: number }, next: number) {
        if (refObj.value !== next) refObj.value = next
    }

    function updateLayout(initial = false) {
        const avail = availableHeight()

        if (!showUserOverview.value) {
            // wenn Overview aus: Hauptbereich = verfügbar (mindestens MIN_MAIN)
            const nextMain = Math.max(MIN_MAIN, avail - TOP_GAP)
            setIfChanged(windowHeight, nextMain)
            return
        }

        // clamp overview height
        const nextOverview = clamp(userOverviewHeight.value, MIN_TOP, maxTop.value)
        if (nextOverview !== userOverviewHeight.value) userOverviewHeight.value = nextOverview

        const nextMain = Math.max(MIN_MAIN, avail - nextOverview - TOP_GAP)
        setIfChanged(windowHeight, nextMain)

        if (!initial && avail > 0) {
            const nextRatio = clamp(nextOverview / avail, 0.05, 0.9)
            // kleines Epsilon, um "micro jitter" zu vermeiden
            if (Math.abs(nextRatio - heightRatio.value) > 0.0001) heightRatio.value = nextRatio
        }
    }

    // rAF Throttle für Drag-Updates
    let dragRafId: number | null = null
    let pendingHeight: number | null = null

    function scheduleDragUpdate(nextHeight: number) {
        pendingHeight = nextHeight
        if (dragRafId) return

        dragRafId = requestAnimationFrame(() => {
            dragRafId = null
            if (pendingHeight == null) return
            userOverviewHeight.value = pendingHeight
            pendingHeight = null
            updateLayout()
        })
    }

    function startResize(e: MouseEvent) {
        e.preventDefault()

        startY.value = e.clientY
        startHeight.value = userOverviewHeight.value

        onMouseMove = (evt: MouseEvent) => {
            const diff = startY.value - evt.clientY
            // nur "planen", nicht sofort 100x pro Sekunde updaten
            scheduleDragUpdate(startHeight.value + diff)
        }

        onMouseUp = (evt: MouseEvent) => {
            evt.preventDefault()

            // falls noch ein rAF pending ist, sofort flushen
            if (dragRafId) {
                cancelAnimationFrame(dragRafId)
                dragRafId = null
                if (pendingHeight != null) {
                    userOverviewHeight.value = pendingHeight
                    pendingHeight = null
                    updateLayout()
                }
            }

            const avail = availableHeight()
            if (avail > 0) {
                const nextRatio = clamp(userOverviewHeight.value / avail, 0.05, 0.9)
                if (Math.abs(nextRatio - heightRatio.value) > 0.0001) heightRatio.value = nextRatio
            }

            if (onMouseMove) document.removeEventListener('mousemove', onMouseMove)
            if (onMouseUp) document.removeEventListener('mouseup', onMouseUp)
            onMouseMove = null
            onMouseUp = null

            saveUserOverviewHeight()
        }

        // mousemove kann passiv sein (wir rufen da kein preventDefault)
        document.addEventListener('mousemove', onMouseMove, { passive: true })
        document.addEventListener('mouseup', onMouseUp as EventListener)
    }

    const saveUserOverviewHeight = debounce(() => {
        applyUserOverviewHeight()
    }, 500)

    function applyUserOverviewHeight() {
        const userId = page.props.auth?.user?.id
        if (userId == null) return

        router.patch(
            route('user.update.userOverviewHeight', { user: userId }),
            { drawer_height: userOverviewHeight.value },
            { preserveScroll: true, preserveState: true },
        )
    }

    onMounted(() => {
        // initial innerHeight sync (SSR safe)
        innerHeight.value = typeof window !== 'undefined' ? window.innerHeight : innerHeight.value
        screenHeight.value = typeof window !== 'undefined' ? window.screen.height : screenHeight.value

        updateLayout(true)

        onWindowResize = () => {
            innerHeight.value = typeof window !== 'undefined' ? window.innerHeight : innerHeight.value
            screenHeight.value = typeof window !== 'undefined' ? window.screen.height : screenHeight.value

            const avail = availableHeight()

            // Ratio beibehalten: Overview proportional anpassen
            if (showUserOverview.value && avail > 0) {
                userOverviewHeight.value = Math.round(avail * heightRatio.value)
            }

            updateLayout()
        }

        window.addEventListener('resize', onWindowResize, { passive: true })
    })

    onUnmounted(() => {
        if (onWindowResize) window.removeEventListener('resize', onWindowResize)
        if (onMouseMove) document.removeEventListener('mousemove', onMouseMove)
        if (onMouseUp) document.removeEventListener('mouseup', onMouseUp)

        if (dragRafId) cancelAnimationFrame(dragRafId)
        dragRafId = null
        pendingHeight = null

        onWindowResize = null
        onMouseMove = null
        onMouseUp = null
    })

    return {
        userOverviewHeight,
        windowHeight,
        screenHeight,
        heightRatio,
        maxTop,
        availableHeight,
        clamp,
        updateLayout,
        startResize,
        applyUserOverviewHeight,
        saveUserOverviewHeight,
    }
}
