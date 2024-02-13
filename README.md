# What is spork?
Spork is a personal multi-tool, a place to let my creativity run while and automate random tasks.

# How do I run it?
If you'd like to get this project up and running, it's pretty straight forward using docker and Laravel sail. Other deployment methods are not supported officially, but will likely work with little issue. I deploy this personally using Laravel Forge.

```bash
$ docker run --rm --pull=always -v "$(pwd)":/opt -w /opt laravelsail/php82-composer:latest bash -c "composer install"
$ ./sail up -d 
```

# What does it do? Features?
 - [x] Syncing domains from Namecheap
 - [x] Syncing servers from Laravel Forge
 - [x] Syncing registrar domains from Cloudflare
 - [x] Syncing zones from Cloudflare
 - [x] Dynamic routes and domains via a page editor
 - [x] RSS Syncing & updating
 - [x] DNS Validation/Verification
 - [x] Projects, with relations to domains, servers, rss feeds, pages (and redirects), and people in a one to many association for organization.
 - [-] Automatically adding and configuring purchased domains to Cloudflare & Laravel Forge, and provision out the routing automatically. (Still in progress)
 - [x] Automatic SSL configuration via forge.

## Planned 
 - [ ] IFTTT inspired Dynamic Automations
 - [x] Plaid integration for asset syncing
 - [-] Budgeting per project
 - [ ] Built-in project researching for Scholarly articles and browsing the web.
 - [ ] Calendar integration for project and site wide events
 - [ ] Task management, tasks are created per project
 - [ ] Personal Dashboard 
   - [ ] List tasks from all projects
   - [ ] List events from all projects
   - [ ] List budget usage -- if applicable.
 - [ ] Domain Purchasing and Renewals


 - Single Chat interface
 - Email interface
