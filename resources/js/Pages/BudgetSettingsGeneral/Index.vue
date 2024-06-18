<template>
    <BudgetSettingsHeader>
        <div class="xsLight mb-5">
            {{ $t('Specify here what the first three columns of new budget tables should be called') }}
        </div>
        <div v-for="budgetColumnSetting in this.budgetColumnSettings"
             class="flex flex-row items-center mb-2 gap-2">
            <span class="xsLight w-20">{{ $t('Column') }} {{ budgetColumnSetting.column_position + 1 }}</span>
            <input-component v-model="budgetColumnSetting.column_name"/>
            <button :class="[
                        budgetColumnSetting.column_name === '' ?
                            'bg-secondary':
                            'bg-artwork-buttons-create hover:bg-artwork-buttons-hover focus:outline-none',
                        'rounded-full ml-1 items-center text-sm p-1 border border-transparent uppercase shadow-sm text-white'
                    ]"
                :disabled="budgetColumnSetting.column_name === ''"
                @click="this.saveBudgetColumnSetting(budgetColumnSetting)">
                <CheckIcon class="h-5 w-5"></CheckIcon>
            </button>
        </div>
    </BudgetSettingsHeader>
    <success-modal v-if="this.$page.props.flash.success"
                   :title="$t('Success')"
                   :description="this.$page.props.flash.success"
                   @closed="this.$page.props.flash.success = null"
                   :button="$t('OK')"
    />
    <error-component v-if="this.$page.props.flash.error"
                     :titel="$t('Error')"
                     :description="this.$page.props.flash.error"
                     @closed="this.$page.props.flash.error = null"
                     :confirm="$t('OK')"
    />
</template>

<script>
import {defineComponent} from "vue";
import BudgetSettingsHeader from "@/Pages/BudgetSettings/BudgetSettingsHeader.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import Button from "@/Jetstream/Button.vue";
import {CheckIcon} from "@heroicons/vue/solid";
import {router} from "@inertiajs/vue3";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import ErrorComponent from "@/Layouts/Components/ErrorComponent.vue";

export default defineComponent({
    components: {
        ErrorComponent,
        SuccessModal,
        CheckIcon,
        Button,
        BudgetSettingsHeader,
        InputComponent
    },
    props: [
        'budgetColumnSettings'
    ],
    methods: {
        saveBudgetColumnSetting(budgetColumnSetting) {
            router.patch(
                route(
                    'budget-settings.general.update',
                    {
                        budgetColumnSetting: budgetColumnSetting.id
                    }
                ),
                {
                    column_name: budgetColumnSetting.column_name
                },
                {
                    preserveState: false,
                    preserveScroll: true
                }
            )
        }
    }
})
</script>
