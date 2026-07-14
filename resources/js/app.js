import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { RouterView } from 'vue-router';
import { router } from './router';
import App from './App.vue';

// Cek apakah element app dirender oleh Inertia dengan data-page yang valid
const el = document.getElementById('app');
let isInertia = false;

if (el && el.hasAttribute('data-page')) {
  try {
    const pageData = JSON.parse(el.dataset.page);
    if (pageData && pageData.component) {
      isInertia = true;
    }
  } catch (e) {
    // Fail silently, fall back to Old SPA
  }
}

if (isInertia) {
  // Ambil data-page hasil parsing untuk diberikan langsung ke Inertia
  const pageData = JSON.parse(el.dataset.page);

  // --- BOOT INERTIA APP ---
  const pages = import.meta.glob(['./Pages/**/*.vue', './views/**/*.vue'], { eager: true });

  createInertiaApp({
    page: pageData, // Berikan page object langsung agar kompatibel dengan berbagai versi backend
    resolve: (name) => {
      let pagePath = `./Pages/${name}.vue`;
      let pageModule = pages[pagePath];

      if (!pageModule) {
        pagePath = `./views/${name}.vue`;
        pageModule = pages[pagePath];
      }

      if (!pageModule) {
        throw new Error(`Page not found: ${name}`);
      }

      const page = pageModule.default || pageModule;

      // Gunakan App.vue sebagai layout default untuk semua halaman Inertia kecuali Login
      if (name !== 'Login') {
        page.layout = page.layout || App;
      }

      return page;
    },

    setup({ el, App: InertiaApp, props, plugin }) {
      const app = createApp({ render: () => h(InertiaApp, props) });

      app.use(plugin);
      app.mount(el);

      return app;
    },

    progress: {
      color: '#4B5563',
      includeCSS: true,
      showSpinner: true,
    },
  });
} else if (el) {
  // --- BOOT OLD VUE ROUTER SPA ---
  // Render RouterView sebagai default slot di dalam App.vue agar layout sidebar tetap muncul dan konten halaman ter-render
  const app = createApp({
    render: () => h(App, null, { default: () => h(RouterView) })
  });
  app.use(router);
  app.mount('#app');
}
