<template>
    <app-layout>
        <div class="max-w-screen-lg mt-12 flex flex-row ml-14 mr-40">
            <div class="flex flex-1 flex-wrap">
                <div class="w-full flex justify-between">
                    <div class="flex flex-wrap">
                        <h2 class="text-3xl font-black font-lexend flex w-full">Raumbelegungen</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="mr-4 ml-14">
            <CalendarComponent :eventTypes=this.eventTypes />
        </div>
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
