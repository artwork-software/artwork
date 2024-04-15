<template>
    <span>
        {{ $t('Budget deadline') }}: {{ budgetDeadline }}
    </span>
</template>

<script>
import {defineComponent} from "vue";

export default defineComponent({
    props: [
        'project',
        'headerObject'
    ],
    computed: {
        budgetDeadline() {
            if (this.project){
                return this.project.budget_deadline ?? this.$t('No budget deadline specified');
            } else if(this.headerObject){
                return this.headerObject.project.budget_deadline ?? this.$t('No budget deadline specified');
            }
        }
    }
});
</script>
