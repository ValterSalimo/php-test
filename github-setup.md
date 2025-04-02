# Adding the Project to GitHub

Follow these steps to add your Recipe API project to GitHub:

## 1. Initialize Git Repository (if not already done)

```bash
# Navigate to your project directory
cd c:\Users\valte\Downloads\php-test\php-test

# Initialize Git repository
git init

# Add all files to staging
git add .

# Make your first commit
git commit -m "Initial commit: PHP Recipe API implementation"
```

## 2. Create a GitHub Repository

1. Go to [GitHub](https://github.com) and sign in to your account
2. Click the "+" icon in the top-right corner and select "New repository"
3. Name your repository (e.g., "php-recipe-api")
4. Provide an optional description (e.g., "A RESTful API for managing recipes")
5. Choose visibility (public or private)
6. Do NOT initialize with README, .gitignore, or license (since you already have a local repo)
7. Click "Create repository"

## 3. Connect and Push to GitHub

GitHub will display commands to connect your local repository. Use these commands:

```bash
# Connect your local repository to GitHub
git remote add origin https://github.com/ValterSalimo/php-recipe-api.git

# Push your code to GitHub
git push -u origin main
# Note: If you're using an older Git version, you might need to use 'master' instead of 'main'
```

## 4. Verify Your Repository

1. Refresh your GitHub repository page
2. You should see all your files and directories listed
3. Your README.md will be displayed on the main page

## 5. Additional GitHub Features to Consider

- **Issues**: Enable issues for bug tracking and feature requests
- **Projects**: Set up a project board for task management
- **Actions**: Configure CI/CD workflows for automated testing
- **Branch Protection**: Setup rules to prevent direct pushes to main branch
- **Pull Request Templates**: Create templates for standardized PRs

## 6. Git Best Practices

- Use branches for new features (`git checkout -b feature/new-feature`)
- Write clear, descriptive commit messages
- Reference issue numbers in commits when applicable
- Make small, focused commits
- Keep sensitive data out of your repository (check your .env files)
