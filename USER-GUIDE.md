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

```bash
curl -X GET "http://localhost:8080/recipes/search?q=spaghetti&vegetarian=false&difficulty=2"
```

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

## Git Version Control

### Basic Git Workflow

1. Check status of your changes:
   ```bash
   git status
   ```

2. Stage changes for commit:
   ```bash
   # Stage specific files
   git add path/to/file1 path/to/file2

   # Stage all changes
   git add .
   ```

3. Commit your changes:
   ```bash
   git commit -m "Brief description of changes"
   ```

4. Push changes to remote repository:
   ```bash
   git push origin main
   ```

### Working with Branches

1. Create and switch to a new branch:
   ```bash
   git checkout -b feature/new-feature
   ```

2. Switch between branches:
   ```bash
   git checkout main
   ```

3. Merge changes from another branch:
   ```bash
   git checkout main
   git merge feature/new-feature
   ```

### Managing Conflicts

If you encounter merge conflicts:

1. Identify conflicting files:
   ```bash
   git status
   ```

2. Open conflicted files and resolve conflicts manually (look for `<<<<<<<`, `=======`, and `>>>>>>>` markers)

3. Stage resolved files:
   ```bash
   git add path/to/resolved/file
   ```

4. Complete the merge:
   ```bash
   git commit
   ```

### Useful Git Commands

- View commit history:
  ```bash
  git log
  git log --oneline --graph
  ```

- Discard local changes:
  ```bash
  # For specific file
  git checkout -- path/to/file

  # For all unstaged changes
  git checkout -- .
  ```

- Update local repository:
  ```bash
  git pull
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
   # Make the script executable first (only needed once)
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

### Key Git Files

- `git-setup.sh`: Shell script that:
  - Initializes a fresh Git repository
  - Creates a standard `.gitignore` file
  - Makes an initial commit
  - Provides instructions for connecting to GitHub

### Setting Up Git

To initialize Git for this project:

1. Make the script executable:
   ```bash
   chmod +x git-setup.sh
   ```

2. Run the setup script:
   ```bash
   ./git-setup.sh
   ```

3. Follow the instructions displayed to connect to GitHub

## Using Swagger UI

For a more interactive experience:

1. Visit http://localhost:8080/swagger
2. Authenticate by using the /auth/login endpoint
3. The token will be automatically stored and used for subsequent protected endpoints
4. Try out different endpoints using the Swagger UI interface

## Troubleshooting

### Docker Connection Issues

If you see errors like `open //./pipe/dockerDesktopLinuxEngine: The system cannot find the file specified`, this indicates Docker Desktop is not running. Follow these steps:

1. Check if Docker Desktop is installed on your system
2. Start Docker Desktop from the Start menu or system tray
3. Wait for Docker Desktop to fully initialize (look for the green "Docker is running" status)
4. Verify Docker is working with: `docker --version` and `docker-compose --version`
5. If Docker Desktop won't start, try:
   - Right-click on Docker Desktop and "Run as administrator"
   - Restart your computer
   - Reinstall Docker Desktop

### General Troubleshooting

1. Check if the containers are running: `docker-compose ps`
2. View the logs: `docker-compose logs`
3. Reset the database: `docker-compose exec php php /server/http/init-db.php`
4. Check database contents: `docker-compose exec postgres psql -U postgres -d hellofresh -c "SELECT * FROM recipes"`
