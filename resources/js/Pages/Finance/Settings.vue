<template>
  <AppLayout>
    <div class="flex flex-wrap gap-4 mx-8">

      <aside class="flex overflow-x-auto border-b border-stone-900/5 py-4 lg:block lg:w-64 lg:flex-none lg:border-0 lg:py-16">
        <nav class="flex-none px-4 sm:px-6 lg:px-0">
          <ul role="list" class="flex gap-x-3 gap-y-1 whitespace-nowrap lg:flex-col">
            <li v-for="item in secondaryNavigation" :key="item.name">
              <a :href="item.href" :class="[item.current ? 'bg-stone-50 dark:bg-stone-800 text-indigo-600' : 'text-stone-700 dark:text-stone-100 hover:text-indigo-600 hover:bg-stone-50 hover:bg-stone-800', 'group flex gap-x-3 rounded-md py-2 pl-2 pr-3 text-sm leading-6 font-semibold']">
                <component :is="item.icon" :class="[item.current ? 'text-indigo-600' : 'text-stone-400 group-hover:text-indigo-600', 'h-6 w-6 shrink-0']" aria-hidden="true" />
                {{ item.name }}
              </a>
            </li>
          </ul>
        </nav>
      </aside>

      <main class="px-4 py-12 sm:px-6 lg:flex-auto lg:px-0 lg:py-16">
        <div class="mx-auto max-w-2xl space-y-16 sm:space-y-20 lg:mx-0 lg:max-w-none">
          <div>
            <h2 class="text-base font-semibold leading-7 text-stone-900 dark:text-stone-50">Profile</h2>
            <p class="mt-1 text-sm leading-6 text-stone-500 dark:text-stone-200">This information will be displayed publicly so be careful what you share.</p>

            <dl class="mt-6 space-y-6 divide-y divide-stone-100 dark:divide-stone-700 border-t border-stone-200 dark:border-stone-700 text-sm leading-6">
              <div class="pt-6 sm:flex">
                <dt class="font-medium text-stone-900 dark:text-stone-50 sm:w-64 sm:flex-none sm:pr-6">Full name</dt>
                <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                  <div class="text-stone-900 dark:text-stone-50">Tom Cook</div>
                  <button type="button" class="font-semibold text-indigo-600 hover:text-indigo-500">Update</button>
                </dd>
              </div>
              <div class="pt-6 sm:flex">
                <dt class="font-medium text-stone-900 dark:text-stone-50 sm:w-64 sm:flex-none sm:pr-6">Email address</dt>
                <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                  <div class="text-stone-900 dark:text-stone-50">tom.cook@example.com</div>
                  <button type="button" class="font-semibold text-indigo-600 hover:text-indigo-500">Update</button>
                </dd>
              </div>
              <div class="pt-6 sm:flex">
                <dt class="font-medium text-stone-900 dark:text-stone-50 sm:w-64 sm:flex-none sm:pr-6">Title</dt>
                <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                  <div class="text-stone-900 dark:text-stone-50">Human Resources Manager</div>
                  <button type="button" class="font-semibold text-indigo-600 hover:text-indigo-500">Update</button>
                </dd>
              </div>
            </dl>
          </div>

          <div>
            <h2 class="text-base font-semibold leading-7 text-stone-900 dark:text-stone-50">Bank accounts</h2>
            <p class="mt-1 text-sm leading-6 text-stone-500 dark:text-stone-200">Connect bank accounts to your account.</p>

            <ul role="list" class="mt-6 divide-y divide-stone-100 dark:divide-stone-700 border-t border-stone-200 dark:border-stone-700 text-sm leading-6">
              <li class="flex justify-between gap-x-6 py-6">
                <div class="font-medium text-stone-900 dark:text-stone-50">TD Canada Trust</div>
                <button type="button" class="font-semibold text-indigo-600 hover:text-indigo-500">Update</button>
              </li>
              <li class="flex justify-between gap-x-6 py-6">
                <div class="font-medium text-stone-900 dark:text-stone-50">Royal Bank of Canada</div>
                <button type="button" class="font-semibold text-indigo-600 hover:text-indigo-500">Update</button>
              </li>
            </ul>

            <div class="flex border-t border-stone-100 dark:border-stone-500 pt-6">
              <button type="button" class="text-sm font-semibold leading-6 text-indigo-600 hover:text-indigo-500"><span aria-hidden="true">+</span> Add another bank</button>
            </div>
          </div>

          <div>
            <h2 class="text-base font-semibold leading-7 text-stone-900 dark:text-stone-50">Integrations</h2>
            <p class="mt-1 text-sm leading-6 text-stone-500 dark:text-stone-200">Connect applications to your account.</p>

            <ul role="list" class="mt-6 divide-y divide-stone-100 dark:divide-stone-700 border-t border-stone-200 dark:border-stone-700 text-sm leading-6">
              <li class="flex justify-between gap-x-6 py-6">
                <div class="font-medium text-stone-900 dark:text-stone-50">QuickBooks</div>
                <button type="button" class="font-semibold text-indigo-600 hover:text-indigo-500">Update</button>
              </li>
            </ul>

            <div class="flex border-t border-stone-100 dark:border-stone-500 pt-6">
              <button type="button" class="text-sm font-semibold leading-6 text-indigo-600 hover:text-indigo-500"><span aria-hidden="true">+</span> Add another application</button>
            </div>
          </div>

          <div>
            <h2 class="text-base font-semibold leading-7 text-stone-900 dark:text-stone-50">Language and dates</h2>
            <p class="mt-1 text-sm leading-6 text-stone-500 dark:text-stone-200">Choose what language and date format to use throughout your account.</p>

            <dl class="mt-6 space-y-6 divide-y divide-stone-100 dark:divide-stone-700 border-t border-stone-200 dark:border-stone-700 text-sm leading-6">
              <div class="pt-6 sm:flex">
                <dt class="font-medium text-stone-900 dark:text-stone-50 sm:w-64 sm:flex-none sm:pr-6">Language</dt>
                <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                  <div class="text-stone-900 dark:text-stone-50">English</div>
                  <button type="button" class="font-semibold text-indigo-600 hover:text-indigo-500">Update</button>
                </dd>
              </div>
              <div class="pt-6 sm:flex">
                <dt class="font-medium text-stone-900 dark:text-stone-50 sm:w-64 sm:flex-none sm:pr-6">Date format</dt>
                <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                  <div class="text-stone-900 dark:text-stone-50">DD-MM-YYYY</div>
                  <button type="button" class="font-semibold text-indigo-600 hover:text-indigo-500">Update</button>
                </dd>
              </div>
              <SwitchGroup as="div" class="flex pt-6">
                <SwitchLabel as="dt" class="flex-none pr-6 font-medium text-stone-900 dark:text-stone-50 sm:w-64" passive>Automatic timezone</SwitchLabel>
                <dd class="flex flex-auto items-center justify-end">
                  <Switch v-model="automaticTimezoneEnabled" :class="[automaticTimezoneEnabled ? 'bg-indigo-600' : 'bg-stone-200 dark:bg-stone-700', 'flex w-8 cursor-pointer rounded-full p-px ring-1 ring-inset ring-stone-900/5 transition-colors duration-200 ease-in-out focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600']">
                    <span aria-hidden="true" :class="[automaticTimezoneEnabled ? 'translate-x-3.5' : 'translate-x-0', 'h-4 w-4 transform rounded-full bg-white shadow-sm ring-1 ring-stone-900/5 transition duration-200 ease-in-out']" />
                  </Switch>
                </dd>
              </SwitchGroup>
            </dl>
          </div>
        </div>
      </main>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Dialog, DialogPanel, Switch, SwitchGroup, SwitchLabel } from '@headlessui/vue'
import { Bars3Icon } from '@heroicons/vue/20/solid'
import {
  BellIcon,
  CreditCardIcon,
  CubeIcon,
  FingerPrintIcon,
  UserCircleIcon,
  UsersIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import AppLayout from "@/Layouts/AppLayout.vue";

const navigation = [
  { name: 'Home', href: '#' },
  { name: 'Invoices', href: '#' },
  { name: 'Clients', href: '#' },
  { name: 'Expenses', href: '#' },
]
const secondaryNavigation = [
  { name: 'General', href: '#', icon: UserCircleIcon, current: true },
  { name: 'Security', href: '#', icon: FingerPrintIcon, current: false },
  { name: 'Notifications', href: '#', icon: BellIcon, current: false },
  { name: 'Plan', href: '#', icon: CubeIcon, current: false },
  { name: 'Billing', href: '#', icon: CreditCardIcon, current: false },
  { name: 'Team members', href: '#', icon: UsersIcon, current: false },
]

const mobileMenuOpen = ref(false)
const automaticTimezoneEnabled = ref(true)
</script>
