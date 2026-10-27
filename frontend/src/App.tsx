import { Navigate, Route, Routes } from 'react-router-dom'
import { AuthGate } from '@/components/AuthGate'
import { AppShell } from '@/components/AppShell'
import { PermissionGate, RoleGate } from '@/components/AuthGate'
import { LoginPage, ForgotPasswordPage, ResetPasswordPage } from '@/pages/AuthPages'
import { DashboardPage } from '@/pages/DashboardPage'
import { EntityPage } from '@/pages/EntityPage'
import { ProfilePage } from '@/pages/ProfilePage'
import { UsersPage } from '@/pages/UsersPage'
import { ReportsPage } from '@/pages/ReportsPage'
import { PublicIntakePage } from '@/pages/PublicIntakePage'
import { ClientPortalForgotPage, ClientPortalGate, ClientPortalLoginPage, ClientPortalRegisterPage, ClientPortalResetPage } from '@/pages/ClientPortalPages'
import { EmailSettingsPage } from '@/pages/EmailSettingsPage'
import { BrandingSettingsPage } from '@/pages/BrandingSettingsPage'

export default function App(){
  return <Routes>
    <Route path="/login" element={<LoginPage/>}/>
    <Route path="/forgot-password" element={<ForgotPasswordPage/>}/>
    <Route path="/reset-password" element={<ResetPasswordPage/>}/>
    <Route path="/apply" element={<ClientPortalGate><PublicIntakePage/></ClientPortalGate>}/>
    <Route path="/client/login" element={<ClientPortalLoginPage/>}/>
    <Route path="/client/register" element={<ClientPortalRegisterPage/>}/>
    <Route path="/client/forgot-password" element={<ClientPortalForgotPage/>}/>
    <Route path="/client/reset-password" element={<ClientPortalResetPage/>}/>
    <Route path="/client/dashboard" element={<ClientPortalGate/>}/>
    <Route element={<AuthGate/>}><Route element={<AppShell/>}>
      <Route index element={<PermissionGate permission="dashboard.view"><DashboardPage/></PermissionGate>}/>
      <Route path="reports" element={<PermissionGate permission="reports.view"><ReportsPage/></PermissionGate>}/>
      <Route path="clients" element={<PermissionGate permission="clients.view"><EntityPage name="clients"/></PermissionGate>}/>
      <Route path="leads" element={<PermissionGate permission="clients.view"><EntityPage name="leads"/></PermissionGate>}/>
      <Route path="applications" element={<PermissionGate permission="applications.view"><EntityPage name="applications"/></PermissionGate>}/>
      <Route path="documents" element={<PermissionGate permission="documents.view"><EntityPage name="documents"/></PermissionGate>}/>
      <Route path="follow-ups" element={<PermissionGate permission="followups.view"><EntityPage name="follow-ups"/></PermissionGate>}/>
      <Route path="payments" element={<PermissionGate permission="payments.view"><EntityPage name="payments"/></PermissionGate>}/>
      <Route path="services" element={<PermissionGate permission="services.manage"><EntityPage name="services"/></PermissionGate>}/>
      <Route path="users" element={<PermissionGate permission="users.manage"><UsersPage/></PermissionGate>}/>
      <Route path="profile" element={<ProfilePage/>}/>
      <Route path="settings/email" element={<RoleGate role="super-admin"><EmailSettingsPage/></RoleGate>}/>
      <Route path="settings/branding" element={<RoleGate role="super-admin"><BrandingSettingsPage/></RoleGate>}/>
    </Route></Route>
    <Route path="*" element={<Navigate to="/" replace/>}/>
  </Routes>
}
