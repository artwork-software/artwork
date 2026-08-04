<template>
    <div class="w-full">
        <div class="w-full flex items-center">
            <div class="text-text-subtle text-md">{{$t('Cost unit:')}} {{ project?.cost_center?.name }}</div>
            <IconEdit :class="[!this.inSidebar ? 'text-black' : 'text-white', 'ml-auto w-6 h-6 p-1 rounded-full bg-darkInputBg']" @click="openCopyrightModal"/>
            <ProjectCopyrightModal
                :show="showCopyrightModal"
                @close-modal="closeCopyrightModal"
                :project="project"
            />
        </div>
        <div class="text-text-subtle text-md">{{$t('GEMA')}}: {{ project.gema ? $t('Yes') : $t('No') }}</div>
        <div class="text-text-subtle text-sm mt-2" v-if="project.cost_center_description">{{ project.cost_center_description }}</div>
        <hr class="my-10 border-darkGray">
        <div class="w-full flex items-center mb-4" v-if="this.$canAny(['can manage global project budgets']) || this.hasAdminRole() || this.hasBudgetAccess() || this.effectiveBudgetInformation?.project_manager_ids?.includes(this.$page.props.auth.user.id)">
            <div class="text-text-subtle text-md">{{$t('Documents')}}</div>
            <IconChevronDown class="w-4 h-4 ml-4" :class="[ showProjectFiles ? 'rotate-180' : '']"
                             @click="showProjectFiles = !showProjectFiles"/>
            <IconUpload v-if="this.hasAdminRole() || this.$can('can manage global project budgets')"
                        class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                        @click="openFileUploadModal"/>
            <ProjectFileUploadModal :show="showFileUploadModal"
                                    :close-modal="closeFileUploadModal"
                                    :project-id="project.id"
                                    :budget-access="effectiveBudgetInformation?.access_budget ?? project.access_budget"/>
        </div>
            <div v-if="showProjectFiles">
            <div v-if="effectiveBudgetInformation?.project_files?.length > 0">
                <div v-for="projectFile in effectiveBudgetInformation.project_files">
                    <div
                        v-if="projectFile.accessibleUsers?.filter(user => user.id === $page.props.auth.user.id).length > 0 || this.hasAdminRole()"
                        class="flex items-center w-full mb-2 cursor-pointer text-text-subtle hover:text-white"
                    >
                        <IconDownload class="w-4 h-4 mr-2" @click="downloadProjectFile(projectFile)"/>
                        <div @click="openFileEditModal(projectFile)">{{ projectFile.name }}</div>
                        <IconCircleX class="w-4 h-4 ml-auto bg-danger rounded-full text-white" @click="openFileDeleteModal(projectFile)"/>
                    </div>
                </div>

                <ProjectFileEditModal
                    :show="showFileEditModal"
                    :close-modal="closeFileEditModal"
                    :file="projectFileToEdit"
                />

                <FileDeleteModal
                    :show="showFileDeleteModal"
                    :close-modal="closeFileDeleteModal"
                    :file="projectFileToDelete"
                    type="project"
                />
            </div>
            <div v-else>
                <div class="text-text-subtle text-sm mt-2">{{$t('No documents available')}}</div>
            </div>
        </div>
        <div
            v-if="$can('view edit upload contracts') || this.hasBudgetAccess()">
            <hr class="my-10 border-darkGray">
            <div class="w-full flex items-center mb-4">
                <div class="text-text-subtle text-md">{{ $t('Contracts')}}</div>
                <IconChevronDown class="w-4 h-4 ml-4" :class="[ showContracts ? 'rotate-180' : '']"
                                 @click="showContracts = !showContracts"/>
                <IconUpload class="ml-auto w-6 h-6 p-1 rounded-full text-white bg-darkInputBg"
                            @click="openContractUploadModal"/>
                <ContractUploadModal
                    :show="showContractUploadModal"
                    :project-id="project.id"
                    :budget-access="this.effectiveBudgetInformation?.access_budget ?? project.access_budget"
                    :contract-types="this.effectiveBudgetInformation?.contract_types ?? project.contract_types"
                    :company-types="this.effectiveBudgetInformation?.company_types ?? project.company_types"
                    :currencies="this.effectiveBudgetInformation?.currencies ?? project.currencies"
                    @close-modal="closeContractUploadModal"/>
            </div>
            <div v-if="showContracts">
                <div v-if="this.effectiveBudgetInformation?.contracts?.length > 0">
                    <div v-for="contract in this.effectiveBudgetInformation.contracts">
                        <div
                            v-if="contract.accessibleUsers?.filter(user => user.id === $page.props.auth.user.id).length > 0 || hasAdminRole()"
                            class="flex items-center w-full mb-2 cursor-pointer text-text-subtle hover:text-white">
                            <IconDownload class="w-4 h-4 mr-2" @click="downloadContract(contract)"/>
                            <div @click="openContractEditModal(contract)">{{ contract.name }}</div>
                            <ContractDeleteModal v-if="showContractDeleteModal"
                                                 :show="showContractDeleteModal === contract?.id"
                                                 :close-modal="closeContractDeleteModal"
                                                 :contract="contract"/>
                            <ContractEditModal v-if="showContractEditModal"
                                               :show="showContractEditModal === contract?.id"
                                               :close-modal="closeContractEditModal"
                                               :contract="contract"
                                               :contract-types="this.effectiveBudgetInformation?.contract_types ?? project.contract_types"
                                               :company-types="this.effectiveBudgetInformation?.company_types ?? project.company_types"
                                               :currencies="this.effectiveBudgetInformation?.currencies ?? project.currencies"/>
                            <IconCircleX class="w-4 h-4 ml-auto bg-danger rounded-full text-white" @click="openContractDeleteModal(contract)"/>
                        </div>
                    </div>
                </div>
                <div v-else>
                    <div class="text-text-subtle text-sm mt-2">{{$t('No contracts available')}}</div>
                </div>
            </div>
            <div v-if="this.$can('view edit add money_sources') || this.hasAdminRole() || this.hasBudgetAccess()">
                <hr class="my-10 border-darkGray">
                <section class="overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-white/5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600"
                        :aria-expanded="showMoneySources"
                        aria-controls="linked-money-sources"
                        @click="showMoneySources = !showMoneySources"
                    >
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-text-subtle">{{ $t('Linked sources of funding') }}</span>
                            <span class="mt-0.5 block text-xs text-text-subtle/70">
                                {{ filteredMoneySources.length }} {{ filteredMoneySources.length === 1 ? $t('source') : $t('sources') }}
                            </span>
                        </span>
                        <IconChevronDown
                            class="h-5 w-5 shrink-0 text-text-subtle transition-transform"
                            :class="showMoneySources ? 'rotate-180' : ''"
                            aria-hidden="true"
                        />
                    </button>

                    <div v-if="showMoneySources" id="linked-money-sources" class="border-t border-white/10 px-4 py-4">
                        <label v-if="moneySources.length > 5" class="relative mb-4 block">
                            <span class="sr-only">{{ $t('Search funding sources') }}</span>
                            <input
                                v-model.trim="moneySourceSearch"
                                type="search"
                                class="w-full rounded-xl border border-white/10 bg-black/10 px-3 py-2 text-sm text-text-subtle placeholder:text-text-subtle/50 focus:border-accent-600 focus:outline-none focus:ring-1 focus:ring-accent-600"
                                :placeholder="$t('Search funding sources')"
                            >
                        </label>

                        <div v-if="filteredMoneySources.length" class="space-y-2">
                            <article
                                v-for="moneySource in filteredMoneySources"
                                :key="moneySource.id"
                                class="rounded-xl border border-white/10 bg-white/5 px-3 py-3 transition hover:border-white/20 hover:bg-white/10"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <Link
                                                v-if="this.$can('view edit add money_sources') || this.hasAdminRole()"
                                                class="truncate text-sm font-semibold text-linkOnDarkColor underline-offset-2 hover:underline"
                                                :href="route('money_sources.show', {moneySource: moneySource.id})"
                                            >
                                                {{ moneySource.name }}
                                            </Link>
                                            <span v-else class="truncate text-sm font-semibold text-text-subtle">{{ moneySource.name }}</span>
                                            <span class="rounded-full border border-white/10 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-text-subtle/80">
                                                {{ moneySource.is_group ? $t('Group') : $t('Funding source') }}
                                            </span>
                                        </div>
                                        <p v-if="moneySource.source_name" class="mt-1 truncate text-xs text-text-subtle/70">
                                            {{ moneySource.source_name }}
                                        </p>
                                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-text-subtle/70">
                                            <span v-if="moneySource.amount !== null && moneySource.amount !== undefined">
                                                {{ $t('Amount') }}: {{ formatMoneySourceAmount(moneySource.amount) }}
                                            </span>
                                            <span v-if="moneySource.start_date || moneySource.end_date">
                                                {{ formatMoneySourcePeriod(moneySource) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>
                        <div v-else class="rounded-xl border border-dashed border-white/10 px-4 py-6 text-center text-sm text-text-subtle/70">
                            {{ moneySourceSearch ? $t('No matching funding sources found') : $t('No sources of funding available') }}
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

</template>

<script>
import {IconChevronDown, IconCircleX, IconDownload, IconEdit, IconUpload} from "@tabler/icons-vue";
import ContractModuleDeleteModal from "@/Layouts/Components/ContractModuleDeleteModal.vue";
import ContractModuleUploadModal from "@/Layouts/Components/ContractModuleUploadModal.vue";
import ProjectFileUploadModal from "@/Layouts/Components/ProjectFileUploadModal.vue";
import ContractDeleteModal from "@/Layouts/Components/ContractDeleteModal.vue";
import FileDeleteModal from "@/Layouts/Components/FileDeleteModal.vue";
import ProjectFileEditModal from "@/Layouts/Components/ProjectFileEditModal.vue";
import ContractUploadModal from "@/Layouts/Components/ContractUploadModal.vue";
import ContractEditModal from "@/Layouts/Components/ContractEditModal.vue";
import ProjectCopyrightModal from "@/Layouts/Components/ProjectCopyrightModal.vue";
import Permissions from "@/Mixins/Permissions.vue";
import {Link} from '@inertiajs/vue3';
import IconLib from "@/Mixins/IconLib.vue";
import axios from 'axios';

export default {
    mixins: [Permissions, IconLib],
    name: "ProjectSidenav",
    components: {
        ContractEditModal,
        ContractUploadModal,
        ProjectFileEditModal,
        FileDeleteModal,
        ContractDeleteModal,
        ProjectFileUploadModal,
        ContractModuleDeleteModal,
        IconDownload,
        IconUpload,
        IconCircleX,
        ContractModuleUploadModal,
        IconEdit,
        IconChevronDown,
        ProjectCopyrightModal,
        Link
    },
    props: [
        'loadedProjectInformation',
        'project',
        'inSidebar'
    ],
    data() {
        return {
            showFileDeleteModal: false,
            showFileUploadModal: false,
            showContractUploadModal: false,
            showContractDeleteModal: null,
            showProjectFiles: false,
            showContracts: false,
            showMoneySources: false,
            showFileEditModal: false,
            showContractEditModal: null,
            showCopyrightModal: false,
            projectFileToEdit: null,
            projectFileToDelete: null,
            moneySourceSearch: '',
            isLoadingBudgetInfo: false,
            loadBudgetInfoError: '',
            localBudgetInformation: this.loadedProjectInformation?.['BudgetInformation'] || null
        }
    },
    computed: {
        effectiveBudgetInformation() {
            return this.localBudgetInformation || this.loadedProjectInformation?.['BudgetInformation'] || {};
        },
        moneySources() {
            return this.effectiveBudgetInformation?.project_money_sources || [];
        },
        filteredMoneySources() {
            const search = this.moneySourceSearch.toLocaleLowerCase().trim();
            if (!search) {
                return this.moneySources;
            }

            return this.moneySources.filter((moneySource) =>
                [moneySource.name, moneySource.source_name, moneySource.description]
                    .filter(Boolean)
                    .some((value) => String(value).toLocaleLowerCase().includes(search))
            );
        }
    },
    mounted() {
        this.fetchBudgetInformation();
    },
    methods: {
        async fetchBudgetInformation() {
            if (this.localBudgetInformation) {
                return;
            }

            const projectId = this.project?.id;
            if (!projectId) {
                return;
            }

            this.isLoadingBudgetInfo = true;
            this.loadBudgetInfoError = '';

            try {
                const { data } = await axios.get(
                    route('projects.tabs.budget-informations', { project: projectId })
                );
                this.localBudgetInformation = data?.BudgetInformation || null;
            } catch (error) {
                console.error(error);
                this.loadBudgetInfoError = 'Unable to load budget information.';
            } finally {
                this.isLoadingBudgetInfo = false;
            }
        },
        hasBudgetAccess() {
            const budgetInfo = this.effectiveBudgetInformation;
            if (!budgetInfo?.access_budget) {
                return false;
            }
            return budgetInfo.access_budget.filter(
                (user) => user.id === this.$page.props.auth.user.id
            ).length > 0;
        },
        formatMoneySourceAmount(amount) {
            return new Intl.NumberFormat(this.$page.props.locale || 'de-DE', {
                style: 'currency',
                currency: this.$page.props.currency || 'EUR'
            }).format(Number(amount || 0));
        },
        formatMoneySourcePeriod(moneySource) {
            const format = (date) => date
                ? new Intl.DateTimeFormat(this.$page.props.locale || 'de-DE').format(new Date(date))
                : '…';

            return `${format(moneySource.start_date)} – ${format(moneySource.end_date)}`;
        },
        downloadContract(contract) {
            let link = document.createElement('a');
            link.href = route('contracts.download', {contract: contract});
            link.target = '_blank';
            link.click();
        },
        downloadProjectFile(file) {
            let link = document.createElement('a');
            link.href = route('download_file', {project_file: file});
            link.target = '_blank';
            link.click();
        },
        openCopyrightModal() {
            this.showCopyrightModal = true
        },
        closeCopyrightModal() {
            this.showCopyrightModal = false
        },
        openFileEditModal(projectFile) {
            this.projectFileToEdit = projectFile
            this.showFileEditModal = true
        },
        openContractEditModal(contract) {
            this.showContractEditModal = contract.id
        },
        closeFileEditModal() {
            this.projectFileToEdit = null
            this.showFileEditModal = false
        },
        closeContractEditModal() {
            this.showContractEditModal = null
        },
        openFileUploadModal() {
            this.showFileUploadModal = true
        },
        closeFileUploadModal() {
            this.showFileUploadModal = false
        },
        openFileDeleteModal(projectFile) {
            this.projectFileToDelete = projectFile
            this.showFileDeleteModal = true
        },
        closeFileDeleteModal() {
            this.projectFileToDelete = null
            this.showFileDeleteModal = false
        },
        openContractUploadModal() {
            this.showContractUploadModal = true
        },
        closeContractUploadModal() {
            this.showContractUploadModal = false
        },
        openContractDeleteModal(contract) {
            this.showContractDeleteModal = contract.id
        },
        closeContractDeleteModal() {
            this.showContractDeleteModal = null;
        },
    }
}
</script>
