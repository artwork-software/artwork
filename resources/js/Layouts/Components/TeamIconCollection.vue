<template>
    <svg v-if="iconName === 'teamIconTech'" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="40" height="40" viewBox="0 0 40 40">
        <defs>
            <clipPath id="clip-path">
                <rect id="Rechteck_165" data-name="Rechteck 165" width="24.215" height="26.947" transform="translate(0 0)" fill="#27233c"/>
            </clipPath>
        </defs>
        <g id="abteilung_pic" transform="translate(6.629 10.449)" style="isolation: isolate">
            <circle id="Ellipse_48" data-name="Ellipse 48" cx="20" cy="20" r="20" transform="translate(-6.629 -10.449)" fill="#337aaa"/>
            <g id="Gruppe_251" data-name="Gruppe 251" transform="translate(3.371 -3.923)">
                <g id="Gruppe_250" data-name="Gruppe 250" clip-path="url(#clip-path)">
                    <path id="Pfad_230" data-name="Pfad 230" d="M13.777,21.94a7.041,7.041,0,0,0,7.956-7.956Z" fill="#fcfcfb"/>
                    <path id="Pfad_231" data-name="Pfad 231" d="M14.663,4.915l-.01.009L10.617.887a3.03,3.03,0,0,0-4.285,0L.887,6.333a3.029,3.029,0,0,0,0,4.284l1.131,1.131v15.2H5.083v-12l4.666,9.273L24.215,9.749ZM3.55,10.527a2.1,2.1,0,1,1,2.1-2.1,2.1,2.1,0,0,1-2.1,2.1m16.654-.47a.6.6,0,0,1-.46-.025L16.773,8.606a.6.6,0,1,1,.52-1.086l2.972,1.426a.6.6,0,0,1-.061,1.111" fill="#27233c" opacity="0.8"/>
                </g>
            </g>
        </g>
    </svg>
    <svg v-else-if="iconName === 'departmentImagePlaceholder'" xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
        <g id="abteilung_pic" transform="translate(6.629 10.449)" style="isolation: isolate">
            <circle id="Ellipse_48" data-name="Ellipse 48" cx="50" cy="50" r="50" transform="translate(-6.629 -10.449)" fill="#f5cac3"/>
            <path id="Polygon_1" data-name="Polygon 1" d="M22.922,0,45.845,43.935H0Z" transform="translate(13.763 19.494)" fill="#fcfcfb" opacity="0.413"/>
            <path id="Polygon_2" data-name="Polygon 2" d="M19.58,0l19.58,35.339H0Z" transform="translate(37.641 28.09)" fill="#fcfcfb" opacity="0.413"/>
            <circle id="Ellipse_39" data-name="Ellipse 39" cx="5.56" cy="5.56" r="5.56" transform="translate(9.943 15.674)" fill="#fcfcfb" opacity="0.66"/>
        </g>
    </svg>
    <img v-else-if="iconName === 'vermietung'" src="/vermietung_test.svg" alt="vermietung">
</template>

<script>

export default {
    name: "TeamIconCollection",
    components: {

    },
    props:['iconName'],
}
</script>

<style scoped>

</style>
