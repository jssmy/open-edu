# Sistema de diseño

Los tokens viven en [`tokens.ts`](./tokens.ts) y alimentan Tailwind (`tailwind.config.ts`). Ninguna vista
puede usar colores o valores de estilo fijos: ESLint lo impide fuera de este directorio.

| Componente                    | Variantes / estados                                                   | Accesibilidad                                        |
| ----------------------------- | --------------------------------------------------------------------- | ---------------------------------------------------- |
| `Button`                      | `primary`, `secondary`, `danger`; `loading`, `disabled`               | `aria-busy` al cargar; foco visible                  |
| `Input`, `Textarea`, `Select` | normal, `error`, `disabled`                                           | `aria-invalid` y `aria-describedby` hacia el mensaje |
| `Checkbox`                    | marcado, `disabled`                                                   | `label` asociado                                     |
| `FormField`                   | con/sin error                                                         | `label for`; error con `role="alert"`                |
| `Alert`                       | `info`, `success`, `warning`, `danger`                                | `role="alert"` (danger) o `status`                   |
| `Spinner`                     | —                                                                     | `role="status"` y `aria-label`                       |
| `EmptyState`                  | con/sin descripción y acción (slot)                                   | Texto visible                                        |
| `Modal`                       | abierto/cerrado; slot de acciones                                     | `role="dialog"`, `aria-modal`, cierra con Esc        |
| `Toast`                       | `success`, `danger`                                                   | `role="status"`                                      |
| `Table`                       | cargando, vacío (slot `empty`), celdas personalizables (`cell-<key>`) | `th scope="col"`                                     |
| `Pagination`                  | primera/última página deshabilitadas                                  | `nav` con `aria-label`                               |
| `Badge`                       | `neutral`, `success`, `warning`, `danger`                             | No depende solo del color: lleva texto               |
| `Card`                        | —                                                                     | `section`                                            |

Toda pantalla debe contemplar los estados de carga, vacío y error usando estos componentes.
