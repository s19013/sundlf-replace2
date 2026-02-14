import type { RouteList, RouteParams } from 'ziggy-js'
import { route } from 'ziggy-js'
import { Ziggy } from '@/shared/api/ziggy'

// ziggy-jsの内部型 RouteName と同等（exportされていないため再定義）
// これにより、ideなどで補完が効くようになる。
type RouteName = keyof RouteList | (string & {})

export const ziggyRoute = <T extends RouteName>(
  name: T,
  params?: RouteParams<T>,
  absolute?: boolean,
): string => {
  return route(name, params, absolute, Ziggy)
}
