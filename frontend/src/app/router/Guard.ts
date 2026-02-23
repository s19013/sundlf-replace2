import type {
  NavigationGuard,
  RouteLocationNormalized,
  RouteRecordRaw,
  RouteRecordName,
} from 'vue-router'

/**
 * setAccessibleList() : アクセス可能ルートを定義
 * setRedirectName() : リダイレクト先を定義
 * shouldRedirect() : リダイレクト条件を定義
 *
 * index.tsにaccessibleListとrouteGuardを登録
 */
export abstract class Guard {
  protected redirectName: RouteRecordName = ''
  public accessibleList: RouteRecordRaw[] = []

  constructor() {
    this.redirectName = this.setRedirectName()
    this.accessibleList = this.setAccessibleList()
  }

  /**  アクセス可能ルートを定義 */
  protected abstract setAccessibleList(): RouteRecordRaw[]

  /** リダイレクト先を定義(パスではなくnameで登録してください) */
  protected abstract setRedirectName(): RouteRecordName

  /** リダイレクト条件を定義 */
  protected abstract shouldRedirect(to: RouteLocationNormalized): boolean

  // http://router.vuejs.org/guide/advanced/navigation-guards.html#Optional-third-argument-next
  /** vue router に渡すガード */
  public routeGuard: NavigationGuard = async (to: RouteLocationNormalized) => {
    // 拒否条件に引っかかったら指定した場所にリダイレクト
    // 通さない
    if (this.shouldRedirect(to)) {
      return { name: this.redirectName }
    }

    return true
  }
}
