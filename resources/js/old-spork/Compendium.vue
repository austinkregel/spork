<template>
    <dual-menu-panel
        name="Compendium"
        :navigation="routes"
        redirect="/compendium"
        store-query-action="queryCompendium"
        feature="compendium"
        :loading="$store.getters.featuresLoading"
    />
</template>

<script>
import {
    BookmarkIcon,
    BeakerIcon,
    UserIcon,
    StarIcon,
    CogIcon,
    RssIcon,
    NewspaperIcon,
    CakeIcon,
    TagIcon,
    BookOpenIcon
} from '@heroicons/vue/solid';

export default {
    computed: {
        routes() {
            console.log(this.$route.path)
            return [
                {
                    name: 'People',
                    href: '/compendium/people',
                    icon: UserIcon,
                    current: false,
                },
                {
                    name: 'News',
                    href: '/compendium/news',
                    icon: NewspaperIcon,
                    current: false,
                },
                {
                    name: 'The Feed',
                    href: '/compendium/rss/unfiltered',
                    icon: BookOpenIcon,
                    current: false,
                },
                {
                    name: 'RSS Feed',
                    href: '/compendium/rss',
                    icon: RssIcon,
                    current: false,
                },
                {
                    name: 'Research',
                    href: '/compendium/research',
                    icon: BeakerIcon,
                    current: false,
                },
                {
                    name: 'Bookmarks',
                    href: '/compendium/bookmarks',
                    icon: BookmarkIcon,
                    current: false,
                },
                {
                    name: 'Favorites',
                    href: '/compendium/favorites',
                    icon: StarIcon,
                    current: false,
                },

                {
                    name: 'Settings',
                    href: '/compendium/settings',
                    icon: CogIcon,
                    current: false,
                },

            ]
        }
    },
    watch: {
        '$route.path'(newVal, oldVal) {
            const feature = newVal.split('/').filter(i => i).filter(i => i !== 'compendium')[0];

            if (!feature) {
                return;
            }

            this.$store.dispatch('fetchFeatures', {
                page: 1,
                limit: 100,
                feature,
            })
        }
    },
    methods: {
        activeRoute(name, path, icon) {
            return {
                name,
                href: path,
                icon,
                current: this.$route.path === path,
            };
        }
    },
    mounted() {
        const feature = this.$route.path.split('/').filter(i => i).filter(i => i !== 'compendium')[0];

        if (!feature) {
            return;
        }

        this.$store.dispatch('fetchFeatures', {
            page: 1,
            limit: 100,
            feature,
        });
    }
}
</script>
