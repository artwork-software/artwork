<template>
    <html>
        <head>
            <title>
                {{ project.title }} - Druckansicht - {{ layout.name }} - {{ new Date().toLocaleDateString() }}
            </title>
        </head>
        <body>
            <div class="print-container relative" :style="{
              '--header-height': `${headerHeight}px`,
              '--footer-height': `${footerHeight}px`
            }">
                <!-- HEADER (dynamic height, visible on every page) -->
                <header ref="headerRef" class="print-header w-full absolute bg-gray-200" :style="{top: `-${headerHeight}px`}">
                    <div class="header-content p-6">
                        <div class="grid gap-4" :class="'grid-cols-' + layout['columns_header']">
                            <div v-for="index in layout['columns_header']" :key="index">
                                <p class="xsDark" v-html="breakLine(layout.notes.header[index - 1])" />
                            </div>
                            <template v-for="col in layout['columns_header']" :key="col">
                                <div>
                                    <div v-if="getComponent(layout, 'header', 1, col)">
                                        <component
                                            :is="componentMapping['Builder' + getComponent(layout, 'header', 1, col)?.component.type]"
                                            :project="project"
                                            :component="getComponent(layout, 'header', 1, col)?.component"
                                        />
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </header>

                <!-- BODY (takes up remaining space) -->
                <main class="print-body p-6 " ref="bodyRef" :style="{marginTop: `${headerHeight}px`, paddingTop: `${headerHeight}px`}">
                    <div class="grid gap-4">
                        <template v-for="row in getRowCount(layout, 'body')" :key="row">
                            <div class="grid grid-cols-1 gap-4 component-wrapper" :class="'grid-cols-' + layout['columns_body']"  ref="componentRefs">
                                <template v-for="(col, index) in layout['columns_body']" :key="col">
                                    <div v-if="getComponent(layout, 'body', row, col)">
                                        <component
                                            :is="componentMapping['Builder' + getComponent(layout, 'body', row, col)?.component.type]"
                                            :project="project"
                                            :component="getComponent(layout, 'body', row, col)?.component"
                                            :eventsInProject="project.events"
                                            :eventTypes="project.event_types"
                                            :eventStatuses="project.event_statuses"
                                            :rooms="project.rooms"
                                            class="break-inside-avoid-page"
                                        />
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </main>



                <!-- FOOTER (dynamic height, visible on every page) -->
                <footer ref="footerRef" class="print-footer bg-gray-200 z-[100] w-full p-6">
                    <div class="footer-content">
                        <div class="grid gap-4" :class="'grid-cols-' + layout['columns_footer']">
                            <div v-for="index in layout['columns_footer']" :key="index">
                                <p class="xsDark" v-html="breakLine(layout.notes.footer[index - 1])" />
                            </div>
                            <template v-for="col in layout['columns_footer']" :key="col">
                                <div>
                                    <div v-if="getComponent(layout, 'footer', 1, col)">
                                        <component
                                            :is="componentMapping['Builder' + getComponent(layout, 'footer', 1, col)?.component.type]"
                                            :project="project"
                                            :component="getComponent(layout, 'footer', 1, col)?.component"
                                        />
                                    </div>
                                </div>
                            </template>
                        </div>
                        <!--<div class="page-number"></div>-->
                    </div>
                </footer>
            </div>
        </body>
    </html>
</template>

<script setup>
import BuilderProjectTitleComponent from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderProjectTitleComponent.vue";
import BuilderProjectTeamComponent from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderProjectTeamComponent.vue";
import BuilderTextArea from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderTextArea.vue";
import BuilderProjectStateComponent from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderProjectStateComponent.vue";
import BuilderShiftContactPersonsComponent from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderShiftContactPersonsComponent.vue";
import BuilderRelevantDatesForShiftPlanningComponent from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderRelevantDatesForShiftPlanningComponent.vue";
import BuilderGeneralShiftInformationComponent from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderGeneralShiftInformationComponent.vue";
import BuilderProjectBudgetDeadlineComponent from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderProjectBudgetDeadlineComponent.vue";
import BuilderProjectAttributesComponent from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderProjectAttributesComponent.vue";
import BuilderBudgetInformations from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderBudgetInformation.vue";
import BuilderTextField from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderTextField.vue";
import BuilderCheckbox from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderCheckbox.vue";
import BuilderDropDown from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderDropDown.vue";
import BuilderTitle from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderTitle.vue";
import EventTable from "@/Pages/Projects/PrintComponents/EventTable.vue";
import BuilderArtistResidenciesComponent from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderArtistResidenciesComponent.vue";
import BuilderProjectAllDocumentsComponent from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderProjectAllDocumentsComponent.vue";
import BuilderCommentAllTabComponent from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderCommentAllTabComponent.vue";
import ChecklistTable from "@/Pages/Projects/PrintComponents/ChecklistTable.vue";

const props = defineProps({
    layout: {
        type: Object,
        required: true
    },
    components: {
        type: Object,
        required: true
    },
    project: {
        type: Object,
        required: true
    }
});

import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
import BuilderSeparatorComponent from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderSeparatorComponent.vue";
import BuilderProjectGroupComponent from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderProjectGroupComponent.vue";
import BuilderArtistNameDisplayComponent from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderArtistNameDisplayComponent.vue";
import BuilderProjectBasicDataDisplayComponent from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderProjectBasicDataDisplayComponent.vue";
import BuilderProjectCostCenterDisplayComponent from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderProjectCostCenterDisplayComponent.vue";
import BuilderProjectMaterialIssueComponent from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderProjectMaterialIssueComponent.vue";
import BuilderProjectContractsDocumentsComponent from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderProjectContractsDocumentsComponent.vue";
import BuilderLinkComponent from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderLinkComponent.vue";
import BuilderLinkListComponent from "@/Pages/Projects/BuilderComponents/PrintLayoutBuilderLinkListComponent.vue";

