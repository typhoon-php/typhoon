# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.4.2] 2024-08-05

### Added

- Add `AttributeReflection::evaluate()` to emphasize the risk of runtime errors.
- Add `AttributeReflection::evaluateArguments()` to emphasize the risk of runtime errors.
- Add `ClassConstantReflection::evaluate()` to emphasize the risk of runtime errors.
- Add `ParameterReflection::evaluateDefault()` to emphasize the risk of runtime errors.
- Add `PropertyReflection::evaluateDefault()` to emphasize the risk of runtime errors.

### Deprecated

- Deprecate calling `AttributeReflection::newInstance()` in favor of `evaluate()`.
- Deprecate calling `AttributeReflection::arguments()` in favor of `evaluateArguments()`.
- Deprecate calling `ClassConstantReflection::value()` in favor of `evaluate()`.
- Deprecate calling `ParameterReflection::defaultValue()` in favor of `evaluateDefault()`.
- Deprecate calling `PropertyReflection::defaultValue()` in favor of `evaluateDefault()`.

## [0.4.1] 2024-08-05

### Fixed

- Reflect trait @use PHPDoc in classes without a class-level PHPDoc.
