# CRM Demo Project

A modern, simplified CRM application built with a Symfony backend, Vue.js frontend, and GraphQL API. 

**This is a demo project.** It serves as a demonstration of the kind of full-stack work I typically do. Since my professional work is almost exclusively on private repositories and proprietary codebases, I created this demo to publicly showcase my skills, coding style, and architecture choices.

## Contact & Hire Me

I am currently open for contract work. If you're interested in working together, feel free to reach out via my website: [grobacz.eu](https://grobacz.eu).

I am also open to requests! If you would like to see a specific feature implemented in this repository as further proof of my skills, please get in touch.

---

## Architecture

- **Backend**: Symfony 6 with GraphQL API
- **Frontend**: Vue 3 with Composition API
- **Database**: PostgreSQL
- **Containerization**: Docker Compose

## Quick Start

### Prerequisites
- Docker and Docker Compose
- Make sure ports 8000 and 3000 are available

### Running with Docker Compose

```bash
# Start all services
docker-compose up -d

# Access the application
# Backend: http://localhost:8000/graphql (GraphQL Playground)
# Frontend: http://localhost:3000
# Database: localhost:5432

# Stop services
docker-compose down
```

## Project Structure

- `src/` - Symfony backend
- `frontend/` - Vue.js frontend
- `docker-compose.yml` - Docker configuration
- `architecture.md` - Detailed architecture documentation
- `AGENTS.md` - Development guidelines

## Development

### Backend
- GraphQL endpoint: `http://localhost:8000/graphql`
- PHP built-in server: `http://localhost:8000`
- GraphQL Playground available at `http://localhost:8000/graphql`

### Frontend
- Development server: `http://localhost:3000`
- Uses Vite for fast development

## GraphQL API

The backend exposes a single GraphQL endpoint at `/graphql`.

### Example Queries

```graphql
# Get hello message
query {
  hello(name: "CRM")
}

# Get customers
query {
  customers {
    id
    name
    email
  }
}
```

### Example Mutations

```graphql
# Create customer
mutation {
  createCustomer(name: "John Doe", email: "john@example.com") {
    id
    name
    email
  }
}
```

## Documentation

- [Architecture Overview](architecture.md)
- [Developer Guidelines](AGENTS.md)

## License

This project is licensed under the [MIT License](LICENSE).
