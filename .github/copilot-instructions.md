# GitHub Copilot Instructions for Spork

## Project Overview

Spork is a personal multi-tool application built with Laravel 12 and Vue 3, designed to automate random tasks and let creativity run wild. It's a comprehensive "everything app" that manages domains, servers, finances, RSS feeds, emails, tasks, and more.

## MCP Servers

When available, use these MCP (Model Context Protocol) servers to enhance development:

- **Laravel MCP Server**: For Laravel-specific operations, migrations, and framework features
- **GitHub MCP Server**: For repository operations, issues, and pull requests
- **Filesystem MCP Server**: For file operations and navigation
- **Database MCP Server**: For database queries and schema inspection

## Backend Architecture

### Core Concepts

This project uses custom architectural patterns that extend Laravel conventions:

#### 1. **Contracts (not Interfaces)**
- **Location**: `app/Contracts/`
- **Pattern**: Use the term "Contract" instead of "Interface" in naming
- **Example**: `App\Contracts\Services\NamecheapServiceContract` instead of `NamecheapServiceInterface`
- **Convention**: Contract namespaces mirror their concrete implementations
  - Interface: `App\Contracts\Services\PlaidServiceContract`
  - Implementation: `App\Services\Finance\PlaidService`

#### 2. **Repositories Pattern**
- **Location**: `app/Repositories/`
- **Purpose**: Centralize database-related code when logic is reused in multiple places
- **Pattern**: Each repository implements a corresponding Contract
- **Example**:
  ```php
  // Contract
  namespace App\Contracts\Repositories;
  interface CredentialRepositoryContract {
      public function findAllOfType(string $type): LengthAwarePaginator;
  }
  
  // Implementation
  namespace App\Repositories;
  class CredentialRepository implements CredentialRepositoryContract {
      public function findAllOfType(string $type, ?int $limit = 15, ?int $page = 1): LengthAwarePaginator
      {
          return Credential::query()
              ->where('type', $type)
              ->paginate($limit, ['*'], 'page', $page);
      }
  }
  ```
- **Binding**: Register in `AppServiceProvider::register()`
  ```php
  $this->app->bind(CredentialRepositoryContract::class, CredentialRepository::class);
  ```

#### 3. **Services Pattern**
- **Location**: `app/Services/`
- **Purpose**: All code that interacts with third-party APIs or external systems
- **Organization**: Services are organized into subdirectories by domain
  - `Services/Finance/` - Plaid, banking integrations
  - `Services/Registrar/` - Domain registrars (Namecheap, Cloudflare)
  - `Services/Messaging/` - IMAP, email services
  - `Services/Weather/` - Weather APIs
  - `Services/News/` - RSS and news feeds
- **Pattern**: Each service implements a corresponding Contract
- **Example**:
  ```php
  namespace App\Services\Registrar;
  use App\Contracts\Services\NamecheapServiceContract;
  
  class NamecheapService implements NamecheapServiceContract {
      // Implementation
  }
  ```

#### 4. **Actions Pattern**
- **Location**: `app/Actions/`
- **Purpose**: Simplified controllers that serve a single concise purpose
- **Pattern**: Single-responsibility actions for specific operations
- **Subdirectories**:
  - `Actions/Fortify/` - Authentication actions
  - `Actions/Jetstream/` - Team and profile actions
  - `Actions/Spork/` - Custom application actions

#### 5. **Logical Events Pattern**
- **Location**: `app/Events/` and `app/Contracts/`
- **Purpose**: Events used specifically for sharing data with the frontend interface
- **Pattern**: Limited use - only for things we want to share with our interface
- **Base Class**: `AbstractLogicalEvent` implements `LogicalEvent` contract
- **Example**:
  ```php
  namespace App\Events;
  use App\Events\AbstractLogicalEvent;
  
  class ExampleEvent extends AbstractLogicalEvent {
      // Event-specific properties and constructor
  }
  ```
- **Key Points**:
  - Events implement `LogicalEvent` contract
  - Used sparingly for frontend communication
  - Extend `AbstractLogicalEvent` for standard functionality
  - Use Laravel's event system for registration

### File Organization

Organize code into specific directories based on domain/functionality:

- **Finance Domain**: Budget, bank account, transaction code → `Finance/` subdirectory
- **Infrastructure Domain**: Servers, domains, server actions → `Infrastructure/` subdirectory  
- **Article Domain**: News articles, RSS feeds → `Article/` subdirectory
- **General Pattern**: Group related functionality in domain-specific subdirectories

