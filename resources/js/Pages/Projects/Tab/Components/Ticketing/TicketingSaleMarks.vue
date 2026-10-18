<template>
    <span v-if="ownText" role="img" :title="$t('Own description in the shop')" :aria-label="$t('Own description in the shop')" class="shrink-0">
        <IconFileDescription class="size-4 text-text-subtle" stroke-width="1.75" />
    </span>
    <span v-if="ownReductions" role="img" :title="reductionsTitle" :aria-label="reductionsTitle" class="shrink-0">
        <IconDiscount class="size-4 text-text-subtle" stroke-width="1.75" />
    </span>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { IconDiscount, IconFileDescription } from '@tabler/icons-vue'
import { hasOwnReductions, offeredIdsOf } from '@/Pages/Projects/Tab/Components/Ticketing/ticketing.js'

/* Where a date sells differently than its production: own text, own reductions. */
const props = defineProps({
    event: { type: Object, required: true },
    production: { type: Object, required: true },
    reductions: { type: Array, required: true },
})

const { t } = useI18n()

const ownText = computed(() => Boolean(props.event.release?.description))
const ownReductions = computed(() => hasOwnReductions(props.event, props.production, props.reductions))
const reductionsTitle = computed(() => {
    const offered = offeredIdsOf(props.event, props.production, props.reductions)
    const names = props.reductions.filter((reduction) => offered.includes(reduction.id)).map((reduction) => reduction.name)
    return names.length ? t('Own reductions: {names}', { names: names.join(', ') }) : t('No reductions on this date')
})
</script>
