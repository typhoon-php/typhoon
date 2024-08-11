# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.4.4]

### Added

- Add `IdentityTypeResolver` — a type resolver that resolves to the passed type.
- Add `TypeResolvers` — a composite for type resolvers.

## [0.4.3] 2024-08-06

### Added

- Add `types::value()` factory that creates a `Type` from an arbitrary value.

### Deprecated

- Deprecate `types::scalar()` in favor of `value()`.

## [0.4.2] 2024-08-05

### Changed

- Drop needless `$type` parameter PHPDoc types in `TypeVisitor`.
- Return `Type<int>` in `types::intMask()` due to possibly overflowing bitmasks.

## [0.4.1] 2024-08-05

### Fixed

- Replace self, parent and static type arguments in RecursiveTypeReplacer.
