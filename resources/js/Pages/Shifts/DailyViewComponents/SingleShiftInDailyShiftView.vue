<template>
    <div class="grid grid-cols-1 md:grid-cols-2 mb-2 w-full rounded-lg" :style="{ backgroundColor: shift.craft.color + '50' }">
        <div class="flex items-center gap-x-2">
            <div class="bg-gray-500 py-1.5 px-2 rounded-l-lg" :style="{ backgroundColor: shift.craft.color + '90' }">{{ shift.start }} - {{ shift.end }}</div>
            <div class="text-gray-700 font-semibold">{{ shift.craft.abbreviation }}: {{ shift.craft.name }}</div>
        </div>
        <div class="flex justify-between items-center w-full px-3">
            <div class="flex items-center gap-x-2">
                <div v-for="qualification in shift.shifts_qualifications">
                    <div class="text-gray-500 text-[10px] flex items-center gap-x-1 group hover:bg-gray-50 ring-inset hover:ring-1 ring-artwork-buttons-create p-1 rounded-lg transition-all duration-150 ease-in-out cursor-pointer hover:text-artwork-buttons-create">
                        <component :is="findShiftQualification(qualification.shift_qualification_id)?.icon" class="size-3" />
                        <div>
                            0/{{ qualification.value }}
                        </div>
                        {{ findShiftQualification(qualification.shift_qualification_id)?.name || 'Unbekannte Qualifikation' }}
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end px-3">
                <component is="IconChevronDown" class="size-5 text-gray-500 hover:text-gray-700 cursor-pointer transition-all duration-150 ease-in-out" />
            </div>
        </div>
    </div>

    <pre>
        {{ crafts }}
    </pre>
</template>

<script setup>

const props = defineProps({
    shift: {
        type: Object,
        required: true
    },
    shiftQualifications: {
        type: Array,
        required: true
    },
    crafts: {
        type: Object,
        required: true
    }
})

const findShiftQualification = (qualificationId) => {
    return props.shiftQualifications.find(q => q.id === qualificationId);
}


</script>

<style scoped>

</style>