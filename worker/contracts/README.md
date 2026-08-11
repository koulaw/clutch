# Contrat Laravel–Awpy v1

Les fichiers `input.schema.json`, `output.schema.json` et `error.schema.json` définissent le contrat JSON échangé entre Laravel et le worker. Une modification incompatible exige une nouvelle valeur majeure de `schema_version`. `parser_version` correspond à la version installée d’Awpy.

## Identifiants

- Les identifiants Steam (`steamid`) sont des SteamID64 non signés et doivent être transportés sans perte de précision. Dans du JSON destiné au navigateur, ils sont sérialisés comme chaînes.
- Les numéros de round (`round_num`) commencent à 1.
- Les ticks (`tick`) sont des entiers issus de la démo et identifient l’instant de référence des événements.
- Les noms de datasets d’événements deviennent des clés de `manifest.events` et ne contiennent que lettres, chiffres, `_` ou `-`.

## Temps et unités

- `tick` est exprimé en ticks de la démo.
- Les durées suffixées `_seconds` sont exprimées en secondes décimales.
- Les timestamps absolus suffixés `_at` suivent ISO 8601 en UTC.
- Les distances et positions `x`, `y`, `z` sont exprimées en unités monde Source 2, avant transformation vers le radar.
- Les angles (`yaw`, `pitch`) sont exprimés en degrés.
- Les dégâts et points de vie sont des entiers sans unité.

## Coordonnées

Awpy fournit un repère monde droitier Source 2. Le worker conserve les coordonnées brutes dans les Parquet. La transformation monde-vers-radar dépend de la carte et de sa version ; elle n’est jamais appliquée silencieusement dans ce contrat.

## Sorties et erreurs

Une exécution écrit exactement un objet JSON sur stdout. `ok=true` référence les artefacts produits dans `manifest`. Les données analytiques complètes restent en Parquet. `manifest.replays` référence un JSON gzip privé par round, échantillonné à 16 images par seconde et versionné indépendamment. `ok=false` fournit une erreur normalisée ; `retryable` indique si l’orchestrateur peut planifier une nouvelle tentative. Les chemins du manifeste sont relatifs à `output_directory` et utilisent `/` comme séparateur.
