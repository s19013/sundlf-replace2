const Ziggy = {
  url: 'http://localhost:8000',
  port: 8000,
  defaults: {},
  routes: {
    'sanctum.csrf-cookie': { uri: 'sanctum/csrf-cookie', methods: ['GET', 'HEAD'] },
    'spa.login': { uri: 'api/spa/login', methods: ['POST'] },
    'spa.logout': { uri: 'api/spa/logout', methods: ['POST'] },
    'spa.user': { uri: 'api/spa/user', methods: ['GET', 'HEAD'] },
    'storage.local': {
      uri: 'storage/{path}',
      methods: ['GET', 'HEAD'],
      wheres: { path: '.*' },
      parameters: ['path'],
    },
  },
}
if (typeof window !== 'undefined' && typeof window.Ziggy !== 'undefined') {
  Object.assign(Ziggy.routes, window.Ziggy.routes)
}
export { Ziggy }
