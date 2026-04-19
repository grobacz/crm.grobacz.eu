# CRM Project - Agent Guidelines

## Project Structure
```
crm-app/
├── src/              # Symfony backend
│   ├── Entity/       # Doctrine entities
│   ├── GraphQL/      # GraphQL types and resolvers
│   ├── Repository/   # Doctrine repositories
│   ├── Controller/   # GraphQL controller
│   └── config/       # Symfony configuration
├── frontend/         # Vue.js frontend
│   ├── src/
│   │   ├── components/  # Vue components
│   │   ├── views/       # Vue views
│   │   ├── graphql/     # GraphQL queries
│   │   └── router/      # Vue Router
│   └── public/
├── docker-compose.yml
├── Dockerfile.backend
├── Dockerfile.frontend
└── architecture.md
```

## Development Guidelines

### Backend (Symfony)
1. **Entity Creation**: Use Doctrine annotations for entity mapping
2. **GraphQL Schema**: Define types in `src/GraphQL/Types/`
3. **Resolvers**: Implement in `src/GraphQL/Resolver/`
4. **Database**: Use Doctrine migrations for schema changes
5. **Validation**: Use Symfony Validator component

### Frontend (Vue.js)
1. **Components**: Use Composition API with `<script setup>`
2. **GraphQL**: Use graphql-request or Apollo Client
3. **State Management**: Use Pinia or Vue's reactive refs
4. **Routing**: Vue Router with history mode

### Docker Setup
- Backend: PHP 8.2 with Apache/FPM
- Frontend: Node.js 18+ with Nginx
- Database: PostgreSQL 15+

## Code Conventions
- **PHP**: PSR-12 coding standard
- **JavaScript/PHP**: Use strict types
- **Naming**: camelCase for JS, PascalCase for PHP classes
- **Testing**: PHPUnit for backend, Jest/Vitest for frontend

## Agent Tasks
- Always check existing files before creating new ones
- Follow the established directory structure
- Maintain consistent coding styles
- Test changes before committing
- Update documentation when making significant changes
