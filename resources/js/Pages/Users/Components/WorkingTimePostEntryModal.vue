<template>
    <ArtworkBaseModal title="User Book working hours" description="Add or edit working hours for the user" @close="$emit('close')">
        <div v-if="bookingForm.user">
            <div class="flex items-center gap-x-4 font-lexend">
                <div class="shrink-0">
                    <img alt="user.first_name" :src="bookingForm.user.profile_photo_url" class="size-16 rounded-full object-cover">
                </div>
                <div>
                    <h4 class="text-lg font-bold">{{ bookingForm.user.first_name }} {{ bookingForm.user.last_name }}</h4>
                    <p class="text-xs">{{ bookingForm.user.position }}</p>
                </div>
            </div>
            <div class="text-right text-xs font-lexend text-artwork-buttons-create underline cursor-pointer">
                <div @click="bookingForm.user = null">{{ $t('Select another user') }}</div>
            </div>
        </div>

        <div v-else>
            <UserSearch @user-selected="selectUser" />
        </div>

        <form @submit.prevent="submit" class="mt-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mt-10" >
                <div class="col-span-full md:col-span-1">
                    <Listbox as="div" class="relative" v-model="bookingForm.plus_minus">
                        <ListboxButton class="menu-button">
                        <span class="flex items-center justify-between w-full">
                            <span class="block truncate">{{ bookingForm.plus_minus }}</span>
                        </span>
                            <component :is="IconChevronDown" class="h-5 w-5" aria-hidden="true" />
                        </ListboxButton>
                        <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                            <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg border border-gray-300 ring-opacity-5 focus:outline-none sm:text-sm">
                                <ListboxOption as="template" v-for="value in selectableValues" :key="value.name" :value="value.name">
                                    <li :class="['relative cursor-default select-none py-2 pl-3 pr-9']">
                                        <span :class="['block truncate']">{{ value.name }}</span>
                                    </li>
                                </ListboxOption>
                            </ListboxOptions>
                        </transition>
                    </Listbox>
                </div>

                <div class="col-span-full md:col-span-4">
                    <BaseInput id="hours" v-model="bookingForm.hours" label="Hours" type="time" required/>
                </div>
                <div class="col-span-full md:col-span-5">
                    <BaseInput id="hours" v-model="bookingForm.nightly_working_hours" label="Of which night hours" type="time"/>
                </div>

                <div class="col-span-full md:col-span-5">
                    <BaseInput id="date" label="Date" type="date" v-model="bookingForm.date" required/>
                </div>

                <div class="col-span-full md:col-span-5">
                    <BaseTextarea id="comment" v-model="bookingForm.comment" label="Comment" placeholder="Add a comment*" required />
                </div>
            </div>

            <div class="mt-5 flex justify-center">
                <BaseUIButton :label="$t('Save')" is-add-button type="submit"/>
            </div>
        </form>

    </ArtworkBaseModal>
</template>

<script setup>

import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import SelectComponent from "@/Components/Inputs/SelectComponent.vue";
import {useForm} from "@inertiajs/vue3";
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from "@headlessui/vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import {IconChevronDown} from "@tabler/icons-vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    user: {
        type: Object,
        required: false
    }
})

const bookingForm = useForm({
    user: props.user ?? null,
    user_id: props.user ? props.user.id : null,
    hours: '',
    nightly_working_hours: '00:00',
    plus_minus: '+',
    comment: '',
    date: new Date().toISOString().split('T')[0] // Default to today
})

const selectableValues = [
    { id: 1, name: '+' },
    { id: 2, name: '-' }
]

const emit = defineEmits(['close'])
const selectUser = (user) => {
    bookingForm.user = user;
}

const submit = () => {
    bookingForm.user_id = bookingForm.user ? bookingForm.user.id : null;
    bookingForm.post(route('users.worktimes.store', bookingForm.user_id), {
        onSuccess: () => {
            emit('close');
        },
        preserveScroll: true,
        preserveState: false,
    });
}
</script>

<style scoped>

</style>
