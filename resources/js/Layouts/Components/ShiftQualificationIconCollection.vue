<template>
    <div>
        <UserIcon v-if="this.iconName === 'user-icon'"/>
        <AcademicCapIcon v-if="this.iconName === 'academic-cap-icon'"/>
        <BellIcon v-else-if="this.iconName === 'bell-icon'"/>
        <ChatIcon v-else-if="this.iconName === 'chat-icon'"/>
        <AdjustmentsIcon v-else-if="this.iconName === 'adjustments-icon'"/>
        <BookOpenIcon v-else-if="this.iconName === 'book-open-icon'"/>
        <BriefcaseIcon v-else-if="this.iconName === 'briefcase-icon'"/>
        <CameraIcon v-else-if="this.iconName === 'camera-icon'"/>
        <ClipboardIcon v-else-if="this.iconName === 'clipboard-icon'"/>
        <EyeIcon v-else-if="this.iconName === 'eye-icon'"/>
        <FilmIcon v-else-if="this.iconName === 'film-icon'"/>
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
        'iconName'
    ],
}
</script>
