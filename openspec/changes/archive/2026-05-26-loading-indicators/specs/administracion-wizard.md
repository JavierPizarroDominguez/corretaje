# Delta: Administracion Wizard Loading Indicator

## Modification to `administracion-wizard` — spinner on propiedad select.

## Requirement: Propiedad select spinner during loadPropiedadesPorArrendador

Select MUST show `spinner-border` (not plain text) while `GET /api/propiedades/por-arrendador/{id}` fetches. Disabled during loading.

#### Scenario: Spinner lifecycle

- GIVEN user selects arrendador, fetch begins
- THEN select shows spinner, disabled
- WHEN API returns properties, THEN spinner removed, options populated
- WHEN API returns empty, THEN spinner removed, "Sin propiedades" shown
- WHEN fetch fails, THEN spinner removed, error displayed
