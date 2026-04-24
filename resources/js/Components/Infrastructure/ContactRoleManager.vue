<template>
  <div class="space-y-4">
    <GlassSurface
      v-for="(contact, index) in localContacts"
      :key="contact.id ?? index"
      class="space-y-3 p-4"
    >
      <div class="grid gap-3 md:grid-cols-2">
        <GlassField v-slot="{ id, describedby, invalid }" label="Role">
          <GlassSelect
            :id="id"
            v-model="contact.role"
            :invalid="invalid"
            :describedby="describedby"
            @change="emitUpdate(contact)"
          >
            <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
          </GlassSelect>
        </GlassField>
        <GlassField v-slot="{ id, describedby, invalid }" label="Name">
          <GlassInput :id="id" v-model="contact.name" :invalid="invalid" :describedby="describedby" @blur="emitUpdate(contact)" />
        </GlassField>
        <GlassField v-slot="{ id, describedby, invalid }" label="Email">
          <GlassInput :id="id" v-model="contact.email" type="email" :invalid="invalid" :describedby="describedby" @blur="emitUpdate(contact)" />
        </GlassField>
        <GlassField v-slot="{ id, describedby, invalid }" label="Phone">
          <GlassInput :id="id" v-model="contact.phone" type="tel" :invalid="invalid" :describedby="describedby" @blur="emitUpdate(contact)" />
        </GlassField>
      </div>
    </GlassSurface>

    <GlassButton variant="secondary" size="sm" @click="addContact">Add contact</GlassButton>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import GlassSurface from '@/Components/Glass/GlassSurface.vue';
import GlassButton from '@/Components/Glass/GlassButton.vue';
import GlassField from '@/Components/Glass/GlassField.vue';
import GlassInput from '@/Components/Glass/GlassInput.vue';
import GlassSelect from '@/Components/Glass/GlassSelect.vue';

const props = defineProps({
  contacts: { type: Array, default: () => [] },
  roles: { type: Array, default: () => ['Registrant', 'Admin', 'Tech', 'Billing'] },
});

const emit = defineEmits(['update']);

const localContacts = ref(props.contacts.map((contact) => ({ ...contact })));

watch(
  () => props.contacts,
  (contacts) => {
    localContacts.value = contacts.map((contact) => ({ ...contact }));
  },
);

function emitUpdate(contact) {
  emit('update', { ...contact });
}

function addContact() {
  const contact = {
    id: `${Date.now()}-${localContacts.value.length}`,
    role: props.roles[0],
    name: '',
    email: '',
    phone: '',
  };
  localContacts.value.push(contact);
  emitUpdate(contact);
}
</script>
