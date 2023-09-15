# Compatibility with native PHP Reflection

## ClassReflection

| ReflectionClass                   | ClassReflection                        |
|-----------------------------------|----------------------------------------|
| `IS_EXPLICIT_ABSTRACT`            | ✅                                      |
| `IS_FINAL`                        | ✅                                      |
| `IS_IMPLICIT_ABSTRACT`            | ✅                                      |
| `IS_READONLY`                     | ✅                                      |
| `$name`                           | ✅                                      |
| `__clone()`                       | ✅ Cloning is forbidden                 |
| `__construct()`                   | ❌ `@internal`                          |
| `__toString()`                    | ❌ Not going to implement               |
| `getAttributes()`                 | ❌ TODO                                 |
| `getConstant()`                   | ❌ TODO                                 |
| `getConstants()`                  | ❌ TODO                                 |
| `getConstructor()`                | ✅                                      |
| `getDefaultProperties()`          | ❌ Not going to implement               |
| `getDocComment()`                 | ✅️ Returns `?non-empty-string`         |
| `getEndLine()`                    | ✅ Returns `?positive-int`              |
| `getExtension()`                  | ❌ Not going to implement               |
| `getExtensionName()`              | ✅                                      |
| `getFileName()`                   | ✅ Returns `?non-empty-string`          |
| `getInterfaceNames()`             | ✅                                      |
| `getInterfaces()`                 | ❌                                      |
| `getMethod()`                     | ✅                                      |
| `getMethods()`                    | ✅                                      |
| `getModifiers()`                  | ✅                                      |
| `getName()`                       | ✅                                      |
| `getNamespaceName()`              | ✅                                      |
| `getParentClass()`                | ✅ Returns `?ClassReflection`           |
| `getProperties()`                 | ✅                                      |
| `getProperty()`                   | ✅                                      |
| `getReflectionConstant()`         | ❌ TODO                                 |
| `getReflectionConstants()`        | ❌ TODO                                 |
| `getShortName()`                  | ✅                                      |
| `getStartLine()`                  | ✅️ Returns `?positive-int`             |
| `getStaticProperties()`           | ❌ Not going to implement               |
| `getStaticPropertyValue()`        | ❌ Use `PropertyReflection::setValue()` |
| `getTraitAliases()`               | ❌ Not going to implement               |
| `getTraitNames()`                 | ❌ TODO                                 |
| `getTraits()`                     | ❌ TODO                                 |
| `hasConstant()`                   | ❌ TODO                                 |
| `hasMethod()`                     | ✅                                      |
| `hasProperty()`                   | ✅                                      |
| `implementsInterface()`           | ✅                                      |
| `inNamespace()`                   | ✅                                      |
| `isAbstract()`                    | ✅                                      |
| `isAnonymous()`                   | ✅                                      |
| `isCloneable()`                   | ✅                                      |
| `isEnum()`                        | ✅                                      |
| `isFinal()`                       | ✅                                      |
| `isInstance()`                    | ❌ TODO                                 |
| `isInstantiable()`                | ✅                                      |
| `isInterface()`                   | ✅                                      |
| `isInternal()`                    | ✅                                      |
| `isIterable()`                    | ✅                                      |
| `isIterateable()`                 | ❌ Use `ClassReflection::isIterable()`  |
| `isReadOnly()`                    | ✅                                      |
| `isSubclassOf()`                  | ✅                                      |
| `isTrait()`                       | ✅                                      |
| `isUserDefined()`                 | ✅                                      |
| `newInstance()`                   | ✅ Via native reflection                |
| `newInstanceArgs()`               | ✅ Via native reflection                |
| `newInstanceWithoutConstructor()` | ✅ Via native reflection                |
| `setStaticPropertyValue()`        | ❌ Use `PropertyReflection::setValue()` | 

## PropertyReflection

| ReflectionProperty    | PropertyReflection             |
|-----------------------|--------------------------------|
| `IS_PRIVATE`          | ✅                              |
| `IS_PROTECTED`        | ✅                              |
| `IS_PUBLIC`           | ✅                              |
| `IS_READONLY`         | ✅                              |
| `IS_STATIC`           | ✅                              |
| `$class`              | ✅                              |
| `$name`               | ✅                              |
| `__clone()`           | ✅ Cloning is forbidden         |
| `__construct()`       | ❌ `@internal`                  |
| `__toString()`        | ❌ Not going to implement       |
| `getAttributes()`     | ❌ TODO                         |
| `getDeclaringClass()` | ❌                              |
| `getDefaultValue()`   | ✅ Via native reflection        |
| `getDocComment()`     | ✅️ Returns `?non-empty-string` |
| `getModifiers()`      | ✅                              |
| `getName()`           | ✅                              |
| `getType()`           | ⚠️ Returns `TypeReflection`    |
| `getValue()`          | ✅ Via native reflection        |
| `hasDefaultValue()`   | ✅                              |
| `hasType()`           | ❌ Use `getType()`              |
| `isDefault()`         | ❌                              |
| `isInitialized()`     | ✅ Via native reflection        |
| `isPrivate()`         | ✅                              |
| `isPromoted()`        | ✅                              |
| `isProtected()`       | ✅                              |
| `isPublic()`          | ✅                              |
| `isReadOnly()`        | ✅                              |
| `isStatic()`          | ✅                              |
| `setAccessible()`     | ❌ Has no effect since 8.1      |
| `setValue()`          | ✅ Via native reflection        |

