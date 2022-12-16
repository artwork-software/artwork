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
                    :list="legalForms"
                    filterName="legalFormsFilter"
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
            this.filter.legalFormsFilter = []
            this.filter.contractTypesFilter = []
            this.updateFilter()
        },
        updateFilter(params) {
            if(params.item.checked) {
                this.filter[params.filterName].push(params.item)
            }
            else {
                this.filter[params.filterName] = this.filter[params.filterName].filter(it => params.item.name !== it.name)
            }
            this.$emit('filter', this.filter)
        }
    },
    data() {
        return {
            filter: {
                costsFilter: [],
                legalFormsFilter: [],
                contractTypesFilter: []
            },
            additionalCosts: [
                {
                    "checked": false,
                    "name": "Kosten 1"
                },
                {
                    "checked": false,
                    "name": "Kosten 2"
                }
            ],
            legalForms: [
                {
                    "checked": false,
                    "name": "GmbH"
                },
                {
                    "checked": false,
                    "name": "GbR"
                }
            ],
            contractTypes: [
                {
                    "checked": false,
                    "name": "Werkvertrag"
                },
                {
                    "checked": false,
                    "name": "Lizenzvertrag"
                }
            ],
        }
    }
}
</script>

<style scoped>

</style>
