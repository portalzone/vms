import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

// Base pages
import Home from '../views/Home.vue'
import Login from '../views/Login.vue'

import Toast from 'vue-toastification'
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
import RecentActivityPage from '@/views/RecentActivityPage.vue';

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
    meta: { guestOnly: true },
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
    meta: { guestOnly: true },
  },
  {
    path: '/about',
    name: 'About',
    component: About,
  },
  {
  path: '/support',
  name: 'Support',
  component: () => import('@/views/Support.vue')
},
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true },
  },
// audit trial
{
  path: '/audit-trail',
  name: 'AuditTrail',
  component: () => import('@/views/Audit/AuditTrailList.vue'),
  meta: { requiresAuth: true, roles: ['admin', 'manager']  }
}, 
// income
{
    path: '/incomes',
    name: 'Incomes',
    component: IncomeList,
    meta: { requiresAuth: true, roles: ['admin', 'manager']  }
  },
  {
    path: '/incomes/create',
    name: 'IncomeCreate',
    component: IncomeFormPage,
    meta: { requiresAuth: true, roles: ['admin', 'manager']  }

  },
  {
    path: '/incomes/:id/edit',
    name: 'IncomeEdit',
    component: IncomeFormPage,
    props: true,
        meta: { requiresAuth: true, roles: ['admin', 'manager']  }

  },

// user profile
{
  path: '/profile',
  name: 'UserProfile',
  component: () => import('@/views/Profile/UserProfile.vue'),
  meta: { requiresAuth: true }
},

  // Drivers
  {
    path: '/drivers',
    name: 'Drivers',
    component: DriversPage,
    meta: { requiresAuth: true, roles: ['admin', 'manager', 'vehicle_owner', 'gate_security'] },
  },
  {
    path: '/drivers/new',
    name: 'DriverCreate',
    component: DriverFormPage,
    meta: { requiresAuth: true, roles: ['admin', 'manager', 'gate_security']  },
  },
  {
    path: '/drivers/:id/edit',
    name: 'DriverEdit',
    component: DriverFormPage,
    meta: { requiresAuth: true, roles: ['admin', 'manager']  },
  },
  {
  path: '/drivers/:id',
  name: 'DriverProfile',
  component: () => import('@/views/Drivers/DriverProfilePage.vue'),
  meta: { requiresAuth: true, roles: ['admin', 'manager', 'vehicle_owner', 'gate_security']  },
},

  // Vehicles
  {
    path: '/vehicles',
    name: 'Vehicles',
    component: VehiclesPage,
    meta: { requiresAuth: true, roles: ['admin', 'manager', 'vehicle_owner']  },
  },
  {
    path: '/vehicles/new',
    name: 'VehicleCreate',
    component: VehicleFormPage,
    meta: { requiresAuth: true, roles: ['admin', 'manager', 'vehicle_owner']  },
  },
  {
    path: '/vehicles/:id/edit',
    name: 'VehicleEdit',
    component: VehicleFormPage,
    meta: { requiresAuth: true, roles: ['admin', 'manager', 'vehicle_owner']  },
  },
  {
        path: '/vehicle-within',
        name: 'VehicleWithin',
        component: () => import('@/views/Vehicles/VehicleWithin.vue'),
        meta: {
          requiresAuth: true,
          roles: ['gate_security', 'admin', 'manager'],
        },

      },

  // Check-Ins
  {
    path: '/checkins',
    name: 'CheckIns',
    component: CheckInsPage,
    meta: { requiresAuth: true, roles: ['admin', 'manager', 'gate_security']  },
  },
  {
    path: '/checkins/new',
    name: 'CheckInCreate',
    component: CheckInFormPage,
    meta: { requiresAuth: true, roles: ['admin', 'manager', 'gate_security']  },
  },
  {
    path: '/checkins/:id/edit',
    name: 'CheckInEdit',
    component: CheckInFormPage,
    meta: { requiresAuth: true, roles: ['admin', 'manager', 'gate_security']  },
  },

  // Maintenance
  {
    path: '/maintenance',
    name: 'Maintenance',
    component: MaintenancePage,
    meta: { requiresAuth: true, roles: ['admin', 'manager', 'vehicle_owner', 'driver']  },
  },
  {
    path: '/maintenance/new',
    name: 'MaintenanceNew',
    component: MaintenanceFormPage,
    meta: { requiresAuth: true, roles: ['admin', 'manager', 'driver', 'vehicle_owner']  },
  },
  {
    path: '/maintenance/:id/edit',
    name: 'MaintenanceEdit',
    component: MaintenanceFormPage,
    props: true,
    meta: { requiresAuth: true, roles: ['admin', 'manager', 'driver']  },
  },
// Expense 
 {
  path: '/expenses',
  name: 'Expenses',
  component: ExpensesPage,
  meta: { requiresAuth: true, roles: ['admin', 'manager', 'vehicle_owner', 'driver'] },
},
{
  path: '/expenses/new',
  name: 'ExpenseCreate',
  component: ExpenseFormPage,
  meta: { requiresAuth: true, roles: ['admin', 'manager', 'driver']  },
},
{
  path: '/expenses/:id/edit',
  name: 'ExpenseEdit',
  component: ExpenseFormPage,
  props: true,
  meta: { requiresAuth: true, roles: ['admin', 'manager', 'driver']  },
},

// User route

{
  path: '/users',
  component: () => import('@/views/Users/UsersPage.vue'),
  meta: { requiresAuth: true, roles: ['admin', 'manager', 'gate_security'] }
},
{
  path: '/users/new',
  component: () => import('@/views/Users/UserFormPage.vue'),
  meta: { requiresAuth: true, roles: ['admin', 'manager', 'gate_security'] }
},
{
  path: '/users/:id/edit',
  component: () => import('@/views/Users/UserFormPage.vue'),
  meta: { requiresAuth: true, roles: ['admin', 'manager', 'gate_security'] }
},


// Trips
{
  path: '/trips',
  name: 'Trips',
  component: TripsPage,
  meta: { requiresAuth: true, roles: ['admin', 'manager', 'vehicle_owner', 'driver', 'gate_security']  }
},
{
  path: '/trips/create',
  name: 'TripCreate',
  component: TripFormPage,
  meta: { requiresAuth: true, roles: ['admin', 'manager', 'driver']  }
},
{
  path: '/trips/:id/edit',
  name: 'TripEdit',
  component: TripFormPage,
  props: true,
  meta: { requiresAuth: true, roles: ['admin', 'manager', 'driver']  }
},

// recent activity
{
    path: '/recent-activity',
    name: 'RecentActivity',
    component: RecentActivityPage,
    meta: { requiresAuth: true, roles: ['admin', 'manager']  }
  },

//not authorized
{
    path: '/not-authorized',
    name: 'NotAuthorized',
    component: NotAuthorized
  },
  // Fallback 404
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: NotFound,
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to, from, next) => {
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
