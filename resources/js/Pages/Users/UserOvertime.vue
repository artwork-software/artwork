<template>
    <UserEditHeader :user_to_edit="userToEdit">
        <div class="space-y-6">
        <div class="space-y-8">
            <div>
                <h2 class="text-lg font-semibold mb-1">{{ $t('Overtime') }}</h2>
                <p class="text-sm text-gray-600">
                    {{ $t('Overtime account, deadlines and payouts for this user.') }}
                <p class="text-sm text-gray-600">{{ $t('Overview of overtime accrued for this user.') }}</p>
            </div>

            <!-- Rule inactive hint -->
            <div v-if="!overtimeRuleActive" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                {{ $t('The overtime rule is not active for this user. Activate it in the employment contract tab.') }}
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-3 gap-3">
                <div class="rounded-lg border border-zinc-200 px-4 py-3">
                    <div class="text-[11px] uppercase tracking-wide text-zinc-400">{{ $t('Open') }}</div>
                    <div class="text-sm font-semibold text-zinc-800">{{ formatMinutes(overtimeStats.open_minutes) }}</div>
                </div>
                <div class="rounded-lg border border-amber-200 bg-amber-50/40 px-4 py-3">
                    <div class="text-[11px] uppercase tracking-wide text-amber-500">{{ $t('Overtime to be paid out') }}</div>
                    <div class="text-sm font-semibold text-amber-700">{{ formatMinutes(overtimeStats.payable_minutes) }}</div>
                </div>
                <div class="rounded-lg border border-zinc-200 px-4 py-3">
                    <div class="text-[11px] uppercase tracking-wide text-zinc-400">{{ $t('Paid out') }}</div>
                    <div class="text-sm font-semibold text-zinc-800">{{ formatMinutes(overtimeStats.paid_out_minutes) }}</div>
                </div>
            </div>

            <!-- Overtime days list -->
            <section>
                <h3 class="text-sm font-semibold text-zinc-700 mb-3 flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-blue-500"></span>
                    {{ $t('Days with overtime') }}
                    <span class="text-xs font-normal text-zinc-400">({{ overtime.length }})</span>
                </h3>

                <div v-if="overtime.length" class="overflow-hidden rounded-lg border border-zinc-200">
                    <table class="min-w-full text-xs">
                        <thead class="bg-zinc-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Date') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Overtime') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Remaining') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Deadline') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Status') }}</th>
                                <th class="px-3 py-2 text-right font-medium text-zinc-500">{{ $t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            <tr v-for="entry in overtime" :key="entry.id">
                                <td class="px-3 py-2.5 text-zinc-700">{{ formatDate(entry.date) }}</td>
                                <td class="px-3 py-2.5 text-zinc-700">{{ formatMinutes(entry.minutes) }}</td>
                                <td class="px-3 py-2.5 text-zinc-700">{{ formatMinutes(entry.remaining_minutes) }}</td>
                                <td class="px-3 py-2.5 text-zinc-700">{{ formatDate(entry.deadline) }}</td>
                                <td class="px-3 py-2.5">
                                    <span
                                        class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                                        :class="statusClass(entry.status)"
                                    >
                                        {{ statusLabel(entry.status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-right">
                                    <BaseUIButton
                                        v-if="entry.status === 'payable'"
                                        :label="$t('Book out')"
                                        is-add-button
                                        is-small
                                        @click="openBookOut(entry)"
                                    />
                                    <span v-else-if="entry.status === 'paid_out'" class="text-[11px] text-zinc-400">
                                        {{ $t('Paid out') }}<template v-if="entry.paid_out_by_user">:
                                            {{ entry.paid_out_by_user.first_name }} {{ entry.paid_out_by_user.last_name }}</template>
                                    </span>
                                    <span v-else class="text-zinc-300">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-zinc-400 italic">{{ $t('No overtime recorded.') }}</p>
            </section>
        </div>

        <!-- Book out modal -->
        <ArtworkBaseModal
            v-if="showBookOutModal && selectedEntry"
            :title="$t('Book out overtime')"
            description=""
            modal-size="max-w-lg"
            @close="closeBookOut"
        >
            <div class="space-y-4 text-sm">
                <p class="text-xs text-zinc-600">
                    {{ formatDate(selectedEntry.date) }} — {{ formatMinutes(selectedEntry.remaining_minutes) }}
                </p>
            </div>

            <UserOvertimePanel :user-id="userToEdit.id" :data="overtime" />
        </div>
                <div>
                    <label class="block text-[11px] font-medium text-zinc-500 mb-1">{{ $t('Reason') }}</label>
                    <BaseTextarea
                        id="overtime_payout_reason"
                        v-model="bookOutReason"
                        :label="$t('Reason')"
                        :show-label="false"
                        no-margin-top
                    />
                </div>
                <div class="flex justify-between pt-2 border-t border-zinc-100">
                    <BaseUIButton :label="$t('Cancel')" is-cancel-button @click="closeBookOut" />
                    <BaseUIButton
                        :label="$t('Book out')"
                        is-add-button
                        :disabled="bookingOut"
                        @click="submitBookOut"
                    />
                </div>
            </div>
        </ArtworkBaseModal>
    </UserEditHeader>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import UserEditHeader from '@/Pages/Users/Components/UserEditHeader.vue';
import UserOvertimePanel from '@/Pages/Shifts/Components/UserOvertimePanel.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import { useI18n } from 'vue-i18n';

defineProps({
const { t } = useI18n();

const props = defineProps({
    userToEdit: { type: Object, required: true },
    overtime: { type: Object, required: true },
    currentTab: { type: String, default: 'overtime' },
    overtime: { type: Array, default: () => [] },
    overtimeStats: { type: Object, default: () => ({ open_minutes: 0, payable_minutes: 0, paid_out_minutes: 0 }) },
    overtimePeriod: { type: Number, default: null },
    overtimeRuleActive: { type: Boolean, default: false },
});

const showBookOutModal = ref(false);
const selectedEntry = ref(null);
const bookOutReason = ref('');
const bookingOut = ref(false);

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('de-DE');
}

function formatMinutes(minutes) {
    const m = Math.abs(parseInt(minutes || 0, 10));
    const h = Math.floor(m / 60);
    const rest = m % 60;
    return `${h}h ${rest}m`;
}

function statusLabel(status) {
    const map = {
        open: t('Open'),
        compensated: t('Compensated'),
        payable: t('Overtime to be paid out'),
        paid_out: t('Paid out'),
    };
    return map[status] || status;
}

function statusClass(status) {
    const map = {
        open: 'bg-blue-100 text-blue-700',
        compensated: 'bg-green-100 text-green-700',
        payable: 'bg-amber-100 text-amber-700',
        paid_out: 'bg-zinc-100 text-zinc-600',
    };
    return map[status] || 'bg-zinc-100 text-zinc-600';
}

function openBookOut(entry) {
    selectedEntry.value = entry;
    bookOutReason.value = '';
    showBookOutModal.value = true;
}

function closeBookOut() {
    showBookOutModal.value = false;
    selectedEntry.value = null;
}

function submitBookOut() {
    if (!selectedEntry.value) return;
    bookingOut.value = true;
    router.post(
        route('overtime.book-out', { userOvertime: selectedEntry.value.id }),
        { reason: bookOutReason.value },
        {
            preserveScroll: true,
            onSuccess: () => closeBookOut(),
            onFinish: () => { bookingOut.value = false; },
        }
    );
}
</script>
