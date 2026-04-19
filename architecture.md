# CRM Application Architecture

## Overview
This is a modern CRM application built with:
- **Backend**: Symfony (PHP) with GraphQL API
- **Frontend**: Vue.js with Node.js server
- **Communication**: GraphQL over HTTP
- **Containerization**: Docker Compose with separate services

## Architecture Components

### 1. Backend (Symfony)
- **Framework**: Symfony 6 with PHP 8.2+
- **GraphQL**: Webonyx/graphql-php library
- **Database**: PostgreSQL (with Doctrine ORM)
- **API**: GraphQL endpoint at `/graphql`
- **Structure**:
  - `src/Entity/` - Doctrine entities (Customer, Contact, Deal, etc.)
  - `src/GraphQL/` - GraphQL types, resolvers, queries/mutations
  - `src/Repository/` - Doctrine repositories
  - `src/Controller/` - GraphQL controller

### 2. Frontend (Vue.js)
- **Framework**: Vue 3 with Composition API
- **GraphQL Client**: Apollo Client or graphql-request
- **Node.js Server**: Express.js for SSR or static file serving
- **Structure**:
  - `src/components/` - Vue components
  - `src/graphql/` - GraphQL queries and mutations
  - `src/views/` - Vue views/pages
  - `src/router/` - Vue Router configuration

### 3. Database Layer
- PostgreSQL as the primary database
- Doctrine ORM for entity management
- Migrations for schema management

### 4. Containerization
- **Backend Service**: Symfony application with PHP-FPM
- **Frontend Service**: Node.js + Vue.js application
- **Database Service**: PostgreSQL
- **Communication**: Internal Docker network

## Service Communication
```
Frontend (Vue) → GraphQL HTTP → Backend (Symfony) → Database (PostgreSQL)
```

## API Design
- **Single GraphQL endpoint**: `/graphql`
- **Queries**: Read operations (get customers, contacts, etc.)
- **Mutations**: Write operations (create/update/delete records)
- **Subscriptions**: Real-time updates (optional)

## Development Workflow
1. Backend development in PHP/Symfony
2. Frontend development in Vue/JavaScript
3. GraphQL schema as the contract between frontend and backend
4. Docker Compose for consistent local development

## Deployment Architecture
- Separate containers for frontend and backend
- Shared network for internal communication
- Environment-specific configuration
- Health checks for service monitoring
