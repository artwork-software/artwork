<template>
    <ToolSettingsHeader :title="$t('Externe Nutzerverwaltung')">
        <div v-if="this.$page.props.flash.success"
             class="w-full font-bold text-sm border-1 border-green-600 rounded bg-green-600 p-2 text-white mb-3">
            {{ this.$page.props.flash.success }}
        </div>

        <div class="mt-10">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-semibold text-gray-900">{{ $t('External User Sources') }}</h2>
                <BaseUIButton
                    :label="$t('Add Source')"
                    is-add-button
                    @click="showCreateSourceModal = true"
                />
            </div>

            <!-- Sources List -->
            <div v-if="sources.length > 0" class="space-y-4">
                <div
                    v-for="source in sources"
                    :key="source.id"
                    class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm"
                >
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">{{ source.name }}</h3>
                            <div class="mt-2 flex items-center gap-4 text-sm text-gray-500">
                                <span :class="source.active ? 'text-green-600' : 'text-gray-400'">
                                    {{ source.active ? $t('Active') : $t('Inactive') }}
                                </span>
                                <span>{{ source.type === 'ldap' ? 'LDAP' : 'OIDC' }}</span>
                                <span v-if="source.config?.host">{{ source.config.host }}</span>
                                <span v-else-if="source.config?.discovery_url" class="truncate max-w-xs">{{ source.config.discovery_url }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                v-if="source.type === 'ldap'"
                                @click="syncSource(source)"
                                :disabled="syncingSource === source.id || !source.active"
                                class="px-3 py-1.5 text-sm bg-green-50 text-green-700 rounded hover:bg-green-100 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5"
                                :title="!source.active ? $t('Activate this source before syncing') : $t('Sync now')"
                            >
                                <component :is="IconRefresh" class="h-4 w-4" :class="{ 'animate-spin': syncingSource === source.id }" />
                                <span v-if="syncingSource === source.id">{{ $t('Syncing...') }}</span>
                                <span v-else>{{ $t('Sync now') }}</span>
                            </button>
                            <button
                                @click="testConnection(source)"
                                :disabled="testingConnection === source.id"
                                class="px-3 py-1.5 text-sm bg-blue-50 text-blue-700 rounded hover:bg-blue-100 disabled:opacity-50"
                            >
                                <span v-if="testingConnection === source.id">{{ $t('Testing...') }}</span>
                                <span v-else>{{ $t('Test Connection') }}</span>
                            </button>
                            <button
                                @click="editSource(source)"
                                class="px-3 py-1.5 text-sm bg-gray-50 text-gray-700 rounded hover:bg-gray-100 flex items-center gap-1.5"
                                :title="$t('Edit')"
                            >
                                <component :is="IconEdit" class="h-4 w-4" />
                                <span>{{ $t('Edit') }}</span>
                            </button>
                            <button
                                @click="showDeleteModal = source.id"
                                class="px-3 py-1.5 text-sm bg-red-50 text-red-700 rounded hover:bg-red-100 flex items-center gap-1.5"
                                :title="$t('Delete')"
                            >
                                <component :is="IconTrash" class="h-4 w-4" />
                                <span>{{ $t('Delete') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Connection Test Result -->
                    <div v-if="connectionTestResult[source.id]" class="mb-4 p-3 rounded"
                         :class="connectionTestResult[source.id].success ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
                        {{ connectionTestResult[source.id].message }}
                    </div>

                    <!-- Sync Result -->
                    <div v-if="syncResult[source.id]" class="mb-4 p-3 rounded"
                         :class="syncResult[source.id].success === false
                             ? 'bg-red-50 text-red-800'
                             : syncResult[source.id].complete
                                 ? 'bg-green-50 text-green-800'
                                 : 'bg-blue-50 text-blue-800'">
                        <div>{{ syncResult[source.id].message }}</div>
                        <div v-if="syncResult[source.id].result" class="mt-1 text-xs">
                            {{ $t('Processed') }}: {{ syncResult[source.id].result.total }} ·
                            {{ $t('Synchronized') }}: {{ syncResult[source.id].result.synced }} ·
                            {{ $t('Skipped') }}: {{ syncResult[source.id].result.skipped }}
                        </div>
                    </div>

                    <!-- Group Mappings -->
                    <div v-if="source.group_mappings && source.group_mappings.length > 0" class="mt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">{{ $t('Group Mappings') }}</h4>
                        <div class="space-y-2">
                            <div
                                v-for="mapping in source.group_mappings"
                                :key="mapping.id"
                                class="text-sm text-gray-600 bg-gray-50 p-2 rounded"
                            >
                                <span class="font-medium">{{ mapping.ad_group_name }}</span>
                                <span v-if="mapping.include_nested_groups" class="ml-2 text-xs text-gray-500">
                                    ({{ $t('including nested groups') }})
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-12 bg-gray-50 rounded-lg">
                <p class="text-gray-500">{{ $t('No external user sources configured yet.') }}</p>
            </div>
        </div>

        <!-- Create/Edit Source Modal -->
        <SourceModal
            v-if="showCreateSourceModal || editingSource"
            :show="showCreateSourceModal || !!editingSource"
            :source="editingSource"
            @close="closeModal"
            @saved="handleSourceSaved"
        />

        <!-- Delete Confirmation Modal -->
        <ConfirmDeleteModal
            v-if="showDeleteModal"
            :title="$t('Delete Source')"
            :description="$t('Are you sure you want to delete this source? This action cannot be undone.')"
            @closed="showDeleteModal = null"
            @delete="deleteSource"
        />
    </ToolSettingsHeader>
</template>

<script>
import {defineComponent} from "vue";
import {router} from "@inertiajs/vue3";
import axios from "axios";
import {IconEdit, IconTrash, IconRefresh} from "@tabler/icons-vue";
import ToolSettingsHeader from "@/Pages/ToolSettings/ToolSettingsHeader.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import SourceModal from "./Components/SourceModal.vue";

export default defineComponent({
    components: {
        ToolSettingsHeader,
        BaseUIButton,
        ConfirmDeleteModal,
        SourceModal,
        IconEdit,
        IconTrash,
        IconRefresh,
    },
    props: {
        sources: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            showCreateSourceModal: false,
            editingSource: null,
            showDeleteModal: null,
            testingConnection: null,
            connectionTestResult: {},
            syncingSource: null,
            syncResult: {},
        }
    },
    methods: {
        editSource(source) {
            this.editingSource = source;
        },
        closeModal() {
            this.showCreateSourceModal = false;
            this.editingSource = null;
        },
        handleSourceSaved() {
            this.closeModal();
            router.reload({ only: ['sources'] });
        },
        deleteSource() {
            if (!this.showDeleteModal) return;

            router.delete(route('tool.external-user-management.sources.destroy', {
                externalUserSource: this.showDeleteModal
            }), {
                preserveScroll: true,
                onSuccess: () => {
                    this.showDeleteModal = null;
                }
            });
        },
        async syncSource(source) {
            if (this.syncingSource) return;

            this.syncingSource = source.id;
            this.syncResult[source.id] = null;

            try {
                const response = await axios.post(
                    route('tool.external-user-management.sources.sync', {
                        externalUserSource: source.id
                    })
                );

                this.syncResult[source.id] = {
                    success: true,
                    complete: false,
                    message: response.data.message,
                };

                await this.waitForSyncCompletion(source.id);
            } catch (error) {
                this.syncResult[source.id] = {
                    success: false,
                    message: error.response?.data?.message || error.message || this.$t('Sync failed')
                };
            } finally {
                this.syncingSource = null;
            }
        },
        async waitForSyncCompletion(sourceId) {
            const terminalStatuses = ['completed', 'failed', 'cancelled'];

            for (let attempt = 0; attempt < 900; attempt += 1) {
                await new Promise((resolve) => window.setTimeout(resolve, 2000));

                const response = await axios.get(
                    route('tool.external-user-management.sources.sync-status', {
                        externalUserSource: sourceId
                    })
                );
                const status = response.data;

                this.syncResult[sourceId] = {
                    success: status.status !== 'failed' && status.status !== 'cancelled',
                    complete: terminalStatuses.includes(status.status),
                    message: status.message,
                    result: status.result,
                };

                if (terminalStatuses.includes(status.status)) {
                    if (status.status === 'completed') {
                        router.reload({ only: ['sources'] });
                    }

                    return;
                }
            }

            throw new Error(this.$t('The synchronization is still running. You can leave this page and check again later.'));
        },
        async testConnection(source) {
            this.testingConnection = source.id;
            this.connectionTestResult[source.id] = null;

            try {
                const response = await axios.post(
                    route('tool.external-user-management.sources.test-connection', {
                        externalUserSource: source.id
                    })
                );

                this.connectionTestResult[source.id] = {
                    success: response.data.success,
                    message: response.data.message
                };
            } catch (error) {
                this.connectionTestResult[source.id] = {
                    success: false,
                    message: error.response?.data?.message || this.$t('Connection test failed')
                };
            } finally {
                this.testingConnection = null;
            }
        }
    }
})
</script>
