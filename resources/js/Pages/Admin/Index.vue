<template>
    <AppLayout title="Administration">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-stone-800 dark:text-stone-100">Administration</h2>
        </template>
        <div v-if="enabled && disabled" class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
            <GlassCard title="Installed packages" subtitle="Composer">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <composer-package
                        v-for="pkg in installed"
                        :key="pkg.name"
                        :enabled="enabled"
                        :composer-package="pkg"
                    />
                </div>
            </GlassCard>

            <GlassCard title="Available packages" subtitle="Not installed">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <composer-package
                        v-for="pkg in notInstalled"
                        :key="pkg.name"
                        :composer-package="pkg"
                        @update="() => window.document.dispatchEvent(new Event('updatePackages'))"
                    />
                </div>
            </GlassCard>

            <GlassModal :open="!!jobId" title="Composer log" size="lg" @close="jobId = null">
                <div class="grid grid-cols-1">
                    <VueTerm
                        v-if="jobId"
                        :job-id="jobId"
                        :event-handler="adminEventHandler"
                    />
                </div>
                <template #footer>
                    <GlassButton :disabled="!!jobId" @click="jobId = null">Close</GlassButton>
                </template>
            </GlassModal>
        </div>
    </AppLayout>
</template>

<script>
import dayjs from 'dayjs';
import { router } from '@inertiajs/vue3';
import { notify } from "notiwind";
import AppLayout from '@/Layouts/AppLayout.vue';
import ComposerPackage from "@/Components/ComposerPackage.vue";
import VueTerm from '@/Components/VueTerm.vue';
import GlassCard from '@/Components/Glass/GlassCard.vue';
import GlassModal from '@/Components/Glass/GlassModal.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';

export default {
    components: { AppLayout, ComposerPackage, VueTerm, GlassCard, GlassModal, GlassButton },
    props: ['job_id', 'enabled', 'disabled', 'installed', 'notInstalled'],
    data() {
        return {
            logLines: [],
            jobId: null,
        };
    },
    computed: {
        user() {
            return this.$attrs.auth?.user;
        },
        activityItems() {
            return this.$attrs.activityItems?.data;
        },
    },
    methods: {
        prettyClass(className) {
            return className.split('\\').pop();
        },
        prettyDate(date) {
            return dayjs(date).format('MMM D, YYYY hh:mm A');
        },
        adminEventHandler(AdminChannel) {
            AdminChannel.listen('ComposerActionFinished', () => {
                this.jobId = null;
                window.document.dispatchEvent(new Event('updatePackages'));
                notify({ group: "generic", title: "Info", text: "Composer action has finished successfully" }, 4000);
            }).listen('ComposerActionFailed', () => {
                this.jobId = null;
                window.document.dispatchEvent(new Event('updatePackages'));
                notify({ group: "generic", title: "Info", text: "Composer action has failed" }, 4000);
            });
        },
    },
    mounted() {
        const fetch = () => router.reload({ only: ['enabled', 'disabled', 'installed', 'notInstalled'] });
        window.document.removeEventListener('updatePackages', fetch);
        window.document.addEventListener('updatePackages', fetch);
        Echo.private('user.' + this.user.id)
            .listen('SubscribeToJobEvent', ({ jobId }) => {
                this.jobId = jobId;
            });
    },
};
</script>
