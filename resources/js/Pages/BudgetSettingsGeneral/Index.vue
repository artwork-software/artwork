<template>
    <BudgetSettingsHeader :title="$t('General')" :description="$t('Define settings for your budget.')">
        <SettingsGuideBanner
            variant="banner"
            storage-key="settings-guide.budget.general"
            title="How the column names work"
            :paragraphs="guideParagraphs"
            class="mb-6"
        />
        <div class="text-sm/5 font-bold text-text-subtle mb-5">
            {{ $t('Specify here what the first three columns of new budget tables should be called') }}
        </div>
        <div v-for="budgetColumnSetting in this.budgetColumnSettings"
             class="flex flex-row items-center mb-2 gap-2">
            <span class="text-sm/5 font-bold text-text-subtle w-20">{{ $t('Column') }} {{ budgetColumnSetting.id }}</span>
            <input type="text" class="input w-52"  v-model="budgetColumnSetting.column_name"/>
            <button :class="[
                        budgetColumnSetting.column_name === '' ?
                            'bg-text-subtle':
                            'bg-accent-600 hover:bg-accent-700 focus:outline-none',
                        'rounded-full ml-1 items-center text-sm p-1 border border-transparent uppercase shadow-sm text-white'
                    ]"
                :disabled="budgetColumnSetting.column_name === ''"
                @click="this.saveBudgetColumnSetting(budgetColumnSetting)">
                <IconCheck class="h-5 w-5"></IconCheck>
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
import {IconCheck} from "@tabler/icons-vue";
import {defineComponent} from "vue";
import BudgetSettingsHeader from "@/Pages/BudgetSettings/BudgetSettingsHeader.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import Button from "@/Jetstream/Button.vue";
import {router} from "@inertiajs/vue3";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import ErrorComponent from "@/Layouts/Components/ErrorComponent.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";

export default defineComponent({
    components: {
        SettingsGuideBanner,
        ErrorComponent,
        SuccessModal,
        IconCheck,
        Button,
        BudgetSettingsHeader,
        InputComponent
    },
    props: [
        'budgetColumnSettings'
    ],
    data() {
        return {
            guideParagraphs: [
                'The column names apply only to newly created budget tables — existing project budgets keep their names.',
                'If account management is activated, columns 1 and 2 become select fields for accounts and cost units — the names entered here then only serve as headings.',
            ],
        };
    },
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
