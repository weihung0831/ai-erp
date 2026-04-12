export default {
    token: localStorage.getItem('token'),
    user: null,
    showLogoutModal: false,
    loggingOut: false,

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

    async confirmLogout() {
        this.showLogoutModal = false;
        this.loggingOut = true;
        const minDelay = new Promise(r => setTimeout(r, 2000));
        try { await Promise.all([window.axios.post('/api/logout'), minDelay]); } catch { await minDelay; }
        this.clearToken();
        window.location.href = '/login';
    },

    async fetchUser() {
        try {
            const res = await window.axios.get('/api/user');
            this.user = res.data.user;
        } catch {
            this.clearToken();
        }
    },

};
