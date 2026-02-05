<template>
    <div class="w-full">
        <!-- Contracts Section -->
        <div class="mb-8">
            <ToolbarHeader
                :icon="IconFileText"
                :title="$t('Contracts')"
                icon-bg-class="bg-emerald-600/10 text-emerald-700"
                :description="contracts.length ? `${contracts.length} ${$t('Contracts')}` : ''"
            >
                <template #actions>
                    <button class="ui-button-add" @click="openContractUploadModal">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                        {{ $t('New contract') }}
                    </button>
                </template>
            </ToolbarHeader>

            <!-- Contracts Table -->
            <BaseTable
                :rows="contracts"
                :columns="contractCols"
                row-key="id"
                v-model:page="contractPage"
                :empty-title="$t('No contracts available')"
                :empty-message="$t('No contracts have been uploaded yet.')"
            >
                <!-- Contract Partner -->
                <template #cell-partner="{ row }">
                    <div class="font-medium text-gray-900">{{ row.partner || '-' }}</div>
                </template>

                <!-- Access Users & Departments -->
                <template #cell-accessUsers="{ row }">
                    <div class="flex items-center">
                        <div class="flex -space-x-2">
                            <img
                                v-for="user in row.accessibleUsers?.slice(0, 3)"
                                :key="'user-' + user.id"
                                :src="user.profile_photo_url"
                                :alt="user.first_name + ' ' + user.last_name"
                                class="size-8 rounded-full ring-2 ring-white object-cover"
                                v-tooltip.top="{ value: user.first_name + ' ' + user.last_name, appendTo: 'body', class: 'aw-tooltip' }"
                            />
                            <div
                                v-for="department in row.accessibleDepartments?.slice(0, Math.max(0, 3 - (row.accessibleUsers?.length || 0)))"
                                :key="'dept-' + department.id"
                                class="size-8 rounded-full ring-2 ring-white bg-gray-100 flex items-center justify-center"
                                v-tooltip.top="{ value: department.name, appendTo: 'body', class: 'aw-tooltip' }"
                            >
                                <TeamIconCollection :iconName="department.svg_name" class="size-6" />
                            </div>
                        </div>
                        <BaseMenu
                            v-if="(row.accessibleUsers?.length || 0) + (row.accessibleDepartments?.length || 0) > 3"
                            :show-icon="false"
                            :show-menu-button-text="true"
                            :menu-button-text="'+' + ((row.accessibleUsers?.length || 0) + (row.accessibleDepartments?.length || 0) - 3)"
                            classes="ml-2 cursor-pointer"
                            classes-button="text-xs text-gray-500 hover:text-gray-700 cursor-pointer"
                            white-menu-background
                        >
                            <div class="p-2 min-w-48">
                                <div v-for="user in row.accessibleUsers" :key="'menu-user-' + user.id" class="flex items-center py-1.5 px-2 hover:bg-gray-50 rounded">
                                    <img :src="user.profile_photo_url" :alt="user.first_name + ' ' + user.last_name" class="size-6 rounded-full object-cover mr-2" />
                                    <span class="text-sm text-gray-700">{{ user.first_name }} {{ user.last_name }}</span>
                                </div>
                                <div v-for="department in row.accessibleDepartments" :key="'menu-dept-' + department.id" class="flex items-center py-1.5 px-2 hover:bg-gray-50 rounded">
                                    <TeamIconCollection :iconName="department.svg_name" class="size-6 mr-2" />
                                    <span class="text-sm text-gray-700">{{ department.name }}</span>
                                </div>
                            </div>
                        </BaseMenu>
                    </div>
                </template>

                <!-- File Name (Download) -->
                <template #cell-filename="{ row }">
                    <a :href="route('contracts.download', row.id)" class="text-blue-600 hover:text-blue-800 flex items-center">
                        <IconDownload class="size-4 mr-2" />
                        {{ row.name }}
                    </a>
                </template>

                <!-- Actions -->
                <template #row-actions="{ row }">
                    <BaseMenu has-no-offset white-menu-background>
                        <BaseMenuItem :icon="IconEdit" :title="$t('Edit')" white-menu-background @click="openContractEditModal(row)" />
                        <BaseMenuItem :icon="IconTrash" :title="$t('Delete')" white-menu-background @click="openContractDeleteModal(row)" />
                    </BaseMenu>
                </template>
            </BaseTable>
        </div>

        <!-- Document Requests Section -->
        <div>
            <ToolbarHeader
                :icon="IconFileDescription"
                :title="$t('Document Requests')"
                icon-bg-class="bg-blue-600/10 text-blue-700"
                :description="totalRequests ? `${totalRequests} ${$t('Requests')}` : ''"
            >
                <template #actions>
                    <button v-if="can('can create document requests')" class="ui-button-add" @click="showCreateRequestModal = true">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                        {{ $t('Create document request') }}
                    </button>
                </template>
            </ToolbarHeader>

            <!-- Tab Navigation -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex space-x-8">
                    <button
                        @click="activeTab = 'assigned'"
                        :class="[
                            activeTab === 'assigned'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                            'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
                        ]"
                    >
                        {{ $t('Assigned to me') }}
                        <span class="ml-2 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                            {{ openAssignedRequests.length }}
                        </span>
                    </button>
                    <button
                        @click="activeTab = 'created'"
                        :class="[
                            activeTab === 'created'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                            'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
                        ]"
                    >
                        {{ $t('Created by me') }}
                        <span class="ml-2 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                            {{ openCreatedRequests.length }}
                        </span>
                    </button>
                    <button
                        @click="activeTab = 'completed'"
                        :class="[
                            activeTab === 'completed'
                                ? 'border-green-500 text-green-600'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                            'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
                        ]"
                    >
                        {{ $t('Completed requests') }}
                        <span class="ml-2 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-600">
                            {{ completedRequests.length }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- Assigned Requests Table -->
            <div v-if="activeTab === 'assigned'">
                <BaseTable
                    :rows="openAssignedRequests"
                    :columns="requestCols"
                    row-key="id"
                    v-model:page="requestPage"
                    :empty-title="$t('No document requests')"
                    :empty-message="$t('No document requests assigned to you.')"
                >
                    <template #cell-title="{ row }">
                        <div class="font-medium text-gray-900">{{ row.title }}</div>
                        <div class="text-sm text-gray-500">{{ row.description }}</div>
                    </template>

                    <template #cell-requester="{ row }">
                        <div v-if="row.requester" class="flex items-center">
                            <img :src="row.requester.profile_photo_url" alt="" class="size-8 rounded-full object-cover" />
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ row.requester.first_name }} {{ row.requester.last_name }}
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #cell-status="{ row }">
                        <span :class="getStatusClass(row.status)" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                            {{ getStatusLabel(row.status) }}
                        </span>
                    </template>

                    <template #cell-deadline="{ row }">
                        <span v-if="row.deadline_date" class="text-sm text-gray-900">{{ formatDate(row.deadline_date) }}</span>
                        <span v-else class="text-sm text-gray-400">-</span>
                    </template>

                    <template #row-actions="{ row }">
                        <BaseMenu has-no-offset white-menu-background>
                            <BaseMenuItem :icon="IconUpload" :title="$t('Upload document')" white-menu-background @click="openUploadModal(row)" />
                            <BaseMenuItem :icon="IconEye" :title="$t('View details')" white-menu-background @click="openDetailModal(row)" />
                        </BaseMenu>
                    </template>
                </BaseTable>
            </div>

            <!-- Created Requests Table -->
            <div v-if="activeTab === 'created'">
                <BaseTable
                    :rows="openCreatedRequests"
                    :columns="requestColsCreated"
                    row-key="id"
                    v-model:page="requestPage"
                    :empty-title="$t('No document requests')"
                    :empty-message="$t('You have not created any document requests yet.')"
                >
                    <template #cell-title="{ row }">
                        <div class="font-medium text-gray-900">{{ row.title }}</div>
                        <div class="text-sm text-gray-500">{{ row.description }}</div>
                    </template>

                    <template #cell-requested="{ row }">
                        <div v-if="row.requested" class="flex items-center">
                            <img :src="row.requested.profile_photo_url" alt="" class="size-8 rounded-full object-cover" />
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ row.requested.first_name }} {{ row.requested.last_name }}
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #cell-status="{ row }">
                        <span :class="getStatusClass(row.status)" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                            {{ getStatusLabel(row.status) }}
                        </span>
                    </template>

                    <template #cell-contract="{ row }">
                        <a v-if="row.contract" :href="route('contracts.download', row.contract.id)" class="text-blue-600 hover:text-blue-800 text-sm">
                            {{ row.contract.name }}
                        </a>
                        <span v-else class="text-sm text-gray-400">-</span>
                    </template>

                    <template #row-actions="{ row }">
                        <BaseMenu has-no-offset white-menu-background>
                            <BaseMenuItem :icon="IconEye" :title="$t('View details')" white-menu-background @click="openDetailModal(row)" />
                            <BaseMenuItem :icon="IconEdit" :title="$t('Edit')" white-menu-background @click="openEditRequestModal(row)" />
                            <BaseMenuItem :icon="IconTrash" :title="$t('Delete')" white-menu-background @click="openDeleteRequestModal(row)" />
                        </BaseMenu>
                    </template>
                </BaseTable>
            </div>

            <!-- Completed Requests Table -->
            <div v-if="activeTab === 'completed'">
                <BaseTable
                    :rows="completedRequests"
                    :columns="requestColsCompleted"
                    row-key="id"
                    v-model:page="requestPage"
                    :empty-title="$t('No document requests')"
                    :empty-message="$t('No completed document requests yet.')"
                >
                    <template #cell-title="{ row }">
                        <div class="font-medium text-gray-900">{{ row.title }}</div>
                        <div class="text-sm text-gray-500">{{ row.description }}</div>
                    </template>

                    <template #cell-requester="{ row }">
                        <div v-if="row.requester" class="flex items-center">
                            <img :src="row.requester.profile_photo_url" alt="" class="size-8 rounded-full object-cover" />
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ row.requester.first_name }} {{ row.requester.last_name }}
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #cell-requested="{ row }">
                        <div v-if="row.requested" class="flex items-center">
                            <img :src="row.requested.profile_photo_url" alt="" class="size-8 rounded-full object-cover" />
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ row.requested.first_name }} {{ row.requested.last_name }}
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #cell-file="{ row }">
                        <a v-if="row.contract" :href="route('contracts.download', row.contract.id)" class="text-blue-600 hover:text-blue-800 text-sm flex items-center">
                            <IconDownload class="size-4 mr-1" />
                            {{ row.contract.name }}
                        </a>
                        <span v-else class="text-sm text-gray-400">-</span>
                    </template>

                    <template #row-actions="{ row }">
                        <BaseMenu has-no-offset white-menu-background>
                            <BaseMenuItem :icon="IconEye" :title="$t('View details')" white-menu-background @click="openDetailModal(row)" />
                        </BaseMenu>
                    </template>
                </BaseTable>
            </div>
        </div>

        <!-- Contract Upload Modal -->
        <ContractUploadModal
            :show="showContractUploadModal"
            @close-modal="closeContractUploadModal"
            :project-id="project.id"
            :company-types="companyTypes"
            :contract-types="contractTypes"
            :currencies="currencies"
            :first_project_calendar_tab_id="first_project_calendar_tab_id"
        />

        <!-- Contract Edit Modal -->
        <ContractEditModal
            v-if="showContractEditModal"
            :contract-types="contractTypes"
            :currencies="currencies"
            :company-types="companyTypes"
            :show="showContractEditModal !== null"
            @closeModal="closeContractEditModal"
            :contract="selectedContract"
        />

        <!-- Contract Delete Modal -->
        <ContractDeleteModal
            v-if="showContractDeleteModal"
            :show="showContractDeleteModal !== null"
            :close-modal="closeContractDeleteModal"
            :contract="selectedContract"
        />

        <!-- Create Document Request Modal -->
        <DocumentRequestCreateModal
            v-if="showCreateRequestModal"
            :show="showCreateRequestModal"
            :contract-types="contractTypes"
            :company-types="companyTypes"
            :preselected-project="project"
            @close="showCreateRequestModal = false"
        />

        <!-- Edit Document Request Modal -->
        <DocumentRequestEditModal
            v-if="showEditRequestModal"
            :show="showEditRequestModal"
            :document-request="selectedRequest"
            :contract-types="contractTypes"
            :company-types="companyTypes"
            @close="closeEditRequestModal"
        />

        <!-- Detail Modal -->
        <DocumentRequestDetailModal
            v-if="showDetailModal"
            :show="showDetailModal"
            :document-request="selectedRequest"
            @close="showDetailModal = false"
        />

        <!-- Contract Upload Modal for Document Request -->
        <ContractUploadModal
            v-if="showUploadModal"
            :show="showUploadModal"
            :company-types="companyTypes"
            :contract-types="contractTypes"
            :currencies="currencies"
            :document-request="selectedRequest"
            :project-id="project.id"
            :first_project_calendar_tab_id="first_project_calendar_tab_id"
            @close-modal="closeUploadModal"
        />

        <!-- Delete Request Modal -->
        <BaseModal @closed="closeDeleteRequestModal" v-if="showDeleteRequestModal" modal-image="/Svgs/Overlays/illu_warning.svg">
            <div class="mx-4">
                <div class="text-2xl font-bold text-zinc-900 my-2">
                    {{ $t('Delete document request') }}
                </div>
                <div class="text-sm text-red-600">
                    {{ $t('Are you sure you want to delete this document request?') }}
                </div>
                <div class="mt-6 flex items-center justify-between">
                    <BaseUIButton :label="$t('Delete')" is-delete-button @click="deleteRequest" />
                    <button @click="closeDeleteRequestModal" class="text-sm text-zinc-500 hover:text-zinc-800">
                        {{ $t('Cancel') }}
                    </button>
                </div>
            </div>
        </BaseModal>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { IconCirclePlus, IconFileText, IconFileDescription, IconEdit, IconTrash, IconDownload, IconUpload, IconEye } from '@tabler/icons-vue'
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue'
import BaseTable from '@/Artwork/Table/BaseTable.vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue'
import BaseModal from '@/Components/Modals/BaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import TeamIconCollection from '@/Layouts/Components/TeamIconCollection.vue'
import ContractUploadModal from '@/Layouts/Components/ContractUploadModal.vue'
import ContractEditModal from '@/Layouts/Components/ContractEditModal.vue'
import ContractDeleteModal from '@/Layouts/Components/ContractDeleteModal.vue'
import DocumentRequestCreateModal from '@/Pages/DocumentRequests/Components/DocumentRequestCreateModal.vue'
import DocumentRequestEditModal from '@/Pages/DocumentRequests/Components/DocumentRequestEditModal.vue'
import DocumentRequestDetailModal from '@/Pages/DocumentRequests/Components/DocumentRequestDetailModal.vue'
import {can} from "laravel-permission-to-vuejs";

