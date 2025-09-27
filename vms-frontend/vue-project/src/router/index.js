import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

// Base pages
import Home from '../views/Home.vue'
import Login from '../views/Login.vue'

// import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'

import NotAuthorized from '@/views/NotAuthorized.vue'

// Lazy-loaded views
const Register = () => import('../views/Register.vue')
const About = () => import('../views/About.vue')
const Dashboard = () => import('../views/Dashboard.vue')
const NotFound = () => import('../views/NotFound.vue')

// Driver views
const DriversPage = () => import('../views/Drivers/DriversPage.vue')
const DriverFormPage = () => import('../views/Drivers/DriverFormPage.vue')

// Vehicle views
const VehiclesPage = () => import('../views/Vehicles/VehiclesPage.vue')
const VehicleFormPage = () => import('../views/Vehicles/VehicleFormPage.vue')

// Income views
const IncomeList = () => import('../views/Income/IncomeList.vue')
const IncomeFormPage = () => import('../views/Income/IncomeFormPage.vue')

// Check-in views
const CheckInsPage = () => import('../views/CheckIns/CheckInsPage.vue')
const CheckInFormPage = () => import('../views/CheckIns/CheckInFormPage.vue')

// Maintenance views
const MaintenancePage = () => import('../views/Maintenances/MaintenancePage.vue')
const MaintenanceFormPage = () => import('../views/Maintenances/MaintenanceFormPage.vue')

// Expense views
const ExpensesPage = () => import('../views/Expenses/ExpensesPage.vue')
const ExpenseFormPage = () => import('../views/Expenses/ExpenseFormPage.vue')

// recent activity
import RecentActivityPage from '@/views/RecentActivityPage.vue'

// trip view
import TripsPage from '@/views/Trips/TripsPage.vue'
import TripFormPage from '@/views/Trips/TripFormPage.vue'

