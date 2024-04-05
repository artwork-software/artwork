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
import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue";
import {Link} from "@inertiajs/inertia-vue3";
import SeparatorComponent from "@/Pages/Projects/TabTest/Components/SeparatorComponent.vue";
export default {
    name: "TabContent",
    components: {
        Link,
        BaseSidenav,
        ProjectHeaderComponent,
        TextField, Checkbox, Title, TextArea, DropDown, ProjectStateComponent, CalendarTab, ShiftTab,
        BudgetTab, ProjectBudgetDeadlineComponent, SeparatorComponent
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
    data() {
        return {
            show: false,
            currentSideBarTab: 0
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
                <Component :in-sidebar="false" :is="component.component?.type" :loadedProjectInformation="loadedProjectInformation" :header-object="headerObject" :data="component.component" :project-id="headerObject.project.id"  />
            </div>
        </div>

        <BaseSidenav @toggle="this.show =! this.show" v-if="dataObject.currentTab.hasSidebarTabs">
            <div class="w-full">
                <div class="mb-5 ml-3">
                    <div class="hidden sm:block">
                        <div class="border-gray-200">
                            <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8" aria-label="Tabs">
                                <div v-for="(tab, index) in dataObject.currentTab.sidebar_tabs" :key="tab?.name" @click="currentSideBarTab = index"
                                      :class="[index === currentSideBarTab ? 'text-artwork-buttons-create border-artwork-buttons-create' : 'border-transparent text-secondary hover:text-artwork-buttons-hover hover:border-artwork-buttons-hover', 'whitespace-nowrap py-2 px-1 border-b-2 font-medium font-semibold cursor-pointer']"
                                      :aria-current="index === currentSideBarTab ? 'page' : undefined">
                                    {{ tab.name }}
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
                <div class="px-3">
                    <div v-for="component in dataObject.currentTab.sidebar_tabs[currentSideBarTab]?.components_in_sidebar">
                        <Component :is="component.component?.type" :loadedProjectInformation="loadedProjectInformation" :in-sidebar="true" :header-object="headerObject" :data="component.component" :project-id="headerObject.project.id"  />
                    </div>
                </div>
            </div>
        </BaseSidenav>
    </ProjectHeaderComponent>
</template>

<style scoped>

</style>
