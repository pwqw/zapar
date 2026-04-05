---
name: find-untranslated-text
description: Encuentra texto hardcodeado en inglés en Vue templates y composables que debería usar $t() de vue-i18n. Usar cuando el usuario quiere auditar o completar traducciones i18n, encontrar texto sin traducir, agregar claves a en.json/es.json, o cuando menciona: i18n, traducción, texto en inglés, $t(), locales.
---

# Encontrar Texto Sin Traducir (i18n Audit)

Este proyecto usa **vue-i18n** (Composition API). Locales en `resources/assets/js/locales/en.json` y `es.json`.

---

## Ejecutar el escaneo completo

Ejecuta el script con el Shell tool y muestra su salida stdout **directamente en tu respuesta** como texto plano. No guardes la salida en ningún archivo.

```bash
bash .claude/skills/find-untranslated-text/scripts/scan.sh
```

Produce un **árbol ASCII** con todos los archivos y la línea exacta de cada texto sin traducir. Escanea la totalidad del codebase, no solo cambios de git.

**Ejemplo de salida:**
```
Textos sin traducir (i18n audit)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
├── resources/assets/js/components/screens/
│   ├── FavoritesScreen.vue  (3 hits)
│   │     L5   Your Favorites
│   │     L22  Download All
│   │     L57  No favorites yet.
│   └── RandomSongs.vue  (1 hit)
│         L4   Something Random
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
91 archivo(s) · 198 texto(s) sin traducir
```

---

## Qué detecta el script

| Patrón | Ejemplo |
|--------|---------|
| Texto plano en template | `Recently Played` (línea sola) |
| Texto inline `>Texto</tag>` | `<h1>Edit User</h1>` |
| Atributos visibles sin binding | `placeholder="Album name"` |
| `toastSuccess/Warning/Error` con string literal | `toastSuccess('Done!')` |

---

## Flujo para traducir un hit

1. Elige la clave siguiendo la convención `seccion.nombreCamelCase`, ej: `home.recentlyPlayed`.
2. Agrega a `en.json` (texto original) y a `es.json` (traducción).
3. Reemplaza en el `.vue`:
   - Texto plano: `{{ $t('home.recentlyPlayed') }}`
   - Atributo: `:placeholder="$t('forms.albumName')"`
   - Script/composable: `import { useI18n } from 'vue-i18n'` → `const { t } = useI18n()` → `t('...')`

---

## Notas

- `$t()` está disponible globalmente en templates Vue 3 con `<script setup>`; no importar `useI18n`.
- En composables `.ts` sí importar `useI18n`.
- Los archivos `*.spec.ts` con strings literales en toasts pueden ignorarse (son tests).
