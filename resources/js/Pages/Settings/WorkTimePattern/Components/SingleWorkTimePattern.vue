<template>
    <div class="flex min-w-0 gap-x-4">
        <div class="min-w-0 flex-auto">
            <p class="text-sm/6 font-semibold text-gray-900">
                {{ workTimePattern.name }}
            </p>
            <p class="mt-1 flex text-xs/5 text-gray-500">
                {{ workTimePattern.description }}
            </p>
            <p class="mt-1 flex text-xs/5 text-gray-500 space-x-2 divide-x divide-gray-200">
                <span><b>{{ $t('Monday')}}</b>: {{ workTimePattern.monday }}</span>
                <span class="pl-2"><b>{{ $t('Tuesday')}}</b>: {{ workTimePattern.tuesday }}</span>
                <span class="pl-2"><b>{{ $t('Wednesday')}}</b>: {{ workTimePattern.wednesday }}</span>
                <span class="pl-2"><b>{{ $t('Thursday')}}</b>: {{ workTimePattern.thursday }}</span>
                <span class="pl-2"><b>{{ $t('Friday')}}</b>: {{ workTimePattern.friday }}</span>
                <span class="pl-2"><b>{{ $t('Saturday')}}</b>: {{ workTimePattern.saturday }}</span>
                <span class="pl-2"><b>{{ $t('Sunday')}}</b>: {{ workTimePattern.sunday }}</span>
            </p>
        </div>
    </div>
    <div class="flex shrink-0 items-center gap-x-6">
        <BaseMenu has-no-offset white-menu-background>
            <BaseMenuItem title="Edit" white-menu-background icon="IconEdit" @click="showCreateOrUpdateWorkTimePatternModal = true"/>
            <BaseMenuItem title="Delete" white-menu-background icon="IconTrash" @click="showDeleteModal = true"/>
        </BaseMenu>
    </div>

    <CreateOrUpdateWorkTimePatternModal
        v-if="showCreateOrUpdateWorkTimePatternModal"
        @close="showCreateOrUpdateWorkTimePatternModal = false"
        :work-time-pattern="workTimePattern"
    />

    <ConfirmDeleteModal
        v-if="showDeleteModal"
        @close="showDeleteModal = false"
        :title="$t('Delete Work Time Pattern')"
        :description="$t('Are you sure you want to delete this work time pattern? This action cannot be undone.')"
        @delete="deleteWorkTimePattern"
    />
</template>

<script setup>

import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import {defineAsyncComponent, ref} from "vue";
import {router} from "@inertiajs/vue3";

const props = defineProps({
    workTimePattern: {
        type: Object,
        default: () => ({
            id: null,
            name: '',
            description: '',
            monday: '00:00',
            tuesday: '00:00',
            wednesday: '00:00',
            thursday: '00:00',
            friday: '00:00',
            saturday: '00:00',
            sunday: '00:00',
        })
    },
})

const showCreateOrUpdateWorkTimePatternModal = ref(false)
const showDeleteModal = ref(false)

const CreateOrUpdateWorkTimePatternModal = defineAsyncComponent({
    loader: () => import('@/Pages/Settings/WorkTimePattern/Components/CreateOrUpdateWorkTimePatternModal.vue'),
    delay: 200,
})

const ConfirmDeleteModal = defineAsyncComponent({
    loader: () => import('@/Layouts/Components/ConfirmDeleteModal.vue'),
    delay: 200,
})

const deleteWorkTimePattern = () => {
    router.delete(route('shift.work-time-pattern.destroy', {workTimePattern: props.workTimePattern.id}), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            // Optionally, you can emit an event to notify the parent component
            // that the work time pattern has been deleted.
            // $emit('work-time-pattern-deleted', props.workTimePattern.id);
        },
        onError: (error) => {
            console.error('Error deleting work time pattern:', error);
        }
    });
}
</script>

<style scoped>

</style>