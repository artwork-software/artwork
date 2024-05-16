<template>
    <app-layout :title="$t('Budget settings')">
        <div class="max-w-screen-xl ml-14 mr-40 mb-4">
            <div class="mb-5">
                <h2 class="headline1 mb-2">{{ $t('Budget settings') }}</h2>
                <div class="headline3Light">
                    {{ $t('Define settings for your budget.') }}
                </div>
            </div>
            <BudgetSettingsTabs class="mb-5"/>
            <div>
                <slot></slot>
            </div>
        </div>
    </app-layout>
</template>

<script>
import {defineComponent} from 'vue';
import BudgetSettingsTabs from "@/Pages/BudgetSettings/BudgetSettingsTabs.vue";
import AppLayout from "@/Layouts/AppLayout.vue";

export default defineComponent({
    components: {
        AppLayout,
        BudgetSettingsTabs
    }
});
</script>
