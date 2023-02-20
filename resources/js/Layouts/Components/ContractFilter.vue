<template>
    <BaseFilter>
        <div class="inline-flex border-none justify-end w-full">
            <button class="flex" @click="resetContractFilter">
                <XIcon class="w-3 mr-1 mt-0.5"/>
                <label class="text-xs">Zurücksetzen</label>
            </button>
        </div>
        <div class="mx-auto w-full max-w-md rounded-2xl bg-primary border-none mt-2">
            <BaseFilterDisclosure title="Zusatzkosten">
                <BaseFilterCheckboxList
                    :list="additionalCosts"
                    filterName="costsFilter"
                    text-if-empty="Noch keine Zusatzkosten angelegt"
                    @changeFilterItems="updateFilter"
                />
            </BaseFilterDisclosure>

            <hr class="border-gray-500 rounded-full mt-2 mb-2">
            <BaseFilterDisclosure title="Rechtsform">
                <BaseFilterCheckboxList
                    :list="companyTypes"
                    filterName="companyTypesFilter"
                    text-if-empty="Noch keine Rechtsformen angelegt"
                    @changeFilterItems="updateFilter" />
            </BaseFilterDisclosure>

            <hr class="border-gray-500 rounded-full mt-2 mb-2">
            <BaseFilterDisclosure title="Vertragsart">
                <BaseFilterCheckboxList
                    :list="contractTypes"
                    filterName="contractTypesFilter"
                    text-if-empty="Noch keine Vertragsarten angelegt"
                    @changeFilterItems="updateFilter" />
            </BaseFilterDisclosure>
        </div>
    </BaseFilter>
</template>

<script>

import BaseFilter from "@/Layouts/Components/BaseFilter";
import {
    XIcon
} from '@heroicons/vue/outline';
import BaseFilterDisclosure from "@/Layouts/Components/BaseFilterDisclosure";
import BaseFilterCheckboxList from "@/Layouts/Components/BaseFilterCheckboxList";
export default {
    name: "ContractFilter",
    components: {BaseFilterCheckboxList, BaseFilterDisclosure, BaseFilter, XIcon},
    methods: {
        resetContractFilter() {
            this.filter.costsFilter = []
            this.filter.companyTypesFilter = []
            this.filter.contractTypesFilter = []
            this.updateFilter()
        },
        updateFilter(params) {
            if(params){
                if(params?.item.checked) {
                    this.filter[params.filterName].push(params.item)
                }
                else {
                    this.filter[params?.filterName] = this.filter[params?.filterName].filter(item => params?.item?.id !== item.id)
                }
            }else{
                this.filter.costsFilter = []
                this.filter.companyTypesFilter = []
                this.filter.contractTypesFilter = []
            }

            this.$emit('filter', this.filter)
        }
    },
    mounted() {
        axios.get(route('contract_types.index')).then(res => {
            this.contractTypes = res.data
        })
        axios.get(route('company_types.index')).then(res => {
            this.companyTypes = res.data
        })
    },
    data() {
        return {
            filter: {
                costsFilter: [],
                companyTypesFilter: [],
                contractTypesFilter: []
            },
            additionalCosts: [
                {
                    "checked": false,
                    "name": "KSK-pflichtig",
                },
                {
                    "checked": false,
                    "name": "Im Ausland ansässig"
                }
            ],
            companyTypes: [],
            contractTypes: []
        }
    }
}
</script>

<style scoped>

</style>
