# Diagramas · Clasificador Bayesiano

Diagramas del clasificador que reemplazaría al `RandomForestService`.

> **Estado: propuesta de diseño.** El código actual sigue usando
> `App\Services\RandomForestService`. No existe `config/bayes.php` ni ninguna
> implementación de Naive Bayes en el repositorio. Estos diagramas describen un
> trabajo **futuro**, no el sistema tal como está. Si se implementa, habrá que
> crear `config/bayes.php` (`variance_floor`, `min_muestras_por_clase`, etc.).

| Archivo | Qué contiene | Cómo abrirlo |
| --- | --- | --- |
| `clasificador-bayesiano.puml` | Diagrama de **clases**: paquetes, responsables, métodos, fórmulas | PlantUML |
| `clasificador-bayesiano-secuencia.puml` | Diagrama de **secuencia**: entrenamiento e inferencia por partida | PlantUML |
| `clasificador-bayesiano.drawio` | Los dos anteriores editables, en dos pestañas | diagrams.net / draw.io Desktop |
| `*.png` | Render de referencia de cada PlantUML | cualquier visor |

## Cómo regenerar los PNG

Local (recomendado, sin subir nada a un servidor):

```bash
# Extensión "PlantUML" de VS Code:  Alt+D  con el .puml abierto
# o con la CLI de PlantUML instalada:
plantuml -tpng docs/diagramas/clasificador-bayesiano.puml
plantuml -tpng docs/diagramas/clasificador-bayesiano-secuencia.puml
```

## Las decisiones de diseño que fijan estos diagramas

1. **Variante**: *Gaussian Naive Bayes*. La telemetría es numérica continua
   (`latencia_promedio_ms`, `frecuencia_tapping`, `tiempo_total_ms`,
   `conteo_rectificaciones`, `score_final`), así que cada atributo se modela
   como `Normal(μ, σ²)` por clase. Multinomial y Bernoulli no aplican a un
   vector numérico.
2. **Relación con el Random Forest**: lo **reemplazaría**. Un solo modelo, una sola
   matriz de confusión, métricas que informan algo real.
3. **Entrenamiento real**: `μ`, `σ²` y `P(C)` se **aprenden** de
   `telemetria_gameplay`, no se escriben a mano. Eso es lo que hace que el
   modelo sea defendible ante el comité.

## Puntos que el diagrama deja explícitos

- **Privacidad por construcción**: la consulta de entrenamiento lee solo las 6
  columnas del modelo y **no incluye `user_id`**. El conjunto de entrenamiento
  no puede identificar a nadie aunque alguien lo inspeccione.
- **Estabilidad numérica**: el posterior se calcula con **log-sum-exp**, no
  exponenciando directamente. Sin eso, con 5 features aparecen underflows a
  0.0 y el modelo devolvería «ninguna clase» en silencio.
- **Piso de varianza**: `config('bayes.variance_floor')` evita la división por
  cero cuando `σ → 0`.
- **Priors con suavizado de Laplace**: `P(C) = (N_c + α) / (N + α·K)`, para que
  una clase con pocas muestras no tenga probabilidad cero.
- **Degradación segura**: una clase con menos de `min_muestras_por_clase`
  conserva los `μ/σ/prior` de `config/bayes.php` y se anota en
  `metadatos['clases_debilitadas']`. El panel lo muestra; no se degrada en
  silencio.
- **Reentrenar es barato**: una sola consulta agregada; la inferencia **no toca
  la base de datos**.
