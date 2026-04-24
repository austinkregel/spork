<script setup>
import { ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import ActionSection from '@/Components/ActionSection.vue';
import FormSection from '@/Components/FormSection.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import GlassModal from '@/Components/Glass/GlassModal.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassSurface from '@/Components/Glass/GlassSurface.vue';
import { CheckCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    team: Object,
    availableRoles: Array,
    userPermissions: Object,
});

const addTeamMemberForm = useForm({
    email: '',
    role: null,
});

const updateRoleForm = useForm({
    role: null,
});

const leaveTeamForm = useForm({});
const removeTeamMemberForm = useForm({});

const currentlyManagingRole = ref(false);
const managingRoleFor = ref(null);
const confirmingLeavingTeam = ref(false);
const teamMemberBeingRemoved = ref(null);

const addTeamMember = () => {
    addTeamMemberForm.post(route('team-members.store', props.team), {
        errorBag: 'addTeamMember',
        preserveScroll: true,
        onSuccess: () => addTeamMemberForm.reset(),
    });
};

const cancelTeamInvitation = (invitation) => {
    router.delete(route('team-invitations.destroy', invitation), {
        preserveScroll: true,
    });
};

const manageRole = (teamMember) => {
    managingRoleFor.value = teamMember;
    updateRoleForm.role = teamMember.membership.role;
    currentlyManagingRole.value = true;
};

const updateRole = () => {
    updateRoleForm.put(route('team-members.update', [props.team, managingRoleFor.value]), {
        preserveScroll: true,
        onSuccess: () => currentlyManagingRole.value = false,
    });
};

const confirmLeavingTeam = () => {
    confirmingLeavingTeam.value = true;
};

const leaveTeam = () => {
    leaveTeamForm.delete(route('team-members.destroy', [props.team, usePage().props.auth.user]));
};

const confirmTeamMemberRemoval = (teamMember) => {
    teamMemberBeingRemoved.value = teamMember;
};

const removeTeamMember = () => {
    removeTeamMemberForm.delete(route('team-members.destroy', [props.team, teamMemberBeingRemoved.value]), {
        errorBag: 'removeTeamMember',
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => teamMemberBeingRemoved.value = null,
    });
};

const displayableRole = (role) => {
    return props.availableRoles.find(r => r.key === role).name;
};
</script>

