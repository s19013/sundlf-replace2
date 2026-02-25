export interface User {
  id: number
  name: string
}

export interface LoginCredentials {
  email: string
  password: string
  remember: boolean
}