const props = defineProps({
    project: {
        type: Object,
        required: true
    },
    data: {
        type: Object,
        default: () => ({})
    },
    canEditComponent: {
        type: Boolean,
        default: false
    },
    first_project_calendar_tab_id: {
        type: Number,
        default: null
    }
})

// Use usePage().props directly for reactivity with Inertia partial reloads
const page = usePage()

// Contracts data from page props (reactive)
const contracts = computed(() => page.props.projectContracts || [])
const contractTypes = computed(() => page.props.contractTypes || [])
const companyTypes = computed(() => page.props.companyTypes || [])
const currencies = computed(() => page.props.currencies || [])

// Document requests data from page props (reactive)
const createdRequests = computed(() => page.props.projectCreatedRequests || [])
const assignedRequests = computed(() => page.props.projectAssignedRequests || [])

// Contract state
const contractPage = ref(1)
const showContractUploadModal = ref(false)
const showContractEditModal = ref(null)
const showContractDeleteModal = ref(null)
const selectedContract = ref(null)

const contractCols = ref([
    { key: 'partner', label: 'Contract partner', sortable: false },
    { key: 'accessUsers', label: 'Access users', sortable: false },
    { key: 'filename', label: 'File name', sortable: false },
])

// Document request state
const activeTab = ref('assigned')
const requestPage = ref(1)
const showCreateRequestModal = ref(false)
const showEditRequestModal = ref(false)
const showDetailModal = ref(false)
const showDeleteRequestModal = ref(false)
const showUploadModal = ref(false)
const selectedRequest = ref(null)

