import axios from 'axios'

const backendWebUrl = import.meta.env.VITE_BACKEND_WEB_URL

if (!backendWebUrl) {
  console.error('backendWebUrl が設定されていません')
  throw new Error('backendWebUrl が設定されていません')
}

export const webClient = axios.create({
  withCredentials: true,
  withXSRFToken: true,
  baseURL: backendWebUrl,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json;charset=utf-8',
  },
})
