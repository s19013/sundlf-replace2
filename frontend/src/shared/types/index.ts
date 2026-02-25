import type { components } from './schema'

export type ValidationError =
  components['responses']['ValidationException']['content']['application/json']

export type MessageError =
  components['responses']['AuthenticationException']['content']['application/json']

export type ApiErrorResponse = ValidationError | MessageError
