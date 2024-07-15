import { inject } from 'vue';

export function usePermissions(pageProps) {

    const permissions = pageProps.permissionsArray;
    const rolesArray = pageProps.rolesArray;

    function isCurrentUserInComponentUsers(users) {
        return users.findIndex((user) => pageProps.user.id === user.id) >= 0;
    }

    function isCurrentUserInComponentDepartments(departments) {
        let foundUserInDepartment = false;

        departments.forEach((department) => {
            department.users.forEach((user) => {
                if (user.id === pageProps.user.id) {
                    foundUserInDepartment = true;
                }
            });
        });

        return foundUserInDepartment;
    }

    function canSeeComponent(component) {
        if (
            hasAdminRole() ||
            component.permission_type === null ||
            component.permission_type === 'allSeeAndEdit' ||
            component.permission_type === 'allSeeSomeEdit'
        ) {
            return true;
        }

        if (component.permission_type === 'someSeeSomeEdit') {
            return isCurrentUserInComponentUsers(component.users) ||
                isCurrentUserInComponentDepartments(component.departments);
        }
    }

    function canEditComponent(component) {
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
            let foundUserIndex = component.users.findIndex(
                    (user) => pageProps.user.id === user.id
                ),
                foundUserCanWrite = false;

            if (foundUserIndex > -1) {
                foundUserCanWrite = component.users[foundUserIndex].pivot.can_write;
            }

            let foundUserInDepartmentAndCanWrite = false;
            component.departments.forEach((department) => {
                department.users.forEach((user) => {
                    if (!foundUserInDepartmentAndCanWrite && user.id === pageProps.user.id) {
                        foundUserInDepartmentAndCanWrite = department.pivot.can_write;
                    }
                });
            });

            return foundUserCanWrite || foundUserInDepartmentAndCanWrite;
        }
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
