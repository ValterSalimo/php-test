# Recipe API User Guide

The Recipe API allows you to manage recipes, search for recipes, and rate them. This guide will show you how to use each endpoint.

## Getting Started

1. Ensure Docker Desktop is running before executing any commands
2. Start the application with Docker:
   ```
   docker-compose down
   docker-compose build
   docker-compose up -d
   ```
3. Initialize the database:
   ```
   docker-compose exec php php /server/http/init-db.php
   ```
4. Access the API at http://localhost:8080
5. Use Swagger UI for interactive testing: http://localhost:8080/swagger

## Recent Improvements

The following improvements have been made to the API:

1. **Fixed Port Preservation**: URLs now correctly maintain port numbers in redirects (e.g., localhost:8080 will redirect to localhost:8080/swagger instead of losing the port)
2. **Enhanced Error Handling**: Better error logging and user-friendly error responses
3. **Improved Router**: Better handling of route parameters and path matching
4. **API Documentation**: Expanded Swagger documentation for all endpoints
5. **Rate Limiting**: Added protection against API abuse
6. **Search Functionality**: Fixed issues with search parameters handling
7. **Database Error Context**: Improved error logging for database operations
8. **URL Parameter Handling**: Better handling of URL parameters with trailing ampersands and malformed query strings

## Test User Credentials

A test user is automatically created when initializing the database:
- Username: `testuser`
- Password: `testpassword`

## Authentication

Before you can use protected endpoints, you need to register and get a token:

### Register a New User

```bash
curl -X POST http://localhost:8080/auth/register \
  -H "Content-Type: application/json" \
  -d '{"username":"newuser","password":"password123"}'
```

### Login to Get a JWT Token

```bash
curl -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"testuser","password":"testpassword"}'
```

This will return a token like `{"token":"eyJ0eXAiOiJKV..."}` that you'll need for protected endpoints.

## Working with Recipes

### List All Recipes

```bash
curl -X GET http://localhost:8080/recipes
```

You can add pagination:

```bash
curl -X GET "http://localhost:8080/recipes?page=1&limit=10"
```

### Create a New Recipe (Protected)

```bash
curl -X POST http://localhost:8080/recipes \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "name": "Spaghetti Carbonara",
    "prepTime": 30,
    "difficulty": 2,
    "vegetarian": false
  }'
```

### Get a Specific Recipe

```bash
curl -X GET http://localhost:8080/recipes/1
```

### Update a Recipe (Protected)

```bash
curl -X PUT http://localhost:8080/recipes/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "name": "Improved Spaghetti Carbonara",
    "prepTime": 25
  }'
```

### Delete a Recipe (Protected)

```bash
curl -X DELETE http://localhost:8080/recipes/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Rate a Recipe

```bash
curl -X POST http://localhost:8080/recipes/1/rating \
  -H "Content-Type: application/json" \
  -d '{"rating": 5}'
```

### Search for Recipes

The search functionality has been improved to better handle various parameters. You can search using any combination of:

```bash
# Search by name only
curl -X GET "http://localhost:8080/recipes/search?q=spaghetti"

# Search by vegetarian status only
curl -X GET "http://localhost:8080/recipes/search?vegetarian=true"

# Search by difficulty only
curl -X GET "http://localhost:8080/recipes/search?difficulty=2"

# Combine multiple search criteria
curl -X GET "http://localhost:8080/recipes/search?q=spaghetti&vegetarian=false&difficulty=2"
```

Note that the search is now more robust and can handle empty parameters, trailing ampersands, and various parameter formats.

## API Reference

### Recipe Object

```json
{
  "id": 1,
  "name": "Spaghetti Carbonara",
  "prepTime": 30,
  "difficulty": 2,
  "vegetarian": false,
  "avgRating": 4.5,
  "ratings": 2
}
```

### Query Parameters for Search

- `q`: Search term (matches against recipe name)
- `vegetarian`: Filter by vegetarian status (true/false)
- `difficulty`: Filter by difficulty level (1-3)

## Notes

- Recipe difficulty must be between 1-3 (1 = easy, 2 = medium, 3 = hard)
- Ratings must be between 1-5 stars
- All protected endpoints require the Authorization header with your JWT token
- The JWT token expires after 1 hour

## Data Management

### Data Persistence

The application is configured to persist your data even when Docker containers are stopped. All database information is stored in a named volume called `postgres_data`.

- Your recipes, ratings, and user accounts will be saved when you stop the containers 
- Data will persist across container recreations

### Resetting the Database

If you want to reset the database and start fresh:

```bash
# Stop containers and remove volumes
docker-compose down -v

# Start containers again
docker-compose up -d

# Reinitialize the database with test data
docker-compose exec php php /server/http/init-db.php
```

## Testing the Application

This project includes testing functionality built with PHPUnit. Here's how to work with tests:

### Key Testing Files

- `phpunit.xml`: Configuration file for PHPUnit that defines:
  - Test suites (Unit and Integration tests)
  - Code coverage settings
  - Environment variables for the test database

- `run-tests.sh`: Shell script to:
  - Install PHPUnit dependencies
  - Create test directories if they don't exist
  - Run all PHPUnit tests

### Running Tests

To run the tests:

1. Ensure Docker containers are running
2. Execute the test script:
   ```bash
 
   chmod +x run-tests.sh
   
   # Run the tests
   ./run-tests.sh
   ```

Alternatively, run tests directly with:
```bash
docker-compose exec php vendor/bin/phpunit
```

### Creating New Tests

1. Place Unit tests in the `tests/Unit` directory
2. Place Integration tests in the `tests/Integration` directory
3. Follow PHPUnit naming conventions (e.g., `RecipeTest.php`)

## Version Control Setup

The project includes Git setup functionality:

1. A pre-configured `.gitignore` file is included with common exclusions
2. Local development configuration files are excluded from version control
3. Composer dependencies are excluded to minimize repository size
4. Docker-specific files and temporary data are properly excluded

## Using Swagger UI

For a more interactive experience:

1. Visit http://localhost:8080/swagger
2. Authenticate by using the /auth/login endpoint
3. The token will be automatically stored and used for subsequent protected endpoints
4. Try out different endpoints using the Swagger UI interface

**Note:** When accessing the root URL http://localhost:8080, you'll be automatically redirected to the Swagger UI documentation.

## Troubleshooting

### General Troubleshooting

1. Check if the containers are running: `docker-compose ps`
2. View the logs: `docker-compose logs`
3. Reset the database: `docker-compose exec php php /server/http/init-db.php`
4. Check database contents: `docker-compose exec postgres psql -U postgres -d hellofresh -c "SELECT * FROM recipes"`

