<template>
  <div>
      <div class="text-4xl m-4">
          Initial Setup
      </div>
    <div class="shadow sm:rounded-md sm:overflow-hidden m-4" v-if="form.development">
        <div class="bg-white dark:bg-slate-600 py-6 px-4 space-y-6 sm:p-6">
          <div>
            <h3 class="text-lg leading-6 font-medium text-slate-900 dark:text-slate-50">Deveopment</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Should we import modules in your `system` folder?.</p>
          </div>

          <div class="grid grid-cols-3 gap-2">
            <div v-for="(project, index) in (form?.development?.projects ?? [])" :key="'project.'+index" class="col-span-3 sm:col-span-2 border-t pt-2">
                <label for="company-website" class="block text-sm font-medium text-slate-700 dark:text-slate-200"> Name </label>
                <div class="mt-1 rounded-md shadow-sm flex">
                  <spork-input v-model="form.development.projects[index].name" type="text" />
                </div>
                <label for="company-website" class="block text-sm font-medium text-slate-700 dark:text-slate-200"> Path </label>
                <div class="mt-1 rounded-md shadow-sm flex">
                  <spork-input v-model="form.development.projects[index].settings.path" type="text" />
                </div>
                <button @click="form.development.projects = form?.development?.projects.filter((s, i) => i !== index)" class="text-red-500 hover:text-red-700">Remove</button>
            </div>

            <div class="col-span-3">
              <button class="text-xs" @click="form.development.projects.push({ name: '', settings: {}})">+ Add status</button>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-6">
             <div class="flex items-center">
                <input v-model="form.development.import" id="push-nothing" name="push-notifications" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                <label for="push-nothing" class="ml-3">
                  <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">Import System Development (Best for developing with Spork)</span>
                </label>
              </div>
          </div>
          <div class="grid grid-cols-3 gap-6">
             <div class="flex items-center">
                <input v-model="form.development.enabled" id="enable_development" name="enable_development" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                <label for="enable_development" class="ml-3">
                  <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">Enable Feature</span>
                </label>
              </div>
          </div>
        </div>
    </div>

    <div class="shadow sm:rounded-md sm:overflow-hidden m-4" v-if="form.calendar">
        <div class="bg-white dark:bg-slate-600 py-6 px-4 space-y-6 sm:p-6">
          <div>
            <h3 class="text-lg leading-6 font-medium text-slate-900 dark:text-slate-50">Calendar</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">This is the name of your calendar.</p>
          </div>

          <div class="grid grid-cols-3 gap-6">
            <div class="col-span-3 sm:col-span-2">
              <label for="company-website" class="block text-sm font-medium text-slate-700 dark:text-slate-200"> Name </label>
              <div class="mt-1 rounded-md shadow-sm flex">
                <spork-input v-model="form.calendar.name" type="text" />
              </div>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-6">
             <div class="flex items-center">
                <input v-model="form.calendar.enabled" name="enable" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                <label class="ml-3">
                  <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">Enable Feature</span>
                </label>
              </div>
          </div>
        </div>
    </div>

    <div class="shadow sm:rounded-md sm:overflow-hidden m-4" v-if="form.finance">
        <div class="bg-white dark:bg-slate-600 py-6 px-4 space-y-6 sm:p-6">
          <div>
            <h3 class="text-lg leading-6 font-medium text-slate-900 dark:text-slate-50">Finance</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">This is your Plaid configuration. Please setup your account through Plaid.com</p>
          </div>

          <div class="grid grid-cols-3 gap-6">
            <div class="col-span-3 sm:col-span-2">
              <label for="plaid_client_secret" class="block text-sm font-medium text-slate-700 dark:text-slate-200"> Plaid Client Secret </label>
              <div class="mt-1 rounded-md shadow-sm flex">
                <spork-input v-model="form.finance.plaid_client_secret" type="text" name="plaid_client_secret" id="plaid_client_secret" />
              </div>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-6">
            <div class="col-span-3 sm:col-span-2">
              <label for="company-website" class="block text-sm font-medium text-slate-700 dark:text-slate-200"> Plaid Client ID </label>
              <div class="mt-1 rounded-md shadow-sm flex">
                <spork-input v-model="form.finance.plaid_client_id" type="text" name="plaid_client_id" id="plaid_client_id" />
              </div>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-6">
            <div class="col-span-3 sm:col-span-2">
              <label for="company-website" class="block text-sm font-medium text-slate-700 dark:text-slate-200"> Plaid API Key </label>
              <div class="mt-1 rounded-md shadow-sm flex">
                <spork-input v-model="form.finance.plaid_env" type="text" name="plaid_env" id="plaid_env" />
              </div>
            </div>
          </div>
          <div class="">
             <div class="flex items-center">
                <input v-model="form.finance.enabled" name="enable" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                <label class="ml-3">
                  <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">Enable Feature</span>
                </label>
              </div>
          </div>
        </div>
    </div>

    
    <div class="shadow sm:rounded-md sm:overflow-hidden m-4" v-if="form.garage">
        <div class="bg-white dark:bg-slate-600 py-6 px-4 space-y-6 sm:p-6">
          <div>
            <h3 class="text-lg leading-6 font-medium text-slate-900 dark:text-slate-50">Garage</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Details of the vehicles in your garage, and potentially reminders for maintenance!</p>
          </div>

          <div class="grid grid-cols-3 gap-6">
            <div class="col-span-3 sm:col-span-2">
              <label for="company-website" class="block text-sm font-medium text-slate-700 dark:text-slate-200"> Name </label>
              <div class="mt-1 rounded-md shadow-sm flex">
                <spork-input v-model="form.garage.name" type="text" />
              </div>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-6">
            <div class="col-span-3 sm:col-span-2">
              <label for="company-website" class="block text-sm font-medium text-slate-700 dark:text-slate-200"> VIN </label>
              <div class="mt-1 rounded-md shadow-sm flex">
                <spork-input v-model="form.garage.vin" type="text"/>
              </div>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-6">
             <div class="flex items-center">
                <input v-model="form.garage.enabled" name="enable" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                <label class="ml-3">
                  <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">Enable Feature</span>
                </label>
              </div>
          </div>
        </div>
    </div>
    <div class="shadow sm:rounded-md sm:overflow-hidden m-4" v-if="form.properties">
        <div class="bg-white dark:bg-slate-600 py-6 px-4 space-y-6 sm:p-6">
          <div>
            <h3 class="text-lg leading-6 font-medium text-slate-900 dark:text-slate-50">Properties</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Details of land, or homes you may own or lease.</p>
          </div>

          <div class="grid grid-cols-3 gap-6">
            <div class="col-span-3 sm:col-span-2">
              <label for="company-website" class="block text-sm font-medium text-slate-700 dark:text-slate-200"> Name </label>
              <div class="mt-1 rounded-md shadow-sm flex">
                <spork-input v-model="form.properties.name" type="text" />
              </div>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-6">
            <div class="col-span-3 sm:col-span-2">
              <label for="company-website" class="block text-sm font-medium text-slate-700 dark:text-slate-200"> Address </label>
              <div class="mt-1 rounded-md shadow-sm flex">
                <spork-input v-model="form.properties.settings.address" type="text"/>
              </div>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-6">
             <div class="flex items-center">
                <input v-model="form.properties.settings.is_primary_address" name="enable" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                <label class="ml-3">
                  <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">Primary Address (Where you spend more than 6 months of the year)</span>
                </label>
              </div>
          </div>
          <div class="grid grid-cols-3 gap-6">
             <div class="flex items-center">
                <input v-model="form.properties.enabled" name="enable" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                <label class="ml-3">
                  <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">Enable Feature</span>
                </label>
              </div>
          </div>
        </div>
    </div>

    <div class="shadow sm:rounded-md sm:overflow-hidden m-4" v-if="form.planning">
        <div class="bg-white dark:bg-slate-600 py-6 px-4 space-y-6 sm:p-6">
          <div>
            <h3 class="text-lg leading-6 font-medium text-slate-900 dark:text-slate-50">Planning</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">A Kanban style board to help coordinate progress</p>
          </div>


          <div class="grid grid-cols-3 gap-2">
            <div class="col-span-3 sm:col-span-2">
              <label for="company-website" class="block text-sm font-medium text-slate-700 dark:text-slate-200"> Name </label>
              <div class="mt-1 rounded-md shadow-sm flex" v-for="(status, index) in form.planning.statuses">
                <spork-input v-model="form.planning.statuses[index]" type="text" />
                <button @click="form.planning.statuses = form.planning.statuses.filter((s, i) => i !== index)" class="text-red-500 hover:text-red-700 ml-2">Remove</button>
              </div>
            </div>

            <div class="col-span-3">
              <button class="text-xs" @click="form.planning.statuses.push('')">+ Add status</button>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-6">
             <div class="flex items-center">
                <input v-model="form.planning.enabled" name="enable" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                <label class="ml-3">
                  <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">Enable Feature</span>
                </label>
              </div>
          </div>
        </div>
    </div>

    <div class="shadow sm:rounded-md sm:overflow-hidden m-4" v-if="form.research">
        <div class="bg-white dark:bg-slate-600 py-6 px-4 space-y-6 sm:p-6">
          <div>
            <h3 class="text-lg leading-6 font-medium text-slate-900 dark:text-slate-50">Research</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Keep notes on things you're looking into in a searchable database!</p>
          </div>

          <div class="grid grid-cols-3 gap-6">
            <div class="col-span-3 sm:col-span-2">
              <label for="company-website" class="block text-sm font-medium text-slate-700 dark:text-slate-200"> Google Search API Key </label>
              <div class="mt-1 rounded-md shadow-sm flex">
                <spork-input v-model="form.research.google_search_api_key" type="text" name="google_search_api_key" id="google_search_api_key" />
              </div>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div class="col-span-3 sm:col-span-2">
              <label for="company-website" class="block text-sm font-medium text-slate-700 dark:text-slate-200"> Topic </label>
              <div class="mt-1 rounded-md shadow-sm flex" v-for="(status, index) in form.research.topics">
                <spork-input v-model="form.research.topics[index]" type="text" />
                <button @click="form.research.topics = form.research.topics.filter((s, i) => i !== index)" class="text-red-500 hover:text-red-700 ml-2">Remove</button>
              </div>
            </div>

            <div class="col-span-3">
              <button class="text-xs" @click="form.research.topics.push('')">+ Add topic</button>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-6">
             <div class="flex items-center">
                <input v-model="form.research.enabled" name="enable" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                <label class="ml-3">
                  <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">Enable Feature</span>
                </label>
              </div>
          </div>
        </div>
    </div>

    <div class="shadow sm:rounded-md sm:overflow-hidden m-4" v-if="form.greenhouse">
        <div class="bg-white dark:bg-slate-600 py-6 px-4 space-y-6 sm:p-6">
          <div>
            <h3 class="text-lg leading-6 font-medium text-slate-900 dark:text-slate-50">Greenhouse</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Keep track of plants you grow from seed to harvest</p>
          </div>

          <div class="grid grid-cols-3 gap-6">
             <div class="flex items-center">
                <input v-model="form.greenhouse.enabled" name="enable" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                <label class="ml-3">
                  <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">Enable Feature</span>
                </label>
              </div>
          </div>
        </div>
    </div>

    <div class="shadow sm:rounded-md sm:overflow-hidden m-4" v-if="form.food">
        <div class="bg-white dark:bg-slate-600 py-6 px-4 space-y-6 sm:p-6">
          <div>
            <h3 class="text-lg leading-6 font-medium text-slate-900 dark:text-slate-50">Food</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Explore foods, and exclude allergies.</p>
          </div>

          <div class="grid grid-cols-3 gap-6">
             <div class="flex items-center">
                <input v-model="form.food.enabled" name="enable" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                <label class="ml-3">
                  <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">Enable Feature</span>
                </label>
              </div>
          </div>
        </div>
    </div>

    <div v-if="Object.keys(form).length === 0" class="w-full text-lg p-4">
      You have no spork plugins installed
    </div>

    <div class="px-4 py-3 bg-gray-50 dark:bg-slate-800 text-right sm:px-6">
        <button @click="submit" type="submit" class="bg-indigo-600 border border-transparent rounded-md shadow-sm py-2 px-4 inline-flex justify-center text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600">
            Start Setup
        </button>
    </div>
    <pre class="text-white">{{ system }}</pre>
  </div>
