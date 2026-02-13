import axios from 'axios'

// ziggyにより/apiがついた状態で出力されるのでbaseurlに"/api"はつけない
const apiBaseUrl = import.meta.env.VITE_API_BASE_URL

if (!apiBaseUrl) {
  console.error('apiBaseUrl が設定されていません')
  throw new Error('apiBaseUrl が設定されていません')
}

export const apiClient = axios.create({
  withCredentials: true,
  withXSRFToken: true,
  baseURL: apiBaseUrl,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json;charset=utf-8',
  },
})
