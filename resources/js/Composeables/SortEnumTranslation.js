import {useTranslation} from "@/Composeables/Translation.js";

export function useSortEnumTranslation () {
    const getSortEnumTranslation = (sortEnum) => {
        let parts = sortEnum.split('_');
        let translationKey = parts[0].slice(0,1) +
            parts[0].substring(1).toLowerCase() +
            ' ' +
            parts[1].toLowerCase();

        if (parts.length > 2) {
            let leftover = parts.slice(2).map((part) => part.toLowerCase());
            leftover.unshift('');

            translationKey += leftover.join(' ');
        }

        return useTranslation()(translationKey);
    }

    return {
        getSortEnumTranslation
    };
}
