<template>
    <!-- Seitenpadding liefert der artwork-anchored-page-Wrapper (Ankerlinien-Konzept) -->
    <div :class="hideProjectHeader ? '' : 'mt-6 bg-surface-canvas'">
        <div class="flex bg-surface-canvas w-full">
            <BudgetComponent v-if="resolvedTable"
                             :sage-not-assigned="sageNotAssigned ?? effectiveBudgetData?.sageNotAssigned"
                             :hide-project-header="hideProjectHeader"
                             :table="resolvedTable"
                             :columnCalculatedNames="budget?.columnCalculatedNames ?? effectiveBudgetData?.budget?.columnCalculatedNames"
                             :project="project ?? headerObject?.project"
                             :selectedCell="budget?.selectedCell ?? effectiveBudgetData?.budget?.selectedCell"
                             :selectedRow="budget?.selectedRow ?? effectiveBudgetData?.budget?.selectedRow"
                             :templates="budget?.templates ?? effectiveBudgetData?.budget?.templates"
                             :selected-sum-detail="localSelectedSumDetail ?? effectiveBudgetData?.selectedSumDetail"
                             :money-sources="moneySources ?? effectiveBudgetData?.moneySources"
                             :budget-access="access_budget ?? project?.access_budget ?? headerObject?.access_budget"
                             :project-manager="managerUsers ?? project?.managerUsers ?? headerObject?.managerUsers"
                             :first_project_budget_tab_id="first_project_budget_tab_id"
                             :can-edit-component="canEditComponent"
                             @changeProjectHeaderVisualisation="changeProjectHeaderVisualisation"
                             @budget-updated="handleBudgetUpdated"
                             @sumDetailLoaded="handleSumDetailLoaded"
            />
            <div v-else class="w-full py-8">
                <div v-if="loadBudgetError" class="text-danger text-sm">
                    {{ loadBudgetError }}
                </div>
                <div v-else-if="isLoadingBudget" class="text-text-subtle text-sm">
                    {{ $t('Loading data...') }}
                </div>
                <div v-else class="text-text-subtle text-sm">
                    {{ $t('No budget data available.') }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import BudgetComponent from "@/Layouts/Components/BudgetComponent.vue";
import {usePage} from "@inertiajs/vue3";
import axios from 'axios';
import { stopListeningOnPrivateChannel } from "@/Composeables/Listener/echoChannel.js";

export default{
    components: {
        BudgetComponent,
    },
    props: [
        'project',
        'budget',
        'moneySources',
        'projectWriteIds',
        'projectManagerIds',
        'sageNotAssigned',
        'loadedProjectInformation',
        'headerObject',
        'first_project_budget_tab_id',
        'canEditComponent'
    ],
    data(){
        return {
            hideProjectHeader: false,
            isLoadingBudget: false,
            loadBudgetError: '',
            localBudgetData: this.loadedProjectInformation?.['BudgetTab'] || null,
            localSelectedSumDetail: null,
            access_budget: null,
            managerUsers: null,

            // 🔥 Broadcast state
            echoChannelName: null,
            budgetReloadQueued: false,
        }
    },
    computed: {
        effectiveBudgetData() {
            return this.localBudgetData || this.loadedProjectInformation?.['BudgetTab'] || {};
        },
        resolvedTable() {
            return this.budget?.table ?? this.effectiveBudgetData?.budget?.table ?? null;
        }
    },
    mounted() {
        this.fetchBudgetData();
        this.initBudgetBroadcast();
    },
    beforeUnmount() {
        this.destroyBudgetBroadcast();
    },
    methods: {
        usePage,

        initBudgetBroadcast() {
            const projectId = this.project?.id;
            if (!projectId) return;

            // falls schon aktiv
            this.destroyBudgetBroadcast();

            this.echoChannelName = `project.${projectId}`;

            // Eigener Handler, damit beim Abmelden nur dieser entfernt wird (Kanal ist geteilt)
            this.budgetUpdateHandler = (payload) => {
                // optional: nur reagieren, wenn es wirklich unser Projekt ist
                if (payload?.projectId && payload.projectId !== projectId) return;

                // Entprellen (trailing, 400 ms) mit maxWait (2 s): mehrere Updates kurz hintereinander →
                // EIN Nachladen nach dem letzten; bei Dauerfeuer spätestens 2 s nach dem ersten
                const now = Date.now();
                if (this.budgetReloadFirstPendingAt == null) {
                    this.budgetReloadFirstPendingAt = now;
                }
                const remainingMaxWait = 2000 - (now - this.budgetReloadFirstPendingAt);
                clearTimeout(this.budgetReloadTimer);
                this.budgetReloadTimer = setTimeout(() => {
                    this.budgetReloadTimer = null;
                    this.budgetReloadFirstPendingAt = null;
                    this.fetchBudgetData(true);
                }, Math.max(0, Math.min(400, remainingMaxWait)));
            };
            (window.Echo ?? Echo)
                .private(this.echoChannelName)
                .listen(".budget.update", this.budgetUpdateHandler);
        },

        destroyBudgetBroadcast() {
            clearTimeout(this.budgetReloadTimer);
            this.budgetReloadTimer = null;
            this.budgetReloadFirstPendingAt = null;
            this.budgetReloadQueued = false;
            if (!this.echoChannelName) return;

            // Nur den eigenen Handler abmelden – Echo.leave entfernte alle Listener des geteilten Projektkanals
            if (this.budgetUpdateHandler) {
                stopListeningOnPrivateChannel(this.echoChannelName, ".budget.update", this.budgetUpdateHandler);
            }
            this.echoChannelName = null;
            this.budgetUpdateHandler = null;
        },

        async fetchBudgetData(force = false) {
            if (this.isLoadingBudget) {
                // Erzwungenes Nachladen während eines laufenden Ladevorgangs nicht verwerfen, sondern
                // danach einmal nachholen (sonst fehlt eine Änderung, die während des Ladens kam)
                if (force) this.budgetReloadQueued = true;
                return;
            }
            if (!force && this.localBudgetData) return;

            const projectId = this.project?.id;
            if (!projectId) return;

            this.isLoadingBudget = true;
            this.loadBudgetError = "";

            try {
                const urlParams = new URLSearchParams(window.location.search);
                const selectedCell = urlParams.get("selectedCell");

                const { data } = await axios.get(
                    route("projects.tabs.budget", { project: projectId }),
                    { params: { selectedCell } }
                );

                this.localBudgetData = data?.BudgetTab || null;
                this.access_budget = data?.access_budget || null;
                this.managerUsers = data?.managerUsers || null;

                if (data?.users && this.headerObject?.project) {
                    this.headerObject.project.users = data.users;
                }
            } catch (error) {
                console.error(error);
                this.loadBudgetError = this.$t('Unable to load budget data.');
            } finally {
                this.isLoadingBudget = false;
                if (this.budgetReloadQueued) {
                    this.budgetReloadQueued = false;
                    this.fetchBudgetData(true);
                }
            }
        },

        changeProjectHeaderVisualisation(boolean) {
            this.hideProjectHeader = boolean;
        },
        handleBudgetUpdated() {
            // Force reload budget data after deletion
            this.fetchBudgetData(true);
        },
        handleSumDetailLoaded(sumDetail) {
            this.localSelectedSumDetail = sumDetail;
        }
    },
}
</script>
