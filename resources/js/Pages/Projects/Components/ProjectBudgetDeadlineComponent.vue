<template>
    <span>
        {{ $t('Budget deadline') }}: {{ project.budget_deadline ?? $t('No budget deadline specified') }}
    </span>
</template>

<script>
import {defineComponent} from "vue";

export default defineComponent({
    props: [
        'project'
    ]
});
</script>
