// Composable untuk mengakses shared data dari Inertia
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useAuth() {
  const page = usePage();

  return {
    user: computed(() => page.props.auth?.user),
    isAuthenticated: computed(() => !!page.props.auth?.user),
    isAdmin: computed(() => page.props.auth?.user?.role === 'admin'),
    isManager: computed(() => page.props.auth?.user?.role === 'manager'),
    isTechnician: computed(() => page.props.auth?.user?.role === 'technician'),
  };
}

export function useFlash() {
  const page = usePage();

  return {
    success: computed(() => page.props.flash?.success),
    error: computed(() => page.props.flash?.error),
    warning: computed(() => page.props.flash?.warning),
    errors: computed(() => page.props.errors || {}),
  };
}

export function useSharedProps() {
  const page = usePage();

  return {
    props: page.props,
    url: page.url,
    component: page.component,
  };
}
