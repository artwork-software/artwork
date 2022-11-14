<template>
    <div class="max-w-3xl">
        <h2 class="headline2 my-2">Benachrichtigung per E-Mail</h2>
        <div class="xsLight">
            Lass dich per E-Mail benachrichtigen und bleibe auf dem neusten Stand – auch wenn du gerade nicht im artwork
            online bist.
        </div>

        <div v-for="settings in notificationSettings" class="mt-10 pb-10 border-b-secondary border-b">

            <div class="flex items-start">
                <Switch :class="[settings.enabled ? 'bg-buttonBlue' :
                                    'bg-gray-300',
            'relative inline-flex flex-shrink-0 h-3 w-6 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none mt-1']">
                <span aria-hidden="true"
                      :class="[settings.enabled ? 'translate-x-3' : 'translate-x-0',
                      'pointer-events-none inline-block h-2 w-2 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"/>
                </Switch>

                <div class="ml-3 ">
                    <h3 class="headline3 ">{{ settings.group_title }}</h3>
                    <p class="xsLight mt-3">{{ settings.description }}</p>
                </div>
            </div>

            <div v-for="types in settings.types">
                <div class="flex justify-between mt-6 ml-9 items-start">
                    <Switch :class="[types.enabled ? 'bg-buttonBlue' :
                                    'bg-gray-300',
            'relative inline-flex flex-shrink-0 h-3 w-6 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none mt-1']">
                <span aria-hidden="true"
                      :class="[types.enabled ? 'translate-x-3' : 'translate-x-0',
                      'pointer-events-none inline-block h-2 w-2 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200']"/>
                    </Switch>

                    <div class="ml-3 flex-grow">
                        <h3 class="headline3">{{ types.type_title }}</h3>
                        <p class="xsLight mt-3">{{ settings.description }}</p>
                    </div>

                    <Listbox as="div" class="flex relative">
                        <ListboxButton
                            class="border w-32 p-2 border-gray-300 bg-white relative cursor-pointer focus:outline-none sm:text-sm">
                            <div class="flex items-center my-auto justify-between">
                            <span class="truncate font-bold">
                                {{ types.frequency }}
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
                                class="absolute z-10 mt-10 bg-primary shadow-lg max-h-64 p-1 text-base ring-1 ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                <div>
                                    <ListboxOption as="template" class="max-h-8"
                                                   v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 px-3 text-sm subpixel-antialiased']">
                                        <span
                                            :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                             Sofort
                                        </span>
                                            <span
                                                :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                        <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                   aria-hidden="true"/>
                                        </span>
                                        </li>
                                    </ListboxOption>
                                    <ListboxOption as="template" class="max-h-8"
                                                   v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 px-3 text-sm subpixel-antialiased']">
                                        <span
                                            :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                             1x Täglich
                                        </span>
                                            <span
                                                :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                        <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                   aria-hidden="true"/>
                                        </span>
                                        </li>
                                    </ListboxOption>
                                    <ListboxOption as="template" class="max-h-8"
                                                   v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 px-3 text-sm subpixel-antialiased']">
                                        <span
                                            :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                             1x Wöchentlich
                                        </span>
                                            <span
                                                :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                        <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                   aria-hidden="true"/>
                                            </span>
                                        </li>
                                    </ListboxOption>

                                    <ListboxOption as="template" class="max-h-8"
                                                   v-slot="{ active, selected }">
                                        <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 px-3 text-sm subpixel-antialiased']">
                                        <span
                                            :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                             2x Wöchentlich
                                        </span>
                                            <span
                                                :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center text-sm subpixel-antialiased']">
                                                        <CheckIcon v-if="selected" class="h-5 w-5 flex text-success"
                                                                   aria-hidden="true"/>
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
</template>

<script>
import {Switch, Listbox, ListboxButton, ListboxOptions, ListboxOption} from "@headlessui/vue";
import {ChevronDownIcon, CheckIcon} from "@heroicons/vue/solid";

export default {
    name: "NotificationFrequencySettings",
    data() {
        return {
            notificationSettings: [
                {
                    enabled: true,
                    group_title: "Raumbelegungen & Termine",
                    description: "Erfahre ob deine Raumanfragen bestätigt oder abgelehnt wurden, es Änderungen an deinen Terminen gibt und mehr.",
                    types: [
                        {
                            enabled: true,
                            type_title: "Raumanfragen bestätigt oder abgelehnt",
                            description: "Erfahre ob deine Raumanfragen bestätigt oder abgelehnt wurden.",
                            frequency: "Täglich"
                        },
                        {
                            enabled: true,
                            type_title: "Raumanfragen bestätigt oder abgelehnt",
                            description: "Erfahre ob deine Raumanfragen bestätigt oder abgelehnt wurden.",
                            frequency: "Täglich",
                        },
                        {
                            enabled: true,
                            type_title: "Raumanfragen bestätigt oder abgelehnt",
                            description: "Erfahre ob deine Raumanfragen bestätigt oder abgelehnt wurden.",
                            frequency: "Täglich"
                        },
                        {
                            enabled: true,
                            type_title: "Raumanfragen bestätigt oder abgelehnt",
                            description: "Erfahre ob deine Raumanfragen bestätigt oder abgelehnt wurden.",
                            frequency: "Täglich"
                        }
                    ]
                },
                {
                    enabled: true,
                    group_title: "Raumbelegungen & Termine",
                    description: "Erfahre ob deine Raumanfragen bestätigt oder abgelehnt wurden, es Änderungen an deinen Terminen gibt und mehr.",
                    types: [
                        {
                            enabled: true,
                            type_title: "Raumanfragen bestätigt oder abgelehnt",
                            description: "Erfahre ob deine Raumanfragen bestätigt oder abgelehnt wurden.",
                            frequency: "Täglich"
                        },
                        {
                            enabled: true,
                            type_title: "Raumanfragen bestätigt oder abgelehnt",
                            description: "Erfahre ob deine Raumanfragen bestätigt oder abgelehnt wurden.",
                            frequency: "Täglich",
                        },
                        {
                            enabled: true,
                            type_title: "Raumanfragen bestätigt oder abgelehnt",
                            description: "Erfahre ob deine Raumanfragen bestätigt oder abgelehnt wurden.",
                            frequency: "Täglich"
                        },
                        {
                            enabled: true,
                            type_title: "Raumanfragen bestätigt oder abgelehnt",
                            description: "Erfahre ob deine Raumanfragen bestätigt oder abgelehnt wurden.",
                            frequency: "Täglich"
                        }
                    ]
                }
            ]
        }
    },
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
