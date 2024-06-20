<template>
    <div class="border-gray-200">
        <nav class="-mb-px uppercase text-xs tracking-wide flex space-x-8" aria-label="Tabs">
            <Link v-for="tab in this.computedTabs"
                  :href="tab.href"
                  :key="tab.name"
                  :class="[
                      tab.current ?
                          'border-artwork-buttons-create text-artwork-buttons-create' :
                          'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300',
                      'whitespace-nowrap py-4 px-1 border-b-2 font-semibold'
                  ]"
                  :aria-current="tab.current ? 'page' : undefined">
                {{ tab.name }}
            </Link>
        </nav>
    </div>
</template>

<script>
import {defineComponent} from 'vue';
import {Link} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";

export default defineComponent({
    name: 'BudgetSettingsTabs',
    mixins: [Permissions],
    components: {
        Link
    },
    computed: {
        computedTabs() {
            let computedTabs = [];

            this.tabs.forEach((tab) => {
                if (tab.hasPermission) {
                    computedTabs.push(tab);
                }
            });

            return computedTabs;
        }
    },
    data() {
        return {
            tabs: [
                {
                    name: this.$t('General'),
                    href: route('budget-settings.general'),
                    current: route().current('budget-settings.general'),
                    hasPermission: this.$canAny(
                        [
                            'can manage global project budgets',
                            'can manage all project budgets without docs'
                        ]
                    )
                },
                {
                    name: this.$t('Account management'),
                    href: route('budget-settings.account-management'),
                    current: route().current('budget-settings.account-management'),
                    hasPermission: this.$canAny(
                        [
                            'can manage global project budgets',
                            'can manage all project budgets without docs'
                        ]
                    )
                },
                {
                    name: this.$t('Budget templates'),
                    href: route('budget-settings.templates'),
                    current: route().current('budget-settings.templates'),
                    hasPermission: this.$can('view budget templates')
                }
            ]
        }
    }
})
</script>
