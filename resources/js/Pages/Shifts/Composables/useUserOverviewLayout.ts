import { ref, computed, onMounted, onUnmounted, type Ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

type UseUserOverviewLayoutOptions = {
    headerHeight?: number; // Standard: 100
    topGap?: number;       // Standard: 16
    minTop?: number;       // Standard: 100
    minMain?: number;      // Standard: 200
    initialHeight?: number;
    pageProps ?: Record<string, any>;
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
    const TOP_GAP   = options.topGap ?? 16
    const MIN_TOP   = options.minTop ?? 100
    const MIN_MAIN  = options.minMain ?? 200
    const pageProps = options.pageProps ?? {}

    const page = usePage() as unknown as {
        props: {
            auth?: { user?: { id: number|string; drawer_height?: number } }
        }
    }

    const startY = ref(0)
    const startHeight = ref(0)
    const windowHeight = ref<number>(typeof window !== 'undefined' ? window.innerHeight : 0)
    const screenHeight = ref<number>(typeof window !== 'undefined' ? window.screen.height : 0)
    const heightRatio = ref(0.4)
    const userOverviewHeight = ref<number>(
        options.initialHeight ?? (pageProps.auth?.user?.drawer_height ?? Math.round(windowHeight.value * heightRatio.value))
    )

    let onMouseMove: ((e: MouseEvent) => void) | null = null
    let onMouseUp: ((e: MouseEvent) => void) | null = null
    let onWindowResize: (() => void) | null = null
    const availableHeight = () =>
        Math.max(0, (typeof window !== 'undefined' ? window.innerHeight : 0) - HEADER_H)

    const clamp = (n: number, min: number, max: number) =>
        Math.min(Math.max(n, min), max)

    const maxTop = computed(() => {
        const avail = availableHeight()
        return Math.max(MIN_TOP, avail - MIN_MAIN - TOP_GAP)
    })

    function updateLayout(initial = false) {
        const avail = availableHeight()
        if (!showUserOverview.value) {
            windowHeight.value = Math.max(MIN_MAIN, avail - TOP_GAP)
            return
        }

        userOverviewHeight.value = clamp(userOverviewHeight.value, MIN_TOP, maxTop.value)
        windowHeight.value = Math.max(MIN_MAIN, avail - userOverviewHeight.value - TOP_GAP)

        if (!initial && avail > 0) {
            heightRatio.value = clamp(userOverviewHeight.value / avail, 0.05, 0.9)
        }
    }

    function startResize(e: MouseEvent) {
        e.preventDefault()
        startY.value = e.clientY
        startHeight.value = userOverviewHeight.value
        onMouseMove = (evt: MouseEvent) => {
            const diff = startY.value - evt.clientY
            userOverviewHeight.value = startHeight.value + diff
            updateLayout()
        }

        onMouseUp = (evt: MouseEvent) => {
            evt.preventDefault()
            const avail = availableHeight()
            if (avail > 0) {
                heightRatio.value = clamp(userOverviewHeight.value / avail, 0.05, 0.9)
            }

            if (onMouseMove) document.removeEventListener('mousemove', onMouseMove)
            if (onMouseUp) document.removeEventListener('mouseup', onMouseUp)
            onMouseMove = null
            onMouseUp = null

            saveUserOverviewHeight()
        }

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
        updateLayout(true)

        onWindowResize = () => {
            const avail = availableHeight()
            userOverviewHeight.value = Math.round(avail * heightRatio.value)
            windowHeight.value = typeof window !== 'undefined' ? window.innerHeight : windowHeight.value
            screenHeight.value = typeof window !== 'undefined' ? window.screen.height : screenHeight.value
            updateLayout()
        }

        window.addEventListener('resize', onWindowResize, { passive: true })
    })

    onUnmounted(() => {
        if (onWindowResize) window.removeEventListener('resize', onWindowResize)
        if (onMouseMove) document.removeEventListener('mousemove', onMouseMove)
        if (onMouseUp) document.removeEventListener('mouseup', onMouseUp)
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
