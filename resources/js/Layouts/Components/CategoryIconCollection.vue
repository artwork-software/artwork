<template>
    <svg v-if="iconName === 'orange'" xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
        <g id="abteilung_pic" transform="translate(6.629 10.449)" style="isolation: isolate">
            <circle id="Ellipse_48" data-name="Ellipse 48" cx="50" cy="50" r="50" transform="translate(-6.629 -10.449)" fill="#f79256"/>
        </g>
    </svg>
    <svg v-if="iconName === 'turquoise'" xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
        <g id="abteilung_pic" transform="translate(6.629 10.449)" style="isolation: isolate">
            <circle id="Ellipse_48" data-name="Ellipse 48" cx="50" cy="50" r="50" transform="translate(-6.629 -10.449)" fill="#59b1bf"/>
        </g>
    </svg>
    <svg v-if="iconName === 'green'" xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
        <g id="abteilung_pic" transform="translate(6.629 10.449)" style="isolation: isolate">
            <circle id="Ellipse_48" data-name="Ellipse 48" cx="50" cy="50" r="50" transform="translate(-6.629 -10.449)" fill="#8cbc54"/>
        </g>
    </svg>

</template>

<script>

export default {
    name: "CategoryIconCollection",
    components: {

    },
    props:['iconName'],
}
</script>

<style scoped>

</style>
