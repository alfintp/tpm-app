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
  }
});
export {
  vite_config_default as default
};
//# sourceMappingURL=data:application/json;base64,ewogICJ2ZXJzaW9uIjogMywKICAic291cmNlcyI6IFsidml0ZS5jb25maWcuanMiXSwKICAic291cmNlc0NvbnRlbnQiOiBbImNvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9kaXJuYW1lID0gXCJEOlxcXFxsYWRhbmdsaW1hXFxcXHRwbS1hcHBcIjtjb25zdCBfX3ZpdGVfaW5qZWN0ZWRfb3JpZ2luYWxfZmlsZW5hbWUgPSBcIkQ6XFxcXGxhZGFuZ2xpbWFcXFxcdHBtLWFwcFxcXFx2aXRlLmNvbmZpZy5qc1wiO2NvbnN0IF9fdml0ZV9pbmplY3RlZF9vcmlnaW5hbF9pbXBvcnRfbWV0YV91cmwgPSBcImZpbGU6Ly8vRDovbGFkYW5nbGltYS90cG0tYXBwL3ZpdGUuY29uZmlnLmpzXCI7aW1wb3J0IHsgZGVmaW5lQ29uZmlnIH0gZnJvbSAndml0ZSc7XG5pbXBvcnQgbGFyYXZlbCBmcm9tICdsYXJhdmVsLXZpdGUtcGx1Z2luJztcbmltcG9ydCB0YWlsd2luZGNzcyBmcm9tICdAdGFpbHdpbmRjc3Mvdml0ZSc7XG5pbXBvcnQgdnVlIGZyb20gJ0B2aXRlanMvcGx1Z2luLXZ1ZSc7XG5pbXBvcnQgcGF0aCBmcm9tICdwYXRoJztcblxuZXhwb3J0IGRlZmF1bHQgZGVmaW5lQ29uZmlnKHtcbiAgICBwbHVnaW5zOiBbXG4gICAgICAgIGxhcmF2ZWwoe1xuICAgICAgICAgICAgaW5wdXQ6IFsncmVzb3VyY2VzL2Nzcy9hcHAuY3NzJywgJ3Jlc291cmNlcy9qcy9hcHAuanMnXSxcbiAgICAgICAgICAgIHJlZnJlc2g6IHRydWUsXG4gICAgICAgIH0pLFxuICAgICAgICB0YWlsd2luZGNzcygpLFxuICAgICAgICB2dWUoKSxcbiAgICBdLFxuICAgIHJlc29sdmU6IHtcbiAgICAgICAgYWxpYXM6IHtcbiAgICAgICAgICAgICd2dWUnOiAndnVlL2Rpc3QvdnVlLmVzbS1idW5kbGVyLmpzJyxcbiAgICAgICAgICAgICdAL2xpYic6IHBhdGgucmVzb2x2ZShfX2Rpcm5hbWUsICcuL3Jlc291cmNlcy92aWV3cy9saWInKSxcbiAgICAgICAgICAgICdAL2NvbXBvbmVudHMvdWknOiBwYXRoLnJlc29sdmUoX19kaXJuYW1lLCAnLi9yZXNvdXJjZXMvdmlld3MvY29tcG9uZW50cy91aScpLFxuICAgICAgICAgICAgJ0AvY29tcG9zYWJsZXMnOiBwYXRoLnJlc29sdmUoX19kaXJuYW1lLCAnLi9yZXNvdXJjZXMvanMvY29tcG9zYWJsZXMnKSxcbiAgICAgICAgICAgICdAL2NvbXBvbmVudHMnOiBwYXRoLnJlc29sdmUoX19kaXJuYW1lLCAnLi9yZXNvdXJjZXMvanMvY29tcG9uZW50cycpLFxuICAgICAgICAgICAgJ0AnOiBwYXRoLnJlc29sdmUoX19kaXJuYW1lLCAnLi9yZXNvdXJjZXMvanMnKSxcbiAgICAgICAgfSxcbiAgICB9LFxufSk7XG4iXSwKICAibWFwcGluZ3MiOiAiO0FBQXVQLFNBQVMsb0JBQW9CO0FBQ3BSLE9BQU8sYUFBYTtBQUNwQixPQUFPLGlCQUFpQjtBQUN4QixPQUFPLFNBQVM7QUFDaEIsT0FBTyxVQUFVO0FBSmpCLElBQU0sbUNBQW1DO0FBTXpDLElBQU8sc0JBQVEsYUFBYTtBQUFBLEVBQ3hCLFNBQVM7QUFBQSxJQUNMLFFBQVE7QUFBQSxNQUNKLE9BQU8sQ0FBQyx5QkFBeUIscUJBQXFCO0FBQUEsTUFDdEQsU0FBUztBQUFBLElBQ2IsQ0FBQztBQUFBLElBQ0QsWUFBWTtBQUFBLElBQ1osSUFBSTtBQUFBLEVBQ1I7QUFBQSxFQUNBLFNBQVM7QUFBQSxJQUNMLE9BQU87QUFBQSxNQUNILE9BQU87QUFBQSxNQUNQLFNBQVMsS0FBSyxRQUFRLGtDQUFXLHVCQUF1QjtBQUFBLE1BQ3hELG1CQUFtQixLQUFLLFFBQVEsa0NBQVcsaUNBQWlDO0FBQUEsTUFDNUUsaUJBQWlCLEtBQUssUUFBUSxrQ0FBVyw0QkFBNEI7QUFBQSxNQUNyRSxnQkFBZ0IsS0FBSyxRQUFRLGtDQUFXLDJCQUEyQjtBQUFBLE1BQ25FLEtBQUssS0FBSyxRQUFRLGtDQUFXLGdCQUFnQjtBQUFBLElBQ2pEO0FBQUEsRUFDSjtBQUNKLENBQUM7IiwKICAibmFtZXMiOiBbXQp9Cg==
