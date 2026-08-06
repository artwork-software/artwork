<template>
    <div class="">

        <div class="my-10">
            <h2 class="font-lexend font-semibold text-[clamp(18px,2.5vw,20px)]/[25px] text-text my-2">{{$t('Notification by push message')}}</h2>
            <div class="text-sm/5 font-bold text-text-subtle">
                {{$t('Get notified via push notifications and stay up to date.')}}
            </div>
        </div>

        <div v-for="(settings, groupType) in notificationSettings" class="mt-10 pb-10 border-b-secondary border-b">

            <div class="flex items-start">
                <Switch @click="toggleGroup(settings,groupType)" :class="[!groupDisabled(settings) ? 'bg-accent-600' :
                                    'bg-border',
            'relative inline-flex flex-shrink-0 h-3 w-6 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none mt-1']">
                <span aria-hidden="true"
                      :class="[!groupDisabled(settings) ? 'translate-x-3' : 'translate-x-0',
                      'pointer-events-none inline-block h-2 w-2 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"/>
                </Switch>

                <div class="ml-3 ">
                    <h3 class="font-lexend font-semibold text-[clamp(16px,2vw,18px)]/[21px] text-text ">{{ $t(groupTypes[groupType].title) }}</h3>
                    <p class="text-sm/5 font-bold text-text-subtle mt-3">{{ $t(groupTypes[groupType].description) }}</p>
                </div>
            </div>
            <div v-if="!groupDisabled(settings)">
                <div v-for="type in settings">
                    <div class="flex justify-between mt-6 ml-9 items-start">
                        <Switch @click="togglePush(type)" :class="[type.enabled_push ? 'bg-accent-600' :
                                                'bg-border',
                        'relative inline-flex flex-shrink-0 h-3 w-6 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none mt-1']">
                            <span aria-hidden="true"
                                  :class="[type.enabled_push ? 'translate-x-3' : 'translate-x-0',
                                  'pointer-events-none inline-block h-2 w-2 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"/>
                        </Switch>

                        <div class="ml-3 flex-grow">
                            <h3 class="font-lexend font-semibold text-[clamp(16px,2vw,18px)]/[21px] text-text">{{ $t(type.title) }}</h3>
                            <p class="text-sm/5 font-bold text-text-subtle mt-3">{{ $t(type.description) }}</p>
                        </div>


                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import {IconCheck, IconChevronDown} from "@tabler/icons-vue";
import {Switch, Listbox, ListboxButton, ListboxOptions, ListboxOption} from "@headlessui/vue";
import {router} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";

export default {
    mixins: [Permissions],
    name: "NotificationPushSettings",
    methods: {
        toggleGroup(settings, groupType) {
            if(this.groupDisabled(settings)) {
                router.patch(route('notifications.group'), {
                    groupType,
                    enabled_push: true
                }, {preserveState: true, preserveScroll: true})
                return;
            }
            router.patch(route('notifications.group'), {
                groupType,
                enabled_push: false
            }, {preserveState: true, preserveScroll: true})
        },
        groupDisabled(settings) {
            return settings.every( setting => ! setting.enabled_push);
        },
        togglePush(type) {
            router.patch(route('notifications.settings', {setting: type.id}), {
                enabled_push: !type.enabled_push
            }, {preserveState: true, preserveScroll: true})
        },
    },
    props: ["notificationSettings", "groupTypes"],
    components: {
        Switch,
        Listbox,
        ListboxButton,
        ListboxOptions,
        ListboxOption,
        IconChevronDown,
        IconCheck
    }
}
</script>

<style scoped>

</style>
