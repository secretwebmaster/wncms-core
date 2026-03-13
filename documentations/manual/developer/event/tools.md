# Tools Events

## Backend Tools

#### wncms.backend.tools.index.resolve

Triggered before the backend tools index view is rendered.

Parameters:
- `&$view` (string)
- `&$params` (array)
- `$request` (Request)

#### wncms.view.backend.tools.index.cards

View slot event for injecting tool cards into the backend tools index grid.

Parameters:
- `$request` (Request)

Listener return:
- HTML string containing one or more tool grid columns
- each injected card should include its own `.col-12.col-md-6.col-lg-3.d-flex` wrapper