const routes = [
  {
    path: '/',
    name: 'Home',
    component: Home,
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { guestOnly: true, title: 'Login - Vehicle Management System' },
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
    meta: { guestOnly: true, title: 'Register - Vehicle Management System' },
  },
  {
    path: '/about',
    name: 'About',
    component: About,
    meta: { title: 'About - Vehicle Management System' },
  },
  {
    path: '/support',
    name: 'Support',
    component: () => import('@/views/Support.vue'),
    meta: { title: 'Support - Vehicle Management System' },
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true, title: 'Dashboard - Vehicle Management System' },
  },
  // audit trial
  {
    path: '/audit-trail',
    name: 'AuditTrail',
    component: () => import('@/views/Audit/AuditTrailList.vue'),
    meta: {
      requiresAuth: true,
      title: 'Audit Trail - Vehicle Management System',
      roles: ['admin', 'manager'],
    },
  },
  // income
  {
    path: '/incomes',
    name: 'Incomes',
    component: IncomeList,
    meta: {
      requiresAuth: true,
      title: 'Income List - Vehicle Management System',
      roles: ['admin', 'manager'],
    },
  },
  {
    path: '/incomes/create',
    name: 'IncomeCreate',
    component: IncomeFormPage,
    meta: {
      requiresAuth: true,
      title: 'Create Income - Vehicle Management System',
      roles: ['admin', 'manager'],
    },
  },
  {
    path: '/incomes/:id/edit',
    name: 'IncomeEdit',
    component: IncomeFormPage,
    props: true,
    meta: {
      requiresAuth: true,
      title: 'Edit Income - Vehicle Management System',
      roles: ['admin'],
    },
  },

  // user profile
  {
    path: '/profile',
    name: 'UserProfile',
    component: () => import('@/views/Profile/UserProfile.vue'),
    meta: { requiresAuth: true, title: 'My Profile - Vehicle Management System' },
  },

  // Drivers
  {
    path: '/drivers',
    name: 'Drivers',
    component: DriversPage,
    meta: {
      requiresAuth: true,
      title: 'Driver Page - Vehicle Management System',
      roles: ['admin', 'manager', 'vehicle_owner', 'gate_security'],
    },
  },
  {
    path: '/drivers/new',
    name: 'DriverCreate',
    component: DriverFormPage,
    meta: {
      requiresAuth: true,
      title: 'Create New Driver - Vehicle Management System',
      roles: ['admin', 'manager', 'gate_security'],
    },
  },
  {
    path: '/drivers/:id/edit',
    name: 'DriverEdit',
    component: DriverFormPage,
    meta: {
      requiresAuth: true,
      title: 'Edit Driver - Vehicle Management System',
      roles: ['admin', 'manager'],
    },
  },
  {
    path: '/drivers/:id',
    name: 'DriverProfile',
    component: () => import('@/views/Drivers/DriverProfilePage.vue'),
    meta: {
      requiresAuth: true,
      title: 'Driver Profile - Vehicle Management System',
      roles: ['admin', 'manager', 'vehicle_owner', 'gate_security'],
    },
  },

  // Vehicles
  {
    path: '/vehicles',
    name: 'Vehicles',
    component: VehiclesPage,
    meta: {
      requiresAuth: true,
      title: 'Vehicle Page - Vehicle Management System',
      roles: ['admin', 'manager', 'vehicle_owner', 'gate_security'],
    },
  },
  {
    path: '/vehicles/new',
    name: 'VehicleCreate',
    component: VehicleFormPage,
    meta: {
      requiresAuth: true,
      title: 'Register New Vehicle - Vehicle Management System',
      roles: ['admin', 'manager', 'gate_security'],
    },
  },
  {
    path: '/vehicles/:id/edit',
    name: 'VehicleEdit',
    component: VehicleFormPage,
    meta: {
      requiresAuth: true,
      title: 'Edit Vehicle - Vehicle Management System',
      roles: ['admin', 'manager', 'gate_security'],
    },
  },
  {
    path: '/vehicle-within',
    name: 'VehicleWithin',
    component: () => import('@/views/Vehicles/VehicleWithin.vue'),
    meta: {
      requiresAuth: true,
      title: 'Vehicle Within Premises - Vehicle Management System',
      roles: ['gate_security', 'admin', 'manager'],
    },
  },

  // Check-Ins
  {
    path: '/checkins',
    name: 'CheckIns',
    component: CheckInsPage,
    meta: {
      requiresAuth: true,
      title: 'Check In Page - Vehicle Management System',
      roles: ['admin', 'manager', 'gate_security'],
    },
  },
  {
    path: '/checkins/new',
    name: 'CheckInCreate',
    component: CheckInFormPage,
    meta: {
      requiresAuth: true,
      title: 'Check In - Vehicle Management System',
      roles: ['admin', 'manager', 'gate_security'],
    },
  },
  {
    path: '/checkins/:id/edit',
    name: 'CheckInEdit',
    component: CheckInFormPage,
    meta: {
      requiresAuth: true,
      title: 'Update Check In - Vehicle Management System',
      roles: ['admin', 'manager', 'gate_security'],
    },
  },

  // Maintenance
  {
    path: '/maintenance',
    name: 'Maintenance',
    component: MaintenancePage,
    meta: {
      requiresAuth: true,
      title: 'Maintenance Page - Vehicle Management System',
      roles: ['admin', 'manager', 'vehicle_owner', 'driver'],
    },
  },
  {
    path: '/maintenance/new',
    name: 'MaintenanceNew',
    component: MaintenanceFormPage,
    meta: {
      requiresAuth: true,
      title: 'Create New Maintenance - Vehicle Management System',
      roles: ['admin', 'manager', 'driver', 'vehicle_owner'],
    },
  },
  {
    path: '/maintenance/:id/edit',
    name: 'MaintenanceEdit',
    component: MaintenanceFormPage,
    props: true,
    meta: {
      requiresAuth: true,
      title: 'Edit Maintenance - Vehicle Management System',
      roles: ['admin', 'manager', 'driver'],
    },
  },
  // Expense
  {
    path: '/expenses',
    name: 'Expenses',
    component: ExpensesPage,
    meta: {
      requiresAuth: true,
      title: 'Expenses Page - Vehicle Management System',
      roles: ['admin', 'manager', 'vehicle_owner', 'driver'],
    },
  },
  {
    path: '/expenses/new',
    name: 'ExpenseCreate',
    component: ExpenseFormPage,
    meta: {
      requiresAuth: true,
      title: 'Create New Expenses - Vehicle Management System',
      roles: ['admin', 'manager', 'driver'],
    },
  },
  {
    path: '/expenses/:id/edit',
    name: 'ExpenseEdit',
    component: ExpenseFormPage,
    props: true,
    meta: {
      requiresAuth: true,
      title: 'Edit Expenses - Vehicle Management System',
      roles: ['admin', 'manager', 'driver'],
    },
  },

  // User route

  {
    path: '/users',
    component: () => import('@/views/Users/UsersPage.vue'),
    meta: {
      requiresAuth: true,
      title: 'User List - Vehicle Management System',
      roles: ['admin', 'manager', 'gate_security'],
    },
  },
  {
    path: '/users/new',
    component: () => import('@/views/Users/UserFormPage.vue'),
    meta: {
      requiresAuth: true,
      title: 'Create New User - Vehicle Management System',
      roles: ['admin', 'manager', 'gate_security'],
    },
  },
  {
    path: '/users/:id/edit',
    component: () => import('@/views/Users/UserFormPage.vue'),
    meta: {
      requiresAuth: true,
      title: 'Edit User - Vehicle Management System',
      roles: ['admin', 'manager', 'gate_security'],
    },
  },

  // Trips
  {
    path: '/trips',
    name: 'Trips',
    component: TripsPage,
    meta: {
      requiresAuth: true,
      title: 'Trips - Vehicle Management System',
      roles: ['admin', 'manager', 'vehicle_owner', 'driver', 'gate_security'],
    },
  },
  {
    path: '/trips/create',
    name: 'TripCreate',
    component: TripFormPage,
    meta: {
      requiresAuth: true,
      title: 'Create Trip - Vehicle Management System',
      roles: ['admin', 'manager', 'driver'],
    },
  },
  {
    path: '/trips/:id/edit',
    name: 'TripEdit',
    component: TripFormPage,
    props: true,
    meta: {
      requiresAuth: true,
      title: 'Edit Trip - Vehicle Management System',
      roles: ['admin', 'manager', 'driver'],
    },
  },

  // recent activity
  {
    path: '/recent-activity',
    name: 'RecentActivity',
    component: RecentActivityPage,
    meta: {
      requiresAuth: true,
      title: 'Recent Activities - Vehicle Management System',
      roles: ['admin', 'manager'],
    },
  },

  //not authorized
  {
    path: '/not-authorized',
    name: 'NotAuthorized',
    component: NotAuthorized,
    meta: { title: 'Action Not Authorized - Vehicle Management System' },
  },
  // Fallback 404
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: NotFound,
    meta: { title: 'Page not found - Vehicle Management System' },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to, from, next) => {
  document.title = to.meta?.title || 'Vehicle Management System'

  const auth = useAuthStore()

  if (auth.token && !auth.user) {
    try {
      await auth.fetchUser()
    } catch {
      auth.logout()
      return next('/login')
    }
  }

  if (to.meta.requiresAuth && !auth.user) {
    return next('/login')
  }

  if (to.meta.guestOnly && auth.user) {
    return next('/dashboard')
  }

  if (to.meta.roles && !to.meta.roles.includes(auth.user?.role)) {
    return next({ name: 'NotAuthorized' })
  }

  return next()
})

export default router
