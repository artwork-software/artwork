<template>
    <div class="my-5">
        <!-- Absences -->
        <div v-if="vacations?.length > 0" class="mb-6">
            <h3 class="mb-3 text-base font-semibold text-zinc-800">
                {{ $t('Absences') }}
            </h3>
            <div v-for="vacation in vacationsWithType" :key="vacationKey(vacation)">
                <SingleUserVacation
                    :type="type"
                    :createShowDate="createShowDate"
                    :vacation="vacation"
                    :user="user"
                    :vacationSelectCalendar="vacationSelectCalendar"
                />
            </div>
        </div>

        <!-- Registered availability -->
        <div v-if="availabilities?.length > 0" class="mb-6">
            <h3 class="mb-3 text-base font-semibold text-zinc-800">
                {{ $t('Registered availability') }}
            </h3>
            <div v-for="availability in availabilitiesWithType" :key="availabilityKey(availability)">
                <SingleUserVacation
                    :type="type"
                    :createShowDate="createShowDate"
                    :vacation="availability"
                    :user="user"
                    :vacationSelectCalendar="vacationSelectCalendar"
                />
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="hasNoEntries" class="mb-6">
            <h3 class="mb-2 text-base font-semibold text-zinc-800">
                {{ $t('Availability & absence') }}
            </h3>
            <p class="text-sm text-zinc-500">
                {{ $t('No entry has yet been made for this month.') }}
            </p>
        </div>
    </div>

    <!-- Add / Edit trigger -->
    <div
        v-if="canManage"
        class="inline-flex items-center gap-2 cursor-pointer select-none"
        @click="showAddEditVacationsModal = true"
    >
        <PlusCircleIcon class="h-5 w-5 text-blue-600" />
        <span class="text-sm text-blue-700 underline underline-offset-2">
      {{ $t('Edit availability & absence') }}
    </span>
    </div>

    <!-- Modal -->
    <AddEditVacationsModal
        v-if="showAddEditVacationsModal"
        :createShowDate="createShowDate"
        :type="type"
        :user="user"
        :vacationSelectCalendar="vacationSelectCalendar"
        :selectedDate="showVacationsAndAvailabilitiesDate"
        @closed="showAddEditVacationsModal = false"
    />
</template>

<script setup>
import { computed, ref, getCurrentInstance } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { PlusCircleIcon } from '@heroicons/vue/outline'
import AddEditVacationsModal from '@/Pages/Users/Components/AddEditVacationsModal.vue'
import SingleUserVacation from '@/Pages/Users/Components/SingleUserVacation.vue'
import { can, is } from 'laravel-permission-to-vuejs'
// Props unverändert
const props = defineProps({
    user: { type: Object, required: true },
    vacations: { type: Array, default: () => [] },
    type: { type: String, default: '' },
    vacationSelectCalendar: { type: [Boolean, Array], default: false },
    dateToShow: { type: Array, default: () => [] },
    createShowDate: { type: Array, default: [] },
    availabilities: { type: Array, default: () => [] },
    showVacationsAndAvailabilitiesDate: { type: String, default: '' },
})

const showAddEditVacationsModal = ref(false)

// Schlüssel (stabil) für v-for
const vacationKey = (v) => v?.id ?? `${v?.date_casted}-${v?.start_time}-${v?.end_time}-v`
const availabilityKey = (a) => a?.id ?? `${a?.date_casted}-${a?.start_time}-${a?.end_time}-a`

// Ursprüngliche Computeds: Typen anreichern (ohne Original zu mutieren)
const vacationsWithType = computed(() =>
    (props.vacations || []).map(v => ({ ...v, type: 'vacation' }))
)
const availabilitiesWithType = computed(() =>
    (props.availabilities || []).map(a => ({ ...a, type: 'available' }))
)

const hasNoEntries = computed(
    () => (!props.availabilities || props.availabilities.length <= 0)
        && (!props.vacations || props.vacations.length <= 0)
)

// Berechtigungen: $can & hasAdminRole aus globalem Kontext (wie vorher)
const { proxy } = getCurrentInstance()
const page = usePage()

const canManage = computed(() =>
    can('can manage workers') ||
    is('artwork admin') ||
    props.user?.id === page.props?.auth?.user?.id ||
    can('can manage availability')
)
</script>
