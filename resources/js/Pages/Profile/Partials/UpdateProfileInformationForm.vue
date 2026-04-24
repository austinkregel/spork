<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    _method: 'PUT',
    name: props.user.name,
    email: props.user.email,
    photo: null,
});

const verificationLinkSent = ref(null);
const photoPreview = ref(null);
const photoInput = ref(null);

const updateProfileInformation = () => {
    if (photoInput.value) {
        form.photo = photoInput.value.files[0];
    }

    form.post(route('user-profile-information.update'), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
        onSuccess: () => clearPhotoFileInput(),
    });
};

const sendEmailVerification = () => {
    verificationLinkSent.value = true;
};

const selectNewPhoto = () => {
    photoInput.value.click();
};

const updatePhotoPreview = () => {
    const photo = photoInput.value.files[0];

    if (! photo) return;

    const reader = new FileReader();

    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };

    reader.readAsDataURL(photo);
};

const deletePhoto = () => {
    router.delete(route('current-user-photo.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
            clearPhotoFileInput();
        },
    });
};

const clearPhotoFileInput = () => {
    if (photoInput.value?.value) {
        photoInput.value.value = null;
    }
};
</script>

<template>
    <FormSection @submitted="updateProfileInformation">
        <template #title>
            Profile Information
        </template>

        <template #description>
            Update your account's profile information and email address.
        </template>

        <template #form>
            <div v-if="$page.props.jetstream.managesProfilePhotos" class="col-span-6 sm:col-span-4">
                <input
                    ref="photoInput"
                    type="file"
                    class="hidden"
                    @change="updatePhotoPreview"
                >

                <div class="block text-sm font-medium text-stone-700 dark:text-stone-200">Photo</div>

                <div v-show="! photoPreview" class="mt-2">
                    <img :src="user.profile_photo_url" :alt="user.name" class="rounded-full h-20 w-20 object-cover ring-1 ring-stone-200 dark:ring-stone-700">
                </div>

                <div v-show="photoPreview" class="mt-2">
                    <span
                        class="block rounded-full w-20 h-20 bg-cover bg-no-repeat bg-center ring-1 ring-stone-200 dark:ring-stone-700"
                        :style="'background-image: url(\'' + photoPreview + '\');'"
                    />
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <GlassButton variant="secondary" size="sm" type="button" @click.prevent="selectNewPhoto">
                        Select A New Photo
                    </GlassButton>

                    <GlassButton
                        v-if="user.profile_photo_path"
                        variant="ghost"
                        size="sm"
                        type="button"
                        @click.prevent="deletePhoto"
                    >
                        Remove Photo
                    </GlassButton>
                </div>

                <p v-if="form.errors.photo" class="mt-2 text-xs text-red-500 dark:text-red-400">{{ form.errors.photo }}</p>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <GlassField v-slot="{ id, describedby, invalid }" label="Name" :error="form.errors.name">
                    <GlassInput
                        :id="id"
                        v-model="form.name"
                        type="text"
                        autocomplete="name"
                        :invalid="invalid"
                        :describedby="describedby"
                    />
                </GlassField>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <GlassField v-slot="{ id, describedby, invalid }" label="Email" :error="form.errors.email">
                    <GlassInput
                        :id="id"
                        v-model="form.email"
                        type="email"
                        autocomplete="username"
                        :invalid="invalid"
                        :describedby="describedby"
                    />
                </GlassField>

                <div v-if="$page.props.jetstream.hasEmailVerification && user.email_verified_at === null">
                    <p class="text-sm mt-2 text-stone-700 dark:text-stone-200">
                        Your email address is unverified.

                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="underline text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 rounded-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-stone-50 dark:focus-visible:ring-offset-stone-950"
                            @click.prevent="sendEmailVerification"
                        >
                            Click here to re-send the verification email.
                        </Link>
                    </p>

                    <div v-show="verificationLinkSent" class="mt-2 font-medium text-sm text-emerald-600 dark:text-emerald-400">
                        A new verification link has been sent to your email address.
                    </div>
                </div>
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="mr-3">
                Saved.
            </ActionMessage>

            <GlassButton type="submit" :disabled="form.processing">
                Save
            </GlassButton>
        </template>
    </FormSection>
</template>