## MethodReflection

| ReflectionMethod                  | MethodReflection               |
|-----------------------------------|--------------------------------|
| `IS_ABSTRACT`                     | ✅                              |
| `IS_FINAL`                        | ✅                              |
| `IS_PRIVATE`                      | ✅                              |
| `IS_PROTECTED`                    | ✅                              |
| `IS_PUBLIC`                       | ✅                              |
| `IS_STATIC`                       | ✅                              |
| `$class`                          | ✅                              |
| `$name`                           | ✅                              |
| `__clone()`                       | ✅ Cloning is forbidden         |
| `__construct()`                   | ❌ `@internal`                  |
| `__toString()`                    | ❌ Not going to implement       |
| `getAttributes()`                 | ❌ TODO                         |
| `getClosure()`                    | ✅ Via native reflection        |
| `getClosureCalledClass()`         | ❌                              |
| `getClosureScopeClass()`          | ❌                              |
| `getClosureThis()`                | ❌                              |
| `getClosureUsedVariables()`       | ❌                              |
| `getDeclaringClass()`             | ❌                              |
| `getDocComment()`                 | ✅️ Returns `?non-empty-string` |
| `getEndLine()`                    | ✅ Returns `?positive-int`      |
| `getExtension()`                  | ❌ Not going to implement       |
| `getExtensionName()`              | ✅                              |
| `getFileName()`                   | ✅️ Returns `?non-empty-string` |
| `getModifiers()`                  | ✅                              |
| `getName()`                       | ✅                              |
| `getNamespaceName()`              | ❌ TODO                         |
| `getNumberOfParameters()`         | ✅                              |
| `getNumberOfRequiredParameters()` | ✅                              |
| `getParameters()`                 | ✅                              |
| `getPrototype()`                  | ❌ TODO                         |
| `getReturnType()`                 | ⚠️ Returns `TypeReflection`    |
| `getShortName()`                  | ❌ TODO                         |
| `getStartLine()`                  | ✅️ Returns `?positive-int`     |
| `getStaticVariables()`            | ❌                              |
| `getTentativeReturnType()`        | ❌ Use `getReturnType()`        |
| `hasPrototype()`                  | ❌ TODO                         |
| `hasReturnType()`                 | ❌ Use `getReturnType()`        |
| `hasTentativeReturnType()`        | ❌ Use `getReturnType()`        |
| `inNamespace()`                   | ❌ TODO                         |
| `invoke()`                        | ✅ Via native reflection        |
| `invokeArgs()`                    | ✅ Via native reflection        |
| `isAbstract()`                    | ✅                              |
| `isClosure()`                     | ✅                              |
| `isConstructor()`                 | ✅                              |
| `isDeprecated()`                  | ❌ TODO                         |
| `isDestructor()`                  | ✅                              |
| `isFinal()`                       | ✅                              |
| `isGenerator()`                   | ✅                              |
| `isInternal()`                    | ✅                              |
| `isPrivate()`                     | ✅                              |
| `isProtected()`                   | ✅                              |
| `isPublic()`                      | ✅                              |
| `isStatic()`                      | ✅                              |
| `isUserDefined()`                 | ✅                              |
| `isVariadic()`                    | ✅                              |
| `returnsReference()`              | ✅                              |
| `setAccessible()`                 | ❌ Has no effect since 8.1      |

## ParameterReflection

| ReflectionParameter             | ParameterReflection         |
|---------------------------------|-----------------------------|
| `$name`                         | ✅                           |
| `__clone()`                     | ✅ Cloning is forbidden      |
| `__construct()`                 | ❌ `@internal`               |
| `__toString()`                  | ❌ Not going to implement    |
| `allowsNull()`                  | ❌ Use `getType()`           |
| `canBePassedByValue()`          | ✅                           |
| `getAttributes()`               | ❌ TODO                      |
| `getClass()`                    | ❌ Use `getType()`           |
| `getDeclaringClass()`           | ❌                           |
| `getDeclaringFunction()`        | ❌                           |
| `getDefaultValue()`             | ✅ Via native reflection     |
| `getDefaultValueConstantName()` | ❌                           |
| `getName()`                     | ✅                           |
| `getPosition()`                 | ✅                           |
| `getType()`                     | ⚠️ Returns `TypeReflection` |
| `hasType()`                     | ❌️ Use `getType()`          |
| `isArray()`                     | ❌ Use `getType()`           |
| `isCallable()`                  | ❌ Use `getType()`           |
| `isDefaultValueAvailable()`     | ✅                           |
| `isDefaultValueConstant()`      | ❌                           |
| `isOptional()`                  | ✅                           |
| `isPassedByReference()`         | ✅                           |
| `isPromoted()`                  | ✅                           |
| `isVariadic()`                  | ✅                           |
