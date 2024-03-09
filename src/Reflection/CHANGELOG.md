# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 0.3.1

- Implemented `getType()`, `getReturnType()`, `getTentativeReturnType()`, `hasTentativeReturnType()`, `hasReturnType()`.
- Deprecated `FileResource::changeDetector()`.
- Introduced new parameter `TyphoonReflector::build($fallbackToNativeReflection = true)`.
- Deprecated `NativeReflectionFileLocator`.
- Deprecated `NativeReflectionLocator`.
- Deprecated returning `ReflectionClass` from `ClassLocator::locateClass()`.
