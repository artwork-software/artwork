<template>
    <ArtworkBaseModal @close="closeModal"
                      :title="moneySource.is_group ? 'Funding source group' : 'Source of funding'">
        <div class="space-y-4">
            <div class="flex flex-wrap items-center gap-x-8 gap-y-2 text-[13px] text-text-muted">
                <div v-if="moneySource.users" class="flex items-center gap-x-2">
                    {{ $t('responsible') }}:
                    <div class="flex items-center gap-x-1">
                        <template v-for="(user, index) in moneySource.users">
                            <NewUserToolTip :height="7" :width="7" v-if="user"
                                            :user="user" :id="index + 'moneySourceUser' + user.id"/>
                        </template>
                    </div>
                </div>
                <div v-if="moneySource.creator" class="flex items-center gap-x-2">
                    {{ $t('Created by') }}
                    <NewUserToolTip :height="7" :width="7"
                                    :user="moneySource.creator" :id="moneySource.creator.id + 'creator'"/>
                </div>
            </div>
            <!-- Form when Single Source -->
            <div v-if="!moneySource.is_group" class="space-y-4">
                <BaseInput
                    v-model="editSingleSourceForm.name"
                    id="sourceName"
                    :label="$t('Title*')"
                />
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <BaseInput
                        type="number"
                        v-model="editSingleSourceForm.amount"
                        id="sourceAmount"
                        :label="$t('Sum*')"
                    />
                    <BaseInput
                        type="number"
                        v-model="editSingleSourceForm.source_name"
                        id="nameOfSource"
                        :label="$t('Source')"
                    />
                    <BaseInput
                        type="date"
                        v-model="editSingleSourceForm.start_date"
                        id="sourceStartDate"
                        :label="$t('Runtime Start')"
                    />
                    <BaseInput
                        type="date"
                        v-model="editSingleSourceForm.end_date"
                        id="sourceEndDate"
                        :label="$t('Runtime End')"
                    />
                    <BaseInput
                        type="date"
                        v-model="editSingleSourceForm.funding_start_date"
                        id="fundingStartDate"
                        :label="$t('Funding period Start')"
                    />
                    <BaseInput
                        type="date"
                        v-model="editSingleSourceForm.funding_end_date"
                        id="fundingEndDate"
                        :label="$t('Funding period End')"
                    />
                </div>
                <div>
                    <UserSearch
                        v-model="user_query"
                        :label="$t('Who is responsible?')"
                        @user-selected="addUserToMoneySourceUserArray"
                    />
                    <div v-if="usersToAdd !== null && usersToAdd.length > 0" class="mt-2 flex flex-wrap gap-2">
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
                <BaseTextarea label="Comment / Note"
                              id="description"
                              v-model="editSingleSourceForm.description"
                              rows="4"
                />
            </div>
            <!-- Form when Source Group -->
            <div v-else class="space-y-4">
                <BaseInput
                    v-model="editSourceGroupForm.name"
                    id="sourceGroupName"
                    :label="$t('Title*')"
                />
                <div>
                    <UserSearch
                        v-model="user_query"
                        :label="$t('Who is responsible?')"
                        @user-selected="addUserToMoneySourceUserArray"
                    />
                    <div v-if="usersToAdd !== null && usersToAdd.length > 0" class="mt-2 flex flex-wrap gap-2">
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
                <div>
                    <div class="relative w-full">
                        <BaseInput
                            id="moneySourceSearch"
                            v-model="moneySource_query"
                            :label="$t('Which sources of funding belong to this group?')"
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
                <BaseTextarea :label="$t('Comment / Note')" id="groupDescription" v-model="editSourceGroupForm.description" rows="4"/>
            </div>
        </div>
        <template #footer>
            <BaseUIButton label="Cancel" is-cancel-button hide-icon @click="closeModal(false)"/>
            <BaseUIButton v-if="!moneySource.is_group"
                          label="Save"
                          variant="primary"
                          icon="IconDeviceFloppy"
                          @click="editSingleSource()"
            />
            <BaseUIButton v-else
                          label="Save"
                          variant="primary"
                          icon="IconDeviceFloppy"
                          @click="editGroupSource()"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script>
