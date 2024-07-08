<template>

    <div class="ml-14 pt-4 pr-14">
        <ChecklistFunctionBar
            :project-manager-ids="projectManagerIds"
            :project-can-write-ids="projectCanWriteIds"
            :can-edit-component="canEditComponent"
            :is-admin="$role('artwork admin')"
            :project="project"
            :tab_id="tab_id"
            :checklist_templates="checklist_templates"
        />


       <div v-if="$page.props.user.checklist_style === 'list'">
           <ChecklistListView
               :checklists="allChecklists"
               :can-edit-component="canEditComponent"
               :project-can-write-ids="projectCanWriteIds"
               :project-manager-ids="projectManagerIds"
               :is-admin="$role('artwork admin')"
               :checklist_templates="checklist_templates"
               :project="project"
               :tab_id="tab_id"
           />
       </div>
        <div v-else>
            <ChecklistKanbanView
                :checklists="allChecklists"
                :can-edit-component="canEditComponent"
                :project-can-write-ids="projectCanWriteIds"
                :project-manager-ids="projectManagerIds"
                :is-admin="$role('artwork admin')"
                :checklist_templates="checklist_templates"
                :project="project"
                :tab_id="tab_id"
            />
        </div>
    </div>

</template>

<script>

import ChecklistKanbanView from "@/Components/Checklist/ChecklistKanbanView.vue";
import ChecklistListView from "@/Components/Checklist/ChecklistListView.vue";
import ChecklistFunctionBar from "@/Components/Checklist/ChecklistFunctionBar.vue";
import IconLib from "@/Mixins/IconLib.vue";
import Permissions from "@/Mixins/Permissions.vue";

export default {
    mixins: [IconLib, Permissions],
    name: "ChecklistComponent",
    props: [
        'project',
        'opened_checklists',
        'checklist_templates',
        'projectManagerIds',
        'tab_id',
        'canEditComponent'
    ],
    components: {
        ChecklistFunctionBar,
        ChecklistListView,
        ChecklistKanbanView
    },
    computed: {
        projectCanWriteIds: function () {
            let canWriteArray = [];
            this.project.write_auth?.forEach(write => {
                    canWriteArray.push(write.id)
                }
            )
            return canWriteArray;
        },
        allChecklists: function(){
            // return all checklist with attribute 'private' and 'public'
            return this.project.public_checklists.concat(this.project.private_checklists);
        },
    },
    data() {
        return {

        }
    },
    methods: {

    },
}
</script>
