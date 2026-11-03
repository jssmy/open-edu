# Instrucciones del workspace

## Commits por tarea

- Al completar una tarea de implementación, crea un commit independiente antes de informar que terminó, siempre que las validaciones relevantes pasen.
- No crees commits para consultas, explicaciones, revisiones o planes sin cambios de implementación, ni cuando el usuario pida explícitamente que no hagas commit.
- Incluye únicamente los cambios de la tarea actual. No uses `git add -A` ni incluyas cambios previos o ajenos del usuario; revisa el staging y el diff antes de confirmar.
- Si no puedes separar con seguridad los cambios de esta tarea de otros cambios existentes, no hagas commit y pide instrucciones.
- Si las validaciones fallan, no confirmes los cambios como terminados; informa el fallo y qué falta.
- Usa un mensaje breve que describa el cambio. No hagas push salvo que el usuario lo solicite.
- Añade al mensaje el tráiler `Co-authored-by: Copilot <223556219+Copilot@users.noreply.github.com>`, salvo que el usuario pida omitirlo.
