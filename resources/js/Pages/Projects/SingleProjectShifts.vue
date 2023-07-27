<template>
    <app-layout>
    <ProjectShowHeaderComponent :project="project" :eventTypes="eventTypes" :currentGroup="currentGroup"
                                :states="states" :project-groups="projectGroups"
                                :first-event-in-project="firstEventInProject"
                                :last-event-in-project="lastEventInProject" :rooms-with-audience="RoomsWithAudience" :group-projects="groupProjects" open-tab="shift">
        <ShiftTab :eventsWithRelevant="eventsWithRelevant" :crafts="crafts" :drop-users="dropUsers" :users="project.users" :event-types="eventTypes" />
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

export default {
    components: {
        ShiftTab,
        ProjectSidenav,
        ProjectShiftSidenav,
        ProjectSecondSidenav,
        BaseSidenav,
        AppLayout,
        ProjectShowHeaderComponent},
    props: [
        'project',
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
    ],
    data() {
        return {
            show: false,
        }
    },
    mounted() {
        this.show = true;
        setTimeout(() => {
            this.show = false;
        }, 1000)
    },
    computed: {
        dropUsers(){
            const users = [];
            this.project.users.forEach((user) => {
                users.push({
                    element: user,
                    type: 0
                })
            })
            this.project.freelancers?.forEach((freelancer) => {
                users.push({
                    element: freelancer,
                    type: 1
                })
            })
            this.project.serviceProviders?.forEach((provider) => {
                users.push({
                    element: provider,
                    type: 2
                })
            })

            return users;
        },
    },
    methods:{
        projectManagerIds: function () {
            let managerIdArray = [];
            this.project.project_managers.forEach(manager => {
                    managerIdArray.push(manager.id)
                }
            )
            return managerIdArray;
        },
        projectMembers: function () {

            let projectMemberArray = [];
            this.project.users.forEach(member => {
                    projectMemberArray.push(member.id)
                }
            )
            return projectMemberArray;
        },
        projectCanWriteIds: function () {
            let canWriteArray = [];
            this.project.write_auth.forEach(write => {
                    canWriteArray.push(write.id)
                }
            )
            return canWriteArray;
        },
    }
}
</script>


<style scoped>

</style>
