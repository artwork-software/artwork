<template>
    <AppLayout :title="$t('Work Time Pattern')">
        <div class="artwork-container">
            <div class="">
                <h2 class="headline1">{{$t('Work Time Pattern')}}</h2>
                <div class="xsLight mt-2">
                    {{$t('Work Time Patterns are used to define the working hours and shifts for employees. You can create, edit, and delete work time patterns here.')}}
                </div>
            </div>

            <ShiftSettingTabs />

            <div class="flex items-center justify-between">
                <GlassyIconButton text="Add Work Time Pattern" :icon="IconPlus" @click="showCreateOrUpdateWorkTimePatternModal  = true" />
            </div>



            <div class="card white p-5 mt-5">
                <ul role="list" class="divide-y divide-gray-100" v-if="workTimePatterns.length > 0">
                    <li v-for="workTime in workTimePatterns" :key="workTime.id" class="flex justify-between gap-x-6 py-5">
                        <SingleWorkTimePattern :work-time-pattern="workTime" />
                    </li>
                </ul>
                <div v-else>
                    <BaseAlertComponent message="No work time patterns found. Please create a new one." type="error" use-translation />
                </div>
            </div>
        </div>


        <CreateOrUpdateWorkTimePatternModal
            v-if="showCreateOrUpdateWorkTimePatternModal"
            @close="showCreateOrUpdateWorkTimePatternModal = false"
        />

    </AppLayout>
</template>

<script setup>

import TabComponent from "@/Components/Tabs/TabComponent.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import {ref} from "vue";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";
import CreateOrUpdateWorkTimePatternModal
    from "@/Pages/Settings/WorkTimePattern/Components/CreateOrUpdateWorkTimePatternModal.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import SingleWorkTimePattern from "@/Pages/Settings/WorkTimePattern/Components/SingleWorkTimePattern.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import {IconPlus} from "@tabler/icons-vue";
import ShiftSettingTabs from "@/Pages/Settings/Components/ShiftSettingTabs.vue";

const props = defineProps({
    workTimePatterns: {
        type: Object,
        default: () => ([])
    }
})

const showCreateOrUpdateWorkTimePatternModal = ref(false)

const tabs = ref([
    {
        name: 'Shift Settings',
        href: route('shift.settings'),
        current: route().current('shift.settings'),
        show: true,
        icon: 'IconCalendarUser'
    },
    {
        name: 'Day Services',
        href: route('day-service.index'),
        current: route().current('day-service.index'),
        show: true,
        icon: 'IconHours24'
    },
    {
        name: 'Work Time Pattern',
        href: route('shift.work-time-pattern'),
        current: route().current('shift.work-time-pattern'),
        show: true,
        icon: 'IconClockCog'
    },
    {
        name: 'User Contracts',
        href: route('user-contract-settings.index'),
        current: route().current('user-contract-settings.index'),
        show: true,
        icon: 'IconContract'
    }
])
</script>

<style scoped>

</style>
