<template>
    <div :class="hideProjectHeader ? 'px-5' : 'mt-6 px-5  bg-light-background-gray'">
        <pre>
            {{access_budget}}
            {{managerUsers}}
        </pre>
        <div class="flex bg-light-background-gray w-[95%]">

            <BudgetComponent v-if="resolvedTable"
                             :sage-not-assigned="sageNotAssigned ?? effectiveBudgetData?.sageNotAssigned"
                             :hide-project-header="hideProjectHeader"
                             :table="resolvedTable"
                             :columnCalculatedNames="budget?.columnCalculatedNames ?? effectiveBudgetData?.budget?.columnCalculatedNames"
                             :project="project ?? headerObject?.project"
                             :selectedCell="budget?.selectedCell ?? effectiveBudgetData?.budget?.selectedCell"
                             :selectedRow="budget?.selectedRow ?? effectiveBudgetData?.budget?.selectedRow"
                             :templates="budget?.templates ?? effectiveBudgetData?.budget?.templates"
                             :selected-sum-detail="budget?.selectedSumDetail ?? effectiveBudgetData?.budget?.selectedSumDetail"
                             :money-sources="moneySources ?? effectiveBudgetData?.moneySources"
                             :budget-access="access_budget ?? project?.access_budget ?? headerObject?.access_budget"
                             :project-manager="managerUsers ?? project?.managerUsers ?? headerObject?.managerUsers"
                             :first_project_budget_tab_id="first_project_budget_tab_id"
                             :can-edit-component="canEditComponent"
                             @changeProjectHeaderVisualisation="changeProjectHeaderVisualisation"
            />
            <div v-else class="w-full py-8">
                <div v-if="loadBudgetError" class="text-error text-sm">
                    {{ loadBudgetError }}
                </div>
                <div v-else-if="isLoadingBudget" class="text-secondary text-sm">
                    {{ $t('Loading data...') }}
                </div>
                <div v-else class="text-secondary text-sm">
                    {{ $t('No budget data available.') }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import JetInputError from "@/Jetstream/InputError.vue";
import {DocumentTextIcon, PencilAltIcon, XIcon} from "@heroicons/vue/outline";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {XCircleIcon} from "@heroicons/vue/solid";
import BudgetComponent from "@/Layouts/Components/BudgetComponent.vue";
import ProjectBudgetDeadlineComponent from "@/Pages/Projects/Components/ProjectBudgetDeadlineComponent.vue";
import {usePage} from "@inertiajs/vue3";
import axios from 'axios';

export default{
    components: {
        ProjectBudgetDeadlineComponent,
        BudgetComponent,
        PencilAltIcon,
        XCircleIcon,
        DocumentTextIcon,
        SvgCollection,
        XIcon,
        JetInputError
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
            access_budget: null,
            managerUsers: null,

            // 🔥 Broadcast state
            echoChannelName: null,
            lastReloadAt: 0,
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

            // Echo ist meist global (window.Echo). Falls bei dir global: einfach Echo statt window.Echo.
            (window.Echo ?? Echo)
                .private(this.echoChannelName)
                .listen(".budget.update", (payload) => {
                    // optional: nur reagieren, wenn es wirklich unser Projekt ist
                    if (payload?.projectId && payload.projectId !== projectId) return;

                    // optional: kleines Debounce/Throttle gegen Spam
                    const now = Date.now();
                    if (now - this.lastReloadAt < 400) return;
                    this.lastReloadAt = now;

                    this.fetchBudgetData(true);
                });
        },

        destroyBudgetBroadcast() {
            if (!this.echoChannelName) return;

            // leave = unsub + cleanup
            (window.Echo ?? Echo).leave(this.echoChannelName);
            this.echoChannelName = null;
        },

        async fetchBudgetData(force = false) {
            if (this.isLoadingBudget) return;
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
                this.loadBudgetError = "Unable to load budget data.";
            } finally {
                this.isLoadingBudget = false;
            }
        },

        changeProjectHeaderVisualisation(boolean) {
            this.hideProjectHeader = boolean;
        }
    },
}
</script>
