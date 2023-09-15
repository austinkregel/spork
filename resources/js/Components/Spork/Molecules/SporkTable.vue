<template>
  <div class="p-4 sm:p-6 lg:p-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-base font-semibold leading-6 text-gray-900 dark:text-gray-50">{{ header }}</h1>
        <p class="mt-2 text-sm text-gray-700 dark:text-gray-200">{{ description }}</p>
      </div>
      <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
        <button type="button" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
          Add user
        </button>
      </div>
    </div>
    <div class="mt-8 flow-root">
      <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
          <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600 bg-white dark:bg-gray-700 rounded">
            <thead>
            <tr>
              <th v-for="header in headers" scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-50 sm:pl-3">
                {{  parseTheAccessor(header, null) }}
              </th>
              <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-3">
                <span class="sr-only">Edit</span>
              </th>
            </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900">
            <tr v-for="person in items" :key="person" class="even:bg-gray-50 dark:even:bg-gray-800">
              <td v-for="header in headers" class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-gray-50 sm:pl-3">{{ parseTheAccessor(header, person) }}</td>
              <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-3">
                <a href="#" class="text-indigo-600 dark:text-indigo-100 hover:text-indigo-900">Edit</a>
              </td>
            </tr>

            <tr v-if="items?.length === 0">
              <td :colspan="headers.length + 1" class="text-center p-4 dark:text-gray-50">
                No data available
              </td>
            </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  headers: Array,
  items: Array,
  header: String,
  description: String,
})

const parseTheAccessor = (header, value) => {
  if (typeof header.accessor === 'function') {
      try {
          return header.accessor(value)
      } catch (e) {
          console.error('Unable to execute the header accessor from header:', header, e);
          return 'an error occurred.';
      }
  }

  if (!value) {
      return header.name
  }
  return value[header?.accessor] ?? header.name;
}
</script>
