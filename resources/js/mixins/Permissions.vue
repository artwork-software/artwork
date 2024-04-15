<script>
export default {
    data() {
        return {
            permissions: this.$page.props.permissionsArray,
            rolesArray: this.$page.props.rolesArray
        };
    },
    methods: {
        isCurrentUserInComponentUsers(users) {
            return users.findIndex((user) => this.$page.props.user.id === user.id) >= 0;
        },
        isCurrentUserInComponentDepartments(departments) {
            let foundUserInDepartment = false;

            departments.forEach((department) => {
                department.users.forEach((user) => {
                    if (user.id === this.$page.props.user.id) {
                        foundUserInDepartment = true;
                    }
                });
            });

            return foundUserInDepartment;
        },
        $canSeeComponent(component) {
            if (
                this.hasAdminRole() ||
                component.permission_type === null ||
                component.permission_type === 'allSeeAndEdit' ||
                component.permission_type === 'allSeeSomeEdit'
            ) {
                return true;
            }

            if (component.permission_type === 'someSeeSomeEdit') {
                return this.isCurrentUserInComponentUsers(component.users) ||
                    this.isCurrentUserInComponentDepartments(component.departments);
            }
        },
        $canEditComponent(component) {
            if (
                this.hasAdminRole() ||
                component.permission_type === null ||
                component.permission_type === 'allSeeAndEdit'
            ) {
                return true;
            }

            if (component.permission_type === 'allSeeSomeEdit') {
                return this.isCurrentUserInComponentUsers(component.users) ||
                    this.isCurrentUserInComponentDepartments(component.departments);
            }

            if (component.permission_type === 'someSeeSomeEdit') {
                //find user in component users
                let foundUserIndex = component.users.findIndex(
                      (user) => this.$page.props.user.id === user.id
                    ),
                    foundUserCanWrite = false;

                if (foundUserIndex > -1) {
                    foundUserCanWrite = component.users[foundUserIndex].pivot.can_write;
                }

                //find user in departments
                let foundUserInDepartmentAndCanWrite = false;
                component.departments.forEach((department) => {
                    department.users.forEach((user) => {
                        //only updated if it is not true already, because there is no opportunity to break the
                        //forEach loop
                        if (!foundUserInDepartmentAndCanWrite && user.id === this.$page.props.user.id) {
                            foundUserInDepartmentAndCanWrite = department.pivot.can_write;
                        }
                    });
                });

                return foundUserCanWrite || foundUserInDepartmentAndCanWrite;
            }
        },
        $can(permissionName) {
            return this.$page.props.permissionsArray.includes(permissionName);
        },
        $role(roleName) {
            return this.$page.props.rolesArray.includes(roleName);
        },
        $canAny(permissionNames) {
            for (const permission of permissionNames) {
                if (this.$can(permission)) {
                    return true;
                }
            }
            return false;
        },
        $roleAny(roleNames) {
            for (const role of roleNames) {
                if (this.$role(role)) {
                    return true;
                }
            }
            return false;
        },
        hasAdminRole(){
            return this.$role('artwork admin');
        }
    }
};
</script>
