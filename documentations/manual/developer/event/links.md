# Links Events

## Backend Links CRUD + View Slots

| Hook | Parameters |
| --- | --- |
| `wncms.backend.links.index.query.before` | `$request` (`Request`), `&$q` (`Eloquent\Builder`) |
| `wncms.backend.links.create.resolve` | `&$view` (`string`), `&$params` (`array`) |
| `wncms.backend.links.edit.resolve` | `&$view` (`string`), `&$params` (`array`) |
| `wncms.backend.links.store.before` | `$request` (`Request`), `&$rules` (`array`), `&$messages` (`array`) |
| `wncms.backend.links.store.attributes.before` | `$request` (`Request`), `&$attributes` (`array`) |
| `wncms.backend.links.store.after` | `$link` (`Link`), `$request` (`Request`) |
| `wncms.backend.links.update.before` | `$link` (`Link`), `$request` (`Request`), `&$rules` (`array`), `&$messages` (`array`) |
| `wncms.backend.links.update.attributes.before` | `$link` (`Link`), `$request` (`Request`), `&$attributes` (`array`) |
| `wncms.backend.links.update.after` | `$link` (`Link`), `$request` (`Request`) |
| `wncms.view.backend.links.create.fields` | `$request` (`Request`) |
| `wncms.view.backend.links.edit.fields` | `$link` (`Link`), `$request` (`Request`) |
