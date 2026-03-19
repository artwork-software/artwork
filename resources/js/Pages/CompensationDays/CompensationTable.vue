<template>
    <div class="overflow-hidden rounded-lg border border-zinc-200">
        <table class="min-w-full text-xs">
            <thead class="bg-zinc-50">
                <tr>
                    <th v-if="showUser" class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Employee') }}</th>
                    <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Value') }}</th>
                    <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Deadline') }}</th>
                    <th v-if="showGrantedInfo" class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Granted on') }}</th>
                    <th v-if="showGrantedInfo" class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Granted by') }}</th>
                    <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Rule') }}</th>
                    <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Reason') }}</th>
                    <th class="px-3 py-2 text-right font-medium text-zinc-500">{{ $t('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                <tr
                    v-for="item in items"
                    :key="item.id"
                    class="hover:bg-zinc-50/50"
                    :class="overdueHighlight && isOverdue(item) ? 'bg-red-50/50' : ''"
                >
                    <td v-if="showUser" class="px-3 py-2.5 text-zinc-900 font-medium">
                        {{ item.user?.first_name }} {{ item.user?.last_name }}
                    </td>
                    <td class="px-3 py-2.5">
                        <span
                            class="inline-flex items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                            :class="item.value >= 1.0 ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'"
                        >
                            {{ item.value >= 1.0 ? $t('Full day (1.0)') : $t('Half day (0.5)') }}
                        </span>
                    </td>
                    <td class="px-3 py-2.5" :class="isOverdue(item) ? 'text-red-600 font-medium' : 'text-zinc-700'">
                        {{ formatDate(item.deadline) }}
                        <span v-if="isOverdue(item)" class="ml-1 text-[10px] text-red-500 font-medium">
                            ({{ $t('Deadline expired') }})
                        </span>
                    </td>
                    <td v-if="showGrantedInfo" class="px-3 py-2.5 text-zinc-700">{{ formatDate(item.granted_date) }}</td>
                    <td v-if="showGrantedInfo" class="px-3 py-2.5 text-zinc-600">
                        <template v-if="item.granted_by_user">
                            {{ item.granted_by_user.first_name }} {{ item.granted_by_user.last_name }}
                        </template>
                        <template v-else>-</template>
                    </td>
                    <td class="px-3 py-2.5">
                        <div class="flex items-center gap-1.5">
                            <span
                                v-if="item.violation?.shift_rule"
                                class="inline-block h-2 w-2 rounded-full"
                                :style="{ backgroundColor: item.violation.shift_rule.warning_color || '#ff0000' }"
                            ></span>
                            {{ item.violation?.shift_rule?.name || $t('Manual') }}
                        </div>
                    </td>
                    <td class="px-3 py-2.5 text-zinc-600 max-w-[200px] truncate">
                        {{ item.reason || '-' }}
                    </td>
                    <td class="px-3 py-2.5 text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            <BaseUIButton
                                v-if="showGrant"
                                :label="$t('Grant compensation day')"
                                is-add-button
                                is-small
                                @click="$emit('grant', item)"
                            />
                            <BaseUIButton
                                v-if="showRevoke"
                                :label="$t('Revoke')"
                                is-small
                                @click="$emit('revoke', item)"
                            />
                            <BaseUIButton
                                v-if="showDelete"
                                :label="$t('Delete')"
                                is-delete-button
                                is-small
                                @click="$emit('delete', item)"
                            />
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';

defineProps({
    items: { type: Array, default: () => [] },
    showUser: { type: Boolean, default: false },
    showGrantedInfo: { type: Boolean, default: false },
    showGrant: { type: Boolean, default: false },
    showRevoke: { type: Boolean, default: false },
    showDelete: { type: Boolean, default: false },
    overdueHighlight: { type: Boolean, default: false },
});

defineEmits(['grant', 'revoke', 'delete']);

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('de-DE');
}

function isOverdue(item) {
    if (item.granted_at) return false;
    if (!item.deadline) return false;
    return new Date(item.deadline) < new Date();
}
</script>
