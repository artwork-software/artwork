<template>
    <div>
        <div>
            <div class="text-secondaryHover xsWhiteBold px-1" :class="eventType.svg_name">
                {{ eventType.abbreviation }}: {{ project?.name }}
            </div>
            <div class="bg-backgroundGray">
                <div v-for="shift in event.shifts" class="flex justify-between px-1">
                    <!-- Drop Element --->
                    <ShiftDropElement :shift="shift" />
                </div>
            </div>
        </div>
    </div>
</template>
<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Permissions from "@/mixins/Permissions.vue";
import {CheckIcon} from "@heroicons/vue/outline";
import ShiftDropElement from "@/Layouts/Components/ShiftPlanComponents/ShiftDropElement.vue";

export default defineComponent({
    mixins: [Permissions],
    components: {
        ShiftDropElement,
        CheckIcon,
    },
    props: [
        'event',
        'project',
        'eventType'
    ],
    methods: {

    },
    data() {
        return {}
    },
})

</script>

<style scoped>

.eventType0 {
    background-color: #7F7E88;
}

.eventType1 {
    background-color: #631D53;
}

.eventType2 {
    background-color: #D84387;
}

.eventType3 {
    background-color: #E97A45;
}

.eventType4 {
    background-color: #CB8913;
}

.eventType5 {
    background-color: #648928;
}

.eventType6 {
    background-color: #35A965;
}

.eventType7 {
    background-color: #35ACB2;
}

.eventType8 {
    background-color: #2290C1;
}

.eventType9 {
    background-color: #50908E;
}

.eventType10 {
    background-color: #23485B;
}
</style>
