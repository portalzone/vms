import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

// Base pages
import Home from '../views/Home.vue'
import Login from '../views/Login.vue'

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

// Check-in views
const CheckInsPage = () => import('../views/CheckIns/CheckInsPage.vue')
const CheckInFormPage = () => import('../views/CheckIns/CheckInFormPage.vue')

// Maintenance views
const MaintenancePage = () => import('../views/Maintenances/MaintenancePage.vue')
const MaintenanceFormPage = () => import('../views/Maintenances/MaintenanceFormPage.vue')

// Expense views
const ExpensesPage = () => import('../views/Expenses/ExpensesPage.vue')
const ExpenseFormPage = () => import('../views/Expenses/ExpenseFormPage.vue')


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
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { requiresAuth: true },
  },

  // Drivers
  {
    path: '/drivers',
    name: 'Drivers',
    component: DriversPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/drivers/new',
    name: 'DriverCreate',
    component: DriverFormPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/drivers/:id/edit',
    name: 'DriverEdit',
    component: DriverFormPage,
    meta: { requiresAuth: true },
  },

  // Vehicles
  {
    path: '/vehicles',
    name: 'Vehicles',
    component: VehiclesPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/vehicles/new',
    name: 'VehicleCreate',
    component: VehicleFormPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/vehicles/:id/edit',
    name: 'VehicleEdit',
    component: VehicleFormPage,
    meta: { requiresAuth: true },
  },

  // Check-Ins
  {
    path: '/checkins',
    name: 'CheckIns',
    component: CheckInsPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/checkins/new',
    name: 'CheckInCreate',
    component: CheckInFormPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/checkins/:id/edit',
    name: 'CheckInEdit',
    component: CheckInFormPage,
    meta: { requiresAuth: true },
  },

  // Maintenance
  {
    path: '/maintenance',
    name: 'Maintenance',
    component: MaintenancePage,
    meta: { requiresAuth: true },
  },
  {
    path: '/maintenance/new',
    name: 'MaintenanceNew',
    component: MaintenanceFormPage,
    meta: { requiresAuth: true },
  },
  {
    path: '/maintenance/:id/edit',
    name: 'MaintenanceEdit',
    component: MaintenanceFormPage,
    props: true,
    meta: { requiresAuth: true },
  },
// Expense 
 {
  path: '/expenses',
  name: 'Expenses',
  component: ExpensesPage,
  meta: { requiresAuth: true },
},
{
  path: '/expenses/new',
  name: 'ExpenseCreate',
  component: ExpenseFormPage,
  meta: { requiresAuth: true },
},
{
  path: '/expenses/:id/edit',
  name: 'ExpenseEdit',
  component: ExpenseFormPage,
  props: true,
  meta: { requiresAuth: true },
},

// User route

{
  path: '/users',
  component: () => import('@/views/Users/UsersPage.vue')
},
{
  path: '/users/new',
  component: () => import('@/views/Users/UserFormPage.vue')
},
{
  path: '/users/:id/edit',
  component: () => import('@/views/Users/UserFormPage.vue')
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
    }
  }

  if (to.meta.requiresAuth && !auth.user) {
    return next('/login')
  }

  if (to.meta.guestOnly && auth.user) {
    return next('/dashboard')
  }

  return next()
})

export default router
