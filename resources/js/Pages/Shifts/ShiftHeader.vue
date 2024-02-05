<template>
    <app-layout>
        <div class="ml-9 max-h-screen overflow-hidden">
           <div class="ml-5">
               <h2 class="headline1">Dienstpläne & Schichtvorlagen</h2>
               <ShiftTabs />
           </div>
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
