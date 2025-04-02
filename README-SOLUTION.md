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

## Project Structure

```
php-test/
├── config/                # Configuration files
├── src/                   # Source code
│   ├── Controller/        # Request handlers
│   ├── Core/              # Core framework components
│   ├── Exception/         # Custom exceptions
│   ├── Middleware/        # Request/response middleware
│   ├── Model/             # Domain models
│   ├── Repository/        # Data access layer
│   └── Service/           # Business logic
├── tests/                 # Test files
│   ├── Integration/       # Integration tests
│   └── Unit/              # Unit tests
├── vendor/                # Dependencies (managed by Composer)
├── web/                   # Public web files
│   ├── ui/                # Frontend UI files
│   ├── index.php          # Application entry point
│   └── swagger.json       # API documentation
├── docker-compose.yml     # Docker configuration
├── phpunit.xml            # Testing configuration
├── run-tests.sh           # Test execution script
├── git-setup.sh           # Git initialization script
├── init-db.php            # Database initialization script
└── USER-GUIDE.md          # User documentation
```

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

## User Interface

The application includes a simple web-based UI for interacting with the API:

- **Available at**: http://localhost:8080/ui
- **Features**:
  - User registration and login
  - Listing all recipes
  - Creating new recipes
  - Editing existing recipes
  - Deleting recipes
  - Rating recipes
  - Searching for recipes with filters

The UI is built with vanilla JavaScript and communicates with the API endpoints.

## Testing

To run the tests, execute the following command:

```bash
# Using the provided script
chmod +x run-tests.sh
./run-tests.sh

# Or run directly with PHPUnit
docker-compose exec php vendor/bin/phpunit
```

The testing suite includes:
- Unit tests for models and core functionality
- Integration tests for API endpoints
- Test configuration in phpunit.xml
- Separate test database to prevent affecting production data

### Example Test Files

- `tests/Unit/Model/RecipeTest.php`: Tests recipe creation, validation, and behavior
- Additional tests for controllers, repositories, and services

## Development Workflow

1. Make sure Docker Desktop is running
2. Start the application with `docker-compose up -d`
3. Initialize the database with test data
4. Make changes to the code
5. Run tests to ensure functionality
6. Use Git for version control (script provided: `git-setup.sh`)

## Documentation

- **API Documentation**: Available via Swagger UI at http://localhost:8080/swagger
- **User Guide**: See USER-GUIDE.md for detailed instructions on using the API
- **Code Documentation**: PHP DocBlocks throughout the codebase

## Performance Considerations

- Database connection pooling
- Indexing on frequently queried fields
- Pagination for list endpoints to reduce response size
- Caching opportunities (could be implemented with Redis)

## Future Improvements

Potential enhancements for the project:

1. Add ingredient lists and cooking instructions to recipes
2. Implement user favorites and collections
3. Add image upload functionality for recipes
4. Create a more sophisticated permission system
5. Implement caching with Redis for improved performance
6. Add CI/CD pipeline for automated testing and deployment
7. Enhance search functionality with full-text search
