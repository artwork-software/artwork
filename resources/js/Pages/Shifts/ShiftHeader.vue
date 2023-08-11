<template>
    <app-layout>
        <div class="ml-14">
            <h2 class="headline1">Dienstpläne & Schichtvorlagen</h2>

            <ShiftTabs />

            <slot></slot>
        </div>



    </app-layout>
</template>
<script>
import {defineComponent} from 'vue'
import ShiftTabs from "@/Pages/Shifts/Components/ShiftTabs.vue";
import AppLayout from "@/Layouts/AppLayout.vue";

export default defineComponent({
    name: "ShiftHeader",
    components: {AppLayout, ShiftTabs}
})
</script>


<style scoped>

</style>
