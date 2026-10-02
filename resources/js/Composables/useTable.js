import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

export function useTable({
    routeName,
    initialFilters = {},
    only = [],
    debounceTime = 350,
}) {
    const filters = ref({ ...initialFilters });
    const isLoading = ref(false);
    let debounceTimer = null;

    const navigate = (customFilters = {}, resetPage = false) => {
        const queryParams = {
            ...filters.value,
            ...customFilters,
        };

        if (resetPage) {
            queryParams.page = 1;
        }

        // Clean empty keys
        Object.keys(queryParams).forEach((key) => {
            if (queryParams[key] === '' || queryParams[key] === null || queryParams[key] === undefined) {
                delete queryParams[key];
            }
        });

        const options = {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => { isLoading.value = true; },
            onFinish: () => { isLoading.value = false; },
        };

        if (only && only.length > 0) {
            options.only = only;
        }

        router.get(route(routeName), queryParams, options);
    };

    const handleSearch = (searchTerm) => {
        filters.value.search = searchTerm;
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            navigate({ search: searchTerm }, true);
        }, debounceTime);
    };

    const handleFilterChange = (key, value) => {
        filters.value[key] = value;
        navigate({ [key]: value }, true);
    };

    const resetFilters = () => {
        filters.value = {};
        navigate({}, true);
    };

    return {
        filters,
        isLoading,
        handleSearch,
        handleFilterChange,
        resetFilters,
        navigate,
    };
}
