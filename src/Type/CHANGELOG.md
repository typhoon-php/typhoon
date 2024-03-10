# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 0.4.0

- Removed `TypeVisitor::value()` and `DefaultVisitor::value()`.
- Removed `TypeVisitor::classStringLiteral()` and `DefaultVisitor::classStringLiteral()`.
- Added `@no-named-arguments` phpDocs to `types::alias()`, `types::template()` and `types::object()`.

## 0.3.2

- Deprecated `TypeVisitor::classStringLiteral()` and `DefaultVisitor::classStringLiteral()`.

## 0.3.1

- Deprecated calling `types::alias()`, `types::template()` and `types::object()` with named arguments.
- Deprecated `TypeVisitor::value()` and `DefaultVisitor::value()`.
