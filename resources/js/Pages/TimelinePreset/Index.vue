<template>
    <ShiftHeader>
        <div class="ml-5">
            <ShiftPresetTabs />
        </div>

        <div class="ml-5">
            <div>
                <TinyPageHeadline
                    title="Timeline Presets"
                    description="Create and manage timeline presets for your shifts."
                />
            </div>
            <div>

            </div>
        </div>

    </ShiftHeader>
</template>

<script setup>

import ShiftHeader from "@/Pages/Shifts/ShiftHeader.vue";
import ShiftPresetTabs from "@/Pages/Shifts/Components/ShiftPresetTabs.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";

const props = defineProps({})

</script>

<style scoped>

</style>