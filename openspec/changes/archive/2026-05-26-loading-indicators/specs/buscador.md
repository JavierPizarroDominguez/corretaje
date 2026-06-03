# Delta: Buscador Loading Indicator

## Modification to `buscador` — spinner during autocomplete fetch.

## Requirement: Autocomplete MUST show spinner during fetch

Buscador dropdown MUST display `spinner-border` while search `fetch()` is in flight — shown before fetch, hidden on completion.

#### Scenario: Spinner during search, hidden on results or error

- GIVEN user types in buscador input
- WHEN fetch begins, THEN dropdown shows spinner
- WHEN fetch succeeds, THEN spinner removed, results rendered
- WHEN fetch fails, THEN spinner removed, error shown
