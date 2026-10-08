<template>
    <div class="mt-8">
        <div class="mb-4 flex items-center justify-between gap-4">
            <h2 class="text-lg font-semibold">{{ $t('Purchases in Artwork-Tickets') }}</h2>
            <a :href="route('ticketing.open', { customer: contactId })" target="_blank" rel="noopener" class="ui-button">
                <IconExternalLink class="size-3.5" />{{ $t('Open in Artwork-Tickets') }}
            </a>
        </div>

        <div v-if="loading" class="flex items-center gap-2 text-sm text-text-subtle">
            <IconLoader2 class="size-4 animate-spin" />{{ $t('Loading…') }}
        </div>
        <p v-else-if="error" class="text-sm text-danger">{{ error }}</p>
        <div v-else class="flex flex-col gap-5 text-[13px]">
            <div class="overflow-x-auto rounded-lg border border-border-subtle bg-surface">
                <table class="w-full min-w-[640px] border-collapse">
                    <thead>
                        <tr class="font-lexend text-xs font-medium text-text-subtle">
                            <th class="px-4 py-2.5 text-left font-medium">{{ $t('Order') }}</th>
                            <th class="px-4 py-2.5 text-left font-medium">{{ $t('Production') }}</th>
                            <th class="px-4 py-2.5 text-left font-medium">{{ $t('Purchased on') }}</th>
                            <th class="px-4 py-2.5 text-right font-medium">{{ $t('Tickets') }}</th>
                            <th class="px-4 py-2.5 text-right font-medium">{{ $t('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="customer.orders.length === 0">
                            <td colspan="5" class="px-4 py-6 text-center text-text-subtle">{{ $t('No orders.') }}</td>
                        </tr>
                        <tr v-for="order in customer.orders" :key="order.id" class="border-t border-border-hairline">
                            <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs tabular-nums">
                                {{ order.orderNumber }}
                                <BaseChip v-if="order.status === 'refunded'" variant="warning" class="ml-2">{{ $t('Refunded') }}</BaseChip>
                            </td>
                            <td class="px-4 py-2.5">
                                {{ order.eventTitle ?? $t('Multi-visit pass') }}
                                <span v-if="order.otherEventCount > 0" class="text-text-subtle">· {{ $t('+{count} more', { count: order.otherEventCount }) }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-2.5 tabular-nums">{{ formatDay(order.happenedAt) }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums">{{ order.ticketCount }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-right tabular-nums">{{ formatEuro(order.totalCents) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-if="customer.orderTotal > customer.orders.length" class="text-xs text-text-subtle">
                {{ $t('Showing the latest {shown} of {total} orders.', { shown: customer.orders.length, total: customer.orderTotal }) }}
            </p>

            <div v-if="customer.passes.length > 0" class="overflow-x-auto rounded-lg border border-border-subtle bg-surface">
                <table class="w-full min-w-[640px] border-collapse">
                    <thead>
                        <tr class="font-lexend text-xs font-medium text-text-subtle">
                            <th class="px-4 py-2.5 text-left font-medium">{{ $t('Multi-visit pass') }}</th>
                            <th class="px-4 py-2.5 text-left font-medium">{{ $t('Code') }}</th>
                            <th class="px-4 py-2.5 text-right font-medium">{{ $t('Uses left') }}</th>
                            <th class="px-4 py-2.5 text-left font-medium">{{ $t('Valid until') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="pass in customer.passes" :key="pass.id" class="border-t border-border-hairline">
                            <td class="px-4 py-2.5">{{ pass.name }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs tabular-nums">{{ pass.code }}</td>
                            <td class="px-4 py-2.5 text-right tabular-nums">{{ pass.usesTotal - pass.usesUsed }} / {{ pass.usesTotal }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 tabular-nums">{{ pass.validUntil ? formatDay(pass.validUntil) : '–' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import axios from 'axios'
import dayjs from 'dayjs'
import { IconExternalLink, IconLoader2 } from '@tabler/icons-vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import { useTranslation } from '@/Composeables/Translation.js'
import { formatEuro } from '@/Pages/Projects/Tab/Components/Ticketing/ticketing.js'

/* Orders and passes of one buyer, read from Artwork-Tickets when the contact opens — the CRM only
   keeps the totals the nightly sync writes. */
const props = defineProps({
    contactId: { type: Number, required: true },
})

const $t = useTranslation()

const customer = ref(null)
const loading = ref(true)
const error = ref('')

const formatDay = (value) => dayjs(value).format('DD.MM.YYYY')

onMounted(async () => {
    try {
        const { data } = await axios.get(route('crm.contacts.ticketing', props.contactId))
        customer.value = data.customer
    } catch (requestError) {
        error.value = requestError?.response?.data?.message || $t('The ticketing data could not be loaded.')
    } finally {
        loading.value = false
    }
})
</script>
