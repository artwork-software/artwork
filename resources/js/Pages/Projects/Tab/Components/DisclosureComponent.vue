<template>
    <Disclosure as="div" class="my-2" v-slot="{ open }" >
        <DisclosureButton class="py-2 px-4 bg-gray-200 text-gray-900 flex justify-between items-center w-full" :class="{ 'rounded-t-lg': open, 'rounded-lg': !open }">
            <div class="flex items-center h-full gap-x-2 xsDark font-bold">
                {{ component.component.data.label }}
                <InfoButtonComponent :component="component" />
            </div>
            <div>
                <component :is="IconChevronDown" class="size-3" :class="{ 'transform rotate-180': open }" />
            </div>
        </DisclosureButton>
        <DisclosurePanel class="px-4 py-2 bg-gray-50 rounded-b-lg">
            <div v-for="(disclosureComponent, index) in component.disclosure_components" :key="disclosureComponent.id" class="">
                <Component
                    v-if="disclosureComponent?.id && canSeeComponent(disclosureComponent.component) && componentMapping[disclosureComponent.component?.type]"
                    :is="componentMapping[disclosureComponent.component?.type]"
                    :can-edit-component="canEditComponent(disclosureComponent.component)"
                    :project="headerObject.project"
                    :in-sidebar="false"
                    :loadedProjectInformation="loadedProjectInformation"
                    :header-object="headerObject"
                    :data="disclosureComponent.component"
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
                    :component="disclosureComponent"
                />
            </div>
        </DisclosurePanel>
    </Disclosure>
</template>

<script setup>

import {Disclosure, DisclosureButton, DisclosurePanel} from "@headlessui/vue";
import ComponentIcons from "@/Components/Globale/ComponentIcons.vue";
import InfoButtonComponent from "@/Pages/Projects/Tab/Components/InfoButtonComponent.vue";
import {usePage} from "@inertiajs/vue3";
import {inject, provide} from "vue";
import {usePermission} from "@/Composeables/Permission.js";
import TextField from "@/Pages/Projects/Tab/Components/TextField.vue";
import Checkbox from "@/Pages/Projects/Tab/Components/Checkbox.vue";
import Title from "@/Pages/Projects/Tab/Components/Title.vue";
import TextArea from "@/Pages/Projects/Tab/Components/TextArea.vue";
import DropDown from "@/Pages/Projects/Tab/Components/DropDown.vue";
import LinkComponent from "@/Pages/Projects/Tab/Components/LinkComponent.vue";
import ProjectStateComponent from "@/Pages/Projects/Components/ProjectStateComponent.vue";
import CalendarTab from "@/Pages/Projects/Tab/Components/CalendarTab.vue";
import ShiftTab from "@/Pages/Projects/Tab/Components/ShiftTab.vue";
import BudgetTab from "@/Pages/Projects/Tab/Components/BudgetTab.vue";
import ProjectBudgetDeadlineComponent from "@/Pages/Projects/Components/ProjectBudgetDeadlineComponent.vue";
import SeparatorComponent from "@/Pages/Projects/Tab/Components/SeparatorComponent.vue";
import ProjectGroupComponent from "@/Pages/Projects/Components/ProjectGroupComponent.vue";
import ProjectTeamComponent from "@/Pages/Projects/Components/ProjectTeamComponent.vue";
import ProjectAttributesComponent from "@/Pages/Projects/Components/ProjectAttributesComponent.vue";
// Component can be removed - kept for backwards compatibility if still configured in customer projects
// import RelevantDatesForShiftPlanningComponent
//     from "@/Pages/Projects/Components/RelevantDatesForShiftPlanningComponent.vue";
import ShiftContactPersonsComponent from "@/Pages/Projects/Components/ShiftContactPersonsComponent.vue";
import GeneralShiftInformationComponent from "@/Pages/Projects/Components/GeneralShiftInformationComponent.vue";
import CommentTab from "@/Pages/Projects/Tab/Components/CommentTab.vue";
import ProjectTitleComponent from "@/Pages/Projects/Components/ProjectTitleComponent.vue";
import ChecklistComponent from "@/Pages/Projects/Components/ChecklistComponent.vue";
import ProjectDocumentsComponent from "@/Pages/Projects/Components/ProjectDocumentsComponent.vue";
import ProjectAllDocumentsComponent from "@/Pages/Projects/Components/ProjectAllDocumentsComponent.vue";
import ChecklistAllComponent from "@/Pages/Projects/Components/ChecklistAllComponent.vue";
import CommentAllTab from "@/Pages/Projects/Tab/Components/CommentAllTab.vue";
import BudgetInformations from "@/Pages/Projects/Tab/Components/BudgetInformations.vue";
import BulkBody from "@/Pages/Projects/Components/BulkComponents/BulkBody.vue";
import ArtistResidenciesComponent from "@/Pages/Projects/Tab/Components/ArtistResidenciesComponent.vue";
import GroupProjectDisplayComponent from "@/Pages/Projects/Components/GroupProjectDisplayComponent.vue";
import ProjectGroupDisplayComponent from "@/Pages/Projects/Components/ProjectGroupDisplayComponent.vue";
import {IconChevronDown} from "@tabler/icons-vue";

const props = defineProps({
    data: {
        type: Object,
        required: true
    },
    projectId: {
        type: Number,
        required: true
    },
    inSidebar: {
        type: Boolean,
        required: false
    },
    canEditComponent: {
        type: Boolean,
        required: true
    },
    component: {
        type: Object,
        required: true
    }
})


const pageProps = usePage().props;
provide('pageProps', pageProps);

const headerObject = inject("headerObject");
const currentTab = inject("currentTab");
const first_project_tab_id = inject("first_project_tab_id");
const first_project_calendar_tab_id = inject("first_project_calendar_tab_id");
const first_project_budget_tab_id = inject("first_project_budget_tab_id");
const loadedProjectInformation = inject("loadedProjectInformation");

const { canSeeComponent, canEditComponent } = usePermission(usePage().props);

const componentMapping = {
    TextField,
    Checkbox,
    Title,
    TextArea,
    DropDown,
    Link: LinkComponent,
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
    ProjectGroupDisplayComponent
};


</script>

<style scoped>

</style>
