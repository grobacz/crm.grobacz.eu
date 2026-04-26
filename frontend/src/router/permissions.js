export const ROLE_ACCESS = {
  admin: ['dashboard', 'customers', 'contacts', 'leads', 'deals', 'products', 'campaigns', 'settings'],
  manager: ['dashboard', 'customers', 'contacts', 'leads', 'deals', 'products', 'campaigns'],
  user: ['dashboard', 'customers', 'contacts', 'leads', 'deals']
}

export function canAccessSection(role, section) {
  return ROLE_ACCESS[role]?.includes(section) ?? false
}

export function canAccessRoute(role, route) {
  const requiredSection = route.meta?.section

  if (!requiredSection) {
    return true
  }

  return canAccessSection(role, requiredSection)
}

export function firstAccessibleRoute(role) {
  if (canAccessSection(role, 'dashboard')) {
    return '/dashboard'
  }

  return '/'
}
