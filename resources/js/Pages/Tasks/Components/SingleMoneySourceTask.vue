<script>
import {useForm} from "@inertiajs/vue3";

export default {
    name: "SingleMoneySourceTask",
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
            <div class="flex w-full flex-grow">
                <input @change="updateMoneySourceTaskStatus(task)"
                       v-model="task.done"
                       type="checkbox"
                       class="cursor-pointer h-6 w-6 text-success border-2 my-2 border-gray-300"/>
                <div class="ml-4 my-auto mDark"
                     :class="task.done ? 'text-secondary line-through' : 'text-primary'">
                    {{ task.name }}
                </div>
                <div v-if="!task.done && task.deadline"
                     class="ml-2 my-auto pt-1 xsLight "
                     :class="task.isDeadlineInFuture ? '' : 'text-error'">
                    {{ $t('until')}} {{ task.deadline }}
                </div>
            </div>
            <div class="my-auto">
                <img class="h-9 w-9 rounded-full object-cover"
                     :src="$page.props.user.profile_photo_url"
                     alt=""/>
            </div>
        </div>

        <div class="ml-10 my-3 xsLight">
            {{ task.description }}
        </div>
    </div>

</template>

<style scoped>

</style>
