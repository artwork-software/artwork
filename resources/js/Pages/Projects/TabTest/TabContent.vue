<script>
import ProjectHeaderComponent from "@/Pages/Projects/TabTest/Components/ProjectHeaderComponent.vue";
import TextField from "@/Pages/Projects/TabTest/Components/TextField.vue";
import Checkbox from "@/Pages/Projects/TabTest/Components/Checkbox.vue";
import Title from "@/Pages/Projects/TabTest/Components/Title.vue";
import TextArea from "@/Pages/Projects/TabTest/Components/TextArea.vue";
import DropDown from "@/Pages/Projects/TabTest/Components/DropDown.vue";
import ProjectStateComponent from "@/Pages/Projects/Components/ProjectStateComponent.vue";
import CalendarTab from "@/Pages/Projects/Components/TabComponents/CalendarTab.vue";
import ShiftTab from "@/Pages/Projects/Components/TabComponents/ShiftTab.vue";
import BudgetTab from "@/Pages/Projects/Components/TabComponents/BudgetTab.vue";
import ProjectBudgetDeadlineComponent from "@/Pages/Projects/Components/ProjectBudgetDeadlineComponent.vue";
export default {
    name: "TabContent",
    components: {
        ProjectHeaderComponent,
        TextField, Checkbox, Title, TextArea, DropDown, ProjectStateComponent, CalendarTab, ShiftTab,
        BudgetTab, ProjectBudgetDeadlineComponent
    },
    props: {
        headerObject: {
            type: Object,
            required: true
        },
        dataObject: {
            type: Object,
            required: true
        },
        loadedProjectInformation: {
            type: Object,
            required: false
        }
    },
    methods: {
        removeML(componentType) {
            console.log(componentType)
            if(componentType === 'CalendarTab' || componentType === 'ShiftTab' || componentType === 'BudgetTab'){
                return '-ml-14'
            }
        }
    }
}
</script>

<template>
    <ProjectHeaderComponent :header-object="headerObject">

        <div class="my-10 w-full">
            <div v-for="component in dataObject.currentTab.components"  :class="removeML(component.component?.type)">
                <Component :is="component.component?.type" :loadedProjectInformation="loadedProjectInformation" :header-object="headerObject" :data="component.component" :project-id="headerObject.project.id"  />
            </div>
        </div>
    </ProjectHeaderComponent>
</template>

<style scoped>

</style>
