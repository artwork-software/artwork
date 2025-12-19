<template>
    <ProjectHeaderComponent :header-object="headerObject" :project="project" :current-tab="currentTab" :create-settings="createSettings" :first_project_tab_id="first_project_tab_id" :print-layouts="printLayouts">
        <div class="my-10 w-full">
            <div v-for="(component, idx) in currentTab.components" :key="component?.id ?? component?.component?.id ?? idx" :class="removeML(component.component?.type)">
                <Component
                    v-if="canSeeComponent(component.component) && componentMapping[component.component?.type]"
                    :is="componentMapping[component.component?.type]"
                    :can-edit-component="canEditComponent(component.component)"
                    :project="project"
                    :in-sidebar="false"
                    :loadedProjectInformation="loadedProjectInformation"
                    :header-object="headerObject"
                    :data="component.component"
                    :project-id="project.id"
                    :projectCategories="headerObject.projectCategories"
                    :projectGenres="headerObject.projectGenres"
                    :projectSectors="headerObject.projectSectors"
                    :categories="headerObject.categories"
                    :sectors="headerObject.sectors"
                    :genres="headerObject.genres"
                    :projectCategoryIds="headerObject.projectCategoryIds"
                    :projectGenreIds="headerObject.projectGenreIds"
                    :projectSectorIds="headerObject.projectSectorIds"
                    :event-types="headerObject.eventTypes"
                    :opened_checklists="headerObject.project?.opened_checklists"
                    :checklist_templates="headerObject.project?.checklist_templates"
                    :projectManagerIds="headerObject.projectManagerIds"
                    :projectWriteIds="headerObject.projectWriteIds"
                    :tab_id="currentTab.id"
                    :first_project_tab_id="first_project_tab_id"
                    :first_project_calendar_tab_id="first_project_calendar_tab_id"
                    :first_project_budget_tab_id="first_project_budget_tab_id"
                    :rooms="headerObject.rooms"
                    :eventsInProject="headerObject.project.events"
                    :eventStatuses="headerObject.eventStatuses"
                    :event_properties="headerObject.event_properties"
                    :component="component"
                    :materials="headerObject.materials"
                />
            </div>
        </div>


        <BaseSidenav @toggle="show = !show" v-if="currentTab.hasSidebarTabs">
            <div class="w-full">
                <div class="mb-5 ml-3">
                    <div class="hidden sm:block">
                        <div class="border-gray-200">
                            <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8" aria-label="Tabs">
                                <div v-for="(tab, index) in currentTab.sidebar_tabs" :key="tab?.name"
                                     @click="currentSideBarTab = index"
                                     :class="[index === currentSideBarTab ? 'text-artwork-context-light border-artwork-context-light' : 'border-transparent text-secondary hover:text-artwork-buttons-hover hover:border-artwork-buttons-hover', 'whitespace-nowrap py-2 px-1 border-b-2 font-semibold cursor-pointer']"
                                     :aria-current="index === currentSideBarTab ? 'page' : undefined">
                                    {{ tab.name }}
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
                <div class="px-3">
                    <div v-for="(component, sidx) in currentTab.sidebar_tabs[currentSideBarTab]?.components_in_sidebar" :key="component?.id ?? component?.component?.id ?? sidx">
                        <Component
                            v-if="canSeeComponent(component.component) && componentMapping[component.component?.type]"
                            :is="componentMapping[component.component?.type]"
                            :can-edit-component="canEditComponent(component.component)"
                            :project="project"
                            :in-sidebar="true"
                            :loadedProjectInformation="loadedProjectInformation"
                            :header-object="headerObject"
                            :data="component.component"
                            :project-id="project.id"
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
                            :rooms="headerObject.rooms"
                            :eventStatuses="headerObject.eventStatuses"
                            :event_properties="headerObject.event_properties"
                        />
                    </div>
                </div>
            </div>
        </BaseSidenav>
    </ProjectHeaderComponent>
</template>

