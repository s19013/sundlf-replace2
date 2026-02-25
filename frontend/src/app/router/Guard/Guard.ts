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
  private _redirectName: RouteRecordName | undefined
  private _accessibleList: RouteRecordRaw[] | undefined

  /** アクセス可能ルート（遅延初期化） */
  public get accessibleList(): RouteRecordRaw[] {
    if (this._accessibleList === undefined) {
      this._accessibleList = this.setAccessibleList()
    }
    return this._accessibleList
  }

  /** リダイレクト先名（遅延初期化） */
  protected get redirectName(): RouteRecordName {
    if (this._redirectName === undefined) {
      this._redirectName = this.setRedirectName()
    }
    return this._redirectName
  }

  /**  アクセス可能ルートを定義 */
  protected abstract setAccessibleList(): RouteRecordRaw[]

  /** リダイレクト先を定義(パスではなくnameで登録してください) */
  protected abstract setRedirectName(): RouteRecordName

  /** リダイレクト条件を定義 */
  protected abstract shouldRedirect(to: RouteLocationNormalized): boolean

  // http://router.vuejs.org/guide/advanced/navigation-guards.html#Optional-third-argument-next
  /** vue router に渡すガード */
  public routeGuard: NavigationGuard = (to: RouteLocationNormalized) => {
    // 拒否条件に引っかかったら指定した場所にリダイレクト
    // 通さない
    if (this.shouldRedirect(to)) {
      return { name: this.redirectName }
    }

    return true
  }
}
