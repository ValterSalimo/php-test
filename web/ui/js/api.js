/**
 * API Client for Recipe API
 */
class ApiClient {
    constructor(baseUrl = 'http://localhost:8080') {
        this.baseUrl = baseUrl;
        this.token = localStorage.getItem('auth_token');
    }

    setToken(token) {
        this.token = token;
        localStorage.setItem('auth_token', token);
    }

    clearToken() {
        this.token = null;
        localStorage.removeItem('auth_token');
    }

    getAuthHeaders() {
        return this.token 
            ? { 'Authorization': `Bearer ${this.token}`, 'Content-Type': 'application/json' }
            : { 'Content-Type': 'application/json' };
    }

    async request(endpoint, method = 'GET', data = null) {
        const url = `${this.baseUrl}${endpoint}`;
        const headers = this.getAuthHeaders();
        
        const options = {
            method,
            headers,
            credentials: 'same-origin'
        };

        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);
            
            // For 204 No Content responses (like successful DELETE)
            if (response.status === 204) {
                return { success: true };
            }

            // First check if the response is valid
            const text = await response.text();
            let responseData;
            
            try {
                responseData = JSON.parse(text);
            } catch (error) {
                console.error('Failed to parse response:', text);
                throw new Error(`Invalid JSON response: ${text.substring(0, 100)}...`);
            }
            
            if (!response.ok) {
                throw new Error(responseData.error || 'API request failed');
            }
            
            return responseData;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // Authentication
    async login(username, password) {
        const data = await this.request('/auth/login', 'POST', { username, password });
        if (data.token) {
            this.setToken(data.token);
        }
        return data;
    }

    async register(username, password) {
        return await this.request('/auth/register', 'POST', { username, password });
    }

    // Recipes
    async getRecipes(page = 1, limit = 10) {
        return await this.request(`/recipes?page=${page}&limit=${limit}`);
    }

    async getRecipe(id) {
        return await this.request(`/recipes/${id}`);
    }

    async createRecipe(recipe) {
        return await this.request('/recipes', 'POST', recipe);
    }

    async updateRecipe(id, recipe) {
        return await this.request(`/recipes/${id}`, 'PUT', recipe);
    }

    async deleteRecipe(id) {
        return await this.request(`/recipes/${id}`, 'DELETE');
    }

    async rateRecipe(id, rating) {
        return await this.request(`/recipes/${id}/rating`, 'POST', { rating });
    }

    async searchRecipes(query = '', vegetarian = false, difficulty = '') {
        let url = '/recipes/search?';
        
        if (query) url += `q=${encodeURIComponent(query)}&`;
        if (vegetarian) url += 'vegetarian=true&';
        if (difficulty) url += `difficulty=${difficulty}`;
        
        return await this.request(url);
    }
}

// Export a singleton instance
const api = new ApiClient();
