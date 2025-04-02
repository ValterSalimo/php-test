# This script sets up a fresh git repository for the PHP Recipe API project

# Ensure we're in the project root
cd $PSScriptRoot

# Create a fresh git repository
Write-Host "Initializing new git repository..." -ForegroundColor Green
if (Test-Path .git) { Remove-Item .git -Recurse -Force }
git init

# Create gitignore if it doesn't exist
if (-not (Test-Path .gitignore)) {
  Write-Host "Creating .gitignore file..." -ForegroundColor Green
  @"
/vendor/
.env
.DS_Store
/logs/
*.log
/.phpunit.result.cache
/docker/data/
"@ | Out-File -FilePath .gitignore -Encoding utf8
}

# Add all files
Write-Host "Adding files to git..." -ForegroundColor Green
git add .

# Create initial commit
Write-Host "Creating initial commit..." -ForegroundColor Green
git commit -m "Initial commit: PHP Recipe API"

Write-Host "Git repository initialized successfully!" -ForegroundColor Green
Write-Host ""
Write-Host "To connect to GitHub, follow these steps:" -ForegroundColor Yellow
Write-Host "1. Create a new repository on GitHub (do NOT initialize with README)" -ForegroundColor Yellow
Write-Host "2. Run the following commands:" -ForegroundColor Yellow
Write-Host "   git remote add origin https://github.com/YOUR-USERNAME/YOUR-REPO-NAME.git" -ForegroundColor Cyan
Write-Host "   git branch -M main" -ForegroundColor Cyan
Write-Host "   git push -u origin main" -ForegroundColor Cyan
