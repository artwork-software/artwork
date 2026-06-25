<template>
    <div class="space-y-5">
        <!-- Regel nicht aktiv -->
        <div v-if="!local.rule_active"
             class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
            {{ $t('The overtime rule is not active for this user. Activate it in the employment contract tab.') }}
        </div>

        <!-- Kennzahlen -->
        <div class="grid grid-cols-3 gap-3">
            <div class="rounded-lg border border-zinc-200 px-4 py-3">
                <p class="text-[11px] uppercase tracking-wide text-zinc-400">{{ $t('Open') }}</p>
                <p class="mt-1 text-xl font-semibold text-zinc-800">{{ local.open_formatted }}</p>
            </div>
            <div class="rounded-lg border px-4 py-3"
                 :class="local.payable_minutes > 0 ? 'border-red-200 bg-red-50/40' : 'border-zinc-200'">
                <p class="text-[11px] uppercase tracking-wide"
                   :class="local.payable_minutes > 0 ? 'text-red-500' : 'text-zinc-400'">
                    {{ $t('Overtime to be paid out') }}
                </p>
                <p class="mt-1 text-xl font-semibold" :class="local.payable_minutes > 0 ? 'text-red-600' : 'text-zinc-800'">
                    {{ local.payable_formatted }}
                </p>
            </div>
            <div class="rounded-lg border border-zinc-200 px-4 py-3">
                <p class="text-[11px] uppercase tracking-wide text-zinc-400">{{ $t('Paid out') }}</p>
                <p class="mt-1 text-xl font-semibold text-zinc-800">{{ local.paid_out_formatted }}</p>
            </div>
        </div>

        <!-- Manuelle Auszahlung -->
        <div v-if="local.can_pay_out && local.payable_minutes > 0" class="rounded-lg border border-gray-200 p-3">
            <h4 class="text-sm font-semibold text-gray-900 mb-2">{{ $t('Pay out overtime') }}</h4>
            <p class="text-[11px] text-gray-400 mb-2">
                {{ $t('Booking reduces the time account. The actual payment happens outside artwork.') }}
            </p>
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs text-gray-600">{{ $t('Hours') }}</label>
                    <input v-model.number="form.hours" type="number" min="0"
                           class="mt-1 w-24 rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
                </div>
                <div>
                    <label class="block text-xs text-gray-600">{{ $t('Minutes') }}</label>
                    <input v-model.number="form.minutes" type="number" min="0" max="59"
                           class="mt-1 w-24 rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
                </div>
                <div class="flex-1 min-w-[12rem]">
                    <label class="block text-xs text-gray-600">{{ $t('Comment') }}</label>
                    <input v-model="form.comment" type="text"
                           class="mt-1 w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm" />
                </div>
                <button type="button"
                        :disabled="submitting || totalMinutes < 1 || totalMinutes > local.payable_minutes"
                        class="rounded-lg bg-artwork-buttons-create px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                        @click="submitPayout">
                    {{ $t('Pay out') }}
                </button>
            </div>
            <p v-if="totalMinutes > local.payable_minutes" class="mt-1 text-xs text-amber-600">
                {{ $t('The amount exceeds the payable overtime.') }}
            </p>
            <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
        </div>

        <!-- Tage mit Überstunden -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-1 flex items-center gap-2">
                {{ $t('Days with overtime') }}
                <span class="text-xs font-normal text-zinc-400">({{ local.entries.length }})</span>
            </h4>
            <div v-if="local.entries.length" class="overflow-hidden rounded-lg border border-zinc-200">
                <table class="min-w-full text-xs">
                    <thead class="bg-zinc-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Date') }}</th>
                            <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Overtime') }}</th>
                            <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Remaining') }}</th>
                            <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Deadline') }}</th>
                            <th class="px-3 py-2 text-left font-medium text-zinc-500">{{ $t('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        <tr v-for="entry in local.entries" :key="entry.id">
                            <td class="px-3 py-2.5 text-zinc-700">{{ formatDate(entry.date) }}</td>
                            <td class="px-3 py-2.5 text-zinc-700">{{ entry.minutes_formatted }}</td>
                            <td class="px-3 py-2.5 text-zinc-700">{{ entry.remaining_formatted }}</td>
                            <td class="px-3 py-2.5"
                                :class="entry.status === 'payable' ? 'text-red-600 font-medium' : 'text-zinc-700'">
                                {{ formatDate(entry.deadline) }}
                            </td>
                            <td class="px-3 py-2.5">
                                <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                                      :class="statusClass(entry.status)">
                                    {{ statusLabel(entry.status) }}
                                </span>
                                <span v-if="entry.status === 'paid_out' && entry.paid_out_by"
                                      class="ml-1 text-[10px] text-zinc-400">
                                    {{ entry.paid_out_by }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="text-sm text-zinc-400 italic">{{ $t('No overtime recorded.') }}</p>
        </div>

        <!-- Auszahlungs-Historie -->
        <div v-if="local.payouts.length">
            <h4 class="text-sm font-semibold text-gray-900 mb-1">{{ $t('Payout history') }}</h4>
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                        <th class="py-2 pr-4 font-medium">{{ $t('Date') }}</th>
                        <th class="py-2 px-2 font-medium">{{ $t('Amount') }}</th>
                        <th class="py-2 px-2 font-medium">{{ $t('By') }}</th>
                        <th class="py-2 pl-2 font-medium">{{ $t('Comment') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="p in local.payouts" :key="p.id">
                        <td class="py-2 pr-4">{{ formatDate(p.payout_date) }}</td>
                        <td class="py-2 px-2 font-medium">{{ p.hours_formatted }}</td>
                        <td class="py-2 px-2 text-gray-500">{{ p.created_by }}</td>
                        <td class="py-2 pl-2 text-gray-500">{{ p.comment }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'

const props = defineProps({
    userId: { type: Number, required: true },
    data: { type: Object, required: true },
})

const { t } = useI18n()

const local = ref({ ...props.data })
watch(() => props.data, (v) => { local.value = { ...v } })

const form = reactive({ hours: 0, minutes: 0, comment: '' })
const submitting = ref(false)
const error = ref('')

const totalMinutes = computed(() => (Number(form.hours) || 0) * 60 + (Number(form.minutes) || 0))

const submitPayout = async () => {
    error.value = ''
    if (totalMinutes.value < 1) return
    submitting.value = true
    try {
        const res = await axios.post(route('user.overtime.payout', { user: props.userId }), {
            minutes: totalMinutes.value,
            comment: form.comment || null,
        })
        local.value = res.data
        form.hours = 0
        form.minutes = 0
        form.comment = ''
    } catch (e) {
        error.value = e?.response?.data?.message || 'Error'
    } finally {
        submitting.value = false
    }
}

const statusLabel = (status) => {
    const map = {
        open: t('Open'),
        compensated: t('Compensated'),
        payable: t('Overtime to be paid out'),
        paid_out: t('Paid out'),
    }
    return map[status] || status
}

const statusClass = (status) => {
    const map = {
        open: 'bg-blue-100 text-blue-700',
        compensated: 'bg-green-100 text-green-700',
        payable: 'bg-red-100 text-red-700',
        paid_out: 'bg-zinc-100 text-zinc-600',
    }
    return map[status] || 'bg-zinc-100 text-zinc-600'
}

const formatDate = (value) => {
    if (!value) return '-'
    const date = new Date(value)
    if (isNaN(date.getTime())) return value
    const day = String(date.getDate()).padStart(2, '0')
    const month = String(date.getMonth() + 1).padStart(2, '0')
    return `${day}.${month}.${date.getFullYear()}`
}
</script>
