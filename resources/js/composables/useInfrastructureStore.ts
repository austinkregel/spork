import { reactive } from 'vue';

type InfrastructureFilters = {
    selectedProviders: string[];
    search: string;
    activeTab: string;
};

const filters = reactive<InfrastructureFilters>({
    selectedProviders: [],
    search: '',
    activeTab: 'servers',
});

let hydrated = false;

type FilterOverrides = Partial<InfrastructureFilters>;

export const useInfrastructureStore = (overrides: FilterOverrides = {}) => {
    if (!hydrated) {
        filters.selectedProviders = overrides.selectedProviders ? [...overrides.selectedProviders] : [];
        filters.search = overrides.search ?? '';
        filters.activeTab = overrides.activeTab ?? 'servers';
        hydrated = true;
    }

    const toggleProvider = (slug: string) => {
        const index = filters.selectedProviders.indexOf(slug);

        if (index >= 0) {
            filters.selectedProviders.splice(index, 1);
            return;
        }

        filters.selectedProviders.push(slug);
    };

    const isProviderSelected = (slug: string) => filters.selectedProviders.includes(slug);

    const resetProviderFilters = () => {
        filters.selectedProviders.splice(0, filters.selectedProviders.length);
    };

    const setSearch = (value: string) => {
        filters.search = value;
    };

    const setActiveTab = (tab: string) => {
        filters.activeTab = tab;
    };

    return {
        filters,
        toggleProvider,
        isProviderSelected,
        resetProviderFilters,
        setSearch,
        setActiveTab,
    };
};

























