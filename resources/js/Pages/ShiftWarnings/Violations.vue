<template>
    <ShiftSettingsHeader
        :title="$t('Open violations')"
        :description="$t('Overview of all open shift rule violations.')"
    >
        <SettingsGuideBanner
            storage-key="settings-guide.shift.violations"
            title="How to handle open violations"
            class="mb-6"
            :paragraphs="[
                'This list collects all violations detected by the shift rule check. You have three ways to handle them:',
                '\'Resolve\' marks a violation as done without further consequences.',
                '\'Ignore\' discards it with a documented reason that remains visible on the violation.',
                '\'Edit\' lets you set substitute days off that are credited directly to the person\'s hour account.'
            ]"
        />

        <div class="rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5">
            <div v-if="violations.length === 0" class="text-center py-10 text-text-subtle text-sm">
                {{ $t('No open violations found.') }}
            </div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border-subtle">
                    <thead class="bg-surface-sunken">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Employee') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Rule') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Date') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Severity') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Details') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-text-subtle uppercase tracking-wider">
                                {{ $t('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-border-subtle">
                        <tr v-for="violation in violations" :key="violation.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-text">
                                {{ violation.user_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-subtle">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-block h-3 w-3 rounded-full shrink-0"
                                        :style="{ backgroundColor: violation.warning_color || '#ff0000' }"
                                    ></span>
                                    {{ violation.rule_name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-subtle">
                                {{ formatDate(violation.violation_date) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span
                                    :class="violation.severity === 'error' ? 'bg-danger-surface text-danger' : 'bg-warning-surface text-warning'"
                                    class="inline-flex px-2 py-0.5 text-[11px] font-semibold rounded-full"
                                >
                                    {{ violation.severity === 'error' ? $t('Error') : $t('Warning') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-text-subtle max-w-xs truncate">
                                {{ violation.message }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <BaseMenu white-menu-background has-no-offset>
                                    <BaseMenuItem white-menu-background :title="$t('Edit')" @click="openEditModal(violation)" />
                                    <BaseMenuItem white-menu-background :title="$t('Resolve')" @click="resolveViolation(violation)" />
                                    <BaseMenuItem white-menu-background :title="$t('Ignore')" @click="ignoreViolation(violation)" />
                                </BaseMenu>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <ViolationEditModal
            v-if="selectedViolation"
            :violation="selectedViolation"
            @close="selectedViolation = null"
            @updated="onViolationUpdated"
        />

        <IgnoreViolationModal
            v-if="violationToIgnore"
            :violation="violationToIgnore"
            @close="violationToIgnore = null"
            @ignored="onViolationIgnored"
        />
    </ShiftSettingsHeader>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import ShiftSettingsHeader from "@/Pages/Settings/Components/ShiftSettingsHeader.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
import ViolationEditModal from "@/Pages/Shifts/Components/ViolationEditModal.vue";
import IgnoreViolationModal from "@/Pages/Shifts/Components/IgnoreViolationModal.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";

const props = defineProps({
    violations: { type: Array, default: () => [] },
})

const selectedViolation = ref(null)
const violationToIgnore = ref(null)

function formatDate(date) {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('de-DE')
}

function openEditModal(violation) {
    selectedViolation.value = violation
}

function resolveViolation(violation) {
    router.post(route('shift-rule-violations.resolve', { violation: violation.id }), {}, {
        preserveScroll: true,
    })
}

function ignoreViolation(violation) {
    violationToIgnore.value = violation
}

function onViolationUpdated() {
    selectedViolation.value = null
    router.reload()
}

function onViolationIgnored() {
    violationToIgnore.value = null
    router.reload()
}
</script>
