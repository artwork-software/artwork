<template>
    <BaseModal modal-size="sm:max-w-6xl" @closed="$emit('closed')">
        <ModalHeader
            :title="selectedLog ? $t('Protocol details') : $t('Sage protocol')"
            :description="selectedLog ? formatRunTitle(selectedLog) : $t('Log of imported and deleted Sage bookings')"
        />

        <!-- Runs list -->
        <div v-if="!selectedLog">
            <div v-if="loadingRuns" class="py-8 text-center xsLight">{{ $t('Loading...') }}</div>
            <div v-else-if="!runs || runs.data.length === 0" class="py-8 text-center xsLight">
                {{ $t('No entries available') }}
            </div>
            <div v-else>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left xsLight border-b border-border-subtle">
                                <th class="py-2 pr-4">{{ $t('Date') }}</th>
                                <th class="py-2 pr-4">{{ $t('Type') }}</th>
                                <th class="py-2 pr-4">{{ $t('Source') }}</th>
                                <th class="py-2 pr-4">{{ $t('User') }}</th>
                                <th class="py-2 pr-4 text-right">{{ $t('Created') }}</th>
                                <th class="py-2 pr-4 text-right">{{ $t('Updated') }}</th>
                                <th class="py-2 pr-4 text-right">{{ $t('Deleted') }}</th>
                                <th class="py-2 pr-4"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in runs.data" :key="log.id"
                                class="border-b border-border-subtle hover:bg-surface-sunken cursor-pointer"
                                @click="openRun(log)">
                                <td class="py-2 pr-4 whitespace-nowrap">{{ formatDateTime(log.created_at) }}</td>
                                <td class="py-2 pr-4">
                                    <span :class="[
                                        'inline-block rounded-full px-2 py-0.5 text-xs',
                                        log.type === 'delete' ? 'bg-danger-surface text-danger' : 'bg-success-surface text-success'
                                    ]">{{ translateType(log.type) }}</span>
                                </td>
                                <td class="py-2 pr-4">{{ translateSource(log.source) }}</td>
                                <td class="py-2 pr-4 whitespace-nowrap">{{ log.user?.full_name ?? '–' }}</td>
                                <td class="py-2 pr-4 text-right text-success">+{{ log.created_count }}</td>
                                <td class="py-2 pr-4 text-right text-warning">~{{ log.updated_count }}</td>
                                <td class="py-2 pr-4 text-right text-danger">−{{ log.deleted_count }}</td>
                                <td class="py-2 pr-4 text-right whitespace-nowrap" @click.stop>
                                    <button class="text-artwork-buttons-create hover:underline text-xs"
                                            @click="downloadCsv(log)">
                                        {{ $t('Download CSV') }}
                                    </button>
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

        <!-- Entries of a single run -->
        <div v-else>
            <button class="mb-3 text-sm text-artwork-buttons-create hover:underline" @click="backToRuns">
                &larr; {{ $t('Back to overview') }}
            </button>
            <div class="mb-3 flex items-center justify-end">
                <button class="text-artwork-buttons-create hover:underline text-sm" @click="downloadCsv(selectedLog)">
                    {{ $t('Download CSV') }}
                </button>
            </div>
            <div v-if="loadingEntries" class="py-8 text-center xsLight">{{ $t('Loading...') }}</div>
            <div v-else-if="!entries || entries.data.length === 0" class="py-8 text-center xsLight">
                {{ $t('No entries available') }}
            </div>
            <div v-else>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left xsLight border-b border-border-subtle">
                                <th class="py-2 pr-4">{{ $t('Action') }}</th>
                                <th class="py-2 pr-4">{{ $t('Assignment') }}</th>
                                <th class="py-2 pr-4">{{ $t('Sage ID') }}</th>
                                <th class="py-2 pr-4">{{ $t('KTR') }}</th>
                                <th class="py-2 pr-4">{{ $t('Debit account') }}</th>
                                <th class="py-2 pr-4">{{ $t('Booking date') }}</th>
                                <th class="py-2 pr-4 text-right">{{ $t('Amount') }}</th>
                                <th class="py-2 pr-4">{{ $t('Booking text') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="entry in entries.data" :key="entry.id" class="border-b border-border-subtle">
                                <td class="py-2 pr-4">
                                    <span :class="[
                                        'inline-block rounded-full px-2 py-0.5 text-xs',
                                        entry.action === 'deleted' ? 'bg-danger-surface text-danger'
                                            : entry.action === 'updated' ? 'bg-warning-surface text-warning'
                                            : 'bg-success-surface text-success'
                                    ]">{{ translateAction(entry.action) }}</span>
                                </td>
                                <td class="py-2 pr-4">{{ translateTarget(entry.target_type) }}</td>
                                <td class="py-2 pr-4">{{ entry.sage_id }}</td>
                                <td class="py-2 pr-4">{{ entry.kst_traeger }}</td>
                                <td class="py-2 pr-4">{{ entry.kto_soll }}</td>
                                <td class="py-2 pr-4 whitespace-nowrap">{{ entry.buchungsdatum }}</td>
                                <td class="py-2 pr-4 text-right whitespace-nowrap">{{ entry.buchungsbetrag }}</td>
                                <td class="py-2 pr-4">{{ entry.buchungstext }}</td>
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
    </BaseModal>
</template>

<script>
import { defineComponent } from "vue";
import axios from "axios";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";

export default defineComponent({
    name: "SageBookingLogModal",
    components: { BaseModal, ModalHeader, BasePaginator },
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
            if (!this.selectedLog) return;
            this.loadingEntries = true;
            axios.get(route('tool.interfaces.sage.logs.entries', { sageBookingLog: this.selectedLog.id }),
                { params: { page, perPage } })
                .then(res => { this.entries = res.data.entries; })
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
