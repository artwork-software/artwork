<template>
    <app-layout>
    <ProjectShowHeaderComponent :project-delete-ids="projectDeleteIds" :projectWriteIds="projectWriteIds" :projectManagerIds="projectManagerIds" :project="project" :eventTypes="eventTypes" :currentGroup="currentGroup"
                                :states="states" :project-groups="projectGroups"
                                :first-event-in-project="firstEventInProject"
                                :last-event-in-project="lastEventInProject" :rooms-with-audience="RoomsWithAudience" :group-projects="groupProjects" open-tab="shift">
        <ShiftTab :projectWriteIds="projectWriteIds" :projectManagerIds="projectManagerIds" :eventsWithRelevant="eventsWithRelevant" :crafts="crafts" :drop-users="dropUsers" :users="project.users" :event-types="eventTypes" />
    </ProjectShowHeaderComponent>
        <BaseSidenav :show="show" @toggle="this.show =! this.show">
            <ProjectShiftSidenav
                :project="project"
                :event-types="eventTypes"
            />
        </BaseSidenav>
    </app-layout>
</template>

<script>


import ProjectShowHeaderComponent from "@/Pages/Projects/Components/ProjectShowHeaderComponent.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue";
import ProjectSecondSidenav from "@/Layouts/Components/ProjectSecondSidenav.vue";
import ProjectShiftSidenav from "@/Layouts/Components/ProjectShiftSidenav.vue";
import ProjectSidenav from "@/Layouts/Components/ProjectSidenav.vue";
import ShiftTab from "@/Pages/Projects/Components/TabComponents/ShiftTab.vue";
import InfoTab from "@/Pages/Projects/Components/TabComponents/InfoTab.vue";

export default {
    components: {
        InfoTab,
        ShiftTab,
        ProjectSidenav,
        ProjectShiftSidenav,
        ProjectSecondSidenav,
        BaseSidenav,
        AppLayout,
        ProjectShowHeaderComponent},
    props: [
        'project',
        'usersForShifts',
        'freelancersForShifts',
        'serviceProvidersForShifts',
        'eventTypes',
        'currentGroup',
        'states',
        'projectGroups',
        'firstEventInProject',
        'lastEventInProject',
        'RoomsWithAudience',
        'isMemberOfADepartment',
        'groupProjects',
        'projectState',
        'eventsWithRelevant',
        'crafts',
        'projectWriteIds',
        'projectManagerIds',
        'projectDeleteIds',
    ],
    data() {
        return {
            show: false,
        }
    },
    computed: {
        dropUsers(){
            const users = [];
            this.usersForShifts.forEach((user) => {
                users.push({
                    element: user.user,
                    type: 0,
                    plannedWorkingHours: user.plannedWorkingHours,
                    expectedWorkingHours: user.expectedWorkingHours,
                })
            })
            this.freelancersForShifts.forEach((freelancer) => {
                users.push({
                    element: freelancer.freelancer,
                    type: 1,
                    plannedWorkingHours: freelancer.plannedWorkingHours,
                })
            })
            this.serviceProvidersForShifts.forEach((service_provider) => {
                users.push({
                    element: service_provider.service_provider,
                    type: 2,
                    plannedWorkingHours: service_provider.plannedWorkingHours,
                })
            })

            return users;
        },
    },
    methods:{
        projectMembers: function () {

            let projectMemberArray = [];
            this.project.users.forEach(member => {
                    projectMemberArray.push(member.id)
                }
            )
            return projectMemberArray;
        },
    }
}
</script>


<style scoped>

</style>
