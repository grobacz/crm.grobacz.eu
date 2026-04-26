# CRM Project - Agent Guidelines

## Repository Snapshot

This repository is a split Symfony + Vue CRM app.

- Backend: Symfony 6.4, Doctrine ORM, `webonyx/graphql-php`
- Frontend: Vue 3, Vite 4, Vue Router 4, Pinia, `graphql-request`
- Database: PostgreSQL 15
- Containers: PHP 8.2 Apache image, Node 18 Alpine image, Postgres 15 Alpine

An `architecture.md` file exists in the current repository state.

## Active Project Structure

```text
.
├── AGENTS.md
├── bin/
│   └── console
├── config/
│   ├── bundles.php
│   ├── packages/
│   │   ├── doctrine.yaml
│   │   └── framework.yaml
│   ├── routes.yaml
│   └── services.yaml
├── frontend/
│   ├── index.html
│   ├── package.json
│   ├── public/
│   ├── src/
│   │   ├── components/layout/
│   │   ├── graphql/
│   │   ├── router/
│   │   ├── views/
│   │   ├── App.vue
│   │   └── main.js
│   └── vite.config.js
├── init.sql
├── migrations/
├── public/
│   └── index.php
├── src/
│   ├── Controller/
│   ├── Entity/
│   ├── GraphQL/
│   │   ├── Resolver/
│   │   ├── Types/
│   │   └── schema.graphql
│   ├── Repository/
│   └── Kernel.php
├── composer.json
├── docker-compose.yml
├── Dockerfile.backend
└── Dockerfile.frontend
```

## What Is Actually In Use

### Backend

- HTTP entrypoint is `public/index.php`.
- GraphQL endpoint is `/graphql`, defined in `config/routes.yaml`.
- The GraphQL controller is `src/Controller/GraphQLController.php`.
- GraphQL schema is assembled in PHP from `src/GraphQL/Types/QueryType.php` and `src/GraphQL/Types/MutationType.php`.
- Resolvers live in `src/GraphQL/Resolver/`.
- Doctrine entities live in `src/Entity/`.
- Doctrine repositories live in `src/Repository/`.
- Symfony service wiring comes from `config/services.yaml`.

### Frontend

- The active frontend app is under `frontend/`.
- Vue boots from `frontend/src/main.js`.
- Routing is in `frontend/src/router/index.js`.
- Shared layout components are in `frontend/src/components/layout/`.
- Current views are `Dashboard.vue`, `Customers.vue`, `Contacts.vue`, `Leads.vue`, `Inventory.vue`, `Categories.vue`, `PricingLists.vue`, `CampaignCalls.vue`, and `CampaignEmail.vue`.
- `Dashboard.vue` reads live counts plus `recentActivities` from GraphQL for the Recent Activity card.
- The GraphQL client is `frontend/src/graphql/client.js`.
- `frontend/src/graphql/schema.js` is a frontend-side schema helper, not the backend source of truth.
- Shared call state now lives in `frontend/src/store/callCenter.js`.

### Database

- Docker starts Postgres from `postgres:15-alpine`.
- Initial schema bootstrap comes from `init.sql`.
- The backend container now reapplies `init.sql` on startup so existing dev volumes pick up additive schema changes.
- After schema bootstrap, backend and worker startup both call `php bin/console app:seed-demo-data --if-empty --force`; the command uses a PostgreSQL advisory lock and an `app_setting` marker so automatic seeding runs once across concurrent containers.
- The `migrations/` directory is present and Doctrine Migrations is configured, but `init.sql` is still part of the active bootstrap path in Docker.

## Current Domain Model

The backend currently models:

- `Customer`
- `Contact`
- `Deal`
- `Activity`
- `CallLog`
- `EmailCampaign`
- `EmailCampaignRecipient`
- `Lead`
- `Category`
- `InventoryItem`
- `PricingList`
- `PricingListItem`

GraphQL `QueryType` exposes reads for all product-building-block entities alongside CRM records, plus `callLogs` and `activeCall` for the campaigns call center flow and `emailCampaigns` / `emailCampaign` for outbound email progress tracking. `MutationType` exposes writes for `Customer`, `Contact`, `Deal`, `Lead`, `Category`, `InventoryItem`, `PricingList`, `PricingListItem`, call start/stop operations, and email campaign creation, and those mutations log `Activity` records for the dashboard feed.

