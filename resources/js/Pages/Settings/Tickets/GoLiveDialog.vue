<template>
    <ArtworkBaseModal :title="$t('Go live')"
                      :description="$t('From now on the shop is public and sells for real. Everything sold in test mode is deleted: orders, tickets, passes, subscriptions and check-ins.')"
                      modal-size="sm:max-w-xl" @close="$emit('close')">
        <div class="flex flex-col gap-4 text-[13px]">
            <fieldset class="m-0 flex flex-col gap-2 border-0 p-0">
                <legend class="mb-2 p-0 font-lexend text-xs font-medium text-[#3F424A]">{{ $t('What stays') }}</legend>
                <label v-for="option in options" :key="option.key" class="flex cursor-pointer items-start gap-3 rounded-lg border border-border-subtle bg-surface px-3.5 py-3 hover:bg-surface-sunken">
                    <input v-model="form[option.key]" type="checkbox" class="aw-checklist-input mt-0.5 cursor-pointer" />
                    <span class="flex flex-col gap-0.5">
                        <span class="font-lexend text-[13px] font-medium text-text">{{ option.label }}</span>
                        <span class="text-[12.5px] leading-[18px] text-text-muted">{{ form[option.key] ? option.kept : option.dropped }}</span>
                    </span>
                </label>
            </fieldset>

            <p class="text-[12.5px] leading-[18px] text-text-muted">
                {{ $t('Rooms, price classes, reductions, productions and the team always stay. The payment provider verifies the house again for real under "Details & payouts"; until then only free dates can be sold.') }}
            </p>

            <p v-if="form.errors.tickets" class="text-danger">{{ form.errors.tickets }}</p>
        </div>

        <template #footer>
            <button type="button" class="ui-button" :disabled="form.processing" @click="$emit('close')">{{ $t('Cancel') }}</button>
            <button type="button" class="ui-button-add" :disabled="form.processing" @click="submit">
                <IconRocket class="size-4" />{{ form.processing ? $t('Going live…') : $t('Go live now') }}
            </button>
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { IconRocket } from '@tabler/icons-vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'

/* Ends test mode. Sales always go; each part of the set-up kept in Artwork-Tickets is the house's choice. */

const emit = defineEmits(['close'])

const { t } = useI18n()

const form = useForm({
    keep_dates: true,
    keep_shop_look: true,
    keep_pass_types: true,
    keep_coupons: true,
})

const options = [
    {
        key: 'keep_dates',
        label: t('Dates on sale'),
        kept: t('Stay on sale, now for real, with all places free again.'),
        dropped: t('Are withdrawn and become drafts again.'),
    },
    {
        key: 'keep_shop_look',
        label: t('Shop appearance'),
        kept: t('Stays as set up.'),
        dropped: t('Goes back to the default look.'),
    },
    {
        key: 'keep_pass_types',
        label: t('Passes & subscriptions'),
        kept: t('The kinds stay; the passes sold in test mode go.'),
        dropped: t('All kinds are deleted.'),
    },
    {
        key: 'keep_coupons',
        label: t('Discount codes'),
        kept: t('Stay, with their uses back at zero.'),
        dropped: t('All codes are deleted.'),
    },
]

function submit() {
    form.post(route('settings.tickets.go-live'), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    })
}
</script>
