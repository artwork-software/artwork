<template>
    <Head>
        <link rel="icon" type="image/png" :href="usePage().props.small_logo" />
        <title>{{ title }} - {{ usePage().props.page_title }}</title>
    </Head>
    <div class="artwork relative">
        <div v-if="pushNotifications.length > 0" class="absolute top-16 right-5">
            <div v-for="pushNotification in pushNotifications" :id="pushNotification.id"
                 class="my-2 z-50 flex relative w-full max-w-xs rounded-lg shadow bg-lightBackgroundGray"
                 role="alert">
                <div class="flex p-4">
                    <div class="inline-flex flex-shrink-0 justify-center items-center rounded-lg">
                        <img alt="Notification" v-if="pushNotification.type === 'success'"
                             class="h-9 w-9" src="/Svgs/IconSvgs/icon_push_notification_green.svg"/>
                        <img alt="Notification" v-if="pushNotification.type === 'error'" class="h-9 w-9"
                             src="/Svgs/IconSvgs/icon_push_notification_red.svg"/>
                    </div>
                    <div class="ml-4 xsDark">{{ pushNotification.message }}</div>
                </div>
                <button type="button" class="-mt-4 mr-2">
                    <component :is="IconX" class="-mt-4 h-5 w-5 text-secondary hover:text-error relative"
                           @click="closePushNotification(pushNotification.id)"/>
                </button>
            </div>
        </div>


        <SubMenu />

        <main class="lg:pl-20 xl:pl-20 pb-20">
            <div class="artwork relative" id="main-content-wrapper">

                <PopupChat v-if="$page.props.auth.user.use_chat"/>
                <slot></slot>
            </div>
        </main>
    </div>
</template>

<script setup>
import {Head, router, usePage} from "@inertiajs/vue3"
import SubMenu from "@/Layouts/SubMenu.vue";
import {defineAsyncComponent, onBeforeMount, onMounted, onUnmounted, ref, watchEffect} from "vue";
import {reloadRolesAndPermissions} from "laravel-permission-to-vuejs";
import {useI18n} from "vue-i18n";
const { locale } = useI18n();

const props = defineProps({
    title: {
        type: String,
        default: 'Dashboard'
    },
})

watchEffect(() => {
    window.Laravel = window.Laravel || {}
    if (usePage().props?.permissions) {
        window.Laravel.jsPermissions = usePage().props.permissions;
    }
})

const pushNotifications = ref([])

const closePushNotification = (id) => {
    const pushNotification = document.getElementById(id);
    pushNotification?.remove();
}

onBeforeMount(() => {
    /**
     * i think this is unnecessary, but it is here to ensure that the calendar settings are set correctly

    if ( (route().current('events') === false && route().current('shifts.plan') === false) && usePage().props.auth.user.calendar_settings.use_project_time_period){
        let desiredRoute = route('user.calendar_settings.toggle_calendar_settings_use_project_period');
        let payload = {
            use_project_time_period: false,
            project_id: 0,
            is_axios: true
        };

        axios.patch(desiredRoute, payload);
    }
     */
    reloadRolesAndPermissions()
})

onMounted(() => {
    document.documentElement.lang = usePage().props.auth.user.language
    locale.value = usePage().props.auth.user.language
    window.Echo.private(`notifications.${usePage().props.auth.user.id}`)
        .listen('.incoming-notification', (notification) => {
            pushNotifications.value.push(notification.message);
            setTimeout(() => {
                closePushNotification(notification.message.id)
            }, 3000)
        });


})

const PopupChat = defineAsyncComponent({
    loader: () => import('@/Components/Chat/PopupChat.vue'),
    delay: 0,
    timeout: 3000,
})

</script>
