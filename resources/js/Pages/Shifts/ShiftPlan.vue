<template>
    <app-layout>
        <div>
            {{events}}
        </div>
    </app-layout>
</template>
<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import '@vuepic/vue-datepicker/dist/main.css'
import CalendarComponent from "@/Layouts/Components/CalendarComponent";
import IndividualCalendarComponent from "@/Layouts/Components/IndividualCalendarComponent.vue";
import IndividualCalendarAtGlanceComponent from "@/Layouts/Components/IndividualCalendarAtGlanceComponent.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import {Inertia} from "@inertiajs/inertia";
import Permissions from "@/mixins/Permissions.vue";

export default defineComponent({
    mixins: [Permissions],
    components: {
        AppLayout
    },
    props: [
        'events'
    ],
    methods: {
    },
    data() {
        return {
        }
    },
})
</script>
