<template>
    <ShiftSettingsHeader
        :title="$t('Open violations')"
        :description="$t('Overview of all open shift rule violations.')"
    >
        <div class="card white p-5">
            <div v-if="violations.length === 0" class="text-center py-10 text-gray-400 text-sm">
                {{ $t('No open violations found.') }}
            </div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ $t('Employee') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ $t('Rule') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ $t('Date') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ $t('Severity') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ $t('Details') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ $t('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="violation in violations" :key="violation.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ violation.user_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-block h-3 w-3 rounded-full shrink-0"
                                        :style="{ backgroundColor: violation.warning_color || '#ff0000' }"
                                    ></span>
                                    {{ violation.rule_name }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(violation.violation_date) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span
                                    :class="violation.severity === 'error' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800'"
                                    class="inline-flex px-2 py-0.5 text-[11px] font-semibold rounded-full"
                                >
                                    {{ violation.severity === 'error' ? $t('Error') : $t('Warning') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
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
    </ShiftSettingsHeader>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import ShiftSettingsHeader from "@/Pages/Settings/Components/ShiftSettingsHeader.vue";
import ViolationEditModal from "@/Pages/Shifts/Components/ViolationEditModal.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";

const props = defineProps({
    violations: { type: Array, default: () => [] },
})

const selectedViolation = ref(null)

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
    router.post(route('shift-rule-violations.ignore', { violation: violation.id }), {}, {
        preserveScroll: true,
    })
}

function onViolationUpdated() {
    selectedViolation.value = null
    router.reload()
}
</script>
