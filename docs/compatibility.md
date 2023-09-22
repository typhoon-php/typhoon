# Compatibility with native PHP Reflection

## ClassReflection

| ReflectionClass                   | ClassReflection                                                  |
|-----------------------------------|------------------------------------------------------------------|
| `IS_EXPLICIT_ABSTRACT`            | ✅                                                                |
| `IS_FINAL`                        | ✅                                                                |
| `IS_IMPLICIT_ABSTRACT`            | ✅                                                                |
| `IS_READONLY`                     | ✅                                                                |
| `$name`                           | ✅                                                                |
| `__clone()`                       | ✅ Cloning is forbidden                                           |
| `__construct()`                   | ❌ `@internal`                                                    |
| `__toString()`                    | ❌ Not going to implement                                         |
| `getAttributes()`                 | ❌ TODO                                                           |
| `getConstant()`                   | ❌ TODO                                                           |
| `getConstants()`                  | ❌ TODO                                                           |
| `getConstructor()`                | ✅                                                                |
| `getDefaultProperties()`          | ❌ Not going to implement                                         |
| `getDocComment()`                 | ✅️ Returns `?non-empty-string`                                   |
| `getEndLine()`                    | ✅ Returns `?positive-int`                                        |
| `getExtension()`                  | ❌ Not going to implement                                         |
| `getExtensionName()`              | ✅                                                                |
| `getFileName()`                   | ✅ Returns `?non-empty-string`                                    |
| `getInterfaceNames()`             | ✅                                                                |
| `getInterfaces()`                 | ✅                                                                |
| `getMethod()`                     | ✅                                                                |
| `getMethods()`                    | ✅                                                                |
| `getModifiers()`                  | ✅                                                                |
| `getName()`                       | ✅                                                                |
| `getNamespaceName()`              | ✅                                                                |
| `getParentClass()`                | ✅ Returns `?ClassReflection`                                     |
| `getProperties()`                 | ✅                                                                |
| `getProperty()`                   | ✅                                                                |
| `getReflectionConstant()`         | ❌ TODO                                                           |
| `getReflectionConstants()`        | ❌ TODO                                                           |
| `getShortName()`                  | ✅                                                                |
| `getStartLine()`                  | ✅️ Returns `?positive-int`                                       |
| `getStaticProperties()`           | ❌ Not going to implement: use `ClassReflection::getProperties()` |
| `getStaticPropertyValue()`        | ❌ Not going to implement: use `PropertyReflection::setValue()`   |
| `getTraitAliases()`               | ❌ TODO                                                           |
| `getTraitNames()`                 | ❌ TODO                                                           |
| `getTraits()`                     | ❌ TODO                                                           |
| `hasConstant()`                   | ❌ TODO                                                           |
| `hasMethod()`                     | ✅                                                                |
| `hasProperty()`                   | ✅                                                                |
| `implementsInterface()`           | ✅                                                                |
| `inNamespace()`                   | ✅                                                                |
| `isAbstract()`                    | ✅                                                                |
| `isAnonymous()`                   | ✅                                                                |
| `isCloneable()`                   | ✅                                                                |
| `isEnum()`                        | ✅                                                                |
| `isFinal()`                       | ✅                                                                |
| `isInstance()`                    | ✅                                                                |
| `isInstantiable()`                | ✅                                                                |
| `isInterface()`                   | ✅                                                                |
| `isInternal()`                    | ✅                                                                |
| `isIterable()`                    | ✅                                                                |
| `isIterateable()`                 | ❌ Not going to implement: use `ClassReflection::isIterable()`    |
| `isReadOnly()`                    | ✅                                                                |
| `isSubclassOf()`                  | ✅                                                                |
| `isTrait()`                       | ✅                                                                |
| `isUserDefined()`                 | ✅                                                                |
| `newInstance()`                   | ✅ Via native reflection                                          |
| `newInstanceArgs()`               | ✅ Via native reflection                                          |
| `newInstanceWithoutConstructor()` | ✅ Via native reflection                                          |
| `setStaticPropertyValue()`        | ❌ Not going to implement: use `PropertyReflection::setValue()`   | 

## PropertyReflection

| ReflectionProperty    | PropertyReflection                                                 |
|-----------------------|--------------------------------------------------------------------|
| `IS_PRIVATE`          | ✅                                                                  |
| `IS_PROTECTED`        | ✅                                                                  |
| `IS_PUBLIC`           | ✅                                                                  |
| `IS_READONLY`         | ✅                                                                  |
| `IS_STATIC`           | ✅                                                                  |
| `$class`              | ✅                                                                  |
| `$name`               | ✅                                                                  |
| `__clone()`           | ✅ Cloning is forbidden                                             |
| `__construct()`       | ❌ `@internal`                                                      |
| `__toString()`        | ❌ Not going to implement                                           |
| `getAttributes()`     | ❌ TODO                                                             |
| `getDeclaringClass()` | ❌ TODO                                                             |
| `getDefaultValue()`   | ✅ Via native reflection                                            |
| `getDocComment()`     | ✅️ Returns `?non-empty-string`                                     |
| `getModifiers()`      | ✅                                                                  |
| `getName()`           | ✅                                                                  |
| `getType()`           | ⚠️ Returns `TypeReflection`                                        |
| `getValue()`          | ✅ Via native reflection                                            |
| `hasDefaultValue()`   | ✅                                                                  |
| `hasType()`           | ❌ Not going to implement: use `getType()`                          |
| `isDefault()`         | ❌ Not going to implement: does not make sense in static reflection |
| `isInitialized()`     | ✅ Via native reflection                                            |
| `isPrivate()`         | ✅                                                                  |
| `isPromoted()`        | ✅                                                                  |
| `isProtected()`       | ✅                                                                  |
| `isPublic()`          | ✅                                                                  |
| `isReadOnly()`        | ✅                                                                  |
| `isStatic()`          | ✅                                                                  |
| `setAccessible()`     | ❌ Not going to implement: has no effect since 8.1                  |
| `setValue()`          | ✅ Via native reflection                                            |

