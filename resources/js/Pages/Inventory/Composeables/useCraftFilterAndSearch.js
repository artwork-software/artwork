import {computed, ref} from "vue";

const crafts = ref([]),
    searchValue = ref(''),
    craftFilters = ref([]);

export default function useCraftFilterAndSearch() {
    const filteredCrafts = computed(() => {
        //handle craft filters
        let filteringCrafts = JSON.parse(JSON.stringify(crafts.value));

        if (craftFilters.value.length > 0) {
            filteringCrafts = filteringCrafts.filter(
                (craft) => {
                    return craftFilters.value.length === 0 || craftFilters.value.includes(craft.id);
                }
            );
        }

        if (searchValue.value.length === 0) {
            //if nothing is searched we just append categories properly to the craft object
            filteringCrafts.forEach(
                (craft) => craft.filtered_inventory_categories = craft.inventory_categories
            );

            return filteringCrafts;
        }

        //handle search value
        //@todo: make umlauts searchable Stühl -> Stuhl
        filteringCrafts.forEach((craft) => {
            let filteredCategories = [];

            craft.inventory_categories.forEach((category) => {
                let categoryMatches = false,
                    matchedGroups = [];

                if (category.name.indexOf(searchValue.value) > -1) {
                    categoryMatches = true;
                }

                category.groups.forEach((group) => {
                    let currentGroupMatched = false,
                        matchedItems = [];

                    if (group.name.indexOf(searchValue.value) > -1) {
                        currentGroupMatched = true;
                    }

                    //even if group is not matched we need to filter the items
                    //if group is matched we show all items if no item matches, if at least one item
                    //matches we show only matching items
                    group.items.forEach((item) => {
                        let matchingCells = item.cells.filter((cell) => {
                            return cell.cell_value.indexOf(searchValue.value) > -1
                        });

                        if (matchingCells.length > 0) {
                            matchedItems.push(item);
                        }
                    });

                    //no items found and group not matching, just push if category matched
                    if (matchedItems.length === 0 && !currentGroupMatched) {
                        if (!categoryMatches) {
                            //nothing found
                            return;
                        }
                        //still push group if category matches
                        matchedGroups.push(group);
                        return;
                    }

                    //no items found but group is matching, push it
                    if (matchedItems.length === 0 && currentGroupMatched) {
                        matchedGroups.push(group);
                        return;
                    }

                    //group matched and items too, replace with matched item and push it
                    group.items = matchedItems;
                    matchedGroups.push(group);
                });

                if (
                    categoryMatches ||
                    !categoryMatches && matchedGroups.length > 0
                ) {
                    category.groups = matchedGroups;
                    filteredCategories.push(category);
                }
            });


            craft.filtered_inventory_categories = filteredCategories;
        });

        return filteringCrafts;
    });

    return {searchValue, craftFilters, crafts, filteredCrafts};
}
