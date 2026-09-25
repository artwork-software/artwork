<template>
    <ProjectHeaderComponent :header-object="headerObject" :project="project" :current-tab="currentTab" :create-settings="createSettings" :first_project_tab_id="first_project_tab_id" :print-layouts="printLayouts">
        <div class="my-10 w-full">
            <div v-if="externalAccessEnabled" class="flex items-center justify-end gap-3 mb-4">
                <!-- Externe dieses Tabs mit Abgabe-Status (Hover: wer/wann, Klick: bestätigen/zurückgeben) -->
                <ExternalTabStatus
                    ref="externalTabStatusRef"
                    :project-id="project.id"
                    :tab-id="currentTab.id"
                    @update:open-writers="(writers) => (openExternalWriters = writers)"
                    @reviewed="externalReviewVersion++"
                />
                <BaseUIButton v-if="canInviteExternal" variant="secondary" hide-icon @click="showInviteModal = true">
                    <span class="flex items-center gap-1.5">
                        <IconUserPlus stroke-width="1" class="size-5" />
                        {{ $t('Invite external to this tab') }}
                    </span>
                </BaseUIButton>
            </div>
            <div
                v-if="openExternalWriters.length"
                class="artwork-anchored-column mb-4"
            >
                <div class="flex max-w-2xl items-start gap-2 rounded-lg border border-info-border bg-info-surface px-3 py-2 text-xs text-info">
                    <IconInfoCircle class="mt-0.5 size-4 shrink-0" stroke-width="1.5" />
                    <span>
                        {{ $t('This tab is currently being filled in externally by {names}. The entries are visible immediately but have not been submitted as final yet.', { names: openExternalWriters.map((writer) => writer.name).join(', ') }) }}
                    </span>
                </div>
            </div>
            <InviteExternalModal
                v-if="showInviteModal"
                source="project_tab"
                :project="project"
                :available-tabs="headerObject.tabs ?? []"
                :preselected-tab-id="currentTab.id"
                :external-file-upload-enabled="pageProps.externalFileUploadEnabled === true"
                @close="onInviteModalClosed"
            />
            <div v-for="(component, idx) in currentTab.components" :key="component?.id ?? component?.component?.id ?? idx" :class="outerWidthClass(component.component?.type)">
                <div :class="innerWidthClass(component.component?.type)">
                <Component
                    v-if="canSeeComponent(component.component) && componentMapping[component.component?.type]"
                    :is="componentMapping[component.component?.type]"
                    :can-edit-component="canEditComponent(component.component)"
                    :project="project"
                    :in-sidebar="false"
                    :loadedProjectInformation="loadedProjectInformation"
                    :header-object="headerObject"
                    :create-settings="createSettings"
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
                <p
                    v-if="component.note && showsInlineHint(component.component?.type) && canSeeComponent(component.component)"
                    class="-mt-1 mb-3 whitespace-pre-line text-xs text-text-subtle"
                >{{ component.note }}</p>
                </div>
            </div>
        </div>


        <BaseSidenav @toggle="show = !show" v-if="currentTab.hasSidebarTabs">
            <div class="w-full">
                <div class="mb-5 ml-3">
                    <div class="hidden sm:block">
                        <div class="border-border-subtle">
                            <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8" aria-label="Tabs">
                                <div v-for="(tab, index) in currentTab.sidebar_tabs" :key="tab?.name"
                                     @click="currentSideBarTab = index"
                                     :class="[index === currentSideBarTab ? 'text-border-subtle border-border-subtle' : 'border-transparent text-text-subtle hover:text-accent-700 hover:border-accent-700', 'whitespace-nowrap py-2 px-1 border-b-2 font-semibold cursor-pointer']"
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
                            :create-settings="createSettings"
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
                        <p
                            v-if="component.note && showsInlineHint(component.component?.type) && canSeeComponent(component.component)"
                            class="-mt-1 mb-3 whitespace-pre-line text-xs text-white/70"
                        >{{ component.note }}</p>
                    </div>
                </div>
            </div>
        </BaseSidenav>
    </ProjectHeaderComponent>
</template>

