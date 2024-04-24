<template>
    <div class="my-5">
        <div v-if="vacations?.length > 0">
            <h3 class="sDark mb-4">{{ $t('Absences') }}</h3>
            <div v-for="vacation in vacationsWithType">
                <SingleUserVacation :type="type" :createShowDate="createShowDate" :vacation="vacation" :user="user" :vacationSelectCalendar="vacationSelectCalendar" />
            </div>
        </div>
        <div v-if="availabilities?.length > 0">
            <h3 class="sDark mb-4">{{ $t('Registered availability') }}</h3>
            <div v-for="availability in availabilitiesWithType">
                <SingleUserVacation :type="type" :createShowDate="createShowDate" :vacation="availability" :user="user" :vacationSelectCalendar="vacationSelectCalendar" />
            </div>
        </div>
        <div v-if="availabilities?.length <= 0 && vacations <= 0">
            <h3 class="sDark mb-4">{{ $t('Availability & absence') }}</h3>
            <p class="text-sm text-gray-500">{{ $t('No entry has yet been made for this day.') }}</p>
        </div>
    </div>
    <div v-if="$can('can manage workers') || hasAdminRole()" class="flex items-center gap-2"  @click="showAddEditVacationsModal = true">
        <PlusCircleIcon class="h-5 w-5 text-white bg-[#3017AD] rounded-full cursor-pointer" />
        <div class="underline underline-offset-1 text-[#3017AD] text-sm cursor-pointer">
            {{ $t('Edit availability & absence') }}
        </div>
    </div>
    <AddEditVacationsModal :createShowDate="createShowDate" :type="type" v-if="showAddEditVacationsModal" @closed="showAddEditVacationsModal = false" :user="user" :vacationSelectCalendar="vacationSelectCalendar" />
</template>

<script>
import {defineComponent} from 'vue'
import {PlusCircleIcon} from "@heroicons/vue/outline";
import AddEditVacationsModal from "@/Pages/Users/Components/AddEditVacationsModal.vue";
import SingleUserVacation from "@/Pages/Users/Components/SingleUserVacation.vue";
import Permissions from "@/Mixins/Permissions.vue";

export default defineComponent({
    name: "UserVacations",
    mixins: [Permissions],
    components: {
        SingleUserVacation,
        AddEditVacationsModal,
        PlusCircleIcon
    },
    props: ['user', 'vacations','type', 'vacationSelectCalendar', 'dateToShow', 'createShowDate', 'availabilities'],
    data(){
        return {
            showAddEditVacationsModal: false
        }
    },
    computed: {
        // add value type to all vacations and return them
        vacationsWithType(){
            return this.vacations.map(vacation => {
                vacation.type = 'vacation'
                return vacation
            })
        },
        availabilitiesWithType(){
            return this.availabilities.map(availability => {
                availability.type = 'available'
                return availability
            })
        }
    },
})
</script>