<template>
    <div>
        <div v-if="userPermissions.canAddTeamMembers">
            <SectionBorder />

            <!-- Add Team Member -->
            <FormSection @submitted="addTeamMember">
                <template #title>
                    Add Team Member
                </template>

                <template #description>
                    Add a new team member to your team, allowing them to collaborate with you.
                </template>

                <template #form>
                    <div class="col-span-6">
                        <div class="max-w-xl text-sm text-stone-600 dark:text-stone-400">
                            Please provide the email address of the person you would like to add to this team.
                        </div>
                    </div>

                    <div class="col-span-6 sm:col-span-4">
                        <GlassField v-slot="{ id, describedby, invalid }" label="Email" :error="addTeamMemberForm.errors.email">
                            <GlassInput
                                :id="id"
                                v-model="addTeamMemberForm.email"
                                type="email"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </GlassField>
                    </div>

                    <div v-if="availableRoles.length > 0" class="col-span-6 lg:col-span-4">
                        <div class="block text-sm font-medium text-stone-700 dark:text-stone-200">Role</div>
                        <p v-if="addTeamMemberForm.errors.role" class="mt-2 text-xs text-red-500 dark:text-red-400">{{ addTeamMemberForm.errors.role }}</p>

                        <GlassSurface class="mt-1 divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)] overflow-hidden">
                            <button
                                v-for="role in availableRoles"
                                :key="role.key"
                                type="button"
                                class="block w-full px-4 py-3 text-left transition-colors motion-reduce:transition-none focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-500"
                                @click="addTeamMemberForm.role = role.key"
                            >
                                <div :class="{'opacity-50': addTeamMemberForm.role && addTeamMemberForm.role !== role.key}">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-stone-700 dark:text-stone-200" :class="{'font-semibold': addTeamMemberForm.role === role.key}">{{ role.name }}</span>
                                        <CheckCircleIcon v-if="addTeamMemberForm.role === role.key" class="h-5 w-5 text-emerald-500" aria-hidden="true" />
                                    </div>
                                    <div class="mt-2 text-xs text-stone-500 dark:text-stone-400">{{ role.description }}</div>
                                </div>
                            </button>
                        </GlassSurface>
                    </div>
                </template>

                <template #actions>
                    <ActionMessage :on="addTeamMemberForm.recentlySuccessful" class="mr-3">Added.</ActionMessage>
                    <GlassButton :disabled="addTeamMemberForm.processing">Add</GlassButton>
                </template>
            </FormSection>
        </div>

        <div v-if="team.team_invitations.length > 0 && userPermissions.canAddTeamMembers">
            <SectionBorder />

            <!-- Team Member Invitations -->
            <ActionSection class="mt-10 sm:mt-0">
                <template #title>
                    Pending Team Invitations
                </template>

                <template #description>
                    These people have been invited to your team and have been sent an invitation email. They may join the team by accepting the email invitation.
                </template>

                <!-- Pending Team Member Invitation List -->
                <template #content>
                    <div class="space-y-6">
                        <div v-for="invitation in team.team_invitations" :key="invitation.id" class="flex items-center justify-between">
                            <div class="text-stone-600 dark:text-stone-400">
                                {{ invitation.email }}
                            </div>

                            <div class="flex items-center">
                                <!-- Cancel Team Invitation -->
                                <button
                                    v-if="userPermissions.canRemoveTeamMembers"
                                    class="cursor-pointer ml-6 text-sm text-red-500 focus:outline-none"
                                    @click="cancelTeamInvitation(invitation)"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </ActionSection>
        </div>

        <div v-if="team.users.length > 0">
            <SectionBorder />

            <!-- Manage Team Members -->
            <ActionSection class="mt-10 sm:mt-0">
                <template #title>
                    Team Members
                </template>

                <template #description>
                    All of the people that are part of this team.
                </template>

                <!-- Team Member List -->
                <template #content>
                    <div class="space-y-6">
                        <div v-for="user in team.users" :key="user.id" class="flex items-center justify-between">
                            <div class="flex items-center">
                                <img class="w-8 h-8 rounded-full object-cover" :src="user.profile_photo_url" :alt="user.name">
                                <div class="ml-4 dark:text-white">
                                    {{ user.name }}
                                </div>
                            </div>

                            <div class="flex items-center">
                                <!-- Manage Team Member Role -->
                                <button
                                    v-if="userPermissions.canUpdateTeamMembers && availableRoles.length"
                                    class="ml-2 text-sm text-stone-400 underline"
                                    @click="manageRole(user)"
                                >
                                    {{ displayableRole(user.membership.role) }}
                                </button>

                                <div v-else-if="availableRoles.length" class="ml-2 text-sm text-stone-400">
                                    {{ displayableRole(user.membership.role) }}
                                </div>

                                <!-- Leave Team -->
                                <button
                                    v-if="$page.props.auth.user.id === user.id"
                                    class="cursor-pointer ml-6 text-sm text-red-500"
                                    @click="confirmLeavingTeam"
                                >
                                    Leave
                                </button>

                                <!-- Remove Team Member -->
                                <button
                                    v-else-if="userPermissions.canRemoveTeamMembers"
                                    class="cursor-pointer ml-6 text-sm text-red-500"
                                    @click="confirmTeamMemberRemoval(user)"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </ActionSection>
        </div>

        <GlassModal :open="currentlyManagingRole" title="Manage Role" size="md" @close="currentlyManagingRole = false">
            <div v-if="managingRoleFor">
                <GlassSurface class="divide-y divide-[var(--color-glass-border-light)] dark:divide-[var(--color-glass-border-dark)] overflow-hidden">
                    <button
                        v-for="role in availableRoles"
                        :key="role.key"
                        type="button"
                        class="block w-full px-4 py-3 text-left transition-colors motion-reduce:transition-none focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-500"
                        @click="updateRoleForm.role = role.key"
                    >
                        <div :class="{'opacity-50': updateRoleForm.role && updateRoleForm.role !== role.key}">
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-stone-700 dark:text-stone-200" :class="{'font-semibold': updateRoleForm.role === role.key}">{{ role.name }}</span>
                                <CheckCircleIcon v-if="updateRoleForm.role === role.key" class="h-5 w-5 text-emerald-500" aria-hidden="true" />
                            </div>
                            <div class="mt-2 text-xs text-stone-500 dark:text-stone-400">{{ role.description }}</div>
                        </div>
                    </button>
                </GlassSurface>
            </div>

            <template #footer>
                <GlassButton variant="secondary" @click="currentlyManagingRole = false">Cancel</GlassButton>
                <GlassButton :disabled="updateRoleForm.processing" @click="updateRole">Save</GlassButton>
            </template>
        </GlassModal>

        <GlassModal :open="confirmingLeavingTeam" title="Leave Team" size="sm" @close="confirmingLeavingTeam = false">
            <p class="text-sm text-stone-700 dark:text-stone-200">Are you sure you would like to leave this team?</p>
            <template #footer>
                <GlassButton variant="secondary" @click="confirmingLeavingTeam = false">Cancel</GlassButton>
                <GlassButton variant="destructive" :disabled="leaveTeamForm.processing" @click="leaveTeam">Leave</GlassButton>
            </template>
        </GlassModal>

        <GlassModal :open="!!teamMemberBeingRemoved" title="Remove Team Member" size="sm" @close="teamMemberBeingRemoved = null">
            <p class="text-sm text-stone-700 dark:text-stone-200">Are you sure you would like to remove this person from the team?</p>
            <template #footer>
                <GlassButton variant="secondary" @click="teamMemberBeingRemoved = null">Cancel</GlassButton>
                <GlassButton variant="destructive" :disabled="removeTeamMemberForm.processing" @click="removeTeamMember">Remove</GlassButton>
            </template>
        </GlassModal>
    </div>
</template>
