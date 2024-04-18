<script>
import ProjectHeaderComponent from "@/Pages/Projects/Tab/Components/ProjectHeaderComponent.vue";
import TextField from "@/Pages/Projects/Tab/Components/TextField.vue";
import Checkbox from "@/Pages/Projects/Tab/Components/Checkbox.vue";
import Title from "@/Pages/Projects/Tab/Components/Title.vue";
import TextArea from "@/Pages/Projects/Tab/Components/TextArea.vue";
import DropDown from "@/Pages/Projects/Tab/Components/DropDown.vue";
import ProjectStateComponent from "@/Pages/Projects/Components/ProjectStateComponent.vue";
import CalendarTab from "@/Pages/Projects/Tab/Components/CalendarTab.vue";
import ShiftTab from "@/Pages/Projects/Tab/Components/ShiftTab.vue";
import BudgetTab from "@/Pages/Projects/Tab/Components/BudgetTab.vue";
import ProjectBudgetDeadlineComponent from "@/Pages/Projects/Components/ProjectBudgetDeadlineComponent.vue";
import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue";
import {Link} from "@inertiajs/inertia-vue3";
import SeparatorComponent from "@/Pages/Projects/Tab/Components/SeparatorComponent.vue";
import ProjectGroupComponent from "@/Pages/Projects/Components/ProjectGroupComponent.vue";
import ProjectTeamComponent from "@/Pages/Projects/Components/ProjectTeamComponent.vue";
import ProjectAttributesComponent from "@/Pages/Projects/Components/ProjectAttributesComponent.vue";
import RelevantDatesForShiftPlanningComponent
    from "@/Pages/Projects/Components/RelevantDatesForShiftPlanningComponent.vue";
import ProjectTitleComponent from "@/Pages/Projects/Components/ProjectTitleComponent.vue";
import ChecklistComponent from "@/Pages/Projects/Components/ChecklistComponent.vue";
import ShiftContactPersonsComponent from "@/Pages/Projects/Components/ShiftContactPersonsComponent.vue";
import GeneralShiftInformationComponent from "@/Pages/Projects/Components/GeneralShiftInformationComponent.vue";
import CommentTab from "@/Pages/Projects/Tab/Components/CommentTab.vue";
import ProjectDocumentsComponent from "@/Pages/Projects/Components/ProjectDocumentsComponent.vue";
import ProjectAllDocumentsComponent from "@/Pages/Projects/Components/ProjectAllDocumentsComponent.vue";
import ChecklistAllComponent from "@/Pages/Projects/Components/ChecklistAllComponent.vue";
import CommentAllTab from "@/Pages/Projects/Tab/Components/CommentAllTab.vue";
import Permissions from "@/mixins/Permissions.vue";
import BudgetInformations from "@/Pages/Projects/Tab/Components/BudgetInformations.vue";
export default {
    name: "TabContent",
    mixins: [Permissions],
    computed: {
    },
    components: {
        Link,
        BaseSidenav,
        ProjectHeaderComponent,
        TextField,
        Checkbox,
        Title,
        TextArea,
        DropDown,
        ProjectStateComponent,
        CalendarTab,
        ShiftTab,
        BudgetTab,
        ProjectBudgetDeadlineComponent,
        SeparatorComponent,
        ProjectGroupComponent,
        ProjectTeamComponent,
        ProjectAttributesComponent,
        RelevantDatesForShiftPlanningComponent,
        ShiftContactPersonsComponent,
        GeneralShiftInformationComponent,
        CommentTab,
        ProjectTitleComponent,
        ChecklistComponent,
        ProjectDocumentsComponent,
        ProjectAllDocumentsComponent,
        ChecklistAllComponent,
        CommentAllTab,
        BudgetInformations
    },
    props: [
        'headerObject',
        'currentTab',
        'loadedProjectInformation',
        'first_project_tab_id',
        'first_project_calendar_tab_id',
        'first_project_budget_tab_id'
    ],
    data() {
        return {
            show: false,
            currentSideBarTab: 0
        }
    },
    methods: {
        removeML(componentType) {
            if(
                componentType === 'CalendarTab' ||
                componentType === 'ShiftTab' ||
                componentType === 'BudgetTab' ||
                componentType === 'ChecklistComponent' ||
                componentType === 'ChecklistAllComponent' ||
                componentType === 'CommentTab' ||
                componentType === 'CommentAllTab'
            ){
                return '-ml-14'
            } else {
                return 'max-w-7xl'
            }
        }
    }
}
</script>

