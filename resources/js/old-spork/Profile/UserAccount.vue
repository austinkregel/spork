<template>
<div class="container mx-auto my-8">
  <div class="m-8 sm:mt-0">
    <div class="md:grid md:grid-cols-3 md:gap-6">
      <div class="md:col-span-1">
        <div class="px-4 sm:px-0">
          <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-50">Personal Information</h3>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
            Use a permanent address where you can receive mail.
          </p>
        </div>
      </div>
      <div class="mt-5 md:mt-0 md:col-span-2">
        <form @submit.prevent="save">
          <div class="shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 bg-white dark:bg-gray-600 sm:p-6">
              <div class="grid grid-cols-6 gap-6">
                <div class="col-span-6 sm:col-span-4">
                  <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                  <spork-input v-model="form.name" type="text" name="name" id="name" autocomplete="name" />
                </div>


                <div class="col-span-6 sm:col-span-4">
                  <label for="email-address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email address</label>
                  <spork-input v-model="form.email" type="text" name="email-address" id="email-address" autocomplete="email" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                  <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Country</label>
                  <select v-model="form.country" id="country" name="country" autocomplete="country-name" class="mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-500 dark:bg-gray-500 bg-white rounded-md shadow-sm focus:outline-none sm:text-sm">
                    <option>United States</option>
                    <option>Canada</option>
                    <option>Mexico</option>
                  </select>
                </div>

                <div class="col-span-6">
                  <label for="street-address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Street address</label>
                  <spork-input v-model="form.address" type="text" name="street-address" id="street-address" autocomplete="street-address" />
                </div>

                <div class="col-span-6 sm:col-span-6 lg:col-span-2">
                  <label for="city" class="block text-sm font-medium text-gray-700 dark:text-gray-300">City</label>
                  <spork-input v-model="form.city" type="text" name="city" id="city" autocomplete="address-level2" />
                </div>

                <div class="col-span-6 sm:col-span-3 lg:col-span-2">
                  <label for="region" class="block text-sm font-medium text-gray-700 dark:text-gray-300">State / Province</label>
                  <spork-input v-model="form.state" type="text" name="region" id="region" autocomplete="address-level1" />
                </div>

                <div class="col-span-6 sm:col-span-3 lg:col-span-2">
                  <label for="postal-code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">ZIP / Postal code</label>
                  <spork-input v-model="form.zip" type="text" name="postal-code" id="postal-code" autocomplete="postal-code" />
                </div>
              </div>
            </div>
            <div class="px-4 py-5 bg-white dark:bg-gray-600 space-y-6 sm:p-6">
              <div>
                <input type="file" class="hidden" ref="photo" @change="updatePhotoPreview" />

                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Profile Photo
                </label>
                <div class="mt-1 flex items-center">
                  <span class="inline-block h-12 w-12 rounded-full overflow-hidden bg-gray-100">
                    <img :src="$store.getters.isAuthenticated.profile_photo" :alt="$store.getters.isAuthenticated.name" class="rounded-full w-12 object-cover">
                  </span>
                  <button @click="selectNewPhoto" type="button" 
                  class="ml-5 bg-white dark:bg-gray-500 dark:border-gray-500 py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                      Select A New Photo
                  </button>
                </div>
                <div class="mt-2" v-show="photoPreview">
                    <span class="block rounded-full w-20 h-20 bg-cover bg-no-repeat bg-center"
                          :style="'background-image: url(\'' + photoPreview + '\');'">
                    </span>
                </div>

              </div>
            </div>
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 text-right sm:px-6">
              <button @click="save" type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Save
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="hidden sm:block" aria-hidden="true">
    <div class="py-5">
      <div class="border-t border-gray-200" />
    </div>
  </div>

  <div class="m-8 sm:mt-4">
    <div class="md:grid md:grid-cols-3 md:gap-6">
      <div class="md:col-span-1">
        <div class="px-4 sm:px-0">
          <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-50">Notifications</h3>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
            Decide which communications you'd like to receive and how.
          </p>
        </div>
      </div>
      <div class="mt-5 md:mt-0 md:col-span-2">
        <form @submit.prevent="save">
          <div class="shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 bg-white dark:bg-gray-600 space-y-6 sm:p-6">
              <fieldset>
                <legend class="text-base font-medium text-gray-900 dark:text-gray-50">By Email</legend>
                <div class="mt-4 space-y-4">
                  <div class="flex items-start">
                    <div class="flex items-center h-5">
                      <input id="comments" name="comments" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded" />
                    </div>
                    <div class="ml-3 text-sm">
                      <label for="comments" class="font-medium text-gray-700 dark:text-gray-300">Comments</label>
                      <p class="text-gray-500 dark:text-gray-400">Get notified when someones posts a comment on a posting.</p>
                    </div>
                  </div>
                  <div class="flex items-start">
                    <div class="flex items-center h-5">
                      <input id="candidates" name="candidates" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded" />
                    </div>
                    <div class="ml-3 text-sm">
                      <label for="candidates" class="font-medium text-gray-700 dark:text-gray-300">Candidates</label>
                      <p class="text-gray-500 dark:text-gray-400">Get notified when a candidate applies for a job.</p>
                    </div>
                  </div>
                  <div class="flex items-start">
                    <div class="flex items-center h-5">
                      <input id="offers" name="offers" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded" />
                    </div>
                    <div class="ml-3 text-sm">
                      <label for="offers" class="font-medium text-gray-700 dark:text-gray-300">Offers</label>
                      <p class="text-gray-500 dark:text-gray-400">Get notified when a candidate accepts or rejects an offer.</p>
                    </div>
                  </div>
                </div>
              </fieldset>
              <fieldset>
                <div>
                  <legend class="text-base font-medium text-gray-900 dark:text-gray-50">Push Notifications</legend>
                  <p class="text-sm text-gray-500 dark:text-gray-400">These are delivered via SMS to your mobile phone.</p>
                </div>
                <div class="mt-4 space-y-4">
                  <div class="flex items-center">
                    <input id="push-everything" name="push-notifications" type="radio" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300" />
                    <label for="push-everything" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Everything
                    </label>
                  </div>
                  <div class="flex items-center">
                    <input id="push-email" name="push-notifications" type="radio" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300" />
                    <label for="push-email" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                      Same as email
                    </label>
                  </div>
                  <div class="flex items-center">
                    <input id="push-nothing" name="push-notifications" type="radio" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300" />
                    <label for="push-nothing" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                      No push notifications
                    </label>
                  </div>
                </div>
              </fieldset>
            </div>
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 text-right sm:px-6">
              <button @click="save" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Save
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
</template>
<script>
import { defineComponent } from 'vue'

