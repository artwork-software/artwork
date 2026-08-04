<template>
    <AppLayout :title="isPlanner ? $t('Requested duty rosters') : $t('Meine Dienstplananfragen')">
        <div class="px-4 py-6 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-text">
                        {{ isPlanner ? $t('Shift plan requests') : $t('Meine Dienstplananfragen') }}
                    </h1>
                    <p class="mt-1 text-sm text-text-subtle max-w-2xl">
                        {{ isPlanner
                            ? $t('Here you can see all shift plan requests grouped by craft.')
                            : $t('Hier siehst du alle deine angefragten Dienstpläne, gruppiert nach Gewerken.')
                        }}
                    </p>
                </div>
            </div>

            <!-- Kein Craft / keine Requests -->
            <div
                v-if="!crafts || !crafts.length"
                class="rounded-2xl border border-dashed border-border-subtle bg-white p-8 text-center"
            >
                <p class="text-sm text-text-subtle">
                    {{ $t('Du hast keine Dienstplananfragen.') }}
                </p>
            </div>

            <!-- Cards pro Craft -->
            <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="craft in crafts"
                    :key="craft.id"
                    class="group flex flex-col rounded-2xl border border-border-subtle bg-white shadow-sm hover:border-accent-200 hover:shadow-md transition"
                >
                    <!-- Craft Header -->
                    <div
                        class="flex items-center justify-between px-4 py-3 border-b border-border-subtle bg-gradient-to-r from-white to-surface-sunken rounded-t-2xl"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="h-9 w-9 rounded-full flex items-center justify-center text-xs font-semibold text-white shadow-sm"
                                :style="{ backgroundColor: craft.color || '#4f46e5' }"
                            >
                                {{ craft.abbreviation }}
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-text">
                                    {{ craft.name }}
                                </h2>
                                <p class="text-xs text-text-subtle">
                                    <span v-if="craft.assignable_by_all">
                                        {{ $t('Zuweisbar von allen Planer:innen') }}
                                    </span>
                                    <span v-else>
                                        {{ $t('Eingeschränkte Zuweisung') }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-1">
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-surface-sunken text-text-muted"
                            >
                                {{ $t('Anfragen') }}: {{ craft.shift_plan_requests.length }}
                            </span>
                        </div>
                    </div>

                    <!-- Requests-Liste -->
                    <div class="flex-1">
                        <div v-if="craft.shift_plan_requests.length" class="divide-y divide-border-subtle">
                            <button
                                v-for="request in craft.shift_plan_requests"
                                :key="request.id"
                                type="button"
                                class="w-full text-left px-4 py-3 flex items-center justify-between hover:bg-accent-50 transition"
                                @click="goToRequest(request.id)"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-text">
                                                {{ $t('KW') }} {{ request.week_number }} / {{ request.year }}
                                            </span>
                                            <span
                                                :class="[ 'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium',
                                                    statusClasses(request.status)
                                                ]"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-current"></span>
                                                {{ statusLabel(request.status) }}
                                            </span>
                                        </div>
                                        <p class="mt-0.5 text-xs text-text-subtle">
                                            {{ $t('Angefragt am') }}:
                                            {{ formatDateTime(request.requested_at) }}
                                        </p>
                                        <p v-if="isPlanner && request.requested_by_name" class="text-xs text-text-subtle">
                                            {{ $t('Requested by') }}: {{ request.requested_by_name }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-text-subtle">
                                        {{ $t('Details') }}
                                    </span>
                                    <IconChevronRight class="h-4 w-4 text-text-subtle group-hover:text-accent-600" />
                                </div>
                            </button>
                        </div>

                        <!-- Keine Requests für dieses Craft -->
                        <div
                            v-else
                            class="px-4 py-6 text-center text-xs text-text-subtle"
                        >
                            {{ $t('Keine Dienstplananfragen für dieses Gewerk.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { router } from "@inertiajs/vue3";
import {
    IconChevronRight,
} from "@tabler/icons-vue";
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    crafts: { type: Array, required: true },
    isPlanner: { type: Boolean, default: false },
});

// Status → Label
const statusLabel = (status) => {
    switch (status) {
        case "pending":
            return t('Prüfung ausstehend');
        case "approved":
        case "accepted":
            return t('Angenommen');
        case "rejected":
        case "denied":
            return t('Abgelehnt');
        default:
            return status;
    }
};

// Status → Tailwind-Klassen
const statusClasses = (status) => {
    switch (status) {
        case "pending":
            return "bg-warning-surface text-warning ring-1 ring-warning-border";
        case "approved":
        case "accepted":
            return "bg-success-surface text-success ring-1 ring-success-border";
        case "rejected":
        case "denied":
            return "bg-danger-surface text-danger ring-1 ring-danger-border";
        default:
            return "bg-surface-sunken text-text-muted";
    }
};

const formatDateTime = (value) => {
    if (!value) return "-";
    const date = new Date(value);
    return date.toLocaleString(undefined, {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const goToRequest = (id) => {
    router.get(route("shift-plan-requests.my.show", id));
};
</script>
