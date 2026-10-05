/**
 * Vue-Komponente je Projekt-Tab-Komponententyp – gemeinsam für Tab-Ansicht und Ordner
 * (DisclosureComponent erhält folderComponentMapping per provide/inject aus TabContent, nie per
 * Import: sonst Zyklus). Regeln und Icons: projectTabComponentRules.js.
 */
import TextField from '@/Pages/Projects/Tab/Components/TextField.vue'
import Checkbox from '@/Pages/Projects/Tab/Components/Checkbox.vue'
import Title from '@/Pages/Projects/Tab/Components/Title.vue'
import TextArea from '@/Pages/Projects/Tab/Components/TextArea.vue'
import DropDown from '@/Pages/Projects/Tab/Components/DropDown.vue'
import ProjectStateComponent from '@/Pages/Projects/Components/ProjectStateComponent.vue'
import CalendarTab from '@/Pages/Projects/Tab/Components/CalendarTab.vue'
import ShiftTab from '@/Pages/Projects/Tab/Components/ShiftTab.vue'
import BudgetTab from '@/Pages/Projects/Tab/Components/BudgetTab.vue'
import ProjectBudgetDeadlineComponent from '@/Pages/Projects/Components/ProjectBudgetDeadlineComponent.vue'
import SeparatorComponent from '@/Pages/Projects/Tab/Components/SeparatorComponent.vue'
import ProjectGroupComponent from '@/Pages/Projects/Components/ProjectGroupComponent.vue'
import ProjectTeamComponent from '@/Pages/Projects/Components/ProjectTeamComponent.vue'
import ProjectAttributesComponent from '@/Pages/Projects/Components/ProjectAttributesComponent.vue'
import ProjectTitleComponent from '@/Pages/Projects/Components/ProjectTitleComponent.vue'
import ChecklistComponent from '@/Pages/Projects/Components/ChecklistComponent.vue'
import ShiftContactPersonsComponent from '@/Pages/Projects/Components/ShiftContactPersonsComponent.vue'
import GeneralShiftInformationComponent from '@/Pages/Projects/Components/GeneralShiftInformationComponent.vue'
import CommentTab from '@/Pages/Projects/Tab/Components/CommentTab.vue'
import ProjectDocumentsComponent from '@/Pages/Projects/Components/ProjectDocumentsComponent.vue'
import ProjectAllDocumentsComponent from '@/Pages/Projects/Components/ProjectAllDocumentsComponent.vue'
import ChecklistAllComponent from '@/Pages/Projects/Components/ChecklistAllComponent.vue'
import CommentAllTab from '@/Pages/Projects/Tab/Components/CommentAllTab.vue'
import BudgetInformations from '@/Pages/Projects/Tab/Components/BudgetInformations.vue'
import BulkBody from '@/Pages/Projects/Components/BulkComponents/BulkBody.vue'
import ArtistResidenciesComponent from '@/Pages/Projects/Tab/Components/ArtistResidenciesComponent.vue'
import GroupProjectDisplayComponent from '@/Pages/Projects/Components/GroupProjectDisplayComponent.vue'
import ProjectGroupDisplayComponent from '@/Pages/Projects/Components/ProjectGroupDisplayComponent.vue'
import DisclosureComponent from '@/Pages/Projects/Tab/Components/DisclosureComponent.vue'
import ArtistNameDisplayComponent from '@/Pages/Projects/Components/ArtistNameDisplayComponent.vue'
import ProjectBasicDataDisplayComponent from '@/Pages/Projects/Components/ProjectBasicDataDisplayComponent.vue'
import ProjectCostCenterDisplayComponent from '@/Pages/Projects/Components/ProjectCostCenterDisplayComponent.vue'
import LinkComponent from '@/Pages/Projects/Tab/Components/LinkComponent.vue'
import ProjectMaterialIssueComponent from '@/Pages/Projects/Components/Issue/ProjectMaterialIssueComponent.vue'
import LinkListComponent from '@/Pages/Projects/Tab/Components/LinkListComponent.vue'
import ProjectContractsDocumentsComponent from '@/Pages/Projects/Components/ProjectContractsDocumentsComponent.vue'
import BusinessIntelligenceComponent from '@/Pages/Projects/Tab/Components/BusinessIntelligenceComponent.vue'
import SageInvoiceOverviewComponent from '@/Pages/Projects/Components/SageInvoiceOverviewComponent.vue'
import CrmContactListComponent from '@/Pages/Projects/Tab/Components/CrmContactListComponent.vue'

export const projectTabComponents = {
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
}

/**
 * Für Ordnerinhalte: alles außer Ordnern selbst. In Ordnern gesperrte Typen bleiben renderbar,
 * damit vor der Sperre angelegte Ordnerinhalte nicht verschwinden.
 */
export const folderComponentMapping = () => {
    const { DisclosureComponent, ...mapping } = projectTabComponents

    return mapping
}
