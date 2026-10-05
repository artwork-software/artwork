<template>
    <div class="grid grid-cols-1 lg:grid-cols-12 w-full">
        <div class="lg:col-span-7">
            <h3 class="font-lexend font-semibold text-[clamp(18px,2.5vw,20px)]/[25px] text-text mb-6">{{ $t('Availability')}}</h3>
            <div class="mb-10" v-if="type !== 'freelancer'">
                <TemporarilyHired :user="user" v-if="$can('can manage workers') || hasAdminRole()" />
                <div v-if="user.temporary && user.employStart && user.employEnd">
                    {{ $t('Temporarily employed') }}: {{ dayjs(user.employStart).format('DD.MM.YYYY') }} - {{ dayjs(user.employEnd).format('DD.MM.YYYY') }}
                </div>
            </div>
        </div>
        <div class="hidden lg:block lg:col-span-1">

        </div>
        <div class="hidden lg:block lg:col-span-4 mt-12">
        </div>
    </div>
    <!-- Unter lg stehen Kalender und Abwesenheitsliste untereinander, ab lg nebeneinander. -->
    <div class="grid grid-cols-1 gap-y-8 lg:gap-y-0 lg:grid-cols-12 w-full mb-20 items-start">
        <div ref="calendarCol" class="min-w-0 lg:col-span-7">
            <UserAvailabilityCalendar
                :showVacationsAndAvailabilitiesDate="showVacationsAndAvailabilitiesDate"
                :calendar-data="calendarData"
                :date-to-show="dateToShow"
                :interactive="canManage"
                :project-wishes="projectWishes ?? []"
                @select-range="openCreateModal"
            />
        </div>
        <div class="hidden lg:block lg:col-span-1">

        </div>
        <div class="min-w-0 lg:col-span-4 lg:mt-12 flex min-h-0 flex-col" :style="isSideBySide ? { maxHeight: calendarHeight + 'px' } : null">
            <UserVacations
                :availabilities="availabilities"
                :type="type"
                :user="user"
                :vacations="vacations"
                :showVacationsAndAvailabilitiesDate="showVacationsAndAvailabilitiesDate"
                :project-wishes="projectWishes ?? []"
                @create="openCreateModal(null)"
            />
        </div>
    </div>

    <AddEditVacationsModal
        v-if="showCreateModal"
        :type="type"
        :user="user"
        :initial-start="createRange.start"
        :initial-end="createRange.end"
        :project-wishes="projectWishes ?? []"
        @closed="showCreateModal = false"
    />
</template>

<script>
import {defineComponent, ref, onMounted, onBeforeUnmount, computed} from 'vue'
import UserAvailabilityCalendar from "@/Pages/Users/Components/UserAvailabilityCalendar.vue";
import UserVacations from "@/Pages/Users/Components/UserVacations.vue";
import AddEditVacationsModal from "@/Pages/Users/Components/AddEditVacationsModal.vue";
import TemporarilyHired from "@/Pages/Users/Components/TemporarilyHired.vue";
import Permissions from "@/Mixins/Permissions.vue";
import dayjs from "dayjs";
import {usePage} from "@inertiajs/vue3";
import {can, is} from "laravel-permission-to-vuejs";

export default defineComponent({
    name: "Availability",
    methods: {usePage},
    computed: {
        dayjs() {
            return dayjs
        }
    },
    mixins: [Permissions],
    components: {TemporarilyHired, UserVacations, UserAvailabilityCalendar, AddEditVacationsModal},
    props: [
        'calendarData',
        'dateToShow',
        'user',
        'vacations',
        'type',
        'vacationSelectCalendar',
        'createShowDate',
        'showVacationsAndAvailabilitiesDate',
        'availabilities',
        'projectWishes'
    ],
    setup(props) {
        const calendarCol = ref(null)
        const calendarHeight = ref(500)
        let observer = null

        // Die Höhen-Kopplung der Liste an den Kalender gilt nur im Nebeneinander-Layout (Tailwind lg = 1024px).
        const sideBySideQuery = typeof window !== 'undefined' ? window.matchMedia('(min-width: 1024px)') : null
        const isSideBySide = ref(sideBySideQuery ? sideBySideQuery.matches : true)
        const onLayoutChange = (event) => { isSideBySide.value = event.matches }

        onMounted(() => {
            sideBySideQuery?.addEventListener('change', onLayoutChange)
            if (calendarCol.value) {
                calendarHeight.value = calendarCol.value.offsetHeight
                observer = new ResizeObserver((entries) => {
                    for (const entry of entries) {
                        calendarHeight.value = entry.target.offsetHeight
                    }
                })
                observer.observe(calendarCol.value)
            }
        })

        onBeforeUnmount(() => {
            observer?.disconnect()
            sideBySideQuery?.removeEventListener('change', onLayoutChange)
        })

        const page = usePage()
        const canManage = computed(() =>
            can('can manage workers') ||
            is('artwork admin') ||
            (props.type !== 'freelancer' && props.user?.id === page.props?.auth?.user?.id) ||
            can('can manage availability')
        )

        const showCreateModal = ref(false)
        const createRange = ref({ start: '', end: '' })

        const openCreateModal = (range) => {
            if (!canManage.value) return
            createRange.value = range?.start
                ? { start: range.start, end: range.end }
                : { start: dayjs().format('YYYY-MM-DD'), end: dayjs().format('YYYY-MM-DD') }
            showCreateModal.value = true
        }

        return { calendarCol, calendarHeight, isSideBySide, canManage, showCreateModal, createRange, openCreateModal }
    },
})
</script>