export default defineComponent({
  data() {
    return {
      form: {
        email: this.$store.getters.isAuthenticated ? this.$store.getters.isAuthenticated.email : '',
        name: this.$store.getters.isAuthenticated ? this.$store.getters.isAuthenticated.name : '',
        address: this.$store.getters.primaryAddress?.settings?.address,
        state: this.$store.getters.primaryAddress?.settings?.state,
        city: this.$store.getters.primaryAddress?.settings?.city,
        country: this.$store.getters.primaryAddress?.settings?.country,
        zip: this.$store.getters.primaryAddress?.settings?.zip,
        ... this.$store.getters.features?.properties ? {} : {}
      },
      photoPreview: null,
    }
  },
  watch: {
    '$store.getters.isAuthenticated': {
      handler(newValue) {
        this.form.email = newValue.email
        this.form.name = newValue.name
      },
      immediate: true
    },
    '$store.getters.primaryAddress': {
      handler(newValue) {
        if (!newValue?.settings) {
          return;
        }
        this.form.address = newValue.settings?.address
        this.form.state = newValue.settings?.state
        this.form.city = newValue.settings?.city
        this.form.country = newValue.settings?.country
        this.form.zip = newValue.settings?.zip
      },
      immediate: true
    },
  },
  methods: {
    save() {
       if (this.$refs.photo) {
          this.form.photo = this.$refs.photo.files[0]
       }

      this.$store.dispatch('updateProfile', {
        name: this.form.name,
        email: this.form.email,
        photo: this.form.photo,
      });

      if (this.$store.getters.primaryAddress) {
        this.$store.dispatch('updateFeature', {
          ...this.$store.getters.primaryAddress,
          settings: {
            ...this.$store.getters.primaryAddress.settings,
            address: this.form.address,
            state: this.form.state,
            city: this.form.city,
            country: this.form.country,
            zip: this.form.zip,
          }
        });
      }
    },
    
    updatePhotoPreview() {
        const photo = this.$refs.photo.files[0];
        if (! photo) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            this.photoPreview = e.target.result;
        };
        reader.readAsDataURL(photo);
    },
    selectNewPhoto() {
      this.$refs.photo.click();
    },
  }
})
</script>
