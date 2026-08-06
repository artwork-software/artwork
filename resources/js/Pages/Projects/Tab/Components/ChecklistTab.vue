<template>
    <div class="mt-6 pb-6 bg-surface-canvas">
        <div class="">
            <ChecklistComponent
                :project="project"
                :opened_checklists="opened_checklists"
                :project-can-write-ids="projectWriteIds"
                :project-manager-ids="projectManagerIds"
                :checklist_templates="checklist_templates"
            />
        </div>
    </div>
</template>

<script>
import {IconCircleX, IconEdit, IconFileText, IconX} from "@tabler/icons-vue";
import JetInputError from "@/Jetstream/InputError.vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import ChecklistComponent from "@/Pages/Projects/Components/ChecklistComponent.vue";

export default{
    components: {
        ChecklistComponent,
        IconEdit,
        IconCircleX,
        IconFileText,
        SvgCollection,
        IconX,
        JetInputError
    },
    props: [
        'project',
        'opened_checklists',
        'projectWriteIds',
        'projectManagerIds',
        'checklist_templates'
    ]
}
</script>
