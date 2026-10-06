<template>
    <!-- The question before dates on sale move; asked by ticketingMoveHeaders, mounted once in AppLayout.
         Without a backdrop, like the series question. -->
    <TransitionRoot as="template" :show="question !== null">
        <Dialog as="div" class="artwork relative z-[130]" @close="answer(false)">
            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-start justify-center p-4 sm:pt-16">
                    <TransitionChild as="template" enter="ease-out duration-200 motion-reduce:transition-none"
                                     enter-from="opacity-0 -translate-y-2" enter-to="opacity-100 translate-y-0"
                                     leave="ease-in duration-150 motion-reduce:transition-none"
                                     leave-from="opacity-100 translate-y-0" leave-to="opacity-0 -translate-y-2">
                        <DialogPanel v-if="question" class="flex w-full max-w-2xl flex-col gap-3 rounded-lg border border-border-subtle bg-surface px-5 py-4 text-left shadow-overlay ring-1 ring-black/5">
                            <div class="flex items-start gap-3">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-accent-100 text-accent-700">
                                    <IconTicket class="size-4" stroke-width="1.75" aria-hidden="true" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <DialogTitle class="font-lexend text-[13px] font-semibold leading-8 text-text">{{ title }}</DialogTitle>
                                    <p class="-mt-1 text-[13px] leading-5 text-text-muted">{{ summary }}</p>
                                    <p class="mt-1 text-[13px] leading-5 text-text-muted">
                                        {{ question.mayMove
                                            ? $t('The shop shows the new time and room right away. Buyers are not notified automatically; their tickets point out the change.')
                                            : $t('Only people with the permission "Move dates on sale" can change the time or room of a date on sale. Ask an admin.') }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                <template v-if="question.mayMove">
                                    <button type="button" class="ui-button" @click="answer(false)">{{ $t('Cancel') }}</button>
                                    <button type="button" class="ui-button-add" @click="answer(true)">{{ $t('Move anyway') }}</button>
                                </template>
                                <button v-else type="button" class="ui-button" @click="answer(false)">{{ $t('Close') }}</button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { IconTicket } from '@tabler/icons-vue'
import { ticketingMoveQuestion as question } from '@/Composeables/useTicketingMove.js'

const { t } = useI18n()

const title = computed(() => {
    if (!question.value.mayMove) return t('Date on sale')
    return question.value.several ? t('Move dates on sale?') : t('Move a date on sale?')
})

/** The sold tickets only when tickets answered for every date. */
const summary = computed(() => {
    const { dates, several } = question.value
    const known = dates.every((date) => date.sold !== null)
    const sold = dates.reduce((sum, date) => sum + (date.sold ?? 0), 0)

    if (several) {
        return known
            ? t('{count} of these dates are on sale, {sold} tickets are sold.', { count: dates.length, sold })
            : t('{count} of these dates are on sale.', { count: dates.length })
    }

    return known ? t('This date is on sale, {sold} tickets are sold.', { sold }) : t('This date is on sale.')
})

function answer(confirmed) {
    question.value.resolve(confirmed && question.value.mayMove)
}
</script>
