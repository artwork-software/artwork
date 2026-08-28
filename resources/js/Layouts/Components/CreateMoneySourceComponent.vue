<template>
    <ArtworkBaseModal @close="closeModal" :title="$t('New source of funding')"
                      :description="$t('Create a funding source and link projects and items to get an overview of your budget.')">
        <BaseTabs :tabs="tabs" navigation-mode="events" :use-translation="false" class="!mt-0" @tab-select="changeTab"/>
        <!-- Form when Single Source -->
        <div v-if="isSingleSourceTab" class="space-y-4">
            <BaseInput
                v-model="createSingleSourceForm.name"
                id="sourceName"
                label="Title*"
            />
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <BaseInput
                    type="number"
                    v-model="createSingleSourceForm.amount"
                    id="sourceAmount"
                    label="Sum*"
                />
                <BaseInput
                    v-model="createSingleSourceForm.source_name"
                    id="nameOfSource"
                    label="Source"
                />
                <BaseInput
                    type="date"
                    v-model="createSingleSourceForm.start_date"
                    id="sourceStartDate"
                    label="Runtime Start"
                />
                <BaseInput
                    type="date"
                    v-model="createSingleSourceForm.end_date"
                    id="sourceEndDate"
                    label="Runtime End"
                />
                <BaseInput
                    type="date"
                    v-model="createSingleSourceForm.funding_start_date"
                    id="fundingStartDate"
                    label="Funding period Start"
                />
                <BaseInput
                    type="date"
                    v-model="createSingleSourceForm.funding_end_date"
                    id="fundingEndDate"
                    label="Funding period End"
                />
            </div>
            <div>
                <UserSearch v-model="user_query" @user-selected="addUserToMoneySourceUserArray" :label="$t('Who is responsible?')"/>
                <div v-if="usersToAdd.length > 0" class="mt-2 flex flex-wrap gap-2">
                    <span v-for="(user, index) in usersToAdd" :key="user.id"
                          class="inline-flex items-center gap-x-2 rounded-full border border-border-subtle bg-surface-sunken py-1 pl-1 pr-2">
                        <img class="size-6 rounded-full object-cover"
                             :src="user.profile_photo_url"
                             alt=""/>
                        <span class="text-[13px] font-medium text-text">
                            {{ user.first_name }} {{ user.last_name }}
                        </span>
                        <button type="button" class="text-text-subtle hover:text-danger" @click="deleteUserFromMoneySourceUserArray(index)">
                            <span class="sr-only">{{ $t('Remove user from funding source') }}</span>
                            <IconX stroke-width="1.5" class="size-3.5"/>
                        </button>
                    </span>
                </div>
            </div>
            <div class="space-y-2">
                <BaseCheckbox id="hasGroup" v-model="hasGroup" :label="$t('Belongs to funding Sources Group')"/>
                <div v-if="hasGroup" class="pl-7">
                    <ArtworkBaseListbox
                        v-model="selectedMoneySourceGroup"
                        :items="moneySourceGroups"
                        placeholder="Search for a funding group"
                        :empty-text="$t('No funding source groups available')"
                    />
                </div>
            </div>
            <BaseTextarea
                label="Comment / Note"
                id="description"
                v-model="createSingleSourceForm.description"
                rows="4"
            />
            <div class="space-y-3 border-t border-border-subtle pt-4">
                <div class="space-y-2">
                    <BaseCheckbox id="remindOnExpiration" v-model="remindOnExpiration" :label="$t('Remind me when this source runs out')"/>
                    <div v-if="remindOnExpiration" class="space-y-2 pl-7">
                        <div v-for="(expirationReminder, index) in expirationReminders" :key="index">
                            <div class="flex items-center gap-x-2">
                                <div class="w-20 shrink-0">
                                    <BaseInput
                                        type="number"
                                        is-small
                                        :show-label="false"
                                        :id="'expirationReminderDays' + index"
                                        label="Remind day/s before"
                                        v-model="expirationReminder.days"
                                    />
                                </div>
                                <span class="text-[13px] text-text-muted">
                                    {{ $t('Remind day/s before') }}
                                </span>
                                <button type="button" class="text-text-subtle hover:text-danger" @click="removeExpirationReminder(index)">
                                    <IconTrash stroke-width="1.5" class="size-4"/>
                                </button>
                            </div>
                            <p v-if="!isValidNumber(expirationReminder.days)" class="mt-1 text-xs text-danger">
                                {{ $t('If a reminder is to be created, enter the number of days or remove the reminder.') }}
                            </p>
                        </div>
                        <button type="button"
                                class="flex items-center gap-x-1.5 text-xs font-medium text-accent-600 hover:text-accent-700"
                                @click="addExpirationReminder()">
                            <IconCirclePlus stroke-width="1.5" class="size-4"/>
                            {{ $t('Add another reminder') }}
                        </button>
                    </div>
                </div>
                <div class="space-y-2">
                    <BaseCheckbox id="remindOnThreshold" v-model="remindOnThreshold" :label="$t('Remind me when only a certain percentage of the source still exists')"/>
                    <div v-if="remindOnThreshold" class="space-y-2 pl-7">
                        <div v-for="(thresholdReminder, index) in thresholdReminders" :key="index">
                            <div class="flex items-center gap-x-2">
                                <div class="w-20 shrink-0">
                                    <BaseInput
                                        type="number"
                                        is-small
                                        :show-label="false"
                                        :id="'thresholdReminderPercent' + index"
                                        label="Percent triggers a countdown notification"
                                        v-model="thresholdReminder.threshold"
                                    />
                                </div>
                                <span class="text-[13px] text-text-muted">
                                    {{ $t('Percent triggers a countdown notification') }}
                                </span>
                                <button type="button" class="text-text-subtle hover:text-danger" @click="removeThresholdReminder(index)">
                                    <IconTrash stroke-width="1.5" class="size-4"/>
                                </button>
                            </div>
                            <p v-if="!isValidNumber(thresholdReminder.threshold)" class="mt-1 text-xs text-danger">
                                {{ $t('If a countdown is to be created, enter the percentage or remove the countdown.') }}
                            </p>
                        </div>
                        <button type="button"
                                class="flex items-center gap-x-1.5 text-xs font-medium text-accent-600 hover:text-accent-700"
                                @click="addThresholdReminder()">
                            <IconCirclePlus stroke-width="1.5" class="size-4"/>
                            {{ $t('Add another reminder') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Form when Source Group -->
        <div v-else class="space-y-4">
            <BaseInput
                v-model="createSourceGroupForm.name"
                id="sourceGroupName"
                label="Title*"
            />
            <div>
                <UserSearch v-model="user_query" @user-selected="addUserToMoneySourceUserArray" :label="$t('Who is responsible?')"/>
                <div v-if="usersToAdd.length > 0" class="mt-2 flex flex-wrap gap-2">
                    <span v-for="(user, index) in usersToAdd" :key="user.id"
                          class="inline-flex items-center gap-x-2 rounded-full border border-border-subtle bg-surface-sunken py-1 pl-1 pr-2">
                        <img class="size-6 rounded-full object-cover"
                             :src="user.profile_photo_url"
                             alt=""/>
                        <span class="text-[13px] font-medium text-text">
                            {{ user.first_name }} {{ user.last_name }}
                        </span>
                        <button type="button" class="text-text-subtle hover:text-danger" @click="deleteUserFromMoneySourceUserArray(index)">
                            <span class="sr-only">{{ $t('Remove user from money source') }}</span>
                            <IconX stroke-width="1.5" class="size-3.5"/>
                        </button>
                    </span>
                </div>
            </div>
            <div>
                <div class="relative w-full">
                    <BaseInput
                        id="moneySourceSearch"
                        v-model="moneySource_query"
                        label="Which sources of funding belong to this group?"
                    />
                    <transition leave-active-class="transition ease-in duration-100"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0">
                        <div v-if="moneySource_search_results.length > 0 && moneySource_query.length > 0"
                             class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-border-subtle bg-surface text-sm shadow-overlay">
                            <button v-for="(moneySource, index) in moneySource_search_results" :key="index"
                                    type="button"
                                    class="block w-full cursor-pointer px-3 py-2 text-left font-medium text-text hover:bg-accent-50 hover:text-accent-700"
                                    @click="addMoneySourceToGroup(moneySource)">
                                {{ moneySource.name }}
                            </button>
                        </div>
                    </transition>
                </div>
                <div v-if="subMoneySources.length > 0" class="mt-2 flex flex-wrap gap-2">
                    <span v-for="(subMoneySource, index) in subMoneySources" :key="subMoneySource.id"
                          class="inline-flex items-center gap-x-1.5 rounded-full border border-border-subtle bg-surface-sunken px-2.5 py-1 text-[13px] font-medium text-text">
                        {{ subMoneySource.name }}
                        <button type="button" class="text-text-subtle hover:text-danger" @click="deleteSubMoneySourceFromGroup(index)">
                            <IconX stroke-width="1.5" class="size-3.5"/>
                        </button>
                    </span>
                </div>
            </div>
            <BaseTextarea
                label="Comment / Note"
                id="groupDescription"
                v-model="createSourceGroupForm.description"
                rows="4"
            />
        </div>
        <template #footer>
            <BaseUIButton label="Cancel" is-cancel-button hide-icon @click="closeModal(false)"/>
            <BaseUIButton v-if="isSingleSourceTab"
                          label="Creating a source of funding"
                          variant="primary"
                          :disabled="!isFormComplete()"
                          @click="createSingleSource()"
            />
            <BaseUIButton v-else
                          label="Create funding source group"
                          variant="primary"
                          :disabled="!isGroupFormComplete()"
                          @click="createMoneySourceGroup()"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script>
import {IconCirclePlus, IconTrash, IconX} from "@tabler/icons-vue";

import {router, useForm} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import BaseTabs from "@/Artwork/Tabs/BaseTabs.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

export default {
    name: 'CreateMoneySourceComponent',
    mixins: [Permissions, IconLib],
    components: {
        ArtworkBaseModal,
        ArtworkBaseListbox,
        BaseTabs,
        BaseUIButton,
        BaseCheckbox,
        BaseTextarea,
        BaseInput,
        UserSearch,
        IconX,
        IconTrash,
        IconCirclePlus,
    },
    computed: {
        tabs() {
            return [
                {name: this.$t('Single source'), href: '#', current: this.isSingleSourceTab, permission: true},
                {name: this.$t('Group'), href: '#', current: this.isGroupTab, permission: true},
            ]
        }
    },
    data() {
        return {
            isSingleSourceTab: true,
            isGroupTab: false,
            user_search_results: [],
            user_query: '',
            usersToAdd: [],
            hasGroup: false,
            subMoneySources: [],
            moneySource_query: '',
            moneySource_search_results: [],
            selectedMoneySourceGroup: null,
            searchType: 'single',
            createSingleSourceForm: useForm({
                name: '',
                amount: null,
                start_date: null,
                end_date: null,
                funding_start_date: null,
                funding_end_date: null,
                source_name: null,
                description: null,
                is_group: false,
                group_id: null,
                users: [],
            }),
            createSourceGroupForm: useForm({
                name: '',
                amount: null,
                start_date: null,
                end_date: null,
                source_name: null,
                description: null,
                is_group: true,
                users: [],
                sub_money_source_ids: []
            }),
            remindOnExpiration: false,
            expirationReminders: [
                {
                    days: 1
                }
            ],
            remindOnThreshold: false,
            thresholdReminders: [
                {
                    threshold: 1
                }
            ],
        }
    },

    props: ['moneySourceGroups'],

    emits: ['closed'],

    watch: {
        user_query: {
            handler() {
                if (this.user_query.length > 0) {
                    axios.get('/users/search', {
                        params: {query: this.user_query}
                    }).then(response => {
                        this.user_search_results = response.data
                    })
                }
            },
            deep: true
        },
        moneySource_query: {
            handler() {
                if (this.moneySource_query.length > 0) {
                    axios.get('/money_sources/search/money_source', {
                        params: {query: this.moneySource_query, type: this.searchType}
                    }).then(response => {
                        this.moneySource_search_results = response.data
                    })
                }
            },
            deep: true
        },
    },

    methods: {
        addUserToMoneySourceUserArray(user) {
            if (!this.usersToAdd.find(userToAdd => userToAdd.id === user.id)) {
                this.usersToAdd.push(user);
            }
            this.user_query = '';
        },
        addMoneySourceToGroup(moneySource) {
            this.subMoneySources.push(moneySource);
            this.moneySource_query = '';
        },
        deleteUserFromMoneySourceUserArray(index) {
            this.usersToAdd.splice(index, 1);
        },
        deleteSubMoneySourceFromGroup(index) {
            this.subMoneySources.splice(index, 1);
        },
        createSingleSource() {
            this.createSingleSourceForm.users = {}
            this.usersToAdd.forEach((userToAdd) => {
                this.createSingleSourceForm.users[userToAdd.id] = {
                    user_id: userToAdd.id,
                    competent: true,
                    write_access: true
                };
            })
            if (this.selectedMoneySourceGroup) {
                this.createSingleSourceForm.group_id = this.selectedMoneySourceGroup.id
            }

            this.createSingleSourceForm.post(
                route('money_sources.store'),
                {
                    onSuccess: (response) => {
                        if (
                            (this.remindOnExpiration && this.expirationReminders.length > 0) ||
                            (this.remindOnThreshold && this.thresholdReminders.length > 0)
                        ) {
                            router.post(
                                route(
                                    'money_source.reminder.store',
                                    {
                                        money_source: response.props.recentlyCreatedMoneySourceId
                                    }
                                ),
                                {
                                    expirationReminders: this.remindOnExpiration ? this.expirationReminders : [],
                                    thresholdReminders: this.remindOnThreshold ? this.thresholdReminders : []
                                }
                            );
                        }

                        this.closeModal(true);
                    }
                }
            );
        },
        createMoneySourceGroup() {
            this.createSourceGroupForm.users = {}
            this.usersToAdd.forEach((userToAdd) => {
                this.createSourceGroupForm.users[userToAdd.id] = {
                    user_id: userToAdd.id,
                    competent: true,
                    write_access: true
                };
            })
            this.subMoneySources.forEach((subMoneySource) => {
                this.createSourceGroupForm.sub_money_source_ids.push(subMoneySource.id);
            })
            this.createSourceGroupForm.post(route('money_sources.store'));
            this.closeModal(true);
        },
        changeTab(selectedTab) {
            this.usersToAdd = [];
            this.isSingleSourceTab = false;
            this.isGroupTab = false;
            if (selectedTab.name === this.$t('Single source')) {
                this.isSingleSourceTab = true;
            } else {
                this.isGroupTab = true;
            }
        },
        closeModal(bool) {
            this.$emit('closed', bool);
        },
        isFormComplete() {
            const form = this.createSingleSourceForm;
            let hasInvalidExpirationReminders = false;
            let hasInvalidThresholdReminders = false;

            if (this.remindOnExpiration) {
                hasInvalidExpirationReminders = this.expirationReminders.some(
                    (expirationReminder) => !this.isValidNumber(expirationReminder.days)
                )
            }

            if (this.remindOnThreshold) {
                hasInvalidThresholdReminders = this.thresholdReminders.some(
                    (thresholdReminder) => !this.isValidNumber(thresholdReminder.threshold)
                )
            }

            return form.name && form.amount && !hasInvalidExpirationReminders && !hasInvalidThresholdReminders;
        },
        isGroupFormComplete() {
            const form = this.createSourceGroupForm;
            return form.name;
        },
        addExpirationReminder() {
            this.expirationReminders.push({days: 1});
        },
        removeExpirationReminder(index) {
            this.expirationReminders.splice(index, 1);
        },
        addThresholdReminder() {
            this.thresholdReminders.push({threshold: 1});
        },
        removeThresholdReminder(index) {
            this.thresholdReminders.splice(index, 1);
        },
        isValidNumber(number) {
            return number >= 1 && Number.isInteger(number);
        }
    },
}
</script>

<style scoped></style>
