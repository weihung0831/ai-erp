export default {
    token: localStorage.getItem('token'),
    user: null,

    get loggedIn() {
        return !!this.token;
    },

    setToken(token) {
        this.token = token;
        localStorage.setItem('token', token);
        window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    },

    clearToken() {
        this.token = null;
        localStorage.removeItem('token');
        delete window.axios.defaults.headers.common['Authorization'];
    },

    async fetchUser() {
        try {
            const res = await window.axios.get('/api/user');
            this.user = res.data.user;
        } catch {
            this.clearToken();
        }
    },

    async logout() {
        try {
            await window.axios.post('/api/logout');
        } catch {
            // token 可能已失效，忽略錯誤
        }
        this.clearToken();
        window.location.href = '/login';
    },
};
