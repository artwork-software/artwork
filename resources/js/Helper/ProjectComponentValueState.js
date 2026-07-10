const textValueState = {
    read: (data) => data?.text,
    normalize: (value) => String(value ?? '')
        .replace(/<br\s*\/?\s*>/gi, '\n')
        .replace(/<[^>]*>/g, '')
        .replace(/\u00a0/g, ' ')
        .trim(),
    isMeaningful: (value) => value.length > 0,
};

const componentValueStates = {
    Checkbox: {
        read: (data) => data?.checked,
        normalize: (value) => value === true || value === 1 || value === '1' || value === 'true',
        isMeaningful: (value) => value,
    },
    DropDown: {
        read: (data) => data?.selected,
        normalize: (value) => String(value?.value ?? value ?? '').trim(),
        isMeaningful: (value) => value.length > 0,
    },
    Link: textValueState,
    LinkList: {
        read: (data) => data?.links,
        normalize: (links) => (Array.isArray(links) ? links : [])
            .map((link) => ({
                label: String(link?.label ?? '').trim(),
                url: String(link?.url ?? '').trim(),
            }))
            .filter((link) => link.label.length > 0 || link.url.length > 0),
        isMeaningful: (links) => links.length > 0,
    },
    TextArea: textValueState,
    TextField: textValueState,
};

function valuesAreEqual(first, second) {
    return JSON.stringify(first) === JSON.stringify(second);
}

export function componentHasNonDefaultValue(component) {
    const valueState = componentValueStates[component?.type];
    const projectValue = component?.project_value;

    if (!valueState || !projectValue) {
        return false;
    }

    const currentValue = valueState.normalize(valueState.read(projectValue.data));
    const defaultValue = valueState.normalize(valueState.read(component.data));

    return valueState.isMeaningful(currentValue) && !valuesAreEqual(currentValue, defaultValue);
}

export function countFolderNonDefaultValues(disclosureComponents) {
    return (disclosureComponents ?? []).filter(
        (disclosureComponent) => componentHasNonDefaultValue(disclosureComponent?.component)
    ).length;
}
