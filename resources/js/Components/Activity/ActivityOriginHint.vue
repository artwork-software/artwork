<template>
    <span v-if="label" class="text-xs text-text-subtle whitespace-nowrap">{{ label }}</span>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    via: { type: String, default: null },
    viaDetail: { type: String, default: null },
});

const { t } = useI18n();

/**
 * Herkunft eines Änderungseintrags. "ui" bekommt bewusst keinen Zusatz — der Normalfall soll die
 * Liste nicht zumüllen. "system" ebenso wenig: dort gibt es keine handelnde Person zu erklären.
 */
const label = computed(() => {
    if (props.via === 'ai_assistant') {
        return t('via assistant');
    }

    if (props.via === 'api') {
        return props.viaDetail
            ? t('via API (:name)', { name: props.viaDetail })
            : t('via API');
    }

    return null;
});
</script>
