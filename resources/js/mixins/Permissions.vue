<script>
export default {
    methods: {
        $can(permissionName) {
            return Permissions.indexOf(permissionName) !== -1;
        },
        $role(roleName){
            return Roles.indexOf(roleName) !== -1;
        }
    },
};
</script>
