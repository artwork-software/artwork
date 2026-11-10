<template>
    <ArtworkBaseModal
        :title="selectedLog ? 'Protocol details' : 'Sage protocol'"
        :description="selectedLog ? formatRunTitle(selectedLog) : 'Log of imported and deleted Sage bookings'"
        modal-size="sm:max-w-6xl"
        @close="$emit('closed')"
    >
        <!-- Liste der Läufe -->
        <div v-if="!selectedLog">
            <div v-if="loadingRuns" class="flex justify-center py-8">
                <PropertyIcon name="IconLoader2" class="size-6 animate-spin text-text-muted" aria-hidden="true"/>
                <span class="sr-only">{{ $t('Loading...') }}</span>
            </div>
            <EmptyState
                v-else-if="!runs || runs.data.length === 0"
                icon="IconHistory"
                :title="$t('No entries available')"
            />
            <div v-else>
                <div class="overflow-x-auto rounded-lg border border-border-subtle">
                    <table class="min-w-full text-sm">
                        <thead class="bg-surface-sunken">
                            <tr class="text-left text-xs font-medium text-text-muted border-b border-border-subtle">
                                <th class="py-2 px-3">{{ $t('Date') }}</th>
                                <th class="py-2 px-3">{{ $t('Type') }}</th>
                                <th class="py-2 px-3">{{ $t('Source') }}</th>
                                <th class="py-2 px-3">{{ $t('User') }}</th>
                                <th class="py-2 px-3 text-right">{{ $t('Created') }}</th>
                                <th class="py-2 px-3 text-right">{{ $t('Updated') }}</th>
                                <th class="py-2 px-3 text-right">{{ $t('Deleted') }}</th>
                                <th class="py-2 px-3"><span class="sr-only">{{ $t('Actions') }}</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-surface">
                            <tr v-for="log in runs.data" :key="log.id"
                                class="border-b border-border-subtle text-text last:border-b-0 hover:bg-surface-hover cursor-pointer"
                                @click="openRun(log)">
                                <td class="py-2 px-3 whitespace-nowrap">{{ formatDateTime(log.created_at) }}</td>
                                <td class="py-2 px-3">
                                    <span :class="[
                                        'inline-block rounded-full px-2 py-0.5 text-xs',
                                        log.type === 'delete' ? 'bg-danger-surface text-danger' : 'bg-success-surface text-success'
                                    ]">{{ translateType(log.type) }}</span>
                                </td>
                                <td class="py-2 px-3">{{ translateSource(log.source) }}</td>
                                <td class="py-2 px-3 whitespace-nowrap">{{ log.user?.full_name ?? '–' }}</td>
                                <td class="py-2 px-3 text-right tabular-nums text-success">+{{ log.created_count }}</td>
                                <td class="py-2 px-3 text-right tabular-nums text-warning">~{{ log.updated_count }}</td>
                                <td class="py-2 px-3 text-right tabular-nums text-danger">−{{ log.deleted_count }}</td>
                                <td class="py-2 px-3 text-right whitespace-nowrap" @click.stop>
                                    <BaseUIButton
                                        label="Download CSV"
                                        variant="ghost"
                                        size="sm"
                                        icon="IconDownload"
                                        @click="downloadCsv(log)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4" v-if="runs.links">
                    <BasePaginator :entities="runs" property-name="logs" :emit-update-entities-per-page="true"
                                   @updatePage="(page, perPage) => fetchRuns(page, perPage)"
                                   @updateEntitiesPerPage="(perPage) => fetchRuns(1, perPage)"/>
                </div>
            </div>
        </div>

        <!-- Einträge eines einzelnen Laufs -->
        <div v-else>
            <div v-if="loadingEntries" class="flex justify-center py-8">
                <PropertyIcon name="IconLoader2" class="size-6 animate-spin text-text-muted" aria-hidden="true"/>
                <span class="sr-only">{{ $t('Loading...') }}</span>
            </div>
            <EmptyState
                v-else-if="!entries || entries.data.length === 0"
                icon="IconHistory"
                :title="$t('No entries available')"
            />
            <div v-else>
                <div class="overflow-x-auto rounded-lg border border-border-subtle">
                    <table class="min-w-full text-sm">
                        <thead class="bg-surface-sunken">
                            <tr class="text-left text-xs font-medium text-text-muted border-b border-border-subtle">
                                <th class="py-2 px-3">{{ $t('Action') }}</th>
                                <th class="py-2 px-3">{{ $t('Assignment') }}</th>
                                <th class="py-2 px-3">{{ $t('Sage ID') }}</th>
                                <th class="py-2 px-3">{{ $t('KTR') }}</th>
                                <th class="py-2 px-3">{{ $t('Debit account') }}</th>
                                <th class="py-2 px-3">{{ $t('Booking date') }}</th>
                                <th class="py-2 px-3 text-right">{{ $t('Amount') }}</th>
                                <th class="py-2 px-3">{{ $t('Booking text') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-surface">
                            <tr v-for="entry in entries.data" :key="entry.id" class="border-b border-border-subtle text-text last:border-b-0">
                                <td class="py-2 px-3">
                                    <span :class="[
                                        'inline-block rounded-full px-2 py-0.5 text-xs',
                                        entry.action === 'deleted' ? 'bg-danger-surface text-danger'
                                            : entry.action === 'updated' ? 'bg-warning-surface text-warning'
                                            : 'bg-success-surface text-success'
                                    ]">{{ translateAction(entry.action) }}</span>
                                </td>
                                <td class="py-2 px-3">{{ translateTarget(entry.target_type) }}</td>
                                <td class="py-2 px-3">{{ entry.sage_id }}</td>
                                <td class="py-2 px-3">{{ entry.kst_traeger }}</td>
                                <td class="py-2 px-3">{{ entry.kto_soll }}</td>
                                <td class="py-2 px-3 whitespace-nowrap">{{ entry.buchungsdatum }}</td>
                                <td class="py-2 px-3 text-right whitespace-nowrap tabular-nums">{{ entry.buchungsbetrag }}</td>
                                <td class="py-2 px-3">{{ entry.buchungstext }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4" v-if="entries.links">
                    <BasePaginator :entities="entries" property-name="entries" :emit-update-entities-per-page="true"
                                   @updatePage="(page, perPage) => fetchEntries(page, perPage)"
                                   @updateEntitiesPerPage="(perPage) => fetchEntries(1, perPage)"/>
                </div>
            </div>
        </div>

        <template #footer>
            <template v-if="selectedLog">
                <BaseUIButton
                    label="Back to overview"
                    variant="secondary"
                    icon="IconArrowLeft"
                    @click="backToRuns"
                />
                <BaseUIButton
                    label="Download CSV"
                    variant="primary"
                    icon="IconDownload"
                    @click="downloadCsv(selectedLog)"
                />
            </template>
            <BaseUIButton
                v-else
                label="Close"
                variant="secondary"
                hide-icon
                @click="$emit('closed')"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script>
import { defineComponent } from "vue";
import axios from "axios";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import EmptyState from "@/Artwork/Feedback/EmptyState.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";

export default defineComponent({
    name: "SageBookingLogModal",
    components: { ArtworkBaseModal, BaseUIButton, EmptyState, PropertyIcon, BasePaginator },
    emits: ['closed'],
    data() {
        return {
            loadingRuns: false,
            loadingEntries: false,
            runs: null,
            selectedLog: null,
            entries: null,
        };
    },
    mounted() {
        this.fetchRuns(1, 15);
    },
    methods: {
        fetchRuns(page = 1, perPage = 15) {
            this.loadingRuns = true;
            axios.get(route('tool.interfaces.sage.logs'), { params: { page, perPage } })
                .then(res => { this.runs = res.data.logs; })
                .finally(() => { this.loadingRuns = false; });
        },
        openRun(log) {
            this.selectedLog = log;
            this.entries = null;
            this.fetchEntries(1, 25);
        },
        fetchEntries(page = 1, perPage = 25) {
            if (!this.selectedLog) {
                return;
            }
            const logId = this.selectedLog.id;
            this.loadingEntries = true;
            axios.get(route('tool.interfaces.sage.logs.entries', { sageBookingLog: logId }),
                { params: { page, perPage } })
                .then(res => {
                    // verspätete Antwort eines zuvor geöffneten Laufs verwerfen
                    if (this.selectedLog?.id === logId) {
                        this.entries = res.data.entries;
                    }
                })
                .finally(() => { this.loadingEntries = false; });
        },
        backToRuns() {
            this.selectedLog = null;
            this.entries = null;
        },
        downloadCsv(log) {
            const link = document.createElement('a');
            link.href = route('tool.interfaces.sage.logs.export', { sageBookingLog: log.id });
            link.download = 'sage-log-' + log.id + '.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        formatDateTime(value) {
            if (!value) return '–';
            const d = new Date(value);
            return d.toLocaleString();
        },
        formatRunTitle(log) {
            return this.translateType(log.type) + ' · ' + this.translateSource(log.source) + ' · ' + this.formatDateTime(log.created_at);
        },
        translateType(type) {
            return type === 'delete' ? this.$t('Delete') : this.$t('Import');
        },
        translateSource(source) {
            const map = {
                daily_import: this.$t('Daily import'),
                full_import: this.$t('Full import'),
                specific_day_import: this.$t('Specific booking import'),
                delete_bookings: this.$t('Delete bookings'),
            };
            return map[source] ?? (source ?? '–');
        },
        translateAction(action) {
            const map = {
                created: this.$t('Created'),
                updated: this.$t('Updated'),
                deleted: this.$t('Deleted'),
            };
            return map[action] ?? action;
        },
        translateTarget(target) {
            const map = {
                assigned: this.$t('Assigned'),
                not_assigned: this.$t('Not assigned'),
            };
            return map[target] ?? target;
        },
    }
});
</script>
