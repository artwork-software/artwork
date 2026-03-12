<template>
    <UserEditHeader :user_to_edit="userToEdit">
        <div class="space-y-8">
            <div>
                <h2 class="text-lg font-semibold mb-1">{{ $t('Substitute days off') }}</h2>
                <p class="text-sm text-gray-600">{{ $t('Overview of substitute days off for this user.') }}</p>
            </div>

            <!-- Open compensation days -->
            <section>
                <h3 class="text-sm font-semibold text-zinc-700 mb-3 flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-blue-500"></span>
                    {{ $t('Open compensation days') }}
                    <span class="text-xs font-normal text-zinc-400">({{ openCompensations.length }})</span>
                    <BaseUIButton
                        :label="$t('Add compensation day')"
                        is-add-button
                        is-small
                        class="ml-auto"
                        @click="showCreateForm = true"
                    />
                </h3>

                <!-- Manual create form -->
                <div v-if="showCreateForm" class="mb-4 rounded-lg border border-blue-200 bg-blue-50/50 p-4 space-y-3">
                    <h4 class="text-xs font-semibold text-zinc-700">{{ $t('Add compensation day') }}</h4>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-medium text-zinc-500 mb-1">{{ $t('Value') }}</label>
                            <select
                                v-model="newCompDay.value"
                                class="w-full rounded-md border border-zinc-300 bg-white px-2 py-1.5 text-xs focus:border-blue-400 focus:ring-1 focus:ring-blue-400"
                            >
                                <option value="1.0">{{ $t('Full day (1.0)') }}</option>
                                <option value="0.5">{{ $t('Half day (0.5)') }}</option>
                            </select>
                        </div>
                        <div>
                            <BaseInput
                                type="date"
                                id="new_comp_deadline"
                                v-model="newCompDay.deadline"
                                :label="$t('Deadline')"
                            />
                        </div>
                        <div>
                            <BaseInput
                                id="new_comp_reason"
                                v-model="newCompDay.reason"
                                :label="$t('Reason')"
                            />
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <BaseUIButton
                            :label="$t('Save')"
                            is-add-button
                            is-small
                            @click="createManualCompensationDay"
                        />
                        <BaseUIButton
                            :label="$t('Cancel')"
                            is-cancel-button
                            is-small
                            @click="showCreateForm = false"
                        />
                    </div>
                </div>

                <div v-if="openCompensations.length" class="overflow-hidden rounded-lg border border-zinc-200">
                    <table class="min-w-full text-xs">
                        <thead class="bg-zinc-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Value') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Deadline') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Rule') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Reason') }}</th>
                                <th class="px-3 py-2 text-right font-medium text-zinc-500">{{ $t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            <tr
                                v-for="dayOff in openCompensations"
                                :key="dayOff.id"
                                class="hover:bg-zinc-50/50"
                                :class="isOverdue(dayOff) ? 'bg-red-50/50' : ''"
                            >
                                <td class="px-3 py-2.5 font-medium text-zinc-900">
                                    <span
                                        class="inline-flex items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                                        :class="dayOff.value >= 1.0 ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'"
                                    >
                                        {{ dayOff.value >= 1.0 ? $t('Full day (1.0)') : $t('Half day (0.5)') }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5" :class="isOverdue(dayOff) ? 'text-red-600 font-medium' : 'text-zinc-700'">
                                    {{ formatDate(dayOff.deadline) }}
                                    <span v-if="isOverdue(dayOff)" class="ml-1 text-[10px] text-red-500 font-medium">
                                        ({{ $t('Deadline expired') }})
                                    </span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            v-if="dayOff.violation?.shift_rule"
                                            class="inline-block h-2 w-2 rounded-full"
                                            :style="{ backgroundColor: dayOff.violation.shift_rule.warning_color || '#ff0000' }"
                                        ></span>
                                        {{ dayOff.violation?.shift_rule?.name || $t('Manual') }}
                                    </div>
                                </td>
                                <td class="px-3 py-2.5 text-zinc-600 max-w-[200px] truncate">
                                    {{ dayOff.reason || '-' }}
                                </td>
                                <td class="px-3 py-2.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <BaseUIButton
                                            :label="$t('Grant compensation day')"
                                            is-add-button
                                            is-small
                                            @click="openGrantModal(dayOff)"
                                        />
                                        <BaseUIButton
                                            :label="$t('Delete')"
                                            is-delete-button
                                            is-small
                                            @click="openDeleteModal(dayOff)"
                                        />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-xs text-zinc-500 italic py-3">
                    {{ $t('No open compensation days.') }}
                </div>
            </section>

            <!-- Granted compensation days -->
            <section>
                <h3 class="text-sm font-semibold text-zinc-700 mb-3 flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                    {{ $t('Granted compensation days') }}
                    <span class="text-xs font-normal text-zinc-400">({{ grantedCompensations.length }})</span>
                </h3>

                <div v-if="grantedCompensations.length" class="overflow-hidden rounded-lg border border-zinc-200">
                    <table class="min-w-full text-xs">
                        <thead class="bg-zinc-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Value') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Granted on') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Granted by') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Rule') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Reason') }}</th>
                                <th class="px-3 py-2 text-right font-medium text-zinc-500">{{ $t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            <tr
                                v-for="dayOff in grantedCompensations"
                                :key="dayOff.id"
                                class="hover:bg-zinc-50/50"
                            >
                                <td class="px-3 py-2.5 font-medium text-zinc-900">
                                    <span
                                        class="inline-flex items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                                        :class="dayOff.value >= 1.0 ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'"
                                    >
                                        {{ dayOff.value >= 1.0 ? $t('Full day (1.0)') : $t('Half day (0.5)') }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-zinc-700">{{ formatDate(dayOff.granted_date) }}</td>
                                <td class="px-3 py-2.5 text-zinc-600">
                                    <template v-if="dayOff.granted_by_user">
                                        {{ dayOff.granted_by_user.first_name }} {{ dayOff.granted_by_user.last_name }}
                                    </template>
                                    <template v-else>-</template>
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            v-if="dayOff.violation?.shift_rule"
                                            class="inline-block h-2 w-2 rounded-full"
                                            :style="{ backgroundColor: dayOff.violation.shift_rule.warning_color || '#ff0000' }"
                                        ></span>
                                        {{ dayOff.violation?.shift_rule?.name || $t('Manual') }}
                                    </div>
                                </td>
                                <td class="px-3 py-2.5 text-zinc-600 max-w-[200px] truncate">
                                    {{ dayOff.reason || '-' }}
                                </td>
                                <td class="px-3 py-2.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <BaseUIButton
                                            :label="$t('Revoke')"
                                            is-small
                                            @click="revokeCompensationDay(dayOff.id)"
                                        />
                                        <BaseUIButton
                                            :label="$t('Delete')"
                                            is-delete-button
                                            is-small
                                            @click="openDeleteModal(dayOff)"
                                        />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-xs text-zinc-500 italic py-3">
                    {{ $t('No granted compensation days.') }}
                </div>
            </section>

            <!-- Unprocessed violations -->
            <section>
                <h3 class="text-sm font-semibold text-zinc-700 mb-3 flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-amber-500"></span>
                    {{ $t('Unprocessed violations') }}
                    <span class="text-xs font-normal text-zinc-400">({{ unprocessedViolations.length }})</span>
                </h3>

                <div v-if="unprocessedViolations.length" class="overflow-hidden rounded-lg border border-zinc-200">
                    <table class="min-w-full text-xs">
                        <thead class="bg-zinc-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Date') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Rule') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Severity') }}</th>
                                <th class="px-3 py-2 text-right font-medium text-zinc-500">{{ $t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            <tr
                                v-for="violation in unprocessedViolations"
                                :key="violation.id"
                                class="hover:bg-zinc-50/50"
                            >
                                <td class="px-3 py-2.5 text-zinc-700">{{ formatDate(violation.violation_date) }}</td>
                                <td class="px-3 py-2.5">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="inline-block h-2 w-2 rounded-full"
                                            :style="{ backgroundColor: violation.shift_rule?.warning_color || '#ff0000' }"
                                        ></span>
                                        {{ violation.shift_rule?.name }}
                                    </div>
                                </td>
                                <td class="px-3 py-2.5">
                                    <span
                                        :class="violation.severity === 'error' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'"
                                        class="inline-flex px-1.5 py-0.5 text-[10px] font-medium rounded-full"
                                    >
                                        {{ violation.severity === 'error' ? $t('Error') : $t('Warning') }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-right">
                                    <BaseUIButton
                                        :label="$t('Edit')"
                                        is-small
                                        @click="openViolationEditModal(violation)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-xs text-zinc-500 italic py-3">
                    {{ $t('No unprocessed violations.') }}
                </div>
            </section>
        </div>

        <ViolationEditModal
            v-if="showViolationEditModal && selectedViolation"
            :violation="selectedViolation"
            :compensation-period="props.compensationPeriod"
            @close="showViolationEditModal = false"
            @updated="handleViolationUpdated"
        />

        <GrantCompensationDayModal
            v-if="showGrantModal"
            :user-id="userToEdit.id"
            :user-name="userToEdit.first_name + ' ' + userToEdit.last_name"
            @close="showGrantModal = false"
            @granted="handleGranted"
        />

        <DeleteCompensationDayModal
            v-if="showDeleteModal && selectedCompDayToDelete"
            :compensation-day="selectedCompDayToDelete"
            @close="showDeleteModal = false; selectedCompDayToDelete = null"
            @deleted="handleDeleted"
        />
    </UserEditHeader>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import UserEditHeader from '@/Pages/Users/Components/UserEditHeader.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import ViolationEditModal from '@/Pages/Shifts/Components/ViolationEditModal.vue';
import GrantCompensationDayModal from '@/Pages/Shifts/Components/GrantCompensationDayModal.vue';
import DeleteCompensationDayModal from '@/Pages/Shifts/Components/DeleteCompensationDayModal.vue';

const props = defineProps({
    userToEdit: { type: Object, required: true },
    openCompensations: { type: Array, default: () => [] },
    grantedCompensations: { type: Array, default: () => [] },
    unprocessedViolations: { type: Array, default: () => [] },
    compensationPeriod: { type: Number, default: 0 },
});

const showViolationEditModal = ref(false);
const selectedViolation = ref(null);
const showGrantModal = ref(false);
const showCreateForm = ref(false);
const showDeleteModal = ref(false);
const selectedCompDayToDelete = ref(null);

const newCompDay = ref({
    value: '1.0',
    deadline: '',
    reason: '',
});

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('de-DE');
}

function isOverdue(dayOff) {
    if (!dayOff.deadline) return false;
    return new Date(dayOff.deadline) < new Date();
}

function openGrantModal() {
    showGrantModal.value = true;
}

function openViolationEditModal(violation) {
    selectedViolation.value = violation;
    showViolationEditModal.value = true;
}

function handleViolationUpdated() {
    showViolationEditModal.value = false;
    selectedViolation.value = null;
    router.reload();
}

function handleGranted() {
    showGrantModal.value = false;
    router.reload();
}

function revokeCompensationDay(id) {
    router.post(route('compensation-day-offs.revoke', { compensationDayOff: id }), {}, {
        preserveScroll: true,
        onSuccess: () => router.reload(),
    });
}

function openDeleteModal(compDay) {
    selectedCompDayToDelete.value = compDay;
    showDeleteModal.value = true;
}

function handleDeleted() {
    showDeleteModal.value = false;
    selectedCompDayToDelete.value = null;
    router.reload();
}

function createManualCompensationDay() {
    router.post(route('compensation-day-offs.store-manual'), {
        user_id: props.userToEdit.id,
        value: newCompDay.value.value,
        deadline: newCompDay.value.deadline,
        reason: newCompDay.value.reason,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showCreateForm.value = false;
            newCompDay.value = { value: '1.0', deadline: '', reason: '' };
            router.reload();
        },
    });
}
</script>
