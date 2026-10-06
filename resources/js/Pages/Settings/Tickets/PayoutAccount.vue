<template>
    <div>
        <div v-if="state !== 'open' && !editing" class="flex flex-wrap items-center gap-x-4 gap-y-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full" :class="state === 'verified' ? 'bg-success-surface text-success' : 'bg-info-surface text-info'">
                <IconCheck v-if="state === 'verified'" class="size-5" />
                <IconClock v-else class="size-5" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="font-lexend text-[13px] font-medium text-text">{{ state === 'verified' ? $t('Verified') : $t('In review') }}</p>
                <p class="text-[13px] leading-5 text-text-subtle">
                    {{ state === 'verified' ? $t('The house can sell tickets and is paid out.') : $t('The details are submitted. Stripe is checking them, which usually takes a few minutes.') }}
                </p>
            </div>
            <button v-if="stripeKey" type="button" class="ui-button" @click="editing = true">{{ $t('Change details') }}</button>
        </div>
        <p v-else-if="!stripeKey" class="text-[13px] text-text-subtle">{{ $t('Payments are not set up in artwork tickets yet.') }}</p>
        <div v-else ref="container"></div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
// `pure`: Stripe's script loads only once the form is shown, not with the page.
import { loadConnectAndInitialize } from '@stripe/connect-js/pure'
import { IconCheck, IconClock } from '@tabler/icons-vue'

/* Stripe's own verification form, embedded: who stands behind the house and where it is
   paid. It saves as the house goes and fetches its secret through this installation;
   the house never needs a Stripe login. */

const props = defineProps({
    /** open · review · verified, as tickets reads it from Stripe. */
    state: { type: String, required: true },
    /** null while artwork tickets takes no payments yet. */
    stripeKey: { type: String, default: null },
})

const { locale } = useI18n()
const editing = ref(false)
const container = ref(null)

watch(container, (element) => {
    if (!element) return
    const onboarding = loadConnectAndInitialize({
        publishableKey: props.stripeKey,
        locale: locale.value,
        fetchClientSecret: async () => (await axios.post(route('settings.tickets.billing.stripe-session'))).data.clientSecret,
        appearance: {
            variables: {
                fontFamily: 'Inter, system-ui, sans-serif',
                borderRadius: '6px',
                colorPrimary: getComputedStyle(document.documentElement).getPropertyValue('--color-accent-600').trim(),
            },
        },
    }).create('account-onboarding')
    // Stripe verifies after the form is left; the page asks tickets for the fresh state.
    onboarding.setOnExit(() => {
        editing.value = false
        router.reload()
    })
    element.append(onboarding)
})
</script>