const totalRequests = computed(() => createdRequests.value.length + assignedRequests.value.length)

const openAssignedRequests = computed(() =>
    assignedRequests.value.filter(r => r.status !== 'completed')
)

const openCreatedRequests = computed(() =>
    createdRequests.value.filter(r => r.status !== 'completed')
)

const completedRequests = computed(() => {
    const completed = [
        ...createdRequests.value.filter(r => r.status === 'completed'),
        ...assignedRequests.value.filter(r => r.status === 'completed')
    ]
    return completed.filter((r, index, self) =>
        index === self.findIndex(t => t.id === r.id)
    )
})

const requestCols = ref([
    { key: 'title', label: 'Title', sortable: false },
    { key: 'requester', label: 'Requested by', sortable: false },
    { key: 'status', label: 'Status', sortable: false },
    { key: 'deadline', label: 'Deadline', sortable: false },
])

const requestColsCreated = ref([
    { key: 'title', label: 'Title', sortable: false },
    { key: 'requested', label: 'Assigned to', sortable: false },
    { key: 'status', label: 'Status', sortable: false },
    { key: 'contract', label: 'Document', sortable: false },
])

const requestColsCompleted = ref([
    { key: 'title', label: 'Title', sortable: false },
    { key: 'requester', label: 'Requested by', sortable: false },
    { key: 'requested', label: 'Assigned to', sortable: false },
    { key: 'file', label: 'File', sortable: false },
])

