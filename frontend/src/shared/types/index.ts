export type ValidationError = {
  errors: Record<string, string[]>
}

export type MessageError = {
  message: string
}

export type ApiErrorResponse = ValidationError | MessageError
