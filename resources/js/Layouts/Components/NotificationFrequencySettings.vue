<template>
    <div class="max-w-4xl">

        <div class="my-10">
            <h2 class="headline2 my-2">{{$t('Notification by e-mail')}}</h2>
            <div class="xsLight">
                {{$t('Get notified by e-mail and stay up to date - even if you are not currently online in artwork.')}}
            </div>
        </div>

        <div v-for="(settings, groupType) in notificationSettings" class="mt-10 pb-10 border-b-secondary border-b">

            <div class="flex items-start">
                <Switch @click="toggleGroup(settings,groupType)" :class="[!groupDisabled(settings) ? 'bg-artwork-buttons-create' :
                                    'bg-gray-300',
            'relative inline-flex flex-shrink-0 h-3 w-6 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none mt-1']">
                <span aria-hidden="true"
                      :class="[!groupDisabled(settings) ? 'translate-x-3' : 'translate-x-0',
                      'pointer-events-none inline-block h-2 w-2 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"/>
                </Switch>

                <div class="ml-3 ">
                    <h3 class="headline3 ">{{ $t(groupTypes[groupType].title) }}</h3>
                    <p class="xsLight mt-3">{{ $t(groupTypes[groupType].description) }}</p>
                </div>
            </div>
            <div v-if="!groupDisabled(settings)">
                <div v-for="type in settings">
                    <div class="flex justify-between mt-6 ml-9 items-start">
                        <Switch @click="toggleEmail(type)" :class="[type.enabled_email ? 'bg-artwork-buttons-create' :
                                                'bg-gray-300',
                        'relative inline-flex flex-shrink-0 h-3 w-6 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none mt-1']">
                            <span aria-hidden="true"
                                  :class="[type.enabled_email ? 'translate-x-3' : 'translate-x-0',
                                  'pointer-events-none inline-block h-2 w-2 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"/>
                        </Switch>

                        <div class="ml-3 flex-grow">
                            <h3 class="headline3">{{ $t(type.title) }}</h3>
                            <p class="xsLight mt-3">{{ $t(type.description) }}</p>
                        </div>

                        <Listbox as="div" class="flex relative">
                            <ListboxButton
                                class="border w-36 p-2 border-gray-300 bg-white relative cursor-pointer focus:outline-none sm:text-sm">
                                <div class="flex items-center my-auto justify-between">
                                        <span class="truncate font-bold">
                                            {{ $t(type.frequency_title) }}
                                        </span>
                                    <ChevronDownIcon
                                        class="h-4 w-4 text-primary float-right"
                                        aria-hidden="true"
                                    />
                                </div>
                            </ListboxButton>
                            <transition leave-active-class="transition ease-in duration-100"
                                        leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions
                                    class="absolute z-10 mt-10 bg-artwork-navigation-background shadow-lg max-h-64 p-1 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                    <div>
                                        <ListboxOption
                                            @click="changeFrequency(type, frequency)"
                                            as="template"
                                            class="max-h-8"
                                            v-slot="{ active }"
                                            v-for="frequency in notificationFrequencies"
                                            :key="frequency.value"
                                        >
                                            <li :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 px-3 text-sm subpixel-antialiased']">
                                                    <span :class="[frequency.value === type.frequency ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                         {{$t(frequency.title)}}
                                                    </span>
                                                <span :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                            <CheckIcon v-if="frequency.value === type.frequency" class="h-5 w-5 flex text-success" aria-hidden="true"/>
                                                    </span>
                                            </li>
                                        </ListboxOption>
                                    </div>
                                </ListboxOptions>
                            </transition>
                        </Listbox>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import {Switch, Listbox, ListboxButton, ListboxOptions, ListboxOption} from "@headlessui/vue";
import {ChevronDownIcon, CheckIcon} from "@heroicons/vue/solid";
import {router} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";

export default {
    mixins: [Permissions],
    name: "NotificationFrequencySettings",
    methods: {
        toggleGroup(settings, groupType) {
            if(this.groupDisabled(settings)) {
                router.patch(route('notifications.group'), {
                    groupType,
                    enabled_email: true
                }, {preserveState: true, preserveScroll: true})
                return;
            }
            router.patch(route('notifications.group'), {
                groupType,
                enabled_email: false
            }, {preserveState: true, preserveScroll: true})
        },
        groupDisabled(settings) {
            return settings.every( setting => ! setting.enabled_email);
        },
        toggleEmail(type) {
            router.patch(route('notifications.settings', {setting: type.id}), {
                enabled_email: !type.enabled_email
            }, {preserveState: true, preserveScroll: true})
        },
        changeFrequency(type, frequency) {
            router.patch(route('notifications.settings', {setting: type.id}), {
                frequency: frequency.value
            }, {preserveState: true, preserveScroll: true})
        }
    },
    props: ["notificationSettings", "notificationFrequencies", "groupTypes"],
    components: {
        Switch,
        Listbox,
        ListboxButton,
        ListboxOptions,
        ListboxOption,
        ChevronDownIcon,
        CheckIcon
    }
}
</script>

<style scoped>

</style>
