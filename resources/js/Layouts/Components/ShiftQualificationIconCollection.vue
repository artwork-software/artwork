<template>
    <div>
        <IconUser v-if="this.iconName === 'user-icon'" stroke-width="1.5" :class="classes"/>
        <IconBrandRedhat v-if="this.iconName === 'academic-cap-icon'" stroke-width="1.5" :class="classes"/>
        <IconBell v-else-if="this.iconName === 'bell-icon'" stroke-width="1.5" :class="classes"/>
        <IconMessageDots v-else-if="this.iconName === 'chat-icon'" stroke-width="1.5" :class="classes"/>
        <IconAdjustmentsAlt v-else-if="this.iconName === 'adjustments-icon'" stroke-width="1.5" :class="classes"/>
        <IconBook v-else-if="this.iconName === 'book-open-icon'" stroke-width="1.5"/>
        <IconBriefcase v-else-if="this.iconName === 'briefcase-icon'" stroke-width="1.5" :class="classes"/>
        <IconCamera v-else-if="this.iconName === 'camera-icon'" stroke-width="1.5" :class="classes"/>
        <IconClipboard v-else-if="this.iconName === 'clipboard-icon'" stroke-width="1.5" :class="classes"/>
        <IconEye v-else-if="this.iconName === 'eye-icon'" stroke-width="1.5" :class="classes"/>
        <IconMovie v-else-if="this.iconName === 'film-icon'"  stroke-width="1.5" :class="classes"/>
    </div>
</template>

<script>
import {
    UserIcon,
    AcademicCapIcon,
    BellIcon,
    ChatIcon,
    AdjustmentsIcon,
    BookOpenIcon,
    BriefcaseIcon,
    CameraIcon,
    ClipboardIcon,
    EyeIcon,
    FilmIcon
} from "@heroicons/vue/outline";
import IconLib from "@/Mixins/IconLib.vue";

export default {
    name: "ShiftQualificationIconCollection",
    components: {
        UserIcon,
        AcademicCapIcon,
        BellIcon,
        ChatIcon,
        AdjustmentsIcon,
        BookOpenIcon,
        BriefcaseIcon,
        CameraIcon,
        ClipboardIcon,
        EyeIcon,
        FilmIcon
    },
    props: [
        'iconName', 'classes'
    ],
    mixins: [IconLib],
}
</script>
