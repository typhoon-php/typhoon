# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 0.4.0

- [BC break] Added required parameter `Typhoon\Reflection\Exception\MethodDoesNotExist::__construct(class-string $class)`.
- [BC break] Added required parameter `Typhoon\Reflection\Exception\PropertyDoesNotExist::__construct(class-string $class)`.
- [BC break] Added required parameter `Typhoon\Reflection\Exception\ParameterDoesNotExist::__construct(AtFunction|AtMethod $at)`.
- [BC break] Added required parameter `Typhoon\Reflection\Exception\TemplateDoesNotExist::__construct(AtClass|AtFunction|AtMethod $at)`.

## [Unreleased]

- Implemented `getType()`, `getReturnType()`, `getTentativeReturnType()`, `hasTentativeReturnType()`, `hasReturnType()`.
