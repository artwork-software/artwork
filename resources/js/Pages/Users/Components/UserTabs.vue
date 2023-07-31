<template>
    <div>
        <div>
            <div class="border-gray-200">
                <nav class="-mb-px uppercase text-xs tracking-wide pt-4 flex space-x-8" aria-label="Tabs">
                    <a v-for="tab in tabs" :href="tab.href" :key="tab?.name"
                       :class="[tab.current ? 'border-buttonBlue text-buttonBlue' : 'border-transparent text-secondary hover:text-gray-600 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium font-semibold']"
                       :aria-current="tab.current ? 'page' : undefined">
                        {{ tab?.name }}
                    </a>
                </nav>
            </div>
        </div>
    </div>
</template>

<script>
import {defineComponent} from 'vue'

export default defineComponent({
    name: "UserTabs",
    data(){
        return {
            tabs: [{ name: 'Nutzer*innen', href: route('users'), current: route().current('users') },
                { name: 'Teams', href: route('departments'), current: route().current('departments') }]
        }
    }
})
</script>



<style scoped>

</style>
