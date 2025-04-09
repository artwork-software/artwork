<script>
import {Link, useForm} from "@inertiajs/vue3";
import {IconChevronRight} from "@tabler/icons-vue";

export default {
    name: "SingleMoneySourceTask",
    components: {Link, IconChevronRight},
    props: ['task'],
    methods: {
        updateMoneySourceTaskStatus(task){
            this.$inertia.patch(route('money_source.tasks.update', {moneySourceTask: task.id}))
        }
    },
    data() {
        return {
            highlight: null
        }
    },
    mounted() {
        if(parseInt(this.$page.props.urlParameters.taskId) === this.task.id){
            this.highlight = 'border-2 border-orange-300 rounded-md p-1';

            setTimeout(() => {
                this.highlight = null;
            }, 5000);
        }
    },
}
</script>

<template>
    <div :class="highlight">
        <div class="flex w-full flex-wrap md:flex-nowrap align-baseline">
            <div class="flex w-full items-start flex-grow gap-x-4">
                <input @change="updateMoneySourceTaskStatus(task)"
                       v-model="task.done"
                       type="checkbox"
                       class="cursor-pointer h-6 w-6 text-success border-2 my-2 border-gray-300"/>
                <div>
                    <div class="flex items-center gap-x-2">
                        <div class="mDark"
                             :class="task.done ? 'text-secondary line-through' : 'text-primary'">
                            {{ task.name }}
                        </div>
                        <div v-if="!task.done && task.deadline"
                             class="pt-1 xsLight "
                             :class="task.isDeadlineInFuture ? '' : 'text-error'">
                            {{ $t('until')}} {{ task.deadline }}
                        </div>
                    </div>
                    <div class="xsLight mb-2 flex items-center gap-x-2">
                        {{ $t('Source of funding') }}:
                        <Link v-if="task.money_source_id" :href="route('money_sources.show', task.money_source_id)" class="text-artwork-buttons-create underline flex items-center gap-x-0.5">
                            {{ task.money_source.name }}
                            <IconChevronRight class="h-4 w-4 text-primary" />
                            {{ task.name }}
                        </Link>
                    </div>
                </div>
            </div>
            <div class="my-auto">
                <img class="h-9 w-9 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" alt=""/>
            </div>
        </div>

        <div class="ml-10 mb-3 xsLight">
            {{ task.description }}
        </div>
    </div>

</template>

<style scoped>

</style>