### Directory Structure

```
app/
├── Actions/          # Single-purpose action classes
├── Console/          # Artisan commands
├── Contracts/        # Interfaces (called Contracts)
│   ├── Repositories/
│   └── Services/
├── Events/           # Event classes (limited use for frontend sharing)
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
├── Jobs/             # Queue jobs
├── Listeners/        # Event listeners (organized by domain)
├── Models/           # Eloquent models
├── Observers/        # DEPRECATED - use model events instead
├── Repositories/     # Database abstraction layer
└── Services/         # Third-party integrations (organized by domain)
```

### Special Features

#### Code Manipulation Service
- **Class**: `App\Services\Code`
- **Purpose**: Uses reflection and Nette PHP Generator to modify, update, or get metadata about code
- **Capabilities**:
  - Find all concretes that extend an interface
  - Discover all Model instances
  - Modify code en masse
  - Automatically register CRUD routes
  - Add traits, properties, or attributes to classes
- **Usage**: Use sparingly - powerful but slow
- **Example Use Cases**:
  - Adding listeners to events programmatically
  - Mass refactoring based on programmatic logic
  - Creating temporary commands for code generation

#### Laravel Programming Style
- **Class**: `App\Services\Programming\LaravelProgrammingStyle`
- **Purpose**: Extends Code service for Laravel-specific operations
- **Example**:
  ```php
  LaravelProgrammingStyle::for(EventServiceProvider::class)
      ->addListenerToEvent($event, $listener)
      ->toFile(Code::REPLACE_FILE);
  ```

## Frontend Architecture

### Stack
- **Framework**: Vue 3 (Composition API with `<script setup>`)
- **Router**: Inertia.js v2
- **Styling**: Tailwind CSS v4
- **Build Tool**: Vite 6
- **UI Components**: Headless UI, Heroicons
- **State Management**: Vuex 4 (limited use)
- **Real-time**: Laravel Echo + Pusher

### Component Organization

```
resources/js/
├── Components/
│   ├── Spork/              # Custom Spork UI components
│   │   ├── Atoms/          # Basic building blocks (SporkTable, SporkButton)
│   │   ├── Molecules/      # Composed components
│   │   │   ├── Cards/
│   │   │   ├── Containers/
│   │   │   └── Notifications/
│   │   └── Finance/        # Domain-specific components
│   ├── ContextMenus/
│   └── Messages/
├── Layouts/
│   └── AppLayout.vue       # Main application layout
├── Pages/                  # Inertia pages (route components)
│   ├── Dashboard.vue
│   ├── Domains/
│   └── ...
└── Builder/                # Visual builder components
    └── Components/
```

### Component Patterns

#### 1. **Atomic Design Pattern**
- **Atoms**: Basic components (`SporkButton`, `SporkInput`, `SporkLabel`, `SporkTable`)
- **Molecules**: Composed components built from atoms
- **Organization**: Domain-specific components in subdirectories

#### 2. **Composition API**
- Use `<script setup>` syntax
- Import `ref`, `computed`, `onMounted` from Vue
- Use Inertia's `Link`, `Head`, `router`, `usePage`

#### 3. **Component Example**
```vue
<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-stone-800 dark:text-stone-200">
                {{ title }}
            </h2>
        </template>
        <div class="py-12">
            <!-- Content -->
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    title: String,
    data: Object,
});

// Component logic
</script>
```

#### 4. **Inertia.js Patterns**
- Pass data from controllers to avoid extra API calls
- Use `router.reload()` for refreshing specific props
- Use `Link` component for navigation
- Use `usePage()` for accessing shared props

#### 5. **Real-time Updates**
- Use Laravel Echo for WebSocket communication
- Listen to model-specific channels
- Handle events with `playSound()` for notifications
- Example:
  ```javascript
  Echo.private('App.Models.Server.' + serverId)
      .listen('Models\\Server\\ServerUpdated', e => {
          router.reload({ only: ['servers', 'data'] })
      });
  ```

### Frontend Libraries

- **@headlessui/vue** - Accessible UI components
- **@heroicons/vue** - Icon components
- **dayjs** - Date manipulation
- **vue-chartjs** - Charts
- **xterm** - Terminal emulator
- **drawflow** - Visual workflow editor
- **highlight.js** - Syntax highlighting
- **vue-multiselect** - Advanced select component
- **notiwind** - Toast notifications

## Coding Standards

### PHP Standards

