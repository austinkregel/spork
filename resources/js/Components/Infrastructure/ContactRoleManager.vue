<template>
    <div class="space-y-4">
        <div
            v-for="(contact, index) in localContacts"
            :key="contact.id ?? index"
            class="border border-stone-200 dark:border-stone-800 rounded-lg p-4 bg-white dark:bg-stone-900 shadow-sm space-y-3"
        >
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                        Role
                    </label>
                    <select
                        v-model="contact.role"
                        class="mt-1 block w-full rounded-md border-stone-300 dark:border-stone-700 dark:bg-stone-800 text-sm text-stone-800 dark:text-stone-100 focus:outline-none focus:ring-stone-500 focus:border-stone-500"
                        @change="emitUpdate(contact)"
                    >
                        <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                        Name
                    </label>
                    <SporkInput v-model="contact.name" class="mt-1 w-full" @blur="emitUpdate(contact)" />
                </div>
                <div>
                    <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                        Email
                    </label>
                    <SporkInput v-model="contact.email" class="mt-1 w-full" @blur="emitUpdate(contact)" />
                </div>
                <div>
                    <label class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400 font-semibold">
                        Phone
                    </label>
                    <SporkInput v-model="contact.phone" class="mt-1 w-full" @blur="emitUpdate(contact)" />
                </div>
            </div>
        </div>

        <SporkButton secondary xsmall @click="addContact">
            Add contact
        </SporkButton>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import SporkButton from "@/Components/Spork/SporkButton.vue";
import SporkInput from "@/Components/Spork/SporkInput.vue";

const props = defineProps({
    contacts: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => ['Registrant', 'Admin', 'Tech', 'Billing'],
    },
});

const emit = defineEmits(['update']);

const localContacts = ref(props.contacts.map((contact) => ({ ...contact })));

watch(() => props.contacts, (contacts) => {
    localContacts.value = contacts.map((contact) => ({ ...contact }));
});

const emitUpdate = (contact) => {
    emit('update', { ...contact });
};

const addContact = () => {
    const contact = {
        id: `${Date.now()}-${localContacts.value.length}`,
        role: props.roles[0],
        name: '',
        email: '',
        phone: '',
    };
    localContacts.value.push(contact);
    emitUpdate(contact);
};
</script>



















