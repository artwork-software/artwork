<template>
    <svg v-if="svgName === 'arrowLeft'" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="13.065" height="7" viewBox="0 0 13.065 7">
        <defs>
            <clipPath id="clip-path">
                <rect id="Rechteck_147" data-name="Rechteck 147" width="13.065" height="7" fill="#a7a6b1"/>
            </clipPath>
        </defs>
        <g id="Gruppe_174" data-name="Gruppe 174" clip-path="url(#clip-path)">
            <path id="Pfad_161" data-name="Pfad 161" d="M12.974,5.782c-.855-1.718-2.834-2.737-4.606-3.239a10.281,10.281,0,0,0-5.25-.082l.93-1.254A.808.808,0,0,0,3.76.109.821.821,0,0,0,2.663.4L.128,3.813A.807.807,0,0,0,.82,5.021a3.8,3.8,0,0,1,1.773.395,4.723,4.723,0,0,1,.462.269,1.939,1.939,0,0,1,.3.22,4.507,4.507,0,0,1,.356.34c.056.06.109.123.163.184l.047.057c.026.035.052.071.077.107a.824.824,0,0,0,1.1.288.809.809,0,0,0,.288-1.1A5.628,5.628,0,0,0,3.411,4.044a8.658,8.658,0,0,1,4.4.012,5.75,5.75,0,0,1,3.775,2.537c.46.925,1.845.113,1.386-.81" transform="translate(0 0)" fill="#a7a6b1"/>
        </g>
    </svg>
    <svg v-else-if="svgName === 'departmentImagePlaceholder'" xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
        <g id="abteilung_pic" transform="translate(6.629 10.449)" style="isolation: isolate">
            <circle id="Ellipse_48" data-name="Ellipse 48" cx="50" cy="50" r="50" transform="translate(-6.629 -10.449)" fill="#f5cac3"/>
            <path id="Polygon_1" data-name="Polygon 1" d="M22.922,0,45.845,43.935H0Z" transform="translate(13.763 19.494)" fill="#fcfcfb" opacity="0.413"/>
            <path id="Polygon_2" data-name="Polygon 2" d="M19.58,0l19.58,35.339H0Z" transform="translate(37.641 28.09)" fill="#fcfcfb" opacity="0.413"/>
            <circle id="Ellipse_39" data-name="Ellipse 39" cx="5.56" cy="5.56" r="5.56" transform="translate(9.943 15.674)" fill="#fcfcfb" opacity="0.66"/>
        </g>
    </svg>
    <svg v-else-if="svgName === 'bgCircles'" id="Gruppe_197" data-name="Gruppe 197" xmlns="http://www.w3.org/2000/svg" width="110.26" height="194.684" viewBox="0 0 110.26 194.684">
        <g id="Gruppe_229" data-name="Gruppe 229">
            <path id="Pfad_193" data-name="Pfad 193" d="M97.342,193.684A96.342,96.342,0,0,1,97.342,1h12.9" fill="none" stroke="#ededec" stroke-width="2"/>
            <path id="Pfad_194" data-name="Pfad 194" d="M96.807,164.93a67.588,67.588,0,1,1,0-135.177H110.26" fill="none" stroke="#ededec" stroke-width="2"/>
            <path id="Pfad_195" data-name="Pfad 195" d="M97.342,135.788a38.446,38.446,0,1,1,0-76.892h12.487" fill="none" stroke="#ededec" stroke-width="2"/>
        </g>
    </svg>
    <svg v-else-if="svgName === 'teamIconTech'" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="40" height="40" viewBox="0 0 40 40">
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
    <svg v-else-if="svgName === 'arrowRight'" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="27.74" height="9.172" viewBox="0 0 27.74 9.172">
        <defs>
            <clipPath id="clip-path">
                <rect id="Rechteck_142" data-name="Rechteck 142" width="27.74" height="9.172" transform="translate(0 0)" fill="#27233c"/>
            </clipPath>
        </defs>
        <g id="Gruppe_162" data-name="Gruppe 162" transform="translate(0 0)">
            <g id="Gruppe_161" data-name="Gruppe 161" transform="translate(0 0)" clip-path="url(#clip-path)">
                <path id="Pfad_151" data-name="Pfad 151" d="M11.165,1.962a2.7,2.7,0,0,1,.494.228c.147.119.036.31.01.381l-.039.093c-.042.086-.085.171-.133.255-.148.263-.319.513-.5.778a7.871,7.871,0,0,0-.715,1.191,2.083,2.083,0,0,0-.2,1.37,1.452,1.452,0,0,0,1.4,1.133A7.3,7.3,0,0,0,12.485,7.2c.12-.029.24-.058.359-.084q.6-.131,1.193-.274c.624-.15,1.28-.32,1.95-.506.277-.077.552-.156.828-.236.628-.181,1.278-.369,1.922-.515.2-.046.4-.086.589-.12.1-.017.2-.033.3-.048l.191-.023a10.727,10.727,0,0,1,1.318-.061,23.144,23.144,0,0,1,3.161.3A10.646,10.646,0,0,0,22.4,7.806a.83.83,0,0,0-.063.846.976.976,0,0,0,.81.517l.054,0a.888.888,0,0,0,.754-.416,8.778,8.778,0,0,1,3.316-2.97.874.874,0,0,0,.431-.55.9.9,0,0,0-.094-.714,10.391,10.391,0,0,0-3.366-3.335.848.848,0,0,0-.865-.035.978.978,0,0,0-.5.823.87.87,0,0,0,.433.777,8.36,8.36,0,0,1,1.419,1.094,19.606,19.606,0,0,0-4.712-.3,19.657,19.657,0,0,0-3.775.819c-.292.084-.583.167-.876.248-1.051.289-2.162.559-3.311.8.1-.166.2-.328.3-.481.064-.1.132-.2.2-.3a4.113,4.113,0,0,0,.991-2.435C13.5,1.028,12.446.215,10.738.032A10.82,10.82,0,0,0,7.274.413L6.748.527a20.428,20.428,0,0,1-5.813.527A.833.833,0,0,0,.3,1.287a.945.945,0,0,0-.3.677.924.924,0,0,0,.886.91A19.7,19.7,0,0,0,5.745,2.58c.5-.087,1.006-.2,1.5-.305.689-.149,1.4-.3,2.056-.39a5.9,5.9,0,0,1,1.863.077" transform="translate(0 0)" fill="#27233c"/>
            </g>
        </g>
    </svg>

</template>

<script>

export default {
    name: "SvgCollection",
    components: {

    },
    props:['svgName'],
}
</script>

<style scoped>

</style>
