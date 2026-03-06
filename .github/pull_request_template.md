# Pull Request Template

## Resumen

Describe brevemente qué cambia este PR y por qué.

## Tipo de cambio

- [ ] Feature
- [ ] Bugfix
- [ ] Refactor
- [ ] Documentación
- [ ] Seguridad
- [ ] Otro (indicar):

## Cambios realizados

- 
- 
- 

## Impacto

### Backend (`ctf/backend.php`)

- [ ] No afecta
- [ ] Afecta endpoints
- [ ] Afecta cookies/sesión
- [ ] Afecta puntuación / validación de flags

### Datos (`ctf/retos.csv`, `ctf/private/flags.json`)

- [ ] No afecta
- [ ] Cambia estructura CSV
- [ ] Cambia flags
- [ ] Cambia dificultad/puntos

### Retos / Frontend (`index.php`, `doc/template.php`)

- [ ] No afecta
- [ ] Cambia flujo de submit
- [ ] Cambia comunicación con backend

## Checklist técnica

- [ ] Probado flujo de cookie `usuario_b64`
- [ ] Probado `submit_flag` correcto/incorrecto
- [ ] Verificado que no se expone `private/`, `logs/` ni `retos.csv` por HTTP
- [ ] Actualizada documentación en `doc/` si aplica
- [ ] No se han subido flags reales por error

## Cómo probar

Incluye pasos exactos para reproducir y validar este PR.

1. 
2. 
3. 

## Riesgos y rollback

- Riesgos conocidos:
  - 
- Plan de rollback:
  - 

## Notas adicionales

Añade aquí cualquier contexto útil para review o despliegue.
