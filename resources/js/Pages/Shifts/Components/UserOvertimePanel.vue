<template>
    <div class="space-y-5">
        <!-- Kennzahlen -->
        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-lg border border-gray-100 p-3">
                <p class="text-xs uppercase tracking-wide text-gray-500">{{ $t('Current overtime balance') }}</p>
                <p class="mt-1 text-2xl font-semibold">{{ local.balance_formatted }}</p>
            </div>
            <div class="rounded-lg border p-3"
                 :class="local.payable_minutes > 0 ? 'border-red-200 bg-red-50/40' : 'border-gray-100'">
                <p class="text-xs uppercase tracking-wide text-gray-500">{{ $t('Payable (deadline exceeded)') }}</p>
                <p class="mt-1 text-2xl font-semibold" :class="local.payable_minutes > 0 ? 'text-red-600' : ''">
                    {{ local.payable_formatted }}
                </p>
                <p v-if="!local.payout_active" class="text-[11px] text-gray-400 mt-0.5">
                    {{ $t('No payout deadline configured in the contract.') }}
                </p>
            </div>
        </div>

        <!-- Manuelle Auszahlung -->
        <div v-if="local.can_pay_out" class="rounded-lg border border-gray-200 p-3">
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
                        :disabled="submitting || totalMinutes < 1"
                        class="rounded-lg bg-artwork-buttons-create px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                        @click="submitPayout">
                    {{ $t('Pay out') }}
                </button>
            </div>
            <p v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</p>
        </div>

        <!-- Offene Überstunden-Chunks mit Frist -->
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-1">{{ $t('Open overtime (with deadlines)') }}</h4>
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                        <th class="py-2 pr-4 font-medium">{{ $t('Accrued on') }}</th>
                        <th class="py-2 px-2 font-medium">{{ $t('Amount') }}</th>
                        <th class="py-2 pl-2 font-medium">{{ $t('Deadline') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="(c, i) in local.open_chunks" :key="i">
                        <td class="py-2 pr-4">{{ formatDate(c.accrual_date) }}</td>
                        <td class="py-2 px-2 font-medium">{{ c.remaining_formatted }}</td>
                        <td class="py-2 pl-2">
                            <span :class="c.overdue ? 'text-red-600 font-medium' : ''">
                                {{ c.deadline ? formatDate(c.deadline) : '–' }}
                            </span>
                            <span v-if="c.overdue" class="text-xs text-red-600">({{ $t('payable') }})</span>
                        </td>
                    </tr>
                    <tr v-if="!local.open_chunks.length">
                        <td colspan="3" class="py-4 text-center text-gray-400">{{ $t('No open overtime.') }}</td>
                    </tr>
                </tbody>
            </table>
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

        <!-- Überstunden je Tag -->
        <details class="rounded-lg border border-gray-100">
            <summary class="cursor-pointer px-3 py-2 text-sm font-medium text-gray-700">
                {{ $t('Overtime per day') }}
            </summary>
            <table class="min-w-full text-sm">
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="d in local.per_day" :key="d.date">
                        <td class="py-1.5 px-3 text-gray-700">{{ formatDate(d.date) }}</td>
                        <td class="py-1.5 px-3 text-right text-green-600">+{{ d.minutes_formatted }}</td>
                    </tr>
                    <tr v-if="!local.per_day.length">
                        <td colspan="2" class="py-3 text-center text-gray-400">{{ $t('No overtime recorded.') }}</td>
                    </tr>
                </tbody>
            </table>
        </details>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
    userId: { type: Number, required: true },
    data: { type: Object, required: true },
})

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

const formatDate = (value) => {
    if (!value) return '-'
    const date = new Date(value)
    if (isNaN(date.getTime())) return value
    const day = String(date.getDate()).padStart(2, '0')
    const month = String(date.getMonth() + 1).padStart(2, '0')
    return `${day}.${month}.${date.getFullYear()}`
}
</script>