</template>

<script>
export default {
  props: ['system'],
    data() {
        const form = Object.keys(Features).reduce((listOfFeatures, key) => ({
            ...listOfFeatures,
            [key]: {
                ...listOfFeatures[key],
                enabled: Features[key].enabled ?? true,
                settings: {
                  ...listOfFeatures[key]?.settings ?? {},
                }
            }
        }), {});

        if (form.development) {
          let envData = {};
          try {
            envData = JSON.parse(document.getElementById('data').getAttribute('data-env'))
          } catch (e) {
            console.log('failed to load env data', e);
          }
          form.development.projects = Object.keys(form).filter(feature => form[feature].enabled).map(feature => ({
            name: feature,
            settings: {
              ...(form[feature]?.settings ?? {}),
              path: '/system/' + feature,
            }
          }));


          form.development.import = false;
        }
        if (form.news) {
          form.news.news_api_key = envData?.NEWS_API_KEY ?? '';
        }

        if (form.calendar) {
          form.calendar.name = this.$store.getters.user.name+"'s Calendar";
        }

        if (form.finance) {
          form.finance.plaid_client_secret = envData?.PLAID_CLIENT_SECRET ?? '';
          form.finance.plaid_client_id = envData?.PLAID_CLIENT_ID ?? '';
          form.finance.plaid_env = envData?.PLAID_ENVIRONMENT ?? '';
        }

        if (form.garage) {
          form.garage.name = '';
          form.garage.settings.vin = '';
        }

        if (form.planning) {
          form.planning.statuses = [
            'Backlog',
            'To Do',
            'In Progress',
            'Done'
          ];
        }

        if (form.properties) {
          form.properties.name = '';
          form.properties.settings.address = '';
        }

        if (form.research) {
          form.research.google_search_api_key = envData?.GOOGLE_SEARCH_API_KEY ?? '';
          form.research.topics = [];
        }

        return {
            form,
            Features,
            Object,
        }
    },
    methods: {
        submit() {
          // console.log(this.form)
            axios.post('/api/initial-setup', this.form)
            // axios.post('/api/initial-setup', this.form)
        },
    },
}
</script>
