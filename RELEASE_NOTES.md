### Added
- Adds a `PreserveObjectCache` attribute to prevent the object cache from being
  cleared during unit test HTTP requests.
- Added `Database_Table_Model` class to allow for creating models that
  represent database tables without a post type or taxonomy.
- Added `assertDatabaseHas()` and `assertDatabaseDoesNotHave()` methods to the
  to allow for asserting that a database table has or does not have a specific
  row.
- Added `Mantle\Support\Uri` class and `Mantle\Support\Helpers\uri()` helper to
  create and manipulate URIs. Supports manipulating the current query or
  modifying an arbitrary URI.
### Changed
- Refactored the routing registrar class to be more flexible. Ensure that REST
  API routes are treated the same as web routes.
- Adjusted the template wrapper middleware to use block templates instead of
  `get_header()`/`get_footer()` calls.
- Added callback support to the `assertContent()` method to allow for
  more complex assertions on the content of a response.
### Fixed
- The param for `Post_Type_Arguments::template()` has been fixed to match the argument shape from core.
