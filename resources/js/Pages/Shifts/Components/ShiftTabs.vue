<template>
    <div>
        <div>
            <div class="border-gray-200">
                <nav class="-mb-px uppercase text-xs tracking-wide flex space-x-4" aria-label="Tabs">
                    <a v-for="tab in tabs" :href="tab.href" :key="tab.name"
                       :class="[tab.current ? 'border-artwork-buttons-create text-artwork-buttons-create' : 'border-transparent text-gray-950 hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-1 px-1 border-b-2 font-lexend']"
                       :aria-current="tab.current ? 'page' : undefined">
                        {{ tab.name }}
                    </a>
                </nav>
            </div>
        </div>
    </div>
</template>
<script>
import {defineComponent} from 'vue'

export default defineComponent({
    name: "ShiftTabs",
    data(){
        return {
            tabs: [
                { name: this.$t('Duty rosters'), href: route('shifts.plan'), current: route().current('shifts.plan') },
                { name: this.$t('My Operational plan'), href: route('user.operationPlan', this.$page.props.auth.user.id), current: route().current('user.operationPlan') },
                { name: this.$t('Shift templates'), href: route('shifts.presets'), current: route().current('shifts.presets') || route().current('shifts.timeline-presets.index')}
            ]
        }
    }
})
</script>
