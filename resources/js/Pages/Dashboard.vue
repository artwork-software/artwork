<template>
    <app-layout>
        <div class="max-w-screen-2xl mb-40 my-12 flex ml-14 mr-40">
            <div class="headline1">
                Dashboard
            </div>
            <!-- Schichten Widget -->
            <div>
                <div class="dashboardHeader tracking-widest uppercase">
                    Schichten heute
                </div>
                <div class="flex flex-col w-full bg-white shadow-lg p-4">
                    <div>
                        {{todayDate}}
                    </div>
                    <div v-for="shift of shiftsOfDay" :key="shift.event.id" class="py-2 w-full">
                        <div>
                            <div>
                                <div class="text-secondaryHover xsWhiteBold px-1 py-1"
                                     :class="shift.event?.event_type?.svg_name">
                                    {{ shift.event?.event_type?.abbreviation }}: {{ shift.event?.project?.name }}
                                </div>
                            </div>
                            <div class="bg-backgroundGray">
                                <div class="flex items-center xsLight text-shiftText subpixel-antialiased">
                                    <div>
                                        {{ shift.craft?.abbreviation }} {{ shift.start }} - {{ shift.end }}
                                    </div>
                                    <div v-if="shift.room" class="truncate">
                                        , {{ shift.room?.name }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Aufgaben Widget -->
            <div>
                <div class="dashboardHeader tracking-widest">
                    Nächste Aufgaben
                </div>
                <div class="flex flex-col w-full bg-white shadow-lg p-4">
                    <div v-for="task in tasks" :key="task.id" class="py-2 w-full">
                        <div class="flex w-full justify-between">
                            <div class="flex w-full">
                                <input @change="updateTaskStatus(task)"
                                       v-model="task.done"
                                       type="checkbox"
                                       class="cursor-pointer h-6 w-6 text-success border-2 my-2 border-success"/>
                                <div class="ml-4 my-auto mDark truncate w-96"
                                     :class="task.done ? 'text-secondary line-through' : 'text-primary'">
                                    {{ task.name }}
                                </div>
                            </div>
                            <div v-if="!task.done && task.deadline"
                                 class=" my-auto pt-1 xsLight w-52"
                                 :class="task.isDeadlineInFuture ? '' : 'text-error'">
                                bis {{ task.deadline }}
                            </div>
                        </div>


                        <Link v-if="task.projectId" :href="route('projects.show.checklist',{project: task.projectId})"
                              class="my-1 flex ml-10 xsDark">
                            {{ task.projectName }}
                            <ChevronRightIcon class="h-5 w-5 my-auto mx-3" aria-hidden="true"/>
                            {{ task.checklistName }}
                        </Link>

                        <div class="ml-10 my-3 xsLight">
                            {{ task.description }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>
import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    CalendarIcon,
    ChevronDownIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    DotsHorizontalIcon,
} from '@heroicons/vue/solid'
import {Menu, MenuButton, MenuItem, MenuItems} from '@headlessui/vue'
import CalendarComponent from "@/Layouts/Components/CalendarComponent";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection";
import {Link, useForm} from "@inertiajs/inertia-vue3";
import TeamTooltip from "@/Layouts/Components/TeamTooltip";
import {Inertia} from "@inertiajs/inertia";
import IndividualCalendarComponent from "@/Layouts/Components/IndividualCalendarComponent.vue";
import IndividualCalendarAtGlanceComponent from "@/Layouts/Components/IndividualCalendarAtGlanceComponent.vue";
import Permissions from "@/mixins/Permissions.vue";
import VueMathjax from "vue-mathjax-next";
import {CheckIcon} from "@heroicons/vue/outline";

export default defineComponent({
    mixins: [Permissions],
    props: [
        'tasks',
        'shiftsOfDay',
        'todayDate'
    ],
    components: {
        CheckIcon, VueMathjax,
        IndividualCalendarAtGlanceComponent,
        AppLayout,
        CalendarIcon,
        ChevronRightIcon,
        Menu,
        MenuItem,
        MenuButton,
        MenuItems,
        ChevronLeftIcon,
        DotsHorizontalIcon,
        ChevronDownIcon,
        CalendarComponent,
        TeamIconCollection,
        Link,
        TeamTooltip,
        IndividualCalendarComponent
    },
    created() {
        Echo.private('events')
            .listen('OccupancyUpdated', () => {
                Inertia.reload({only: ['rooms', 'calendar', 'days']})
            });
    },
    methods: {
        updateTaskStatus(task) {
            this.doneTaskForm.done = task.done;
            this.doneTaskForm.patch(route('tasks.update', {task: task.id}));
        },
        changeAtAGlance() {
            this.atAGlance = !this.atAGlance;
        }
    },
    data() {
        return {
            doneTaskForm: useForm({
                done: false
            }),
        }
    },

})
</script>
<style scoped>

.eventType0 {
    background-color: #7F7E88;
}

.eventType1 {
    background-color: #631D53;
}

.eventType2 {
    background-color: #D84387;
}

.eventType3 {
    background-color: #E97A45;
}

.eventType4 {
    background-color: #CB8913;
}

.eventType5 {
    background-color: #648928;
}

.eventType6 {
    background-color: #35A965;
}

.eventType7 {
    background-color: #35ACB2;
}

.eventType8 {
    background-color: #2290C1;
}

.eventType9 {
    background-color: #50908E;
}

.eventType10 {
    background-color: #23485B;
}
</style>