#### File Naming
- **Non-class files**: kebab-case (`my-helper-file.php`)
- **Class files**: PascalCase matching class name (`MyClass.php`)

#### Code Naming Conventions
- **Classes/Enums**: PascalCase (`MyClass`, `StatusEnum`)
- **Methods**: camelCase (`myMethod`, `calculateTotal`)
- **Variables/Properties**: snake_case (`$my_variable`, `$user_id`)
- **Constants/Enum Cases**: SCREAMING_SNAKE_CASE (`MY_CONSTANT`, `STATUS_ACTIVE`)

#### Code Style
- **Strict Types**: Always declare `declare(strict_types=1);` at the top of PHP files
- **Quotes**: Use single quotes for strings
- **Linting**: Use Laravel Pint with laravel preset
- **Configuration**: See `pint.json` for rules
- **Prefer**: Dependency injection of interfaces over helper methods or facades

#### Laravel Conventions
- Follow standard Laravel conventions
- Use PHP 8.2+ features (typed properties, constructor property promotion, etc.)
- Focus on excellent developer experience (DX)
- Prioritize autocompletion and type safety
- Use concise, descriptive method names

### JavaScript/Vue Standards

#### File Naming
- **Components**: PascalCase (`MyComponent.vue`)
- **Utilities**: camelCase (`myHelper.js`)

#### Code Conventions
- Use `const` and `let` (no `var`)
- Prefer arrow functions
- Use template literals for string interpolation
- Destructure props and imports

#### Vue 3 Specific
- Use Composition API with `<script setup>`
- Define props with `defineProps()`
- Emit events with `defineEmits()`
- Use `ref()` for reactive primitives
- Use `computed()` for derived state

### Database Conventions

- **Migrations**: Descriptive names with timestamp
- **Table Names**: Plural, snake_case (`users`, `blog_posts`)
- **Foreign Keys**: Singular_id (`user_id`, `post_id`)
- **Pivot Tables**: Alphabetical order (`post_tag`, not `tag_post`)

## Testing Requirements

### Test Structure
```
tests/
├── Unit/          # Unit tests for isolated logic
├── Feature/       # Feature tests for HTTP routes
└── Integration/   # Integration tests for complex workflows
```

### Testing Guidelines

1. **Domain Logic**: Tests required for any domain logic
2. **Routes**: Basic feature tests required for new routes to ensure they load
3. **Test Naming**: Descriptive test method names
4. **Database**: Use `RefreshDatabase` trait
5. **Factories**: Use factories for test data
6. **Example**:
   ```php
   public function test_credential_repository_finds_all_of_type(): void
   {
       Credential::factory()->count(3)->create(['type' => 'namecheap']);
       Credential::factory()->count(2)->create(['type' => 'cloudflare']);
       
       $repository = new CredentialRepository();
       $result = $repository->findAllOfType('namecheap');
       
       $this->assertCount(3, $result);
   }
   ```

### PHPUnit Configuration
- Environment: testing
- Database: MySQL (testing database)
- Queue: sync
- Cache: array
- Mail: array

## Development Workflow

### Running the Application

```bash
# Start all services (uses concurrently)
composer dev

# Individual services
php artisan serve        # Web server
php artisan queue:listen # Queue worker
php artisan pail         # Log viewer
npm run dev              # Vite dev server
```

### Code Quality

```bash
# Linting
./vendor/bin/pint        # Fix PHP code style

# Testing
php artisan test         # Run all tests
php artisan test --filter=TestName  # Run specific test

# Static Analysis
./vendor/bin/phpstan analyse  # Run static analysis
```

### Building for Production

```bash
npm run build            # Build frontend assets
php artisan optimize     # Optimize Laravel
```

## Common Patterns

### Creating a New Service

1. Create Contract in `app/Contracts/Services/`:
   ```php
   interface MyServiceContract {
       public function doSomething(): void;
   }
   ```

2. Create Service in `app/Services/`:
   ```php
   class MyService implements MyServiceContract {
       public function doSomething(): void {
           // Implementation
       }
   }
   ```

3. Bind in `AppServiceProvider::register()`:
   ```php
   $this->app->bind(MyServiceContract::class, MyService::class);
   ```

4. Inject in controllers/actions:
   ```php
   public function __construct(
       private MyServiceContract $service
   ) {}
   ```

### Creating a New Repository

1. Create Contract in `app/Contracts/Repositories/`:
   ```php
   interface MyRepositoryContract {
       public function findByAttribute(string $value): Collection;
   }
   ```

