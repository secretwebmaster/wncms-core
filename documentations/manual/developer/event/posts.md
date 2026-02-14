# Posts Events

## Frontend Posts

#### wncms.posts.show

Triggered when frontend single post is being shown.

Parameters:
- `$post` (Post)

#### wncms.frontend.posts.show.before

Triggered before frontend post show view is rendered.

Parameters:
- `&$themeView` (string)
- `&$params` (array)
- `&$defaultView` (string)
- `$post` (Post)

#### wncms.frontend.posts.show.after

Triggered after frontend post show response is prepared.

Parameters:
- `$post` (Post)
- `$response` (mixed)
- `$params` (array)
- `$themeView` (string)
- `$defaultView` (string)

## Backend Posts / Plugin Extension

#### wncms.view.backend.posts.edit.sidebar

View slot event for injecting cards/blocks into backend posts edit sidebar.

Parameters:
- `$post` (Post)
- `$request` (Request)

#### wncms.backend.posts.seo_analyze.before

Triggered before plugin/backend SEO analysis executes for posts payload.

Parameters:
- `&$payload` (array)
- `$request` (Request)

#### wncms.backend.posts.seo_analyze.after

Triggered after plugin/backend SEO analysis executes for posts payload.

Parameters:
- `&$result` (array)
- `$payload` (array)
- `$request` (Request)