The frontend only has dedicated views/routes for:

- dashboard
- customers
- contacts
- leads
- inventory
- categories
- pricing-lists
- campaigns/email
- campaigns/calls

There is currently no `Deals.vue` view or deal route.

## Legacy Or Duplicate Paths

These paths exist but are currently empty or non-authoritative:

- `src/Views`
- `src/assets`
- `src/components`
- `src/graphql`
- `src/public`
- `src/router`
- `src/config/`

Use the root `config/` directory for Symfony configuration unless you verify a file under `src/config/` is intentionally being revived. Do not assume the empty Vue-style directories under root `src/` are part of the active frontend.

## Development Guidelines

### Backend (Symfony / GraphQL)

1. Check existing entities, types, and resolvers before adding new backend files.
2. Use PHP 8 attributes for Doctrine mapping. The current entities do not use annotation comments.
3. Keep GraphQL type definitions in `src/GraphQL/Types/` and resolver logic in `src/GraphQL/Resolver/`.
4. Wire new HTTP routes in `config/routes.yaml` and new services in `config/services.yaml` when autowiring is not enough.
5. Prefer extending the existing `Customer`, `Contact`, and `Deal` flows before creating parallel structures.
6. If schema changes are required, update both Doctrine-backed PHP code and the bootstrap SQL or migrations strategy consistently. Right now the repository uses `init.sql` and has no committed migrations.

### Frontend (Vue / Vite)

1. Use Vue 3 Composition API with `<script setup>`.
2. Put route components in `frontend/src/views/`.
3. Put reusable layout or UI pieces in `frontend/src/components/`.
4. Use `frontend/src/graphql/client.js` for API calls unless you are intentionally replacing the frontend GraphQL layer.
5. Keep routing changes in `frontend/src/router/index.js`.
6. Pinia is installed and initialized, but there are no committed stores yet. Add stores under `frontend/src/store/` only when shared state is actually needed.

### Docker / Runtime

- Backend container runs `php -S 0.0.0.0:8000 -t public/`.
- Backend startup goes through `.docker/backend-entrypoint.sh`, which waits for Postgres and applies `init.sql` before starting PHP.
- The worker container goes through `.docker/worker-entrypoint.sh`, which waits for Postgres, applies `init.sql`, attempts the one-time demo seed, and runs the email campaign simulation loop.
- Frontend container runs `npm run serve`, which maps to Vite.
- Worker container runs `php bin/console app:process-email-campaigns` in a loop to simulate email campaign progress.
- Run task-specific commands inside the matching container whenever the runtime matters:
  - backend PHP / Symfony / Doctrine commands: `docker compose exec backend ...`
  - frontend Node / Vite commands: `docker compose exec frontend ...`
- Docker volumes currently mount:
  - backend: `src/`, `config/`, `migrations/`
  - frontend: `frontend/src/`, `frontend/public/`
- If you add files outside those mounted paths and expect live reload in Docker, update the compose setup as needed.

## Code Conventions

- PHP follows the existing Symfony/Doctrine style in the repository.
- JavaScript uses ES modules and Vue SFCs.
- Use PascalCase for PHP classes and Vue component filenames.
- Use camelCase for JS variables, functions, and GraphQL client helpers.
- Follow the surrounding file style instead of doing broad cleanups unrelated to the task.
- Do not rewrite empty legacy directories into active ones unless the task explicitly requires that restructure.

## Verification

There are currently no committed frontend test scripts and no visible PHPUnit config files in the repository root.

When making changes, prefer the checks that match the files you touched:

- backend dependency or container changes: `docker compose config`
- frontend changes: `docker compose exec frontend npm run build`
- backend PHP changes: targeted `docker compose exec backend php -l ...` or Symfony console checks when available

If you add a real test setup, update this document to reflect the new commands.

## Agent Tasks

- Inspect the current file layout before creating new files.
- Prefer active paths over legacy or duplicate directories.
- Preserve uncommitted user changes.
- Keep backend and frontend GraphQL assumptions aligned.
- Update `AGENTS.md` again if the repository structure or workflow changes materially.
