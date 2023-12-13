<script>
import {defineComponent} from 'vue'
import {CheckIcon} from "@heroicons/vue/outline";
import VueMathjax from "vue-mathjax-next";
import ChooseUserSeriesShift from "@/Pages/Projects/Components/ChooseUserSeriesShift.vue";
import Helper from "../../../mixins/Helper.vue";

export default defineComponent({
    name: "ShiftDropElement",
    components: {ChooseUserSeriesShift, CheckIcon, VueMathjax},
    props: ['shift','showRoom','event','room', 'maxCount', 'currentCount', 'freeEmployeeCount', 'freeMasterCount','highlightMode','highlightedId','highlightedType'],
    mixins: [Helper],
    computed: {
        shiftUserIds(){
            const ids = {
                userIds: [],
                freelancerIds: [],
                providerIds: []
            }
            this.shift.allUsers.users.forEach(user => {
                ids.userIds.push(user.id)
            })

            this.shift.allUsers.freelancers?.forEach((freelancer) => {
                ids.freelancerIds.push(freelancer.id)
            })

            this.shift.allUsers.service_providers?.forEach((provider) => {
                ids.providerIds.push(provider.id)
            })

            return ids;
        }
    },
    data(){
        return {
            showChooseUserSeriesShiftModal: false,
            buffer: {
                onlyThisDay: false,
                start: null,
                end: null,
                dayOfWeek: null
            },
            selectedUser: null
        }
    },
    methods: {
        gcd(a, b) {
            return (b) ? this.gcd(b, a % b) : a;
        },
        /*decimalToFraction(decimal) {
            let wholePart = Math.floor(decimal);
            decimal = decimal - wholePart;

            if (decimal === parseInt(decimal)) {
                if (decimal < 1) {
                    return `${wholePart}`;
                }
                return `${parseInt(decimal)}/1`;
            } else {
                let precision = this.getFirstDigitAfterDecimal(decimal) === 3 ? 3 : 1000; // The desired precision for the fraction
                let top = Math.round(decimal * precision);
                let bottom = precision;

                let x = this.gcd(top, bottom);
                return `${wholePart} ${top / x}/${bottom / x}`;
            }
        },*/
        getFirstDigitAfterDecimal(number) {
            const decimalPart = number.toString().split('.')[1];
            if (decimalPart && decimalPart.length > 0) {
                return parseInt(decimalPart[0]);
            }
            return null; // Return null if there is no decimal part
        },
        convertToMathJax(fraction) {
            const parts = fraction.split(' ');

            if (parts.length === 1) {
                return `${parts[0]}`;
            } else {
                const wholePart = parts[0] > 0
                    ? parts[0]
                    : "";
                const fractionParts = parts[1].split('/');
                const numerator = fractionParts[0];
                const denominator = fractionParts[1];
                return `${wholePart}$\\frac{${numerator}}{${denominator}}$`;
            }
        },
        onDragOver(event) {
            event.preventDefault();
        },
        onDrop(event) {
            event.preventDefault();
            this.selectedUser = event.dataTransfer.getData('application/json');
            if(this.event.is_series){
                this.showChooseUserSeriesShiftModal = true
            } else {
                this.saveUser();
            }
        },
        changeBuffer(buffer){
            this.buffer = buffer
            this.showChooseUserSeriesShiftModal = false
            this.saveUser();
        },
        decimalToFraction(decimal) {
            // Überprüfen, ob die Zahl bereits eine ganze Zahl ist
            if (decimal % 1 === 0) {
                // Zahl ist eine ganze Zahl, also einfach zurückgeben
                return decimal.toString();
            } else {
                // Die Zahl ist eine Dezimalzahl, also in Bruch umwandeln
                const tolerance = 1.0E-6;
                let numerator = 1;
                let denominator = 1;
                let lower_n = 0;
                let lower_d = 1;
                let upper_n = 1;
                let upper_d = 0;
                let fraction = decimal;

                while (denominator <= 10000 && Math.abs(numerator / denominator - fraction) > tolerance) {
                    if (fraction < numerator / denominator) {
                        upper_n = numerator;
                        upper_d = denominator;
                        denominator = lower_d + upper_d;
                        numerator = lower_n + upper_n;
                    } else {
                        lower_n = numerator;
                        lower_d = denominator;
                        denominator = lower_d + upper_d;
                        numerator = lower_n + upper_n;
                    }
                }

                // Vereinfache den Bruch, falls möglich
                const gcd = function(a, b) {
                    if (!b) return a;
                    return gcd(b, a % b);
                };
                const greatestCommonDivisor = gcd(numerator, denominator);
                numerator /= greatestCommonDivisor;
                denominator /= greatestCommonDivisor;

                return `${numerator}/${denominator}`;
            }
        },
        isIdHighlighted(highlightedId, highlightedType) {
            // Map the highlightedType to the correct property in shiftUserIds
            const typeMap = {
                0: 'userIds',
                1: 'freelancerIds',
                2: 'providerIds'
            };


            if(highlightedId){
                // Get the correct array from shiftUserIds based on the highlightedType
                const arrayToCheck = this.shiftUserIds[typeMap[highlightedType]];

                // Check if the array contains the highlightedId
                return arrayToCheck.includes(highlightedId);
            }else{
                return false;
            }

        },
        saveUser(){
            let dropElement = this.selectedUser;
            dropElement = JSON.parse(dropElement)[0];

            if(this.maxCount === this.currentCount){
                return;
            }

            if(dropElement.master && this.freeMasterCount === 0 && this.freeEmployeeCount === 0){
                return;
            }

            if(!dropElement.master && this.freeEmployeeCount === 0){
                return;
            }

            if(dropElement.type === 0){
                if(this.shiftUserIds.userIds.includes(dropElement.id)){
                    return;
                }
            }

            if(dropElement.type === 1){
                if(this.shiftUserIds.freelancerIds.includes(dropElement.id)){
                    return;
                }
            }

            if(dropElement.type === 2){
                if(this.shiftUserIds.providerIds.includes(dropElement.id)){
                    return;
                }
            }

            if(dropElement.master && dropElement.type === 0 && this.freeMasterCount > 0){
                this.$inertia.post(route('add.shift.master', {shift: this.shift.id, user: dropElement.id}), {
                        user_id: dropElement.id,
                        chooseData: this.buffer
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )

            } else if (dropElement.type === 0 && !dropElement.master || this.freeMasterCount === 0 && dropElement.master ) {
                this.$inertia.post(route('add.shift.user', {shift: this.shift.id, user: dropElement.id}), {
                        chooseData: this.buffer
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )
            }

            if(dropElement.type === 1 && !dropElement.master){
                this.$inertia.post(route('add.shift.freelancer', {shift: this.shift.id, freelancer: dropElement.id}), {
                        chooseData: this.buffer
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )
            } else if (dropElement.type === 1 && dropElement.master) {
                this.$inertia.post(route('add.shift.freelancer.master', {shift: this.shift.id, freelancer: dropElement.id}), {
                        freelancer_id: dropElement.id,
                        chooseData: this.buffer
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )
            }

            if(dropElement.type === 2 && dropElement.master){
                this.$inertia.post(route('add.shift.provider.master', {shift: this.shift.id, serviceProvider: dropElement.id}), {
                        service_provider_id: dropElement.id,
                        chooseData: this.buffer
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )
            } else if (dropElement.type === 2 && !dropElement.master) {
                this.$inertia.post(route('add.shift.provider', {shift: this.shift.id, serviceProvider: dropElement.id}), {
                        chooseData: this.buffer
                    }, {
                        preserveState: true,
                        preserveScroll: true,
                    }
                )
            }
        }
    }
})
</script>

<template>
    <div :class="highlightMode && !isIdHighlighted(highlightedId, highlightedType) ? 'opacity-30' : ''" class="flex items-center xsLight text-shiftText subpixel-antialiased" @dragover="onDragOver" @drop="onDrop">
        <div>
            {{ shift.craft.abbreviation }} {{ shift.start }} - {{ shift.end }}
        </div>
        <div v-if="!showRoom" class="ml-0.5 text-xs">
             ({{ decimalToCommonFraction(shift.user_count) }}/{{ shift.number_employees }}
            <span v-if="shift.number_masters > 0">| {{ shift.master_count }}/{{ shift.number_masters }}</span>)
        </div>
        <div v-else-if="room" class="truncate">
            , {{room?.name}}
        </div>
        <div v-if="shift.empty_employee_count === 0 && shift.empty_master_count === 0">
            <CheckIcon class="h-5 w-5 flex text-success" aria-hidden="true"/>
        </div>
    </div>


    <ChooseUserSeriesShift v-if="showChooseUserSeriesShiftModal" @close-modal="showChooseUserSeriesShiftModal = false" @returnBuffer="changeBuffer" />

</template>

<style scoped>

</style>
