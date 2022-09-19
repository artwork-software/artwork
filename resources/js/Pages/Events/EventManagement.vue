<template>
    <app-layout>
        <CalendarComponent :eventTypes=this.eventTypes />
    </app-layout>
</template>
<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import '@vuepic/vue-datepicker/dist/main.css'
import CalendarComponent from "@/Layouts/Components/CalendarComponent";

export default defineComponent({
    components: {
        CalendarComponent,
        AppLayout
    },
    props: ['eventTypes'],
})
</script>
