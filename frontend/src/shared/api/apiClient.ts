import axios from 'axios'

// note:ziggyにより"バックのurl/api/~"の状態で出力されるのでbaseurl自体がいらなくなった。

export const apiClient = axios.create({
  withCredentials: true,
  withXSRFToken: true,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json;charset=utf-8',
  },
})
