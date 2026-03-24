import { createStore } from 'vuex'
import axios from 'axios'

// 设置axios默认值
axios.defaults.withCredentials = true
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

const store = createStore({
    state: {
        user: null,
        isAuthenticated: false,
        token: localStorage.getItem('token') || null,
        loading: false,
        errors: []
    },
    mutations: {
        SET_USER(state, user) {
            state.user = user
            state.isAuthenticated = !!user
        },
        SET_TOKEN(state, token) {
            state.token = token
            if (token) {
                localStorage.setItem('token', token)
            } else {
                localStorage.removeItem('token')
            }
        },
        SET_LOADING(state, loading) {
            state.loading = loading
        },
        SET_ERRORS(state, errors) {
            state.errors = errors
        },
        CLEAR_AUTH_DATA(state) {
            state.user = null
            state.isAuthenticated = false
            state.token = null
            localStorage.removeItem('token')
        }
    },
    actions: {
        async login({ commit }, credentials) {
            commit('SET_LOADING', true)
            commit('SET_ERRORS', [])

            try {
                const response = await axios.post('/api/login', credentials)
                const { user, token } = response.data

                commit('SET_USER', user)
                commit('SET_TOKEN', token)

                return { success: true }
            } catch (error) {
                const errors = error.response?.data?.message ? [error.response.data.message] : ['Login failed']
                commit('SET_ERRORS', errors)
                return { success: false, errors }
            } finally {
                commit('SET_LOADING', false)
            }
        },
        async register({ commit }, userData) {
            commit('SET_LOADING', true)
            commit('SET_ERRORS', [])

            try {
                const response = await axios.post('/api/register', userData)
                const { user, token } = response.data

                commit('SET_USER', user)
                commit('SET_TOKEN', token)

                return { success: true }
            } catch (error) {
                const errors = error.response?.data?.errors ?
                    Object.values(error.response.data.errors).flat() :
                    [error.response?.data?.message || 'Registration failed']

                commit('SET_ERRORS', errors)
                return { success: false, errors }
            } finally {
                commit('SET_LOADING', false)
            }
        },
        logout({ commit }) {
            commit('CLEAR_AUTH_DATA')
        }
    },
    getters: {
        isLoggedIn: state => state.isAuthenticated,
        currentUser: state => state.user,
        isLoading: state => state.loading,
        authErrors: state => state.errors
    }
})

export default store
