// vite.config.js
import { defineConfig } from "file:///D:/ladanglima/tpm-app/node_modules/vite/dist/node/index.js";
import laravel from "file:///D:/ladanglima/tpm-app/node_modules/laravel-vite-plugin/dist/index.js";
import tailwindcss from "file:///D:/ladanglima/tpm-app/node_modules/@tailwindcss/vite/dist/index.mjs";
import vue from "file:///D:/ladanglima/tpm-app/node_modules/@vitejs/plugin-vue/dist/index.mjs";
import path from "path";
var __vite_injected_original_dirname = "D:\\ladanglima\\tpm-app";
var vite_config_default = defineConfig({
  plugins: [
    laravel({
      input: ["resources/css/app.css", "resources/js/app.js"],
      refresh: true
    }),
    tailwindcss(),
    vue()
  ],
  resolve: {
    alias: {
      "vue": "vue/dist/vue.esm-bundler.js",
      "@/lib": path.resolve(__vite_injected_original_dirname, "./resources/views/lib"),
      "@/components/ui": path.resolve(__vite_injected_original_dirname, "./resources/views/components/ui"),
      "@/composables": path.resolve(__vite_injected_original_dirname, "./resources/js/composables"),
      "@/components": path.resolve(__vite_injected_original_dirname, "./resources/js/components"),
      "@": path.resolve(__vite_injected_original_dirname, "./resources/js")
    }
  },
  build: {
    chunkSizeWarningLimit: 500,
    rollupOptions: {
      output: {
        manualChunks: {
          "vue-vendor": ["vue", "vue-router", "@inertiajs/vue3"],
          "ui-vendor": ["reka-ui", "@vueuse/core", "lucide-vue-next"],
          "table-vendor": ["@tanstack/vue-table"]
        }
      }
    }
  }
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCJEOlxcXFxsYWRhbmdsaW1hXFxcXHRwbS1hcHBcIjtjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfZmlsZW5hbWUgPSBcIkQ6XFxcXGxhZGFuZ2xpbWFcXFxcdHBtLWFwcFxcXFx2aXRlLmNvbmZpZy5qc1wiO2NvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9pbXBvcnRfbWV0YV91cmwgPSBcImZpbGU6Ly8vRDovbGFkYW5nbGltYS90cG0tYXBwL3ZpdGUuY29uZmlnLmpzXCI7aW1wb3J0IHsgZGVmaW5lQ29uZmlnIH0gZnJvbSAndml0ZSc7XG5pbXBvcnQgbGFyYXZlbCBmcm9tICdsYXJhdmVsLXZpdGUtcGx1Z2luJztcbmltcG9ydCB0YWlsd2luZGNzcyBmcm9tICdAdGFpbHdpbmRjc3Mvdml0ZSc7XG5pbXBvcnQgdnVlIGZyb20gJ0B2aXRlanMvcGx1Z2luLXZ1ZSc7XG5pbXBvcnQgcGF0aCBmcm9tICdwYXRoJztcblxuZXhwb3J0IGRlZmF1bHQgZGVmaW5lQ29uZmlnKHtcbiAgICBwbHVnaW5zOiBbXG4gICAgICAgIGxhcmF2ZWwoe1xuICAgICAgICAgICAgaW5wdXQ6IFsncmVzb3VyY2VzL2Nzcy9hcHAuY3NzJywgJ3Jlc291cmNlcy9qcy9hcHAuanMnXSxcbiAgICAgICAgICAgIHJlZnJlc2g6IHRydWUsXG4gICAgICAgIH0pLFxuICAgICAgICB0YWlsd2luZGNzcygpLFxuICAgICAgICB2dWUoKSxcbiAgICBdLFxuICAgIHJlc29sdmU6IHtcbiAgICAgICAgYWxpYXM6IHtcbiAgICAgICAgICAgICd2dWUnOiAndnVlL2Rpc3QvdnVlLmVzbS1idW5kbGVyLmpzJyxcbiAgICAgICAgICAgICdAL2xpYic6IHBhdGgucmVzb2x2ZShfX2Rpcm5hbWUsICcuL3Jlc291cmNlcy92aWV3cy9saWInKSxcbiAgICAgICAgICAgICdAL2NvbXBvbmVudHMvdWknOiBwYXRoLnJlc29sdmUoX19kaXJuYW1lLCAnLi9yZXNvdXJjZXMvdmlld3MvY29tcG9uZW50cy91aScpLFxuICAgICAgICAgICAgJ0AvY29tcG9zYWJsZXMnOiBwYXRoLnJlc29sdmUoX19kaXJuYW1lLCAnLi9yZXNvdXJjZXMvanMvY29tcG9zYWJsZXMnKSxcbiAgICAgICAgICAgICdAL2NvbXBvbmVudHMnOiBwYXRoLnJlc29sdmUoX19kaXJuYW1lLCAnLi9yZXNvdXJjZXMvanMvY29tcG9uZW50cycpLFxuICAgICAgICAgICAgJ0AnOiBwYXRoLnJlc29sdmUoX19kaXJuYW1lLCAnLi9yZXNvdXJjZXMvanMnKSxcbiAgICAgICAgfSxcbiAgICB9LFxuICAgIGJ1aWxkOiB7XG4gICAgICAgIGNodW5rU2l6ZVdhcm5pbmdMaW1pdDogNTAwLFxuICAgICAgICByb2xsdXBPcHRpb25zOiB7XG4gICAgICAgICAgICBvdXRwdXQ6IHtcbiAgICAgICAgICAgICAgICBtYW51YWxDaHVua3M6IHtcbiAgICAgICAgICAgICAgICAgICAgJ3Z1ZS12ZW5kb3InOiBbJ3Z1ZScsICd2dWUtcm91dGVyJywgJ0BpbmVydGlhanMvdnVlMyddLFxuICAgICAgICAgICAgICAgICAgICAndWktdmVuZG9yJzogWydyZWthLXVpJywgJ0B2dWV1c2UvY29yZScsICdsdWNpZGUtdnVlLW5leHQnXSxcbiAgICAgICAgICAgICAgICAgICAgJ3RhYmxlLXZlbmRvcic6IFsnQHRhbnN0YWNrL3Z1ZS10YWJsZSddLFxuICAgICAgICAgICAgICAgIH0sXG4gICAgICAgICAgICB9LFxuICAgICAgICB9LFxuICAgIH0sXG59KTtcbiJdLAogICJtYXBwaW5ncyI6ICI7QUFBdVAsU0FBUyxvQkFBb0I7QUFDcFIsT0FBTyxhQUFhO0FBQ3BCLE9BQU8saUJBQWlCO0FBQ3hCLE9BQU8sU0FBUztBQUNoQixPQUFPLFVBQVU7QUFKakIsSUFBTSxtQ0FBbUM7QUFNekMsSUFBTyxzQkFBUSxhQUFhO0FBQUEsRUFDeEIsU0FBUztBQUFBLElBQ0wsUUFBUTtBQUFBLE1BQ0osT0FBTyxDQUFDLHlCQUF5QixxQkFBcUI7QUFBQSxNQUN0RCxTQUFTO0FBQUEsSUFDYixDQUFDO0FBQUEsSUFDRCxZQUFZO0FBQUEsSUFDWixJQUFJO0FBQUEsRUFDUjtBQUFBLEVBQ0EsU0FBUztBQUFBLElBQ0wsT0FBTztBQUFBLE1BQ0gsT0FBTztBQUFBLE1BQ1AsU0FBUyxLQUFLLFFBQVEsa0NBQVcsdUJBQXVCO0FBQUEsTUFDeEQsbUJBQW1CLEtBQUssUUFBUSxrQ0FBVyxpQ0FBaUM7QUFBQSxNQUM1RSxpQkFBaUIsS0FBSyxRQUFRLGtDQUFXLDRCQUE0QjtBQUFBLE1BQ3JFLGdCQUFnQixLQUFLLFFBQVEsa0NBQVcsMkJBQTJCO0FBQUEsTUFDbkUsS0FBSyxLQUFLLFFBQVEsa0NBQVcsZ0JBQWdCO0FBQUEsSUFDakQ7QUFBQSxFQUNKO0FBQUEsRUFDQSxPQUFPO0FBQUEsSUFDSCx1QkFBdUI7QUFBQSxJQUN2QixlQUFlO0FBQUEsTUFDWCxRQUFRO0FBQUEsUUFDSixjQUFjO0FBQUEsVUFDVixjQUFjLENBQUMsT0FBTyxjQUFjLGlCQUFpQjtBQUFBLFVBQ3JELGFBQWEsQ0FBQyxXQUFXLGdCQUFnQixpQkFBaUI7QUFBQSxVQUMxRCxnQkFBZ0IsQ0FBQyxxQkFBcUI7QUFBQSxRQUMxQztBQUFBLE1BQ0o7QUFBQSxJQUNKO0FBQUEsRUFDSjtBQUNKLENBQUM7IiwKICAibmFtZXMiOiBbXQp9Cg==
