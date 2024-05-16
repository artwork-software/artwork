<template>
    <div @click="openProjectHistoryModal(notification.history)"
         class="xxsLight cursor-pointer items-center flex text-artwork-buttons-create">
        <ChevronRightIcon class="h-5 w-4 -mr-0.5"/>
        Verlauf ansehen
    </div>

    <ProjectHistoryWithoutBudgetComponent
        v-if="showProjectHistory"
        :project_history="wantedHistory"
        @closed="closeRoomHistoryModal"
    />
</template>

<script>
import {ChevronRightIcon} from "@heroicons/vue/solid";
import ProjectHistoryComponent from "@/Layouts/Components/MoneySourceHistoryComponent.vue";
import ProjectHistoryWithoutBudgetComponent from "@/Layouts/Components/ProjectHistoryWithoutBudgetComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";

export default {
    mixins: [Permissions],
    name: "NotificationPublicChangesInfo",
    props: ['notification'],
    components:{
        ProjectHistoryWithoutBudgetComponent,
        ProjectHistoryComponent,
        ChevronRightIcon,
    },
    data(){
        return {
            wantedHistory: null,
            showProjectHistory: false,
        }
    },
    methods: {
        openProjectHistoryModal(history){
            this.wantedHistory = history;
            this.showProjectHistory = true;
        },
        closeRoomHistoryModal(){
            this.wantedHistory = null;
            this.showProjectHistory = false;
        }
    }
}
</script>

<style scoped>

</style>
