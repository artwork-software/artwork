export function usePermission(pageProps) {

    const permissions = pageProps.permissionsArray;
    const rolesArray = pageProps.rolesArray;

    function isCurrentUserInComponentUsers(users) {
        if (!users || !Array.isArray(users)) {
            return false;
        }
        return users.findIndex((user) => user && pageProps.user && pageProps.user.id === user.id) >= 0;
    }

    function isCurrentUserInComponentDepartments(departments) {
        if (!departments || !Array.isArray(departments)) {
            return false;
        }

        let foundUserInDepartment = false;

        departments.forEach((department) => {
            if (department && department.users && Array.isArray(department.users)) {
                department.users.forEach((user) => {
                    if (user && pageProps.user && user.id === pageProps.user.id) {
                        foundUserInDepartment = true;
                    }
                });
            }
        });

        return foundUserInDepartment;
    }

    function canSeeComponent(component) {
        if (!component) {
            return false;
        }

        if (
            hasAdminRole() ||
            component.permission_type === null ||
            component.permission_type === 'allSeeAndEdit' ||
            component.permission_type === 'allSeeSomeEdit' ||
            can('view projects')
        ) {
            return true;
        }

        if (component.permission_type === 'someSeeSomeEdit') {
            return isCurrentUserInComponentUsers(component.users) ||
                isCurrentUserInComponentDepartments(component.departments);
        }

        return false;
    }

    function canEditComponent(component) {
        if (!component) {
            return false;
        }

        if (
            hasAdminRole() ||
            component.permission_type === null ||
            component.permission_type === 'allSeeAndEdit'
        ) {
            return true;
        }

        if (component.permission_type === 'allSeeSomeEdit') {
            return isCurrentUserInComponentUsers(component.users) ||
                isCurrentUserInComponentDepartments(component.departments);
        }

        if (component.permission_type === 'someSeeSomeEdit') {
            let foundUserIndex = -1;
            let foundUserCanWrite = false;

            if (component.users && Array.isArray(component.users)) {
                foundUserIndex = component.users.findIndex(
                    (user) => user && pageProps.user && pageProps.user.id === user.id
                );

                if (foundUserIndex > -1 && component.users[foundUserIndex] && component.users[foundUserIndex].pivot) {
                    foundUserCanWrite = component.users[foundUserIndex].pivot.can_write;
                }
            }

            let foundUserInDepartmentAndCanWrite = false;
            if (component.departments && Array.isArray(component.departments)) {
                component.departments.forEach((department) => {
                    if (department && department.users && Array.isArray(department.users) && department.pivot) {
                        department.users.forEach((user) => {
                            if (!foundUserInDepartmentAndCanWrite && user && pageProps.user && user.id === pageProps.user.id) {
                                foundUserInDepartmentAndCanWrite = department.pivot.can_write;
                            }
                        });
                    }
                });
            }

            return foundUserCanWrite || foundUserInDepartmentAndCanWrite;
        }

        return false;
    }

    function can(permissionName) {
        return permissions.includes(permissionName);
    }

    function role(roleName) {
        return rolesArray.includes(roleName);
    }

    function canAny(permissionNames) {
        for (const permission of permissionNames) {
            if (can(permission)) {
                return true;
            }
        }
        return false;
    }

    function roleAny(roleNames) {
        for (const role of roleNames) {
            if (role(role)) {
                return true;
            }
        }
        return false;
    }

    function hasAdminRole() {
        return role('artwork admin');
    }

    return {
        canSeeComponent,
        canEditComponent,
        can,
        role,
        canAny,
        roleAny,
        hasAdminRole
    };
}
