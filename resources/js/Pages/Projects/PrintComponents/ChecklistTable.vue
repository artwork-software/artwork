<template>
    <div>
        <h3 class="headline3">{{ $t('All Checklists') }}</h3>
    </div>

    <div class="mt-2" v-for="checklist in project.public_all_checklists">
        <div>
            <h3 class="xsDark">{{ checklist.name }}</h3>
        </div>
        <div class="xsDark">
            <div class="">
                <table class="min-w-full divide-y divide-border">
                    <thead>
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-text sm:pl-0">{{ $t('Task') }}</th>
                        <th scope="col" colspan="1" class="px-3 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Done at')}}</th>
                        <th scope="col" colspan="1" class="px-3 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Completed by') }}</th>
                        <th scope="col" colspan="1" class="px-3 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Deadline') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle">
                        <tr v-for="task in checklist.tasks" :key="task.id">
                            <td colspan="1" class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-text sm:pl-0">{{ task.name }}</td>
                            <td colspan="1" class="whitespace-nowrap px-3 py-4 text-sm text-text-subtle">{{ task.done ? task?.done_at : '-' }}</td>
                            <td colspan="1" class="whitespace-nowrap px-3 py-4 text-sm text-text-subtle">{{ task.done_by_user?.full_name }}</td>
                            <td colspan="1" class="whitespace-nowrap px-3 py-4 text-sm text-text-subtle">{{ task.deadline }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-2" v-for="checklist in project.private_all_checklists">
        <div>
            <h3 class="xsDark">{{ checklist.name }}</h3>
        </div>
        <div class="xsDark">
            <div class="">
                <table class="min-w-full divide-y divide-border">
                    <thead>
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-text sm:pl-0">{{ $t('Task') }}</th>
                        <th scope="col" colspan="1" class="px-3 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Done at')}}</th>
                        <th scope="col" colspan="1" class="px-3 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Completed by') }}</th>
                        <th scope="col" colspan="1" class="px-3 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Deadline') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle">
                    <tr v-for="task in checklist.tasks" :key="task.id">
                        <td colspan="1" class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-text sm:pl-0">{{ task.name }}</td>
                        <td colspan="1" class="whitespace-nowrap px-3 py-4 text-sm text-text-subtle">{{ task.done ? task?.done_at : '-' }}</td>
                        <td colspan="1" class="whitespace-nowrap px-3 py-4 text-sm text-text-subtle">{{ task.done_by_user?.full_name }}</td>
                        <td colspan="1" class="whitespace-nowrap px-3 py-4 text-sm text-text-subtle">{{ task.deadline }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>

const props = defineProps({
    project: {
        type: Object,
        required: true
    }
})

</script>

<style scoped>

</style>