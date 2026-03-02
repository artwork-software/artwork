<template>
    <UserEditHeader :user_to_edit="userToEdit">
        <div class="space-y-8">
            <div>
                <h2 class="text-lg font-semibold mb-1">{{ $t('Substitute days off') }}</h2>
                <p class="text-sm text-gray-600">{{ $t('Overview of substitute days off for this user.') }}</p>
            </div>

            <!-- Open compensations -->
            <section>
                <h3 class="text-sm font-semibold text-zinc-700 mb-3 flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-blue-500"></span>
                    {{ $t('Open compensations') }}
                    <span class="text-xs font-normal text-zinc-400">({{ openCompensations.length }})</span>
                </h3>

                <div v-if="openCompensations.length" class="overflow-hidden rounded-lg border border-zinc-200">
                    <table class="min-w-full text-xs">
                        <thead class="bg-zinc-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Date') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Rule') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Compensation days') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Deadline') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Reason') }}</th>
                                <th class="px-3 py-2 text-right font-medium text-zinc-500">{{ $t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            <tr
                                v-for="violation in openCompensations"
                                :key="violation.id"
                                class="hover:bg-zinc-50/50"
                                :class="isOverdue(violation) ? 'bg-red-50/50' : ''"
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
                                <td class="px-3 py-2.5 font-medium text-zinc-900">{{ violation.compensation_days }}</td>
                                <td class="px-3 py-2.5" :class="isOverdue(violation) ? 'text-red-600 font-medium' : 'text-zinc-700'">
                                    {{ formatDate(violation.compensation_deadline) }}
                                    <span v-if="isOverdue(violation)" class="ml-1 text-[10px] text-red-500 font-medium">
                                        ({{ $t('overdue') }})
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-zinc-600 max-w-[200px] truncate">
                                    {{ violation.compensation_reason || '-' }}
                                </td>
                                <td class="px-3 py-2.5 text-right">
                                    <BaseUIButton
                                        :label="$t('Grant compensation')"
                                        is-add-button
                                        is-small
                                        @click="grantCompensation(violation)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-xs text-zinc-500 italic py-3">
                    {{ $t('No open compensations.') }}
                </div>
            </section>

            <!-- Granted compensations -->
            <section>
                <h3 class="text-sm font-semibold text-zinc-700 mb-3 flex items-center gap-2">
                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                    {{ $t('Granted compensations') }}
                    <span class="text-xs font-normal text-zinc-400">({{ grantedCompensations.length }})</span>
                </h3>

                <div v-if="grantedCompensations.length" class="overflow-hidden rounded-lg border border-zinc-200">
                    <table class="min-w-full text-xs">
                        <thead class="bg-zinc-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Date') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Rule') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Compensation days') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Granted on') }}</th>
                                <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Granted by') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            <tr
                                v-for="violation in grantedCompensations"
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
                                <td class="px-3 py-2.5 font-medium text-zinc-900">{{ violation.compensation_days }}</td>
                                <td class="px-3 py-2.5 text-zinc-700">{{ formatDate(violation.compensation_granted_at) }}</td>
                                <td class="px-3 py-2.5 text-zinc-600">
                                    <template v-if="violation.granted_by_user">
                                        {{ violation.granted_by_user.first_name }} {{ violation.granted_by_user.last_name }}
                                    </template>
                                    <template v-else>-</template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-xs text-zinc-500 italic py-3">
                    {{ $t('No granted compensations.') }}
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
            @close="showViolationEditModal = false"
            @updated="handleViolationUpdated"
        />
    </UserEditHeader>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import UserEditHeader from '@/Pages/Users/Components/UserEditHeader.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import ViolationEditModal from '@/Pages/Shifts/Components/ViolationEditModal.vue';

defineProps({
    userToEdit: { type: Object, required: true },
    openCompensations: { type: Array, default: () => [] },
    grantedCompensations: { type: Array, default: () => [] },
    unprocessedViolations: { type: Array, default: () => [] },
});

const showViolationEditModal = ref(false);
const selectedViolation = ref(null);

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('de-DE');
}

function isOverdue(violation) {
    if (!violation.compensation_deadline) return false;
    return new Date(violation.compensation_deadline) < new Date();
}

function grantCompensation(violation) {
    router.post(route('shift-rule-violations.grant', { violation: violation.id }), {}, {
        preserveScroll: true,
    });
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
</script>