## MethodReflection

| ReflectionMethod                  | MethodReflection                                  |
|-----------------------------------|---------------------------------------------------|
| `IS_ABSTRACT`                     | ✅                                                 |
| `IS_FINAL`                        | ✅                                                 |
| `IS_PRIVATE`                      | ✅                                                 |
| `IS_PROTECTED`                    | ✅                                                 |
| `IS_PUBLIC`                       | ✅                                                 |
| `IS_STATIC`                       | ✅                                                 |
| `$class`                          | ✅                                                 |
| `$name`                           | ✅                                                 |
| `__clone()`                       | ✅ Cloning is forbidden                            |
| `__construct()`                   | ❌ `@internal`                                     |
| `__toString()`                    | ❌ Not going to implement                          |
| `getAttributes()`                 | ❌ TODO                                            |
| `getClosure()`                    | ✅ Via native reflection                           |
| `getClosureCalledClass()`         | ❌ TODO                                            |
| `getClosureScopeClass()`          | ❌ TODO                                            |
| `getClosureThis()`                | ❌ TODO                                            |
| `getClosureUsedVariables()`       | ❌ TODO                                            |
| `getDeclaringClass()`             | ❌ TODO                                            |
| `getDocComment()`                 | ✅️ Returns `?non-empty-string`                    |
| `getEndLine()`                    | ✅ Returns `?positive-int`                         |
| `getExtension()`                  | ❌ Not going to implement                          |
| `getExtensionName()`              | ✅                                                 |
| `getFileName()`                   | ✅️ Returns `?non-empty-string`                    |
| `getModifiers()`                  | ✅                                                 |
| `getName()`                       | ✅                                                 |
| `getNamespaceName()`              | ✅                                                 |
| `getNumberOfParameters()`         | ✅                                                 |
| `getNumberOfRequiredParameters()` | ✅                                                 |
| `getParameters()`                 | ✅                                                 |
| `getPrototype()`                  | ❌ TODO                                            |
| `getReturnType()`                 | ⚠️ Returns `TypeReflection`                       |
| `getShortName()`                  | ✅                                                 |
| `getStartLine()`                  | ✅️ Returns `?positive-int`                        |
| `getStaticVariables()`            | ❌ TODO                                            |
| `getTentativeReturnType()`        | ❌ Not going to implement: use `getReturnType()`   |
| `hasPrototype()`                  | ❌ TODO                                            |
| `hasReturnType()`                 | ❌ Not going to implement: use `getReturnType()`   |
| `hasTentativeReturnType()`        | ❌ Not going to implement: use `getReturnType()`   |
| `inNamespace()`                   | ✅                                                 |
| `invoke()`                        | ✅ Via native reflection                           |
| `invokeArgs()`                    | ✅ Via native reflection                           |
| `isAbstract()`                    | ✅                                                 |
| `isClosure()`                     | ✅                                                 |
| `isConstructor()`                 | ✅                                                 |
| `isDeprecated()`                  | ✅                                                 |
| `isDestructor()`                  | ✅                                                 |
| `isFinal()`                       | ✅                                                 |
| `isGenerator()`                   | ✅                                                 |
| `isInternal()`                    | ✅                                                 |
| `isPrivate()`                     | ✅                                                 |
| `isProtected()`                   | ✅                                                 |
| `isPublic()`                      | ✅                                                 |
| `isStatic()`                      | ✅                                                 |
| `isUserDefined()`                 | ✅                                                 |
| `isVariadic()`                    | ✅                                                 |
| `returnsReference()`              | ✅                                                 |
| `setAccessible()`                 | ❌ Not going to implement: has no effect since 8.1 |

## ParameterReflection

| ReflectionParameter             | ParameterReflection                        |
|---------------------------------|--------------------------------------------|
| `$name`                         | ✅                                          |
| `__clone()`                     | ✅ Cloning is forbidden                     |
| `__construct()`                 | ❌ `@internal`                              |
| `__toString()`                  | ❌ Not going to implement                   |
| `allowsNull()`                  | ❌ Not going to implement: use `getType()`  |
| `canBePassedByValue()`          | ✅                                          |
| `getAttributes()`               | ❌ TODO                                     |
| `getClass()`                    | ❌ Not going to implement: use `getType()`  |
| `getDeclaringClass()`           | ❌ TODO                                     |
| `getDeclaringFunction()`        | ❌ TODO                                     |
| `getDefaultValue()`             | ✅ Via native reflection                    |
| `getDefaultValueConstantName()` | ❌ TODO                                     |
| `getName()`                     | ✅                                          |
| `getPosition()`                 | ✅                                          |
| `getType()`                     | ⚠️ Returns `TypeReflection`                |
| `hasType()`                     | ❌️ Not going to implement: use `getType()` |
| `isArray()`                     | ❌ Not going to implement: use `getType()`  |
| `isCallable()`                  | ❌ Not going to implement: use `getType()`  |
| `isDefaultValueAvailable()`     | ✅                                          |
| `isDefaultValueConstant()`      | ❌ TODO                                     |
| `isOptional()`                  | ✅                                          |
| `isPassedByReference()`         | ✅                                          |
| `isPromoted()`                  | ✅                                          |
| `isVariadic()`                  | ✅                                          |
