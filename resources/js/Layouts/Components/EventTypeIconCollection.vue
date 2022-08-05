<template>
    <!-- Important: iconName must be the same as the name of the color in tailwind css to be shown correctly in calendars -->
    <img alt="Terminart" v-if="iconName === 'eventType0'" src="/Svgs/eventTypeSvgs/eventType_color_00.svg" :width="width !== 0 ? width : 24" :height="height !== 0 ? height : 24" />
    <img alt="Terminart" v-if="iconName === 'eventType1'" src="/Svgs/eventTypeSvgs/eventType_color_01.svg" :width="width !== 0 ? width : 24" :height="height !== 0 ? height : 24" />
    <img alt="Terminart" v-if="iconName === 'eventType2'" src="/Svgs/eventTypeSvgs/eventType_color_02.svg" :width="width !== 0 ? width : 24" :height="height !== 0 ? height : 24" />
    <img alt="Terminart" v-if="iconName === 'eventType3'" src="/Svgs/eventTypeSvgs/eventType_color_03.svg" :width="width !== 0 ? width : 24" :height="height !== 0 ? height : 24" />
    <img alt="Terminart" v-if="iconName === 'eventType4'" src="/Svgs/eventTypeSvgs/eventType_color_04.svg" :width="width !== 0 ? width : 24" :height="height !== 0 ? height : 24" />
    <img alt="Terminart" v-if="iconName === 'eventType5'" src="/Svgs/eventTypeSvgs/eventType_color_05.svg" :width="width !== 0 ? width : 24" :height="height !== 0 ? height : 24" />
    <img alt="Terminart" v-if="iconName === 'eventType6'" src="/Svgs/eventTypeSvgs/eventType_color_06.svg" :width="width !== 0 ? width : 24" :height="height !== 0 ? height : 24" />
    <img alt="Terminart" v-if="iconName === 'eventType7'" src="/Svgs/eventTypeSvgs/eventType_color_07.svg" :width="width !== 0 ? width : 24" :height="height !== 0 ? height : 24" />
    <img alt="Terminart" v-if="iconName === 'eventType8'" src="/Svgs/eventTypeSvgs/eventType_color_08.svg" :width="width !== 0 ? width : 24" :height="height !== 0 ? height : 24" />
    <img alt="Terminart" v-if="iconName === 'eventType9'" src="/Svgs/eventTypeSvgs/eventType_color_09.svg" :width="width !== 0 ? width : 24" :height="height !== 0 ? height : 24" />
    <img alt="Terminart" v-if="iconName === 'eventType10'" src="/Svgs/eventTypeSvgs/eventType_color_10.svg" :width="width !== 0 ? width : 24" :height="height !== 0 ? height : 24" />

</template>

<script>

export default {
    name: "CategoryIconCollection",
    components: {

    },
    props:['iconName','width','height'],
}
</script>

<style scoped>

</style>
