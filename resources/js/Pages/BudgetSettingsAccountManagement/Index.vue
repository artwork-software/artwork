<template>
    <BudgetSettingsHeader>
        <SwitchGroup as="div" class="mb-5">
            <Switch v-model="budgetAccountManagementGlobal"
                    :class="[
                        budgetAccountManagementGlobal ?
                            'bg-indigo-600' :
                            'bg-gray-200',
                        'relative inline-flex h-3 w-8 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-1 focus:ring-indigo-600 focus:ring-offset-2'
                    ]"
            >
                <span aria-hidden="true"
                      :class="[
                          budgetAccountManagementGlobal ?
                              'translate-x-5' :
                              'translate-x-0',
                          'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                      ]"
                />
            </Switch>
            <SwitchLabel as="span">
                <span class="pl-1" :class="[budgetAccountManagementGlobal ? 'xsDark' : 'xsLight', 'text-sm']">
                    {{ $t('Accounts and cost units are used in each project budget for column 1 and column 2') }}
                </span>
            </SwitchLabel>
        </SwitchGroup>
        <hr class="mb-5"/>
        <div class="mb-5 space-y-1">
            <div class="headline3 mb-5">{{ $t('Selectable accounts') }}</div>
            <div class="flex flex-row space-x-5 items-center">
                <input-component :placeholder="$t('Account number')"
                                 v-model="this.accountForm.account_number"
                />
                <input-component :placeholder="$t('Description')"
                                 v-model="this.accountForm.title"
                />
                <SwitchGroup as="div" class="flex items-center">
                    <SwitchLabel as="span" class="mr-3 text-sm" :class="this.accountForm.is_account_for_revenue ? 'text-gray-400' : 'font-bold'">
                        {{ $t('Expense account') }}
                    </SwitchLabel>
                    <Switch v-model="this.accountForm.is_account_for_revenue " class="bg-indigo-600 relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                        <span aria-hidden="true" :class="[this.accountForm.is_account_for_revenue  ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                    </Switch>
                    <SwitchLabel as="span" class="ml-3 text-sm" :class="this.accountForm.is_account_for_revenue ? 'font-bold' : 'text-gray-400'">
                        {{ $t('Revenue account') }}
                    </SwitchLabel>
                </SwitchGroup>
                <AddButton :text="$t('Add')"
                           class="!mt-0"
                           @click="this.saveAccount()"
                />
            </div>
            <div class="errorText" v-if="this.accountFormHelpText">{{ this.accountFormHelpText }}</div>
        </div>
        <div class="mb-5 space-y-1">
            <div class="headline3 mb-5">{{ $t('Selectable cost units') }}</div>
            <div class="flex flex-row space-x-5 items-center">
                <input-component :placeholder="$t('Cost unit number')"
                                 v-model="this.costUnitForm.cost_unit_number"
                />
                <input-component :placeholder="$t('Description')"
                                 v-model="this.costUnitForm.title"
                />
                <AddButton :text="$t('Add')"
                           class="!mt-0"
                           @click="this.saveCostUnit()"
                />
            </div>
            <div class="errorText" v-if="this.costUnitFormHelpText">{{ this.costUnitFormHelpText }}</div>
        </div>
        <hr class="mb-5"/>
        <div class="space-y-5">
            <div class="flex flex-col">
                <div class="flex flex-row space-x-5 items-center">
                    <div class="headline3 w-56">{{ $t('Selectable accounts') }}</div>
                    <div class="flex justify-end w-96 h-12 items-center">
                        <div v-if="!this.showAccountSearch"
                             @click="this.showAccountSearch = !this.showAccountSearch"
                             class="cursor-pointer inset-y-0">
                            <IconSearch class="h-5 w-5"
                                        aria-hidden="true"
                            />
                        </div>
                        <div v-else class="flex items-center w-64">
                            <inputComponent v-model="this.accountSearchQuery"
                                            :placeholder="$t('Search account')"
                            />
                            <IconX class="ml-2 cursor-pointer h-5 w-5"
                                   @click="this.showAccountSearch = false"
                            />
                        </div>
                    </div>
                </div>
                <div class="flex flex-row">
                    <span class="w-56 xsLight mr-2">{{ $t('Account number') }}</span>
                    <span class="w-96 xsLight mr-2">{{ $t('Description') }}</span>
                    <span class="w-72 xsLight">{{ $t('Account type') }}</span>
                </div>
                <div class="flex flex-col"
                     v-for="account in this.filteredAccounts">
                    <div class="flex flex-row items-center">
                        <!-- If not edit for given account -->
                        <div v-if="this.accountIdToEdit !== account.id" class="flex flex-row">
                            <span class="w-56 text-wrap break-words mr-2">{{ account.account_number }}</span>
                            <span class="w-96 text-wrap break-words mr-2">{{ account.title }}</span>
                            <span class="w-72">
                                {{ account.is_account_for_revenue ? $t('Revenue account') : $t('Expense account') }}
                            </span>
                        </div>
                        <!-- if account is edited -->
                        <div v-if="this.accountIdToEdit === account.id" class="flex flex-row items-center">
                            <span class="w-56 mr-2">
                                <input-component v-model="this.editAccountForm.account_number"
                                                 :placeholder="this.editAccountForm.account_number"
                                />
                            </span>
                            <span class="w-96 mr-2">
                                <input-component v-model="this.editAccountForm.title"
                                                 :placeholder="this.editAccountForm.title"
                                />
                            </span>
                            <span class="w-72 flex justify-center">
                                <SwitchGroup as="div" class="flex items-center">
                                    <SwitchLabel as="span" class="mr-3 text-sm" :class="this.editAccountForm.is_account_for_revenue ? 'text-gray-400' : 'font-bold'">
                                        {{ $t('Expense account') }}
                                    </SwitchLabel>
                                    <Switch v-model="this.editAccountForm.is_account_for_revenue " class="bg-indigo-600 relative inline-flex h-3 w-6 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none">
                                        <span aria-hidden="true" :class="[this.editAccountForm.is_account_for_revenue  ? 'translate-x-3' : 'translate-x-0', 'pointer-events-none inline-block h-2 w-2 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                                    </Switch>
                                    <SwitchLabel as="span" class="ml-3 text-sm" :class="this.editAccountForm.is_account_for_revenue ? 'font-bold' : 'text-gray-400'">
                                        {{ $t('Revenue account') }}
                                    </SwitchLabel>
                                </SwitchGroup>
                            </span>
                        </div>
                        <!-- only display edit/trash icons if no account is edited currently -->
                        <div v-if="this.accountIdToEdit === null"
                             class="flex flex-row items-center">
                            <IconEdit class="w-5 h-5 hover:text-error cursor-pointer"
                                      @click="this.initAccountEdit(account)"/>
                            <IconTrash class="w-5 h-5 hover:text-error cursor-pointer"
                                       @click="this.showRemoveConfirmModal(account, 'account')"
                            />
                        </div>
                        <!-- only display save/x icons if current account is edited -->
                        <div v-if="this.accountIdToEdit === account.id" class="flex flex-row items-center">
                            <IconDeviceFloppy @click="this.saveAccountEdit()"
                                              class="w-5 h-5 hover:text-error cursor-pointer"
                            />
                            <IconX @click="this.resetAccountEdit();"
                                   class="w-5 h-5 hover:text-error cursor-pointer"
                            />
                        </div>
                    </div>
                    <div v-if="this.accountIdToEdit === account.id && this.editAccountFormHelpText"
                         class="errorText">
                        {{ this.editAccountFormHelpText }}
                    </div>
                </div>
            </div>
            <div class="flex flex-col">
                <div class="flex flex-row space-x-5 items-center">
                    <div class="headline3 w-72">{{ $t('Selectable cost units') }}</div>
                    <div class="flex justify-end w-80 h-12 items-center">
                        <div v-if="!this.showCostUnitSearch"
                             @click="this.showCostUnitSearch = !this.showCostUnitSearch"
                             class="cursor-pointer inset-y-0">
                            <IconSearch class="h-5 w-5"
                                        aria-hidden="true"
                            />
                        </div>
                        <div v-else class="flex items-center w-64">
                            <inputComponent v-model="this.costUnitSearchQuery"
                                            :placeholder="$t('Search cost center')"
                            />
                            <IconX class="ml-2 cursor-pointer h-5 w-5"
                                   @click="this.showCostUnitSearch = false"
                            />
                        </div>
                    </div>
                </div>
                <div class="flex flex-row">
                    <span class="w-56 xsLight mr-2">{{ $t('Cost unit number') }}</span>
                    <span class="w-96 xsLight">{{ $t('Description') }}</span>
                </div>
                <div class="flex flex-col"
                     v-for="cost_unit in this.filteredCostUnits">
                    <div class="flex flex-row items-center">
                        <!-- If not edit for given cost_unit -->
                        <div v-if="this.costUnitIdToEdit !== cost_unit.id" class="flex flex-row">
                            <span class="w-56 text-wrap break-words mr-2">{{ cost_unit.cost_unit_number }}</span>
                            <span class="w-96 text-wrap break-words">{{ cost_unit.title }}</span>
                        </div>
                        <!-- if cost_unit is edited -->
                        <div v-if="this.costUnitIdToEdit === cost_unit.id" class="flex flex-row items-center">
                            <span class="w-56 mr-2">
                                <input-component v-model="this.editCostUnitForm.cost_unit_number"
                                                 :placeholder="this.editCostUnitForm.cost_unit_number"
                                />
                            </span>
                            <span class="w-96">
                                <input-component v-model="this.editCostUnitForm.title"
                                                 :placeholder="this.editCostUnitForm.title"
                                />
                            </span>
                        </div>
                        <!-- only display edit/trash icons if no cost_unit is edited currently -->
                        <div v-if="this.costUnitIdToEdit === null"
                             class="flex flex-row items-center">
                            <IconEdit class="w-5 h-5 hover:text-error cursor-pointer"
                                      @click="this.initCostUnitEdit(cost_unit)"/>
                            <IconTrash class="w-5 h-5 hover:text-error cursor-pointer"
                                       @click="this.showRemoveConfirmModal(cost_unit, 'cost_unit')"
                            />
                        </div>
                        <!-- only display save/x icons if current cost_unit is edited -->
                        <div v-if="this.costUnitIdToEdit === cost_unit.id" class="flex flex-row items-center ml-2">
                            <IconDeviceFloppy @click="this.saveCostUnitEdit()"
                                              class="w-5 h-5 hover:text-error cursor-pointer"
                            />
                            <IconX @click="this.resetCostUnitEdit();"
                                   class="w-5 h-5 hover:text-error cursor-pointer"
                            />
                        </div>
                    </div>
                    <div v-if="this.costUnitIdToEdit === cost_unit.id && this.editCostUnitFormHelpText"
                         class="errorText">
                        {{ this.editCostUnitFormHelpText }}
                    </div>
                </div>
            </div>
        </div>
        <confirmation-component v-if="this.showRemoveResourceConfirmModal"
                                :titel="this.removeResourceConfirmModalTitle"
                                :description="this.removeResourceConfirmModalDescription"
                                @closed="this.removeResourceByType"
        />
        <success-modal v-if="this.$page.props.flash.success"
                       :title="$t('Success')"
                       :description="this.$page.props.flash.success"
                       :button="$t('Close message')"
                       @closed="this.$page.props.flash.success = null"
        />
        <error-component v-if="this.$page.props.flash.error"
                         :titel="$t('An error has occurred')"
                         :description="this.$page.props.flash.error"
                         :confirm="$t('Close message')"
                         @closed="this.$page.props.flash.error = null;"
        />
    </BudgetSettingsHeader>
</template>
<script>
import {defineComponent} from "vue";
import BudgetSettingsHeader from "@/Pages/BudgetSettings/BudgetSettingsHeader.vue";
import {Switch, SwitchGroup, SwitchLabel} from "@headlessui/vue";
import {router} from "@inertiajs/vue3";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import AddButton from "@/Layouts/Components/AddButton.vue";
import {useForm} from "@inertiajs/vue3";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import ErrorComponent from "@/Layouts/Components/ErrorComponent.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import IconLib from "@/Mixins/IconLib.vue";

export default defineComponent({
    mixins: [IconLib],
    components: {
        SuccessModal,
        ErrorComponent,
        ConfirmationComponent,
        AddButton,
        SwitchLabel,
        Switch,
        SwitchGroup,
        BudgetSettingsHeader,
        InputComponent,
    },
    props: [
        'accounts',
        'cost_units'
    ],
    data() {
        return {
            budgetAccountManagementGlobal: this.$page.props.budgetAccountManagementGlobal,
            accountForm: useForm({
                account_number: '',
                title: '',
                is_account_for_revenue: false
            }),
            editAccountFormHelpText: null,
            accountIdToEdit: null,
            editAccountForm: useForm({
                account_number: '',
                title: '',
                is_account_for_revenue: false
            }),
            accountFormHelpText: null,
            costUnitForm: useForm({
                cost_unit_number: '',
                title: ''
            }),
            editCostUnitFormHelpText: null,
            costUnitIdToEdit: null,
            editCostUnitForm: useForm({
                cost_unit_number: '',
                title: ''
            }),
            costUnitFormHelpText: null,
            showRemoveResourceConfirmModal: false,
            removeResourceConfirmModalTitle: '',
            removeResourceConfirmModalDescription: '',
            removeResourceConfirmModalResourceToDelete: null,
            removeResourceConfirmModalType: null,
            showAccountSearch: false,
            accountSearchQuery: '',
            showCostUnitSearch: false,
            costUnitSearchQuery: ''
        };
    },
    watch: {
        budgetAccountManagementGlobal: {
            handler() {
                router.patch(
                    route('budget-settings.account-management.updateBudgetAccountManagementGlobal'),
                    {
                        enabled: this.budgetAccountManagementGlobal
                    },
                    {
                        preserveState: true,
                        preserveScroll: true
                    }
                );
            },
        }
    },
    computed: {
        filteredAccounts() {
            if (this.accountSearchQuery === '') {
                return this.accounts;
            }

            return this.accounts.filter((account) => {
                return account.account_number.includes(this.accountSearchQuery) ||
                    account.title.toLowerCase().includes(this.accountSearchQuery.toLowerCase());
            });
        },
        filteredCostUnits() {
            if (this.costUnitSearchQuery === '') {
                return this.cost_units;
            }

            return this.cost_units.filter((cost_unit) => {
                return cost_unit.cost_unit_number.includes(this.costUnitSearchQuery) ||
                    cost_unit.title.toLowerCase().includes(this.costUnitSearchQuery.toLowerCase());
            });
        }
    },
    methods: {
        initAccountEdit(account) {
            this.editAccountForm.account_number = account.account_number;
            this.editAccountForm.title = account.title;
            this.editAccountForm.is_account_for_revenue = account.is_account_for_revenue;
            this.accountIdToEdit = account.id;
        },
        resetAccountEdit() {
            this.editAccountFormHelpText = null;
            this.accountIdToEdit = null;
            this.editAccountForm.reset();
        },
        saveAccountEdit() {
            if (this.editAccountForm.account_number === '') {
                this.editAccountFormHelpText = this.$t('The account number must contain at least one character.');
                return;
            }

            if (this.editAccountForm.title === '') {
                this.editAccountFormHelpText = this.$t('The description must contain at least one character.');
                return;
            }

            if (this.editAccountForm.title.length > 255) {
                this.editAccountFormHelpText = this.$t('The description must not be longer than 255 characters.');
                return;
            }

            this.editAccountFormHelpText = null;
            this.editAccountForm.patch(
                route(
                    'budget-settings.account-management.update-account',
                    {
                        budgetManagementAccount: this.accountIdToEdit
                    }
                ),
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        if (!this.$page.props.flash.error) {
                            this.resetAccountEdit();
                        }
                    }
                }
            )
        },
        initCostUnitEdit(cost_unit) {
            this.editCostUnitForm.cost_unit_number = cost_unit.cost_unit_number;
            this.editCostUnitForm.title = cost_unit.title;
            this.costUnitIdToEdit = cost_unit.id;
        },
        resetCostUnitEdit() {
            this.editCostUnitFormHelpText = null;
            this.costUnitIdToEdit = null;
            this.editCostUnitForm.reset();
        },
        saveCostUnitEdit() {
            if (this.editCostUnitForm.cost_unit_number === '') {
                this.editCostUnitFormHelpText = this.$t('The cost center number must contain at least one character.');
                return;
            }

            if (this.editCostUnitForm.title === '') {
                this.editCostUnitFormHelpText = this.$t('The description must contain at least one character.');
                return;
            }

            if (this.editCostUnitForm.title.length > 255) {
                this.editCostUnitFormHelpText = this.$t('The description must not be longer than 255 characters.');
                return;
            }

            this.editCostUnitFormHelpText = null;

            this.editCostUnitForm.patch(
                route(
                    'budget-settings.account-management.update-cost-unit',
                    {
                        budgetManagementCostUnit: this.costUnitIdToEdit
                    }
                ),
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        if (!this.$page.props.flash.error) {
                            this.resetCostUnitEdit();
                        }
                    }
                }
            )
        },
        showRemoveConfirmModal(resourceToDelete, type) {
            this.showRemoveResourceConfirmModal = true;
            switch (type) {
                case 'account':
                    this.removeResourceConfirmModalTitle = this.$t('Delete Account');
                    this.removeResourceConfirmModalDescription = this.$t(
                        'Should the account really be deleted?',
                        [resourceToDelete.account_number]
                    );
                    break;
                case 'cost_unit':
                    this.removeResourceConfirmModalTitle = this.$t('Delete cost center');
                    this.removeResourceConfirmModalDescription = this.$t(
                        'Should the cost center really be deleted?',
                        [resourceToDelete.cost_unit_number]
                    );

                    break;
            }
            this.removeResourceConfirmModalResourceToDelete = resourceToDelete;
            this.removeResourceConfirmModalType = type;
        },
        resetRemoveConfirmModal() {
            this.showRemoveResourceConfirmModal = false;
            this.removeResourceConfirmModalTitle = '';
            this.removeResourceConfirmModalDescription = '';
            this.removeResourceConfirmModalResourceToDelete = null;
            this.removeResourceConfirmModalType = null;
        },
        removeResourceByType(closedToDelete) {
            if (!closedToDelete) {
                this.resetRemoveConfirmModal();
                return;
            }

            let desiredRoute = null;
            switch (this.removeResourceConfirmModalType) {
                case 'account':
                    desiredRoute = route(
                        'budget-settings.account-management.destroy-account',
                        {
                            budgetManagementAccount: this.removeResourceConfirmModalResourceToDelete.id
                        }
                    );
                    break;
                case 'cost_unit':
                    desiredRoute = route(
                        'budget-settings.account-management.destroy-cost-unit',
                        {
                            budgetManagementCostUnit: this.removeResourceConfirmModalResourceToDelete.id
                        }
                    );
                    break;
            }

            if (desiredRoute !== null) {
                router.delete(
                    desiredRoute,
                    {
                        preserveState: false,
                        preserveScroll: true,
                        onFinish: () => {
                            this.resetRemoveConfirmModal()
                        }
                    }
                )
            }
        },
        saveAccount() {
            if (this.accountForm.account_number === '') {
                this.accountFormHelpText = this.$t('The account number must contain at least one character.');
                return;
            }

            if (this.accountForm.title === '') {
                this.accountFormHelpText = this.$t('The description must contain at least one character.');
                return;
            }

            if (this.accountForm.title.length > 255) {
                this.accountFormHelpText = this.$t('The description must not be longer than 255 characters.');
                return;
            }

            this.accountFormHelpText = '';

            this.accountForm.post(
                route('budget-settings.account-management.store-account'),
                {
                    //state is preserved if there are any errors to make sure the user is not required to enter
                    //the data into the form again
                    preserveState: (page) => page.props.flash.error !== null,
                    preserveScroll: true
                }
            );
        },
        saveCostUnit() {
            if (this.costUnitForm.cost_unit_number === '') {
                this.costUnitFormHelpText = this.$t('The cost center number must contain at least one character.');
                return;
            }

            if (this.costUnitForm.title === '') {
                this.costUnitFormHelpText = this.$t('The description must contain at least one character.');
                return;
            }

            if (this.costUnitForm.title.length > 255) {
                this.costUnitFormHelpText = this.$t('The description must not be longer than 255 characters.');
                return;
            }

            this.costUnitFormHelpText = null;

            this.costUnitForm.post(
                route('budget-settings.account-management.store-cost-unit'),
                {
                    preserveState: (page) => page.props.flash.error !== null,
                    preserveScroll: true
                }
            );
        }
    }
})
</script>
