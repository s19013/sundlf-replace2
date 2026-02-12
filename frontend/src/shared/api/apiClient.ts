import axios from 'axios'

export const apiClient = axios.create({
  withCredentials: true,
  withXSRFToken: true,
  baseURL: import.meta.env.VITE_API_BASE_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json;charset=utf-8',
  },
})