<template>
    <ProjectHeaderComponent :header-object="headerObject" :project="headerObject.project">
        <div class="my-10 w-full">
            <div v-for="component in currentTab.components"  :class="removeML(component.component?.type)">
                <Component
                    v-if="this.$canSeeComponent(component.component)"
                    :can-edit-component="this.$canEditComponent(component.component)"
                    :project="headerObject.project"
                    :in-sidebar="false"
                    :is="component.component?.type"
                    :loadedProjectInformation="loadedProjectInformation"
                    :header-object="headerObject"
                    :data="component.component"
                    :project-id="headerObject.project.id"
                    :projectCategories="headerObject.projectCategories"
                    :projectGenres="headerObject.projectGenres"
                    :projectSectors="headerObject.projectSectors"
                    :categories="headerObject.categories"
                    :sectors="headerObject.sectors"
                    :genres="headerObject.genres"
                    :projectCategoryIds="headerObject.projectCategoryIds"
                    :projectGenreIds="headerObject.projectGenreIds"
                    :projectSectorIds="headerObject.projectSectorIds"
                    :eventTypes="headerObject.eventTypes"
                    :opened_checklists="headerObject.project?.opened_checklists"
                    :projectManagerIds="headerObject.projectManagerIds"
                    :tab_id="currentTab.id"
                    :first_project_tab_id="this.first_project_tab_id"
                    :first_project_calendar_tab_id="this.first_project_calendar_tab_id"
                    :first_project_budget_tab_id="this.first_project_budget_tab_id"
                />
            </div>
        </div>


        <BaseSidenav @toggle="this.show =! this.show" v-if="currentTab.hasSidebarTabs">
            <div class="w-full">
                <div class="mb-5 ml-3">
                    <div class="hidden sm:block">
                        <div class="border-gray-200">
                            <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8" aria-label="Tabs">
                                <div v-for="(tab, index) in currentTab.sidebar_tabs" :key="tab?.name" @click="currentSideBarTab = index"
                                      :class="[index === currentSideBarTab ? 'text-artwork-buttons-create border-artwork-buttons-create' : 'border-transparent text-secondary hover:text-artwork-buttons-hover hover:border-artwork-buttons-hover', 'whitespace-nowrap py-2 px-1 border-b-2 font-medium font-semibold cursor-pointer']"
                                      :aria-current="index === currentSideBarTab ? 'page' : undefined">
                                    {{ tab.name }}
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
                <div class="px-3">
                    <div v-for="component in currentTab.sidebar_tabs[currentSideBarTab]?.components_in_sidebar">
                        <Component
                            v-if="this.$canSeeComponent(component.component)"
                            :can-edit-component="this.$canEditComponent(component.component)"
                            :project="headerObject.project"
                            :is="component.component?.type"
                            :loadedProjectInformation="loadedProjectInformation"
                            :in-sidebar="true"
                            :header-object="headerObject"
                            :data="component.component"
                            :project-id="headerObject.project.id"
                            :projectCategories="headerObject.projectCategories"
                            :projectGenres="headerObject.projectGenres"
                            :projectSectors="headerObject.projectSectors"
                            :categories="headerObject.categories"
                            :sectors="headerObject.sectors"
                            :genres="headerObject.genres"
                            :projectCategoryIds="headerObject.projectCategoryIds"
                            :projectGenreIds="headerObject.projectGenreIds"
                            :projectSectorIds="headerObject.projectSectorIds"
                            :eventTypes="headerObject.eventTypes"
                        />
                    </div>
                </div>
            </div>
        </BaseSidenav>
    </ProjectHeaderComponent>
</template>

<style scoped>

</style>
