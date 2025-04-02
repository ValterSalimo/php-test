# Recipes API Solution

This is a RESTful API for managing recipes, built with PHP 7.4 without using any framework.

## Features

- List, create, read, update, and delete recipes
- Rate recipes (1-5 stars)
- Search recipes by name, difficulty, and vegetarian options
- Secure endpoints with JWT authentication
- Database persistence with PostgreSQL
- Unit tests with PHPUnit
- Swagger UI for testing and documenting the API

## Setup Instructions

1. Make sure you have Docker installed on your machine
2. Clone this repository
3. Run `docker-compose up -d` to start the application
4. Initialize the database with: `docker-compose exec php php /server/http/init-db.php`
5. The API will be available at http://localhost:8080
6. Access the Swagger UI documentation at http://localhost:8080/swagger

## Data Persistence

The application uses a Docker volume (`postgres_data`) to ensure that all database data is persisted across container restarts and even across container recreation. This means:

- Your data will be saved even if you stop the Docker containers
- Your data will be preserved if you run `docker-compose down` (but not if you add the `-v` flag which removes volumes)
- If you need to completely reset the database, you can run `docker-compose down -v` to remove all data

## API Endpoints

### Authentication

- `POST /auth/register` - Register a new user
- `POST /auth/login` - Login and get JWT token

### Recipes

- `GET /recipes` - List all recipes (public)
- `POST /recipes` - Create a new recipe (protected)
- `GET /recipes/{id}` - Get a specific recipe (public)
- `PUT /recipes/{id}` - Update a recipe (protected)
- `DELETE /recipes/{id}` - Delete a recipe (protected)
- `POST /recipes/{id}/rating` - Rate a recipe (public)
- `GET /recipes/search?q=query&vegetarian=true&difficulty=2` - Search recipes (public)

## Technical Details

### Architecture

The application follows a clean architecture approach with:

- **Controller Layer**: Handles HTTP requests/responses and input validation
- **Repository Layer**: Provides data access abstraction for models
- **Service Layer**: Contains business logic
- **Model Layer**: Represents domain objects
- **Core Components**: Routing, DI Container, Request/Response handling
- **Middleware**: Cross-cutting concerns like authentication

### Database Schema

```sql
CREATE TABLE recipes (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    prep_time INTEGER NOT NULL,
    difficulty INTEGER NOT NULL CHECK (difficulty BETWEEN 1 AND 3),
    vegetarian BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE recipe_ratings (
    id SERIAL PRIMARY KEY,
    recipe_id INTEGER NOT NULL REFERENCES recipes(id) ON DELETE CASCADE,
    rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

### Used Libraries

- **firebase/php-jwt**: For JWT token generation and validation
- **phpunit/phpunit**: For unit testing

### Security Measures

- JWT token authentication for protected endpoints
- Password hashing with PHP's password_hash function
- Input validation and sanitization
- Prepared statements to prevent SQL injection
- CORS headers for API security
- Validation of request data

### Testing

To run the tests, execute the following command:

```bash
docker-compose exec php vendor/bin/phpunit
```

## Performance Considerations

- Database connection pooling
- Indexing on frequently queried fields
- Pagination for list endpoints to reduce response size
- Caching opportunities (could be implemented with Redis)