import {IconX} from "@tabler/icons-vue";

import {useForm} from "@inertiajs/vue3";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import Permissions from "@/Mixins/Permissions.vue";
import IconLib from "@/Mixins/IconLib.vue";
import UserSearch from "@/Components/SearchBars/UserSearch.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

export default {
    name: 'EditMoneySourceComponent',
    mixins: [Permissions, IconLib],
    components: {
        ArtworkBaseModal,
        ArtworkBaseListbox,
        BaseUIButton,
        BaseCheckbox,
        BaseTextarea,
        BaseInput,
        UserSearch,
        NewUserToolTip,
        IconX,
    },
    computed: {
        subMoneySources() {
            let subMoneySourceArray = [];
            if (this.moneySource.subMoneySources) {
                this.moneySource.subMoneySources.forEach((subMoneySource) => {
                    subMoneySourceArray.push(subMoneySource);
                })
                return subMoneySourceArray;
            }else{
                return [];
            }

        }
    },
    data() {
        return {
            user_search_results: [],
            user_query: '',
            usersToAdd: this.moneySource.users,
            hasGroup: !!this.moneySource.group_id,
            moneySource_query: '',
            moneySource_search_results: [],
            selectedMoneySourceGroup: this.moneySource.group_id ? this.moneySource.moneySourceGroup : null,
            searchType: 'single',
            editSingleSourceForm: useForm({
                name: this.moneySource.name,
                amount: this.moneySource.amount,
                start_date: this.moneySource.start_date,
                end_date: this.moneySource.end_date,
                source_name: this.moneySource.source_name,
                description: this.moneySource.description,
                is_group: false,
                group_id: this.moneySource.group_id,
                funding_start_date: this.moneySource.funding_start_date,
                funding_end_date: this.moneySource.funding_end_date,
                users: []
            }),
            editSourceGroupForm: useForm({
                name: this.moneySource.name,
                amount: this.moneySource.amount,
                start_date: this.moneySource.start_date,
                end_date: this.moneySource.end_date,
                source_name: this.moneySource.source_name,
                description: this.moneySource.description,
                is_group: true,
                users: [],
                sub_money_source_ids: []
            }),
        }
    },

    props: ['moneySource', 'moneySourceGroups', 'moneySources'],

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
                    axios.get('/money_sources/search', {
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
            if(!this.usersToAdd.find(userToAdd => userToAdd.id === user.id)){
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
            this.moneySource.subMoneySources.splice(index,1);
        },
        editSingleSource() {
            this.editSingleSourceForm.users = {};
            this.usersToAdd.forEach((userToAdd) => {
                this.editSingleSourceForm.users[userToAdd.id] = {
                    user_id: userToAdd.id,
                    competent: true,
                    write_access: true
                };
            })
            if (this.selectedMoneySourceGroup && this.hasGroup) {
                this.editSingleSourceForm.group_id = this.selectedMoneySourceGroup.id;
            } else {
                this.editSingleSourceForm.group_id = null;
            }
            this.editSingleSourceForm.is_group = false;

            this.editSingleSourceForm.patch(route('money_sources.update', {moneySource: this.moneySource.id}));
            this.closeModal(true);
        },
        editGroupSource(){
            this.editSourceGroupForm.users = {};
            this.usersToAdd.forEach((userToAdd) => {
                this.editSourceGroupForm.users[userToAdd.id] = {
                    user_id: userToAdd.id,
                    competent: true,
                    write_access: true
                };
            })
            this.subMoneySources.forEach((subMoneySource) => {
                this.editSourceGroupForm.sub_money_source_ids.push(subMoneySource.id);
            })
            this.editSourceGroupForm.patch(route('money_sources.update', {moneySource: this.moneySource.id}));
            this.closeModal(true);
        },
        closeModal(bool) {
            this.usersToAdd = [];
            this.$emit('closed', bool);
        },
    },
}
</script>

<style scoped></style>
