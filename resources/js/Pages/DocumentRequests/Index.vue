<template>
    <app-layout :title="$t('Document Requests')">
        <div class="artwork-container">
            <ToolbarHeader
                :icon="IconFileDescription"
                :title="$t('Document Requests')"
                icon-bg-class="bg-blue-600/10 text-blue-700"
                :description="totalRequests ? `${totalRequests} ${$t('Requests')}` : ''"
            >
                <template #actions>
                    <button class="ui-button-add" @click="showCreateModal = true">
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
                            {{ assignedRequests.length }}
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
                            {{ createdRequests.length }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- Assigned Requests Table -->
            <div v-if="activeTab === 'assigned'">
                <BaseTable
                    :rows="assignedRequests"
                    :columns="cols"
                    row-key="id"
                    v-model:page="page"
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

                    <template #cell-project="{ row }">
                        <span v-if="row.project" class="text-sm text-gray-900">{{ row.project.name }}</span>
                        <span v-else class="text-sm text-gray-400">-</span>
                    </template>

                    <template #cell-status="{ row }">
                        <span :class="getStatusClass(row.status)" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                            {{ getStatusLabel(row.status) }}
                        </span>
                    </template>

                    <template #cell-deadline="{ row }">
                        <span v-if="row.deadline_date" class="text-sm text-gray-900">{{ row.deadline_date }}</span>
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
                    :rows="createdRequests"
                    :columns="colsCreated"
                    row-key="id"
                    v-model:page="page"
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

                    <template #cell-project="{ row }">
                        <span v-if="row.project" class="text-sm text-gray-900">{{ row.project.name }}</span>
                        <span v-else class="text-sm text-gray-400">-</span>
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
                            <BaseMenuItem :icon="IconEdit" :title="$t('Edit')" white-menu-background @click="openEditModal(row)" />
                            <BaseMenuItem :icon="IconTrash" :title="$t('Delete')" white-menu-background @click="openDeleteModal(row)" />
                        </BaseMenu>
                    </template>
                </BaseTable>
            </div>
        </div>

        <!-- Create Document Request Modal -->
        <DocumentRequestCreateModal
            v-if="showCreateModal"
            :show="showCreateModal"
            :contract-types="contract_types"
            :company-types="company_types"
            @close="showCreateModal = false"
        />

        <!-- Edit Document Request Modal -->
        <DocumentRequestEditModal
            v-if="showEditModal"
            :show="showEditModal"
            :document-request="selectedRequest"
            :contract-types="contract_types"
            :company-types="company_types"
            @close="closeEditModal"
        />

        <!-- Detail Modal -->
        <DocumentRequestDetailModal
            v-if="showDetailModal"
            :show="showDetailModal"
            :document-request="selectedRequest"
            @close="showDetailModal = false"
        />

        <!-- Delete Modal -->
        <BaseModal @closed="closeDeleteModal" v-if="showDeleteModal" modal-image="/Svgs/Overlays/illu_warning.svg">
            <div class="mx-4">
                <div class="text-2xl font-bold text-zinc-900 my-2">
                    {{ $t('Delete document request') }}
                </div>
                <div class="text-sm text-red-600">
                    {{ $t('Are you sure you want to delete this document request?') }}
                </div>
                <div class="mt-6 flex items-center justify-between">
                    <BaseUIButton :label="$t('Delete')" is-delete-button @click="deleteRequest" />
                    <button @click="closeDeleteModal" class="text-sm text-zinc-500 hover:text-zinc-800">
                        {{ $t('Cancel') }}
                    </button>
                </div>
            </div>
        </BaseModal>
    </app-layout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { IconCirclePlus, IconFileDescription, IconUpload, IconEye, IconEdit, IconTrash } from '@tabler/icons-vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue'
import BaseTable from '@/Artwork/Table/BaseTable.vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue'
import BaseModal from '@/Components/Modals/BaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import DocumentRequestCreateModal from './Components/DocumentRequestCreateModal.vue'
import DocumentRequestEditModal from './Components/DocumentRequestEditModal.vue'
import DocumentRequestDetailModal from './Components/DocumentRequestDetailModal.vue'

const props = defineProps({
    createdRequests: {
        type: Array,
        default: () => []
    },
    assignedRequests: {
        type: Array,
        default: () => []
    },
    contract_types: {
        type: Array,
        default: () => []
    },
    company_types: {
        type: Array,
        default: () => []
    },
    currencies: {
        type: Array,
        default: () => []
    }
})

const activeTab = ref('assigned')
const page = ref(1)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDetailModal = ref(false)
const showDeleteModal = ref(false)
const selectedRequest = ref(null)

const totalRequests = computed(() => props.createdRequests.length + props.assignedRequests.length)

const cols = ref([
    { key: 'title', label: 'Title', sortable: false },
    { key: 'requester', label: 'Requested by', sortable: false },
    { key: 'project', label: 'Project', sortable: false },
    { key: 'status', label: 'Status', sortable: false },
    { key: 'deadline', label: 'Deadline', sortable: false },
])

const colsCreated = ref([
    { key: 'title', label: 'Title', sortable: false },
    { key: 'requested', label: 'Assigned to', sortable: false },
    { key: 'project', label: 'Project', sortable: false },
    { key: 'status', label: 'Status', sortable: false },
    { key: 'contract', label: 'Document', sortable: false },
])

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

const openUploadModal = (request) => {
    selectedRequest.value = request
    // TODO: Implement upload modal
}

const openDetailModal = (request) => {
    selectedRequest.value = request
    showDetailModal.value = true
}

const openEditModal = (request) => {
    selectedRequest.value = request
    showEditModal.value = true
}

const closeEditModal = () => {
    selectedRequest.value = null
    showEditModal.value = false
}

const openDeleteModal = (request) => {
    selectedRequest.value = request
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    selectedRequest.value = null
    showDeleteModal.value = false
}

const deleteRequest = () => {
    if (selectedRequest.value) {
        router.delete(route('document-requests.destroy', selectedRequest.value.id), {
            onSuccess: () => closeDeleteModal()
        })
    }
}
</script>
