#!/bin/bash

# This script sets up a fresh git repository for the PHP Recipe API project

# Ensure we're in the project root
cd "$(dirname "$0")"

# Create a fresh git repository
echo "Initializing new git repository..."
rm -rf .git
git init

# Create gitignore if it doesn't exist
if [ ! -f .gitignore ]; then
  echo "Creating .gitignore file..."
  echo "/vendor/" > .gitignore
  echo ".env" >> .gitignore
  echo ".DS_Store" >> .gitignore
  echo "/logs/" >> .gitignore
  echo "*.log" >> .gitignore
  echo "/.phpunit.result.cache" >> .gitignore
  echo "/docker/data/" >> .gitignore
fi

# Add all files
echo "Adding files to git..."
git add .

# Create initial commit
echo "Creating initial commit..."
git commit -m "final changes to the version 1: PHP Recipe API"

echo "Git repository initialized successfully!"
echo ""
echo "To connect to GitHub, follow these steps:"
echo "1. Create a new repository on GitHub (do NOT initialize with README)"
echo "2. Run the following commands:"
echo "   git remote add origin https://github.com/ValterSalimo/php-test.git"
echo "   git branch -M main"
echo "   git push -u origin main"