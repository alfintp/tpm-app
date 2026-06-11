import { ref, computed } from 'vue';

const user = ref(JSON.parse(localStorage.getItem('user_profile') || 'null'));
const token = ref(localStorage.getItem('auth_token') || 'null');

if (window.axios) {
  if (token.value && token.value !== 'null') {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
  }
  
  // Set up 401 unauthorized interceptor
  window.axios.interceptors.response.use(
    response => response,
    error => {
      if (error.response && error.response.status === 401) {
        token.value = null;
        user.value = null;
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_profile');
        delete window.axios.defaults.headers.common['Authorization'];
        // Avoid infinite reload loop if already on login page
        if (!window.location.pathname.includes('/login') && !window.location.pathname.includes('/register')) {
          window.location.href = '/login';
        }
      }
      return Promise.reject(error);
    }
  );
}

export function useAuth() {
  const isAuthenticated = computed(() => !!user.value && token.value && token.value !== 'null');
  const role = computed(() => user.value?.role || 'technician');
  
  const isAdmin = computed(() => role.value === 'admin');
  const isManager = computed(() => role.value === 'manager');
  const isTechnician = computed(() => role.value === 'technician');
  const isManagerOrAdmin = computed(() => role.value === 'admin' || role.value === 'manager');

  async function login(email, password) {
    try {
      const response = await window.axios.post('/api/login', { email, password });
      
      token.value = response.data.access_token;
      user.value = response.data.user;

      localStorage.setItem('auth_token', token.value);
      localStorage.setItem('user_profile', JSON.stringify(user.value));

      window.axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
      
      // Dispatch custom event to notify external listeners (like header)
      window.dispatchEvent(new Event('auth-changed'));
      
      return { success: true };
    } catch (error) {
      console.error('Login error:', error);
      return { 
        success: false, 
        message: error.response?.data?.message || 'Login gagal. Hubungi administrator.' 
      };
    }
  }

  async function register(fullName, email, password, requestedRole = 'technician') {
    try {
      const response = await window.axios.post('/api/register', {
        full_name: fullName,
        email,
        password,
        role: requestedRole
      });

      return { success: true, data: response.data };
    } catch (error) {
      console.error('Registration error:', error);
      return { 
        success: false, 
        message: error.response?.data?.message || 'Registrasi gagal.' 
      };
    }
  }

  async function logout() {
    try {
      if (token.value && token.value !== 'null') {
        await window.axios.post('/api/logout');
      }
    } catch (error) {
      console.error('Logout API error:', error);
    } finally {
      token.value = null;
      user.value = null;
      
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user_profile');
      
      if (window.axios) {
        delete window.axios.defaults.headers.common['Authorization'];
      }
      
      window.dispatchEvent(new Event('auth-changed'));
    }
  }

  async function initializeAuth() {
    const savedToken = localStorage.getItem('auth_token');
    const savedProfile = localStorage.getItem('user_profile');

    if (savedToken) {
      token.value = savedToken;
      if (window.axios) {
        window.axios.defaults.headers.common['Authorization'] = `Bearer ${savedToken}`;
      }
      
      try {
        const response = await window.axios.get('/api/user/profile');
        user.value = response.data;
        localStorage.setItem('user_profile', JSON.stringify(user.value));
        window.dispatchEvent(new Event('auth-changed'));
      } catch (error) {
        console.error('Profile fetch failed, logging out...', error);
        await logout();
      }
    } else {
      token.value = null;
      user.value = null;
      localStorage.removeItem('user_profile');
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    role,
    isAdmin,
    isManager,
    isTechnician,
    isManagerOrAdmin,
    login,
    register,
    logout,
    initializeAuth,
  };
}
