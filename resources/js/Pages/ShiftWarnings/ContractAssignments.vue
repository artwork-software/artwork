<template>
    <ShiftSettingsHeader
        :title="$t('Shift warnings - rules')"
        :description="$t('Assign rules per contract: every person with this contract is checked against the assigned rules.')"
    >
        <RuleViewSwitch current="contracts" class="mb-4" />

        <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5">
            <div v-if="!contracts || contracts.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                <IconFileDescription class="h-10 w-10 text-text-subtle mb-3" stroke-width="1.5" />
                <p class="text-sm font-medium text-text">{{ $t('No contracts yet') }}</p>
                <p class="mt-1 text-xs text-text-subtle max-w-md">
                    {{ $t('Create contracts in the \'Contracts\' tab first — rules are assigned per contract.') }}
                </p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border-subtle">
                    <thead class="bg-surface-sunken">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Contract name') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Active for') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Assigned rules') }}
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface divide-y divide-border-subtle">
                        <tr v-for="contract in contracts" :key="contract.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-text align-top">
                                {{ contract.name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-text-subtle align-top">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div
                                        v-for="assign in visibleAssigns(contract)"
                                        :key="assign.user?.id ?? assign.id"
                                        class="flex items-center"
                                    >
                                        <img
                                            v-if="assign.user?.profile_photo_url"
                                            :src="assign.user.profile_photo_url"
                                            :alt="userName(assign.user)"
                                            class="w-7 h-7 rounded-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="w-7 h-7 rounded-full bg-surface-sunken text-text-subtle flex items-center justify-center text-[10px]"
                                        >
                                            {{ getUserInitials(assign.user) }}
                                        </div>
                                        <span class="ml-1 text-xs">{{ assign.user?.first_name }}</span>
                                    </div>
                                    <button
                                        v-if="(contract.user_contract_assigns?.length ?? 0) > 3"
                                        type="button"
                                        class="text-accent-600 hover:text-accent-700 text-xs"
                                        @click="toggleExpanded(contract.id)"
                                    >
                                        <template v-if="expandedContracts.includes(contract.id)">{{ $t('Show less') }}</template>
                                        <template v-else>+{{ contract.user_contract_assigns.length - 3 }} {{ $t('more') }}</template>
                                    </button>
                                    <span v-if="!(contract.user_contract_assigns?.length)" class="text-xs text-text-subtle">
                                        {{ $t('Nobody') }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-text-subtle align-top">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="rule in contract.shift_rules ?? []"
                                        :key="rule.id"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-accent-50 text-accent-700 border border-accent-200"
                                    >
                                        <span class="inline-block h-2 w-2 rounded-full" :style="{ backgroundColor: rule.warning_color }"></span>
                                        {{ rule.name }}
                                    </span>
                                    <span v-if="!(contract.shift_rules?.length)" class="text-text-subtle text-xs">
                                        {{ $t('No rules assigned') }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium align-top">
                                <div class="flex justify-end">
                                    <ToolTipComponent
                                        direction="left"
                                        :tooltip-text="$t('Edit rule assignment')"
                                        icon="IconEdit"
                                        icon-size="h-5 w-5"
                                        classes-button="ui-button"
                                        @click="editAssignments(contract)"
                                    />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Zuordnungs-Modal -->
        <ArtworkBaseModal
            v-if="showAssignmentModal && selectedContract"
            :title="$t('Rule assignment for {contract}', { contract: selectedContract.name })"
            :description="$t('Select the rules that apply to everyone with this contract.')"
            @close="closeAssignmentModal"
        >
            <div class="p-5 space-y-5 text-sm">
                <div v-if="selectedContract.user_contract_assigns?.length">
                    <h3 class="text-xs font-medium text-text-subtle mb-2">{{ $t('Active for') }}</h3>
                    <div class="flex flex-wrap gap-2">
                        <div
                            v-for="assign in selectedContract.user_contract_assigns"
                            :key="assign.user?.id ?? assign.id"
                            class="flex items-center bg-surface-sunken rounded-lg px-2.5 py-1"
                        >
                            <img
                                v-if="assign.user?.profile_photo_url"
                                :src="assign.user.profile_photo_url"
                                :alt="userName(assign.user)"
                                class="w-5 h-5 rounded-full mr-2 object-cover"
                            />
                            <div
                                v-else
                                class="w-5 h-5 rounded-full bg-border text-text-subtle flex items-center justify-center text-[9px] mr-2"
                            >
                                {{ getUserInitials(assign.user) }}
                            </div>
                            <span class="text-xs text-text">{{ userName(assign.user) }}</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <h3 class="text-xs font-medium text-text-subtle">{{ $t('Available rules') }}</h3>
                    <p v-if="!rules || rules.length === 0" class="text-xs text-text-subtle">
                        {{ $t('No rules yet') }} –
                        <Link :href="route('shift-rules.index')" class="text-accent-600 hover:text-accent-700 underline">{{ $t('Create rule') }}</Link>
                    </p>
                    <div v-else class="grid grid-cols-1 gap-2 max-h-72 overflow-y-auto pr-1">
                        <div
                            v-for="rule in rules"
                            :key="rule.id"
                            class="rounded-lg border px-3 py-2 transition"
                            :class="assignmentForm.rule_ids.includes(rule.id) ? 'border-accent-600 bg-accent-50' : 'border-border-subtle hover:bg-surface-sunken'"
                        >
                            <BaseCheckbox
                                :id="`rule_${rule.id}`"
                                :model-value="assignmentForm.rule_ids.includes(rule.id)"
                                :label="rule.name"
                                :description="`${formatTriggerType(rule.trigger_type)} – ${$t('Value')}: ${formatRuleValue(rule, $t)}`"
                                @update:model-value="toggleRule(rule.id, $event)"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex justify-between pt-2 border-t border-border-subtle">
                    <BaseUIButton :label="$t('Cancel')" is-cancel-button @click="closeAssignmentModal" />
                    <BaseUIButton :label="$t('Save')" is-add-button :disabled="assignmentForm.processing" @click="saveAssignments" />
                </div>
            </div>
        </ArtworkBaseModal>

        <NotificationToast
            v-model:show="toastVisible"
            :title="$t('Rule assignments successfully updated')"
        />
    </ShiftSettingsHeader>
</template>

<script setup>
import { ref, defineAsyncComponent } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { IconFileDescription } from '@tabler/icons-vue'
import ShiftSettingsHeader from '@/Pages/Settings/Components/ShiftSettingsHeader.vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import RuleViewSwitch from '@/Pages/ShiftWarnings/Components/RuleViewSwitch.vue'
import { formatRuleValue, ruleTypeLabelKey } from '@/Pages/ShiftWarnings/ruleTypes.js'

const NotificationToast = defineAsyncComponent({
    loader: () => import('@/Artwork/Feedback/NotificationToast.vue'),
})

const props = defineProps({
    contracts: Array,
    rules: Array,
})

const { t: $t } = useI18n()

const showAssignmentModal = ref(false)
const selectedContract = ref(null)
const expandedContracts = ref([])
const toastVisible = ref(false)

const assignmentForm = useForm({
    rule_ids: [],
})

function formatTriggerType(type) {
    const key = ruleTypeLabelKey(type)
    return key ? $t(key) : type
}

function userName(user) {
    if (!user) return ''
    return user.full_name || `${user.first_name ?? ''} ${user.last_name ?? ''}`.trim()
}

function getUserInitials(user) {
    return (user?.first_name?.charAt(0) || '') + (user?.last_name?.charAt(0) || '')
}

function visibleAssigns(contract) {
    const assigns = contract.user_contract_assigns ?? []
    return expandedContracts.value.includes(contract.id) ? assigns : assigns.slice(0, 3)
}

function toggleExpanded(contractId) {
    if (expandedContracts.value.includes(contractId)) {
        expandedContracts.value = expandedContracts.value.filter((id) => id !== contractId)
    } else {
        expandedContracts.value = [...expandedContracts.value, contractId]
    }
}

function editAssignments(contract) {
    selectedContract.value = contract
    assignmentForm.rule_ids = (contract.shift_rules ?? []).map((rule) => rule.id)
    showAssignmentModal.value = true
}

function toggleRule(ruleId, checked) {
    if (checked) {
        if (!assignmentForm.rule_ids.includes(ruleId)) {
            assignmentForm.rule_ids = [...assignmentForm.rule_ids, ruleId]
        }
    } else {
        assignmentForm.rule_ids = assignmentForm.rule_ids.filter((id) => id !== ruleId)
    }
}

function closeAssignmentModal() {
    showAssignmentModal.value = false
    selectedContract.value = null
    assignmentForm.rule_ids = []
}

function saveAssignments() {
    if (!selectedContract.value) return
    assignmentForm.put(route('shift-rules.contracts.assignments.update', selectedContract.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeAssignmentModal()
            toastVisible.value = true
        },
    })
}
</script>