2. Create Repository in `app/Repositories/`:
   ```php
   class MyRepository implements MyRepositoryContract {
       public function findByAttribute(string $value): Collection {
           return MyModel::where('attribute', $value)->get();
       }
   }
   ```

3. Bind in `AppServiceProvider`

### Creating a Logical Event

1. Create Event extending `AbstractLogicalEvent`:
   ```php
   namespace App\Events\MyDomain;
   use App\Events\AbstractLogicalEvent;
   
   class MyLogicalEvent extends AbstractLogicalEvent {
       public function __construct(
           public MyModel $model,
           public string $action
       ) {}
   }
   ```

2. Create Listener:
   ```php
   namespace App\Listeners\MyDomain;
   
   class HandleMyLogicalEvent {
       public function handle(MyLogicalEvent $event): void {
           // Handle event
       }
   }
   ```

3. Register in `EventServiceProvider`:
   ```php
   protected $listen = [
       MyLogicalEvent::class => [
           HandleMyLogicalEvent::class,
       ],
   ];
   ```

### Creating an Inertia Page

1. Create controller action:
   ```php
   use Inertia\Inertia;
   
   public function index()
   {
       return Inertia::render('MyDomain/Index', [
           'items' => MyModel::paginate(),
       ]);
   }
   ```

2. Create Vue component in `resources/js/Pages/MyDomain/Index.vue`:
   ```vue
   <template>
       <AppLayout title="My Page">
           <template #header>
               <h2>My Page</h2>
           </template>
           <div class="py-12">
               <!-- Content -->
           </div>
       </AppLayout>
   </template>
   
   <script setup>
   import AppLayout from '@/Layouts/AppLayout.vue';
   
   defineProps({
       items: Object,
   });
   </script>
   ```

## Best Practices

### General
- Write self-documenting code with clear method names
- Avoid over-commenting; let code speak for itself
- Use dependency injection for testability
- Keep methods focused and concise
- Follow SOLID principles

### Laravel Specific
- Use route model binding where appropriate
- Leverage Eloquent relationships
- Use form requests for validation
- Utilize resource classes for API responses
- Use gates and policies for authorization
- Queue long-running tasks

### Vue/Frontend Specific
- Keep components small and focused
- Extract reusable logic into composables
- Use computed properties for derived state
- Avoid prop drilling; use provide/inject if needed
- Keep template logic simple
- Use Tailwind classes consistently (prefer `dark:` variants)

### Database
- Always use migrations for schema changes
- Use database transactions for related operations
- Index foreign keys and frequently queried columns
- Use eager loading to prevent N+1 queries
- Keep queries optimized and monitor performance

## Security Considerations

- Validate all input data
- Use form requests for complex validation
- Sanitize user input before display
- Use CSRF protection (enabled by default)
- Implement proper authentication and authorization
- Keep dependencies up to date
- Use environment variables for sensitive data
- Never commit secrets to version control

## Performance Considerations

- Cache expensive operations
- Use queue for long-running tasks
- Optimize database queries
- Use pagination for large datasets
- Minimize asset sizes
- Use CDN for static assets
- Enable query caching where appropriate
- Monitor application performance with Horizon

## Deployment

- Use Laravel Forge for deployment (personal preference)
- Docker support via Laravel Sail
- Environment configuration via `.env` files
- Assets compiled via Vite
- Queue workers managed by Supervisor
- Use database backups
- Monitor logs with Laravel Pail or external service

## Additional Notes

### Model Events
- **DEPRECATED**: `app/Observers/` directory
- **PREFERRED**: Declare model events on models directly
- Listeners auto-register with events

### Route Organization
- Routes split across multiple files by domain
- Main routes in `routes/web.php`
- CRUD routes in `routes/crud.php`
- Domain-specific routes in `routes/pages/`
- Multi-domain support via route domains

### Broadcasting
- Private channels for user-specific updates
- Model events broadcast for real-time updates
- Use sound notifications for user feedback
- Channel naming: `App.Models.{Model}.{id}`

## Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Vue 3 Documentation](https://vuejs.org/)
- [Inertia.js Documentation](https://inertiajs.com/)
- [Tailwind CSS Documentation](https://tailwindcss.com/)
- [Laravel Jetstream](https://jetstream.laravel.com/)

---

Remember: This project prioritizes developer experience, type safety, and maintainability. When in doubt, favor clarity over cleverness, and always follow the established patterns in the codebase.