const headerRef = ref(null);
const footerRef = ref(null);
const bodyRef = ref(null);
const componentRefs = ref([]);
const headerHeight = ref(0);
const footerHeight = ref(0);
const pageHeight = 297 * 3.78; // A4 in Pixel (1mm ≈ 3.78px)

// Berechnet die Header & Footer Höhe
const updateHeights = () => {
    const headerPx = Math.ceil(headerRef.value?.offsetHeight || 0);
    const footerPx = Math.ceil(footerRef.value?.offsetHeight || 0);
    headerHeight.value = headerPx;
    footerHeight.value = footerPx;

    nextTick(() => {
        if (headerRef.value && footerRef.value) {

            document.documentElement.style.setProperty('--header-height-mm', `${headerHeight.value * 0.264583}mm`);
            document.documentElement.style.setProperty('--footer-height-mm', `${footerHeight.value * 0.264583}mm`);
        }
    });
};

// Prüft, ob ein Element eine neue Seite braucht
const checkPageBreaks = () => {
    nextTick(() => {
        if (!componentRefs.value || componentRefs.value.length === 0) return;

        let lastBottom = headerHeight.value; // Starten nach dem Header
        const availableHeight = pageHeight - headerHeight.value - footerHeight.value; // Platz für den Body

        componentRefs.value.forEach((el) => {
            if (!el) return;

            const rect = el.getBoundingClientRect();
            const elementTop = rect.top + window.scrollY;
            const elementHeight = rect.height;

            // Falls das Element nicht mehr in den verfügbaren Bereich passt → Umbruch
            if (elementTop - lastBottom + elementHeight > availableHeight) {
                el.classList.add('page-break-before');
                lastBottom = elementTop + elementHeight; // Aktualisiere den letzten unteren Rand
                // margin top of the next page
            } else {
                //el.classList.add('mt-[var(--header-height)]');
                el.classList.remove('page-break-before');
                lastBottom += elementHeight;
            }
        });
    });
};

// Event Listener für Resize & Drucken
onMounted(() => {
    updateHeights();
    window.addEventListener('resize', updateHeights);
    window.addEventListener('beforeprint', checkPageBreaks);
    //setTimeout(checkPageBreaks, 500); // Kurzes Timeout für initiales Laden
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', updateHeights);
    window.removeEventListener('beforeprint', checkPageBreaks);
});


const componentMapping = {
    BuilderProjectTitleComponent,
    BuilderProjectTeamComponent,
    BuilderTextArea,
    BuilderProjectStateComponent,
    BuilderShiftContactPersonsComponent,
    BuilderRelevantDatesForShiftPlanningComponent,
    BuilderGeneralShiftInformationComponent,
    BuilderProjectBudgetDeadlineComponent,
    BuilderProjectAttributesComponent,
    BuilderBudgetInformations,
    BuilderTextField,
    BuilderCheckbox,
    BuilderDropDown,
    BuilderTitle,
    BuilderProjectGroupComponent,
    BuilderSeparatorComponent,
    BuilderBulkBody: EventTable,
    BuilderArtistResidenciesComponent,
    BuilderProjectAllDocumentsComponent,
    BuilderCommentAllTab: BuilderCommentAllTabComponent,
    BuilderChecklistAllComponent: ChecklistTable,
    BuilderArtistNameDisplayComponent,
    BuilderProjectBasicDataDisplayComponent,
    BuilderProjectCostCenterDisplayComponent,
    BuilderProjectMaterialIssueComponent,
    BuilderProjectContractsDocumentsComponent,
    BuilderLink: BuilderLinkComponent,
    BuilderLinkList: BuilderLinkListComponent
};

const getComponent = (layout, section, row, col) => {
    return layout[section + '_components'].find(comp => comp.row === row && comp.position === col) || null;
};

const getRowCount = (layout, section) => {
    if (section !== 'body') return 1; // Header and footer have fixed rows
    const maxOccupiedRow = getMaxOccupiedRow(layout, section);
    const hasComponentInRow = layout[section + '_components'].some(comp => comp.row === maxOccupiedRow);
    return hasComponentInRow ? maxOccupiedRow + 1 : maxOccupiedRow;
};

const getMaxOccupiedRow = (layout, section) => {
    const components = layout[section + '_components'];
    return components.length ? Math.max(...components.map(c => c.row)) : 1;
};

const breakLine = (text) => {
    return text?.replace(/\n/g, '<br>');
};
</script>

<style scoped>
.headline {
    font-size: 24px;
    font-weight: bold;
}

.grid {
    display: grid;
    gap: 10px;
}

.page-break-before {
    page-break-before: always;
}

.print-container {
    position: relative;
}

@media print {
    * {
        box-sizing: border-box;
    }

    html, body {
        margin: 0;
        padding: 0;
        width: 210mm;
        height: 297mm;
    }

    .print-container {
        width: 100%;
        position: relative;
        page-break-before: avoid;
        page-break-after: auto;
    }

    .page-number::after {
        content: "Seite " counter(page);
        font-size: 12px;
    }

    .print-body {
        margin-top: var(--header-height-mm);
        margin-bottom: var(--footer-height-mm);
        overflow: visible;
        break-inside: auto;
        page-break-inside: auto;
    }

    .print-body > div {
        break-inside: avoid;
        page-break-after: auto;
    }

    .component-wrapper {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .page-break-before {
        page-break-before: always;
    }

    @page {
        size: A4 portrait;
        margin: 0;
        counter-increment: page;
    }
}



</style>
