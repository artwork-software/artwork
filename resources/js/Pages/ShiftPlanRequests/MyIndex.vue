<template>
    <AppLayout :title="$t('Meine Dienstplananfragen')">
        <div class="px-4 py-6 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900">
                        {{ $t('Meine Dienstplananfragen') }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 max-w-2xl">
                        {{ $t('Hier siehst du alle deine angefragten Dienstpläne, gruppiert nach Gewerken.') }}
                    </p>
                </div>
            </div>

            <!-- Kein Craft / keine Requests -->
            <div
                v-if="!crafts || !crafts.length"
                class="rounded-2xl border border-dashed border-gray-200 bg-white p-8 text-center"
            >
                <p class="text-sm text-gray-500">
                    {{ $t('Du hast keine Dienstplananfragen.') }}
                </p>
            </div>

            <!-- Cards pro Craft -->
            <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="craft in crafts"
                    :key="craft.id"
                    class="group flex flex-col rounded-2xl border border-gray-200 bg-white shadow-sm hover:border-indigo-200 hover:shadow-md transition"
                >
                    <!-- Craft Header -->
                    <div
                        class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-white to-gray-50 rounded-t-2xl"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="h-9 w-9 rounded-full flex items-center justify-center text-xs font-semibold text-white shadow-sm"
                                :style="{ backgroundColor: craft.color || '#4f46e5' }"
                            >
                                {{ craft.abbreviation }}
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900">
                                    {{ craft.name }}
                                </h2>
                                <p class="text-xs text-gray-500">
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
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600"
                            >
                                {{ $t('Anfragen') }}: {{ craft.shift_plan_requests.length }}
                            </span>
                        </div>
                    </div>

                    <!-- Requests-Liste -->
                    <div class="flex-1">
                        <div v-if="craft.shift_plan_requests.length" class="divide-y divide-gray-100">
                            <button
                                v-for="request in craft.shift_plan_requests"
                                :key="request.id"
                                type="button"
                                class="w-full text-left px-4 py-3 flex items-center justify-between hover:bg-indigo-50/60 transition"
                                @click="goToRequest(request.id)"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-gray-900">
                                                {{ $t('KW') }} {{ request.week_number }} / {{ request.year }}
                                            </span>
                                            <span
                                                :class="[
                                                    'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium',
                                                    statusClasses(request.status)
                                                ]"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-current"></span>
                                                {{ statusLabel(request.status) }}
                                            </span>
                                        </div>
                                        <p class="mt-0.5 text-xs text-gray-500">
                                            {{ $t('Angefragt am') }}:
                                            {{ formatDateTime(request.requested_at) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-400">
                                        {{ $t('Details') }}
                                    </span>
                                    <IconChevronRight class="h-4 w-4 text-gray-400 group-hover:text-indigo-500" />
                                </div>
                            </button>
                        </div>

                        <!-- Keine Requests für dieses Craft -->
                        <div
                            v-else
                            class="px-4 py-6 text-center text-xs text-gray-400"
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
            return "bg-amber-50 text-amber-700 ring-1 ring-amber-200";
        case "approved":
        case "accepted":
            return "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200";
        case "rejected":
        case "denied":
            return "bg-rose-50 text-rose-700 ring-1 ring-rose-200";
        default:
            return "bg-gray-100 text-gray-600";
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
