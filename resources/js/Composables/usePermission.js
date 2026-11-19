import { usePage } from '@inertiajs/vue3';

export function usePermission() {
    const page = usePage();

    const isOwner = () => page.props.auth?.user?.role === 'owner' || Boolean(page.props.auth?.is_owner);
    const isEmployee = () => page.props.auth?.user?.role === 'employee' && !page.props.auth?.is_owner;
    const isAdmin = () => page.props.auth?.user?.role === 'admin';

    /**
     * Memeriksa apakah user yang sedang login memiliki hak akses tertentu.
     * Owner & Super Admin otomatis bernilai true (unrestricted).
     * Employee mengecek matrix permissions jabatannya.
     * 
     * @param {string} menuKey - Key menu standar (misal: 'employee_data', 'leaves', 'payrolls')
     * @param {string} action - 'view' | 'create' | 'edit' | 'delete'
     * @returns {boolean}
     */
    const hasPermission = (menuKey, action = 'view') => {
        const user = page.props.auth?.user;
        if (!user) return false;

        if (user.role === 'owner' || user.role === 'admin' || page.props.auth?.is_owner) {
            return true;
        }

        const permissions = page.props.auth?.permissions;
        if (!permissions || typeof permissions !== 'object') {
            return false;
        }

        return Boolean(permissions[menuKey]?.[action]);
    };

    /**
     * Memeriksa apakah user memiliki minimal 1 aksi aktif pada menu tertentu
     */
    const hasAnyAction = (menuKey) => {
        return (
            hasPermission(menuKey, 'view') ||
            hasPermission(menuKey, 'create') ||
            hasPermission(menuKey, 'edit') ||
            hasPermission(menuKey, 'delete')
        );
    };

    return {
        hasPermission,
        hasAnyAction,
        isOwner,
        isEmployee,
        isAdmin,
    };
}
