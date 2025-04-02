document.addEventListener('DOMContentLoaded', () => {
    // DOM Elements
    const loginSection = document.getElementById('login-section');
    const userSection = document.getElementById('user-section');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const loginBtn = document.getElementById('login-btn');
    const registerBtn = document.getElementById('register-btn');
    const logoutBtn = document.getElementById('logout-btn');
    const usernameDisplay = document.getElementById('username-display');
    
    const recipeList = document.getElementById('recipe-list');
    const recipeDetail = document.getElementById('recipe-detail');
    const recipeFormContainer = document.getElementById('recipe-form-container');
    const recipeForm = document.getElementById('recipe-form');
    const recipeIdInput = document.getElementById('recipe-id');
    const recipeNameInput = document.getElementById('recipe-name');
    const prepTimeInput = document.getElementById('prep-time');
    const difficultyInput = document.getElementById('difficulty');
    const vegetarianInput = document.getElementById('vegetarian');
    const cancelFormBtn = document.getElementById('cancel-form');
    const formTitle = document.getElementById('form-title');
    
    const listRecipesBtn = document.getElementById('list-recipes-btn');
    const createRecipeBtn = document.getElementById('create-recipe-btn');
    const searchForm = document.getElementById('search-form');
    const searchQueryInput = document.getElementById('search-query');
    const vegetarianFilterInput = document.getElementById('vegetarian-filter');
    const difficultyFilterInput = document.getElementById('difficulty-filter');
    
    const notificationElem = document.getElementById('notification');
    const confirmationModal = document.getElementById('confirmation-modal');
    const confirmationMessage = document.getElementById('confirmation-message');
    const confirmYesBtn = document.getElementById('confirm-yes');
    const confirmNoBtn = document.getElementById('confirm-no');
    
    // Current state
    let currentRecipeId = null;
    let confirmAction = null;
    let isLoggedIn = !!localStorage.getItem('auth_token');
    
    // Initialize UI
    initUI();
    
    // Event listeners
    loginBtn.addEventListener('click', handleLogin);
    registerBtn.addEventListener('click', handleRegister);
    logoutBtn.addEventListener('click', handleLogout);
    
    listRecipesBtn.addEventListener('click', loadRecipes);
    createRecipeBtn.addEventListener('click', showCreateForm);
    cancelFormBtn.addEventListener('click', hideForm);
    
    recipeForm.addEventListener('submit', handleRecipeSubmit);
    searchForm.addEventListener('submit', handleSearch);
    
    confirmYesBtn.addEventListener('click', () => {
        if (confirmAction) confirmAction();
        hideConfirmationModal();
    });
    
    confirmNoBtn.addEventListener('click', hideConfirmationModal);
    
    // Functions
    function initUI() {
        updateAuthUI();
        loadRecipes();
    }
    
    function updateAuthUI() {
        if (isLoggedIn) {
            loginSection.style.display = 'none';
            userSection.style.display = 'block';
            createRecipeBtn.style.display = 'block';
            
            // Decode JWT to get username
            const token = localStorage.getItem('auth_token');
            if (token) {
                try {
                    const payload = JSON.parse(atob(token.split('.')[1]));
                    usernameDisplay.textContent = payload.username || 'User';
                } catch (e) {
                    usernameDisplay.textContent = 'User';
                }
            }
        } else {
            loginSection.style.display = 'flex';
            userSection.style.display = 'none';
            createRecipeBtn.style.display = 'none';
        }
    }
    
    async function handleLogin() {
        try {
            const username = usernameInput.value;
            const password = passwordInput.value;
            
            if (!username || !password) {
                showNotification('Please enter username and password', 'error');
                return;
            }
            
            await api.login(username, password);
            isLoggedIn = true;
            updateAuthUI();
            showNotification('Logged in successfully', 'success');
            loadRecipes();
        } catch (error) {
            showNotification('Login failed: ' + error.message, 'error');
        }
    }
    
    async function handleRegister() {
        try {
            const username = usernameInput.value;
            const password = passwordInput.value;
            
            if (!username || !password) {
                showNotification('Please enter username and password', 'error');
                return;
            }
            
            await api.register(username, password);
            showNotification('Registration successful! You can now login.', 'success');
        } catch (error) {
            showNotification('Registration failed: ' + error.message, 'error');
        }
    }
    
    function handleLogout() {
        api.clearToken();
        isLoggedIn = false;
        updateAuthUI();
        showNotification('Logged out successfully', 'success');
        loadRecipes();
    }
    
    async function loadRecipes() {
        try {
            showLoader();
            const response = await api.getRecipes();
            displayRecipes(response.data);
            hideLoader();
        } catch (error) {
            showNotification('Error loading recipes: ' + error.message, 'error');
            hideLoader();
        }
    }
    
    function displayRecipes(recipes) {
        recipeDetail.style.display = 'none';
        recipeFormContainer.style.display = 'none';
        recipeList.style.display = 'grid';
        
        if (!recipes || recipes.length === 0) {
            recipeList.innerHTML = '<p class="no-recipes">No recipes found</p>';
            return;
        }
        
        recipeList.innerHTML = '';
        
        recipes.forEach(recipe => {
            const card = document.createElement('div');
            card.className = 'recipe-card';
            
            const difficultyText = ['', 'Easy', 'Medium', 'Hard'][recipe.difficulty];
            const starsHtml = getStarRating(recipe.avgRating);
            
            card.innerHTML = `
                <h3>${recipe.name}</h3>
                ${recipe.vegetarian ? '<span class="vegetarian-badge">Vegetarian</span>' : ''}
                <div class="recipe-meta">
                    <div>Prep time: ${recipe.prepTime} minutes</div>
                    <div>Difficulty: <span class="difficulty difficulty-${recipe.difficulty}">${difficultyText}</span></div>
                </div>
                <div class="rating-container">
                    ${starsHtml}
                    <span class="star-count">(${recipe.ratings || 0})</span>
                </div>
                <div class="recipe-actions">
                    <button class="primary view-recipe" data-id="${recipe.id}">View</button>
                    ${isLoggedIn ? `
                        <button class="secondary edit-recipe" data-id="${recipe.id}">Edit</button>
                        <button class="danger delete-recipe" data-id="${recipe.id}">Delete</button>
                    ` : ''}
                </div>
            `;
            
            recipeList.appendChild(card);
            
            // Add event listeners
            card.querySelector('.view-recipe').addEventListener('click', () => viewRecipe(recipe.id));
            
            if (isLoggedIn) {
                card.querySelector('.edit-recipe').addEventListener('click', () => showEditForm(recipe.id));
                card.querySelector('.delete-recipe').addEventListener('click', () => confirmDeleteRecipe(recipe.id));
            }
        });
    }
    
    async function viewRecipe(id) {
        try {
            showLoader();
            const recipe = await api.getRecipe(id);
            recipeList.style.display = 'none';
            recipeFormContainer.style.display = 'none';
            recipeDetail.style.display = 'block';
            
            const difficultyText = ['', 'Easy', 'Medium', 'Hard'][recipe.difficulty];
            const starsHtml = getStarRating(recipe.avgRating);
            
            recipeDetail.innerHTML = `
                <h2>${recipe.name}</h2>
                ${recipe.vegetarian ? '<span class="vegetarian-badge">Vegetarian</span>' : ''}
                <div class="detail-meta">
                    <div>Preparation Time: ${recipe.prepTime} minutes</div>
                    <div>Difficulty: <span class="difficulty difficulty-${recipe.difficulty}">${difficultyText}</span></div>
                </div>
                
                <div class="recipe-rating">
                    <h3>Rating</h3>
                    <div class="rating-display">
                        ${starsHtml}
                        <span class="star-count">(${recipe.ratings || 0})</span>
                    </div>
                    <div class="rate-recipe">
                        <h4>Rate this recipe:</h4>
                        <div class="star-rating">
                            <span class="star" data-rating="1">★</span>
                            <span class="star" data-rating="2">★</span>
                            <span class="star" data-rating="3">★</span>
                            <span class="star" data-rating="4">★</span>
                            <span class="star" data-rating="5">★</span>
                        </div>
                    </div>
                </div>
                
                <div class="recipe-actions">
                    <button class="primary back-btn">Back to List</button>
                    ${isLoggedIn ? `
                        <button class="secondary edit-from-detail" data-id="${recipe.id}">Edit Recipe</button>
                        <button class="danger delete-from-detail" data-id="${recipe.id}">Delete Recipe</button>
                    ` : ''}
                </div>
            `;
            
            // Add event listeners
            recipeDetail.querySelector('.back-btn').addEventListener('click', loadRecipes);
            
            // Star rating event listeners
            const stars = recipeDetail.querySelectorAll('.star-rating .star');
            stars.forEach(star => {
                star.addEventListener('click', () => rateRecipe(id, parseInt(star.dataset.rating)));
                
                // Hover effect
                star.addEventListener('mouseover', () => {
                    const rating = parseInt(star.dataset.rating);
                    stars.forEach((s, i) => {
                        s.style.color = i < rating ? '#f39c12' : '#ccc';
                    });
                });
                
                star.addEventListener('mouseout', () => {
                    stars.forEach(s => {
                        s.style.color = '';
                    });
                });
            });
            
            if (isLoggedIn) {
                recipeDetail.querySelector('.edit-from-detail').addEventListener('click', () => showEditForm(recipe.id));
                recipeDetail.querySelector('.delete-from-detail').addEventListener('click', () => confirmDeleteRecipe(recipe.id));
            }
            
            hideLoader();
        } catch (error) {
            showNotification('Error loading recipe: ' + error.message, 'error');
            hideLoader();
        }
    }
    
    async function rateRecipe(id, rating) {
        try {
            await api.rateRecipe(id, rating);
            showNotification('Recipe rated successfully!', 'success');
            viewRecipe(id); // Refresh the view
        } catch (error) {
            showNotification('Error rating recipe: ' + error.message, 'error');
        }
    }
    
    function showCreateForm() {
        if (!isLoggedIn) {
            showNotification('Please login to create a recipe', 'error');
            return;
        }
        
        formTitle.textContent = 'Create New Recipe';
        recipeIdInput.value = '';
        recipeForm.reset();
        
        recipeList.style.display = 'none';
        recipeDetail.style.display = 'none';
        recipeFormContainer.style.display = 'block';
    }
    
    async function showEditForm(id) {
        if (!isLoggedIn) {
            showNotification('Please login to edit a recipe', 'error');
            return;
        }
        
        try {
            showLoader();
            const recipe = await api.getRecipe(id);
            
            formTitle.textContent = 'Edit Recipe';
            recipeIdInput.value = recipe.id;
            recipeNameInput.value = recipe.name;
            prepTimeInput.value = recipe.prepTime;
            difficultyInput.value = recipe.difficulty;
            vegetarianInput.checked = recipe.vegetarian;
            
            recipeList.style.display = 'none';
            recipeDetail.style.display = 'none';
            recipeFormContainer.style.display = 'block';
            
            hideLoader();
        } catch (error) {
            showNotification('Error loading recipe for editing: ' + error.message, 'error');
            hideLoader();
        }
    }
    
    function hideForm() {
        recipeFormContainer.style.display = 'none';
        loadRecipes();
    }
    
    async function handleRecipeSubmit(e) {
        e.preventDefault();
        
        if (!isLoggedIn) {
            showNotification('Please login to save a recipe', 'error');
            return;
        }
        
        const recipeData = {
            name: recipeNameInput.value,
            prepTime: parseInt(prepTimeInput.value),
            difficulty: parseInt(difficultyInput.value),
            vegetarian: vegetarianInput.checked // This will be a proper boolean
        };
        
        // Log for debugging
        console.log('Submitting recipe data:', recipeData);
        
        const id = recipeIdInput.value;
        
        try {
            showLoader();
            if (id) {
                await api.updateRecipe(id, recipeData);
                showNotification('Recipe updated successfully', 'success');
            } else {
                await api.createRecipe(recipeData);
                showNotification('Recipe created successfully', 'success');
            }
            hideLoader();
            loadRecipes();
        } catch (error) {
            showNotification('Error saving recipe: ' + error.message, 'error');
            hideLoader();
        }
    }
    
    function confirmDeleteRecipe(id) {
        confirmationMessage.textContent = 'Are you sure you want to delete this recipe?';
        confirmAction = () => deleteRecipe(id);
        showConfirmationModal();
    }
    
    async function deleteRecipe(id) {
        if (!isLoggedIn) {
            showNotification('Please login to delete a recipe', 'error');
            return;
        }
        
        try {
            showLoader();
            await api.deleteRecipe(id);
            showNotification('Recipe deleted successfully', 'success');
            hideLoader();
            loadRecipes();
        } catch (error) {
            showNotification('Error deleting recipe: ' + error.message, 'error');
            hideLoader();
        }
    }
    
    async function handleSearch(e) {
        e.preventDefault();
        
        const query = searchQueryInput.value;
        const vegetarian = vegetarianFilterInput.checked;
        const difficulty = difficultyFilterInput.value;
        
        try {
            showLoader();
            const response = await api.searchRecipes(query, vegetarian, difficulty);
            displayRecipes(response.data);
            hideLoader();
        } catch (error) {
            showNotification('Error searching recipes: ' + error.message, 'error');
            hideLoader();
        }
    }
    
    // Utility functions
    function getStarRating(rating) {
        const fullStars = Math.floor(rating);
        const halfStar = rating - fullStars >= 0.5;
        const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
        
        let starsHtml = '';
        
        // Full stars
        for (let i = 0; i < fullStars; i++) {
            starsHtml += '<span class="star gold">★</span>';
        }
        
        // Half star
        if (halfStar) {
            starsHtml += '<span class="star half-gold">★</span>';
        }
        
        // Empty stars
        for (let i = 0; i < emptyStars; i++) {
            starsHtml += '<span class="star">★</span>';
        }
        
        return starsHtml;
    }
    
    function showNotification(message, type) {
        notificationElem.textContent = message;
        notificationElem.className = `notification ${type}`;
        notificationElem.classList.add('show');
        
        setTimeout(() => {
            notificationElem.classList.remove('show');
        }, 3000);
    }
    
    function showConfirmationModal() {
        confirmationModal.style.display = 'flex';
    }
    
    function hideConfirmationModal() {
        confirmationModal.style.display = 'none';
        confirmAction = null;
    }
    
    function showLoader() {
        // Create loader if it doesn't exist
        if (!document.getElementById('loader')) {
            const loader = document.createElement('div');
            loader.id = 'loader';
            loader.className = 'loader';
            loader.innerHTML = '<div class="spinner"></div>';
            document.body.appendChild(loader);
            
            // Add loader styles
            const style = document.createElement('style');
            style.textContent = `
                .loader {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0,0,0,0.5);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 2000;
                }
                .spinner {
                    width: 50px;
                    height: 50px;
                    border: 5px solid #f3f3f3;
                    border-top: 5px solid #3498db;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                }
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }
        
        document.getElementById('loader').style.display = 'flex';
    }
    
    function hideLoader() {
        const loader = document.getElementById('loader');
        if (loader) {
            loader.style.display = 'none';
        }
    }
});