<script setup>
import {computed, onMounted, provide, ref} from 'vue';
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
import ProjectCostCenterDisplayComponent from "@/Pages/Projects/Components/ProjectCostCenterDisplayComponent.vue";
import LinkComponent from "@/Pages/Projects/Tab/Components/LinkComponent.vue";
import ProjectMaterialIssueComponent from "@/Pages/Projects/Components/Issue/ProjectMaterialIssueComponent.vue";
import LinkListComponent from "@/Pages/Projects/Tab/Components/LinkListComponent.vue";
import ProjectContractsDocumentsComponent from "@/Pages/Projects/Components/ProjectContractsDocumentsComponent.vue";
import BusinessIntelligenceComponent from "@/Pages/Projects/Tab/Components/BusinessIntelligenceComponent.vue";
import SageInvoiceOverviewComponent from "@/Pages/Projects/Components/SageInvoiceOverviewComponent.vue";
import InviteExternalModal from "@/Pages/CRM/Components/InviteExternalModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import { IconInfoCircle, IconUserPlus } from "@tabler/icons-vue";
import ExternalTabStatus from "@/Pages/Projects/Tab/Components/ExternalTabStatus.vue";
import CrmContactListComponent from "@/Pages/Projects/Tab/Components/CrmContactListComponent.vue";
import { showsInlineHint } from "@/Helper/ComponentInlineHints.js";
import { useTranslation } from "@/Composeables/Translation.js";

const pageProps = usePage().props;
provide('pageProps', pageProps);

const $t = useTranslation();

const { canSeeComponent, canEditComponent, can } = usePermission(usePage().props);

const showInviteModal = ref(false);
const externalAccessEnabled = computed(() => pageProps.externalAccessEnabled === true);
// Einladen-Button nur mit Recht UND instanzweit freigeschaltetem Feature (Einstellungen → Externe Zugänge)
const canInviteExternal = computed(() => externalAccessEnabled.value && can('can invite externals'));
const externalTabStatusRef = ref(null);
// Nach Bestätigen/Zurückgeben laden CRM-Kontaktlisten neu (Markierung „ungeprüft“)
const externalReviewVersion = ref(0);
provide('externalReviewVersion', externalReviewVersion);
const openExternalWriters = ref([]);

const onInviteModalClosed = () => {
    showInviteModal.value = false;
    externalTabStatusRef.value?.reload();
};

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
    ProjectCostCenterDisplayComponent,
    ProjectMaterialIssueComponent,
    LinkList: LinkListComponent,
    ProjectContractsDocumentsComponent,
    BusinessIntelligenceComponent,
    SageInvoiceOverviewComponent,
    CrmContactListComponent,
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

/*
 * Designkonzept „Ankerlinie = Seitenkante" (linksverankertes Layout):
 * Alles hängt an EINER linken Ankerlinie neben der Sidebar. Drei Breitenstufen:
 * - Werkzeuge (Kalender/Schichten/Budget/Terminliste) laufen IMMER vollbreit bis
 *   zum rechten Viewport-Rand — auch neben anderen Komponenten auf dem Tab. Sie
 *   bekommen dasselbe Seitenpadding (artwork-anchored-page), damit ihre linke
 *   Kante mit Header und Spalte fluchtet.
 * - Spaltenbreite (artwork-anchored-column, 1536px-Deckel) für Tabellen/Listen/Dokumente.
 * - Lesebreite (max-w-2xl innerhalb der Spalte) für Text-/Formular-Komponenten.
 * Die Breite gehört dem Layout, nie der Komponente — Komponenten zentrieren sich
 * nicht selbst und setzen keine eigenen Außenbreiten oder Seitenpaddings.
 */
const TOOL_COMPONENT_TYPES = ['CalendarTab', 'ShiftTab', 'BudgetTab', 'BulkBody'];
const PROSE_COMPONENT_TYPES = [
    'TextField', 'TextArea', 'Title', 'Checkbox', 'DropDown', 'Link', 'LinkList',
    'ProjectStateComponent', 'ProjectBudgetDeadlineComponent', 'ArtistNameDisplayComponent',
    'ProjectBasicDataDisplayComponent', 'ProjectCostCenterDisplayComponent', 'ProjectAttributesComponent',
    'CrmContactListComponent',
];

const outerWidthClass = (componentType) => {
    return TOOL_COMPONENT_TYPES.includes(componentType) ? 'artwork-anchored-page' : 'artwork-anchored-column';
};

const innerWidthClass = (componentType) => {
    return PROSE_COMPONENT_TYPES.includes(componentType) ? 'max-w-2xl' : '';
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
            firstEventStart: props.headerObject.firstEventInProject?.event_date_without_time?.start ?? null,
            lastEventEnd: props.headerObject.lastEventInProject?.event_date_without_time?.end ?? null
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
