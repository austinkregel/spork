<template>
    <div class="flex flex-wrap gap-4 m-4">
        <feature-required feature="research" allow-more-than-one ></feature-required>
        <div class="w-full font-medium text-stone-600 dark:text-stone-300 uppercase ml-3">Recent</div>
        <div
            v-for="(topic, i) in $store.getters.features?.research ?? []"
            class="w-64 p-3 border border-stone-200 dark:border-stone-600 rounded-lg bg-white dark:bg-stone-600"
            @contextmenu.prevent="(e) => openContextMenu(e, topic)"
            :key="'research-'+i"
            >
            <router-link
                :to="'/research/'+ topic.id"
            >
                <div  class="font-medium truncate">{{ topic.name }}</div>
            </router-link>
            <pre class=" h-48 shadow-inset overflow-hidden text-xs border-t py-2 my-2">{{ topic.settings.body }}</pre>
            <div class="text-stone-500 dark:text-stone-200 border-t mt-4 pt-2 flex items-center justify-between">
                <span>{{ date(topic.updated_at) }}</span>

                <button @click="() => deleteFeature(topic)">
                    <TrashIcon class="w-4 h-4 text-red-500" />
                </button>
            </div>
        </div>

        <div  v-if="openContext && openForTopic">
            <div @click="openContext = false" class="absolute inset-0 z-0 bg-stone-900/20 cusor-pointer"></div>

            <div class="absolute z-10 mt-2 w-56 rounded-md shadow-lg bg-white dark:bg-stone-600 ring-1 ring-black ring-opacity-5 focus:outline-none" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1"
            :style="'top: '+contextY+'px; left: '+contextX+'px;'"
            >
                <div class="py-1 text-sm flex flex-col" role="none">
                    <!-- Active: "bg-stone-100 text-stone-900", Not Active: "text-stone-700" -->
                    <router-link :to="'/research/'+openForTopic.id" class="flex items-center gap-2 text-stone-700 dark:text-stone-200 px-4 py-2" role="menuitem" tabindex="-1">
                        <ExternalLinkIcon class="w-4 h-4" />
                        Open
                    </router-link>

                    <button @click="duplicateFeature" class="flex items-center gap-2 text-stone-700 dark:text-stone-200 px-4 py-2" role="menuitem" tabindex="-1">
                        <DocumentDuplicateIcon class="w-4 h-4" />
                        Duplicate
                    </button>

                    <button @click="renameFeature" class="flex items-center gap-2 text-stone-700 dark:text-stone-200 px-4 py-2" role="menuitem" tabindex="-1">
                        <PencilIcon class="w-4 h-4" />
                        Rename
                    </button>

                    <button @click="shareFeature" class="flex items-center gap-2 text-stone-700 dark:text-stone-200 px-4 py-2" role="menuitem" tabindex="-1">
                        <UserAddIcon class="w-4 h-4" />
                        Share
                    </button>

                    <hr class="border-t border-stone-200 dark:border-stone-500" />

                    <button @click="deleteFeature" class="flex items-center gap-2 px-4 py-2 ">
                        <TrashIcon class="w-4 h-4 text-red-500" />
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import FeatureRequired from '../../components/FeatureRequired.vue';
import {
    TrashIcon,
    ExternalLinkIcon,
    DocumentDuplicateIcon,
    PencilIcon,
    UserAddIcon
} from '@heroicons/vue/outline';

export default {
    components: {
        FeatureRequired,
        TrashIcon,
        ExternalLinkIcon,
        DocumentDuplicateIcon,
        PencilIcon,
        UserAddIcon
    },
    name: "Dashboard.vue",
    data() {
        return {
            openContext: false,
            contextX: 0,
            contextY: 0,
            openForTopic: null,
        };
    },
    methods: {
        date(date) {
            return dayjs(date).format('lll')
        },
        openContextMenu(e, topic) {
            this.openContext = true;
            this.contextX = e.clientX;
            this.contextY = e.clientY;
            this.openForTopic = topic;
        },
        closeMenu() {
            this.openContext = false;
            this.openForTopic = null;
        },
        duplicateFeature() {
            this.$store.dispatch('duplicateResearch', this.openForTopic);
            this.closeMenu();
        },
        deleteFeature(feature = undefined) {
            console.log({ feature })
            this.$store.dispatch('deleteFeature', feature ?? this.openForTopic);
            this.closeMenu();
        },
        renameFeature() {
            this.$store.dispatch('updateFeature', {
                ...this.openForTopic,
                name: prompt('What\'s the new name for this topic?', this.openForTopic.name)
            });
            this.closeMenu();
        },
        shareFeature() {
            this.$store.dispatch('shareFeature', {
                feature: this.openForTopic,
                email: prompt('What\'s the email address of the person you want to share this topic with?')
            });
            this.closeMenu();
        },
    }
}
</script>
