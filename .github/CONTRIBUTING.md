# Contributing to artwork

Thank you for your interest in contributing! artwork is open-source operations software for theatres, festivals and other cultural venues, and we welcome bug reports, feature requests and pull requests.

## Reporting bugs and requesting features

- Search the [existing issues](https://github.com/artwork-software/artwork/issues) first to avoid duplicates.
- Use the issue templates for bug reports and feature requests.
- Issues can be written in English or German.
- **Security vulnerabilities:** please do not open a public issue — see our [security policy](SECURITY.md) instead.

## Branch structure

| Branch | Purpose |
|--------|---------|
| `main` | Stable/production branch |
| `staging` | Pre-release testing (Beta) |
| `dev` | Development and feature integration |

Please target **`dev`** with your pull requests.

## Development setup

Follow the installation instructions in the [README](../README.md). For local development the repository also ships a [DDEV](https://ddev.readthedocs.io/) configuration (`.ddev/`), which is the quickest way to get a running instance:

```bash
ddev start
```

## Code style and quality

- PHP code style is defined in `phpcs.xml` — check your changes with:
  ```bash
  vendor/bin/phpcs
  ```
- Static analysis is configured in `phpstan.neon.dist`:
  ```bash
  vendor/bin/phpstan analyse
  ```
- Match the style of the surrounding code, in PHP as well as in the Vue frontend.

## Tests

Run the backend test suite before opening a pull request:

```bash
php artisan test
```

If you fix a bug, please add a test that would have caught it. If you add a feature, add tests covering its core behaviour.

## Pull requests

- Keep pull requests focused — one topic per PR.
- Describe **what** the change does and **why**; link related issues.
- Make sure tests, code style checks and static analysis pass.
- New database changes need a migration; remember that `php artisan artwork:update` is run on every deployment.

## License

artwork is licensed under the [AGPL-3.0](../LICENSE.md). By contributing, you agree that your contributions will be licensed under the same license.