// Contract methods
const openContractUploadModal = () => {
    showContractUploadModal.value = true
}

const closeContractUploadModal = () => {
    showContractUploadModal.value = false
}

const openContractEditModal = (contract) => {
    selectedContract.value = contract
    showContractEditModal.value = contract.id
}

const closeContractEditModal = () => {
    showContractEditModal.value = null
    selectedContract.value = null
}

const openContractDeleteModal = (contract) => {
    selectedContract.value = contract
    showContractDeleteModal.value = contract.id
}

const closeContractDeleteModal = () => {
    showContractDeleteModal.value = null
    selectedContract.value = null
}

// Document request methods
const getStatusClass = (status) => {
    switch (status) {
        case 'open':
            return 'bg-yellow-100 text-yellow-800'
        case 'in_progress':
            return 'bg-blue-100 text-blue-800'
        case 'completed':
            return 'bg-green-100 text-green-800'
        default:
            return 'bg-gray-100 text-gray-800'
    }
}

const getStatusLabel = (status) => {
    switch (status) {
        case 'open':
            return 'Open'
        case 'in_progress':
            return 'In Progress'
        case 'completed':
            return 'Completed'
        default:
            return status
    }
}

const formatDate = (dateString) => {
    if (!dateString) return '-'
    const [year, month, day] = dateString.split('-')
    return `${day}.${month}.${year}`
}

