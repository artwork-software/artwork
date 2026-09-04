<script setup>
import { computed, onMounted, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { IconChevronRight } from '@tabler/icons-vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'
import NewUserToolTip from '@/Layouts/Components/NewUserToolTip.vue'

const props = defineProps({
    task: { type: Object, required: true },
})

const page = usePage()
const highlight = ref(null)

/** Zuständige der Aufgabe (money_source_task_users) – nicht die eingeloggte Person. */
const assignees = computed(() => (props.task.money_source_task_users ?? []).filter(Boolean))

function updateMoneySourceTaskStatus(done) {
    // Erledigen/Rückgängig getrennt: der alte Sammel-Endpunkt setzte immer "erledigt".
    const routeName = done ? 'money_source.task.done' : 'money_source.task.undone'
    router.patch(route(routeName, { moneySourceTask: props.task.id }), {}, { preserveScroll: true, preserveState: true })
}

onMounted(() => {
    if (parseInt(page.props.urlParameters?.taskId) === props.task.id) {
        highlight.value = 'border-2 border-special-orange-border rounded-md p-1'

        setTimeout(() => {
            highlight.value = null
        }, 5000)
    }
})
</script>

<template>
    <div :class="highlight">
        <div class="flex w-full flex-wrap md:flex-nowrap align-baseline">
            <div class="flex w-full items-start flex-grow gap-x-4">
                <BaseCheckbox
                    :id="`money-source-task-${task.id}`"
                    v-model="task.done"
                    class="pt-1"
                    @change="updateMoneySourceTaskStatus"
                />
                <div>
                    <div class="flex items-center gap-x-2">
                        <div class="text-lg/[21px] font-semibold"
                             :class="task.done ? 'text-text-subtle line-through' : 'text-text'">
                            {{ task.name }}
                        </div>
                        <div v-if="!task.done && task.deadline"
                             class="pt-1 text-sm/5 font-bold"
                             :class="task.isDeadlineInFuture ? 'text-text-subtle' : 'text-danger'">
                            {{ $t('until') }} {{ task.deadline }}
                        </div>
                    </div>
                    <div class="text-sm/5 font-bold text-text-subtle mb-2 flex items-center gap-x-2">
                        {{ $t('Source of funding') }}:
                        <Link v-if="task.money_source_id && task.money_source"
                              :href="route('money_sources.show', task.money_source_id)"
                              class="text-accent-600 underline flex items-center gap-x-0.5">
                            {{ task.money_source.name }}
                            <IconChevronRight class="h-4 w-4 text-text" />
                        </Link>
                    </div>
                </div>
            </div>
            <div v-if="assignees.length" class="my-auto flex items-center -space-x-2" :title="$t('Assigned to')">
                <NewUserToolTip
                    v-for="(user, idx) in assignees"
                    :key="`money-source-task-${task.id}-user-${user.id ?? idx}`"
                    :user="user"
                    :height="9"
                    :width="9"
                    :id="`money_source_task_${task.id}_assignee_${idx}`"
                />
            </div>
        </div>

        <div v-if="task.description" class="ml-10 mb-3 text-sm/5 font-bold text-text-subtle">
            {{ task.description }}
        </div>
    </div>
</template>