<script setup>
import {onMounted, provide, ref} from 'vue';
import {usePage} from "@inertiajs/vue3";
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
import SeparatorComponent from "@/Pages/Projects/Tab/Components/SeparatorComponent.vue";
import ProjectGroupComponent from "@/Pages/Projects/Components/ProjectGroupComponent.vue";
import ProjectTeamComponent from "@/Pages/Projects/Components/ProjectTeamComponent.vue";
import ProjectAttributesComponent from "@/Pages/Projects/Components/ProjectAttributesComponent.vue";
// Component can be removed - kept for backwards compatibility if still configured in customer projects
// import RelevantDatesForShiftPlanningComponent
//   from "@/Pages/Projects/Components/RelevantDatesForShiftPlanningComponent.vue";
import ProjectTitleComponent from "@/Pages/Projects/Components/ProjectTitleComponent.vue";
import ChecklistComponent from "@/Pages/Projects/Components/ChecklistComponent.vue";
import ShiftContactPersonsComponent from "@/Pages/Projects/Components/ShiftContactPersonsComponent.vue";
import GeneralShiftInformationComponent from "@/Pages/Projects/Components/GeneralShiftInformationComponent.vue";
import CommentTab from "@/Pages/Projects/Tab/Components/CommentTab.vue";
import ProjectDocumentsComponent from "@/Pages/Projects/Components/ProjectDocumentsComponent.vue";
import ProjectAllDocumentsComponent from "@/Pages/Projects/Components/ProjectAllDocumentsComponent.vue";
import ChecklistAllComponent from "@/Pages/Projects/Components/ChecklistAllComponent.vue";
import CommentAllTab from "@/Pages/Projects/Tab/Components/CommentAllTab.vue";
import BudgetInformations from "@/Pages/Projects/Tab/Components/BudgetInformations.vue";
import {usePermission} from "@/Composeables/Permission.js";
import BulkBody from "@/Pages/Projects/Components/BulkComponents/BulkBody.vue";
import ArtistResidenciesComponent from "@/Pages/Projects/Tab/Components/ArtistResidenciesComponent.vue";
import GroupProjectDisplayComponent from "@/Pages/Projects/Components/GroupProjectDisplayComponent.vue";
import ProjectGroupDisplayComponent from "@/Pages/Projects/Components/ProjectGroupDisplayComponent.vue";
import DisclosureComponent from "@/Pages/Projects/Tab/Components/DisclosureComponent.vue";
import ArtistNameDisplayComponent from "@/Pages/Projects/Components/ArtistNameDisplayComponent.vue";
import ProjectBasicDataDisplayComponent from "@/Pages/Projects/Components/ProjectBasicDataDisplayComponent.vue";
import LinkComponent from "@/Pages/Projects/Tab/Components/LinkComponent.vue";
import ProjectMaterialIssueComponent from "@/Pages/Projects/Components/Issue/ProjectMaterialIssueComponent.vue";

const pageProps = usePage().props;
provide('pageProps', pageProps);

const { canSeeComponent, canEditComponent } = usePermission(usePage().props);

const componentMapping = {
    TextField,
    Checkbox,
    Title,
    Link: LinkComponent,
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
    // RelevantDatesForShiftPlanningComponent, // Commented out - component can be removed
    ShiftContactPersonsComponent,
    GeneralShiftInformationComponent,
    CommentTab,
    ProjectTitleComponent,
    ChecklistComponent,
    ProjectDocumentsComponent,
    ProjectAllDocumentsComponent,
    ChecklistAllComponent,
    CommentAllTab,
    BudgetInformations,
    BulkBody,
    ArtistResidenciesComponent,
    GroupProjectDisplayComponent,
    ProjectGroupDisplayComponent,
    DisclosureComponent,
    ArtistNameDisplayComponent,
    ProjectBasicDataDisplayComponent,
    ProjectMaterialIssueComponent
};

const props = defineProps({
    headerObject: {
        type: Object,
        required: true
    },
    currentTab: {
        type: Object,
        required: true
    },
    loadedProjectInformation: {
        type: Object,
        required: true
    },
    first_project_tab_id: {
        type: Number,
        required: true
    },
    first_project_calendar_tab_id: {
        type: Number,
        required: true
    },
    first_project_budget_tab_id: {
        type: Number,
        required: true
    },
    createSettings: {
        type: Object,
        required: true
    },
    printLayouts: {
        type: Object,
        required: true
    },
    project: {
        type: Object,
        required: true
    }
});

provide('headerObject', props.headerObject);
provide('currentTab', props.currentTab);
provide('loadedProjectInformation', props.loadedProjectInformation);
provide('first_project_tab_id', props.first_project_tab_id);
provide('first_project_calendar_tab_id', props.first_project_calendar_tab_id);
provide('first_project_budget_tab_id', props.first_project_budget_tab_id);

const show = ref(false);
const currentSideBarTab = ref(0);

const removeML = (componentType) => {
    if (
        componentType === 'CalendarTab' ||
        componentType === 'ShiftTab' ||
        componentType === 'BudgetTab' ||
        componentType === 'ChecklistComponent' ||
        componentType === 'ChecklistAllComponent'
    ) {
        return '';
    } else {
        return 'artwork-container !pb-0 !mb-0 !mt-0';
    }
};

onMounted(() => {
    try {
        const project = props.headerObject?.project;
        if (!project?.id || !project?.name) return;

        // Bestehende Liste abrufen oder leeres Array initialisieren
        const stored = localStorage.getItem('lastedProjects');
        let lastedProjects = Array.isArray(JSON.parse(stored)) ? JSON.parse(stored) : [];

        // Projekt, falls vorhanden, entfernen
        lastedProjects = lastedProjects.filter(p => p.id !== project.id);

        // Neues Projekt an den Anfang setzen
        lastedProjects.unshift({
            id: project.id,
            name: project.name,
            updatedAt: new Date().toISOString(), // optional: für spätere Sortierung
            key_visual_path: project.key_visual_path,
            is_group: project.is_group,
            firstEventStart: props.headerObject.firstEventInProject.event_date_without_time.start,
            lastEventEnd: props.headerObject.lastEventInProject.event_date_without_time.end
        });

        // Nur die letzten 10 behalten
        if (lastedProjects.length > 10) {
            lastedProjects = lastedProjects.slice(0, 10);
        }

        // In LocalStorage speichern
        localStorage.setItem('lastedProjects', JSON.stringify(lastedProjects));
    } catch (error) {
        console.warn('Fehler beim Aktualisieren der letzten Projekte:', error);
    }
});

</script>