const openUploadModal = (request) => {
    selectedRequest.value = request
    showUploadModal.value = true
}

const closeUploadModal = () => {
    showUploadModal.value = false
    selectedRequest.value = null
}

const openDetailModal = (request) => {
    selectedRequest.value = request
    showDetailModal.value = true
}

const openEditRequestModal = (request) => {
    selectedRequest.value = request
    showEditRequestModal.value = true
}

const closeEditRequestModal = () => {
    selectedRequest.value = null
    showEditRequestModal.value = false
}

const openDeleteRequestModal = (request) => {
    selectedRequest.value = request
    showDeleteRequestModal.value = true
}

const closeDeleteRequestModal = () => {
    selectedRequest.value = null
    showDeleteRequestModal.value = false
}

const deleteRequest = () => {
    if (selectedRequest.value) {
        router.delete(route('document-requests.destroy', selectedRequest.value.id), {
            onSuccess: () => closeDeleteRequestModal()
        })
    }
}

// Listen for broadcast updates
let echoChannel = null

onMounted(() => {
    if (window.Echo && props.project?.id) {
        echoChannel = window.Echo.private(`project.${props.project.id}`)
            .listen('.contracts-documents.updated', () => {
                router.reload({ only: ['projectContracts', 'projectCreatedRequests', 'projectAssignedRequests'] })
            })
    }
})

onUnmounted(() => {
    if (echoChannel && props.project?.id) {
        window.Echo.leave(`project.${props.project.id}`)
    }
})
</script>
