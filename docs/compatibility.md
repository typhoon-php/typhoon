# Compatibility with native reflection

| `ReflectionClass`                 | `Typhoon\Reflection\ClassReflection` |
|-----------------------------------|--------------------------------------|
| `IS_READONLY`                     | ✅ Defined for PHP 8.1                |
| `$name`                           | ✅                                    |
| `__construct()`                   | ❌ `@internal`                        |
| `__toString()`                    | ✅ Via native reflection              |
| `getAttributes()`                 | ✅                                    |
| `getConstant()`                   | ✅ Via native reflection              |
| `getConstants()`                  | ✅ Via native reflection. TODO        |
| `getConstructor()`                | ✅                                    |
| `getDefaultProperties()`          | ✅ Via native reflection              |
| `getDocComment()`                 | ✅️                                   |
| `getEndLine()`                    | ✅                                    |
| `getExtension()`                  | ✅ Via native reflection              |
| `getExtensionName()`              | ✅                                    |
| `getFileName()`                   | ✅                                    |
| `getInterfaceNames()`             | ✅                                    |
| `getInterfaces()`                 | ✅                                    |
| `getMethods()`                    | ✅                                    |
| `getMethod()`                     | ✅                                    |
| `getModifiers()`                  | ✅                                    |
| `getName()`                       | ✅                                    |
| `getNamespaceName()`              | ✅                                    |
| `getParentClass()`                | ✅                                    |
| `getProperties()`                 | ✅                                    |
| `getProperty()`                   | ✅                                    |
| `getReflectionConstant()`         | ✅ Via native reflection. TODO        |
| `getReflectionConstants()`        | ✅ Via native reflection. TODO        |
| `getShortName()`                  | ✅                                    |
| `getStartLine()`                  | ✅️                                   |
| `getStaticProperties()`           | ✅ Via native reflection              |
| `getStaticPropertyValue()`        | ✅ Via native reflection              |
| `getTraitAliases()`               | ✅                                    |
| `getTraitNames()`                 | ✅                                    |
| `getTraits()`                     | ✅                                    |
| `hasConstant()`                   | ✅ Via native reflection. TODO        |
| `hasMethod()`                     | ✅                                    |
| `hasProperty()`                   | ✅                                    |
| `implementsInterface()`           | ✅                                    |
| `inNamespace()`                   | ✅                                    |
| `isAbstract()`                    | ✅                                    |
| `isAnonymous()`                   | ✅                                    |
| `isCloneable()`                   | ✅                                    |
| `isEnum()`                        | ✅                                    |
| `isFinal()`                       | ✅                                    |
| `isInstance()`                    | ✅                                    |
| `isInstantiable()`                | ✅                                    |
| `isInterface()`                   | ✅                                    |
| `isInternal()`                    | ✅                                    |
| `isIterable()`                    | ✅                                    |
| `isIterateable()`                 | ✅                                    |
| `isReadOnly()`                    | ✅                                    |
| `isSubclassOf()`                  | ✅                                    |
| `isTrait()`                       | ✅                                    |
| `isUserDefined()`                 | ✅                                    |
| `newInstance()`                   | ✅ Via native reflection              |
| `newInstanceArgs()`               | ✅ Via native reflection              |
| `newInstanceWithoutConstructor()` | ✅ Via native reflection              |
| `setStaticPropertyValue()`        | ✅ Via native reflection              | 

| `ReflectionProperty`  | `Typhoon\Reflection\PropertyReflection` |
|-----------------------|-----------------------------------------|
| `$class`              | ✅                                       |
| `$name`               | ✅                                       |
| `__construct()`       | ❌ `@internal`                           |
| `__toString()`        | ✅ Via native reflection                 |
| `getAttributes()`     | ✅                                       |
| `getDeclaringClass()` | ✅                                       |
| `getDefaultValue()`   | ✅ Via native reflection                 |
| `getDocComment()`     | ✅️                                      |
| `getModifiers()`      | ✅                                       |
| `getName()`           | ✅                                       |
| `getType()`           | ✅ Via native reflection                 |
| `getValue()`          | ✅ Via native reflection                 |
| `hasDefaultValue()`   | ✅                                       |
| `hasType()`           | ✅                                       |
| `isDefault()`         | ✅                                       |
| `isInitialized()`     | ✅ Via native reflection                 |
| `isPrivate()`         | ✅                                       |
| `isPromoted()`        | ✅                                       |
| `isProtected()`       | ✅                                       |
| `isPublic()`          | ✅                                       |
| `isReadOnly()`        | ✅                                       |
| `isStatic()`          | ✅                                       |
| `setAccessible()`     | ✅                                       |
| `setValue()`          | ✅ Via native reflection                 |

| `ReflectionMethod`                       | `Typhoon\Reflection\MethodReflection`        |
|------------------------------------------|----------------------------------------------|
| `$class`                                 | ✅                                            |
| `$name`                                  | ✅                                            |
| `__construct()`                          | ❌ `@internal`                                |
| `__toString()`                           | ✅ Via native reflection                      |
| `createFromMethodName()`                 | ❌                                            |
| `getAttributes()`                        | ✅                                            |
| `getClosure()`                           | ✅ Via native reflection                      |
| `getClosureCalledClass()`                | ✅                                            |
| `getClosureScopeClass()`                 | ✅                                            |
| `getClosureThis()`                       | ✅                                            |
| `getClosureUsedVariables()`              | ✅                                            |
| `getDeclaringClass()`                    | ✅                                            |
| `getDocComment()`                        | ✅️                                           |
| `getEndLine()`                           | ✅                                            |
| `getExtension()`                         | ✅ Via native reflection                      |
| `getExtensionName()`                     | ✅                                            |
| `getFileName()`                          | ✅️                                           |
| `getModifiers()`                         | ✅                                            |
| `getName()`                              | ✅                                            |
| `getNamespaceName()`                     | ✅                                            |
| `getNumberOfParameters()`                | ✅                                            |
| `getNumberOfRequiredParameters()`        | ✅                                            |
| `getParameters()`                        | ✅                                            |
| `getPrototype()`                         | ✅                                            |
| `getReturnType()`                        | ✅ Via native reflection                      |
| `getShortName()`                         | ✅                                            |
| `getStartLine()`                         | ✅️                                           |
| `getStaticVariables()`                   | ✅️ Via native reflection                     |
| `getTentativeReturnType()`               | ✅ Via native reflection                      |
| `hasPrototype()`                         | ✅                                            |
| `hasReturnType()`                        | ✅ Via native reflection for internal methods |
| `hasTentativeReturnType()`               | ✅ Via native reflection                      |
| `inNamespace()`                          | ✅                                            |
| `invoke()`                               | ✅ Via native reflection                      |
| `invokeArgs()`                           | ✅ Via native reflection                      |
| `isAbstract()`                           | ✅                                            |
| `isClosure()`                            | ✅                                            |
| `isConstructor()`                        | ✅                                            |
| `isDeprecated()`                         | ✅                                            |
| `isDestructor()`                         | ✅                                            |
| `isFinal()`                              | ✅                                            |
| `isGenerator()`                          | ✅                                            |
| `isInternal()`                           | ✅                                            |
| `isPrivate()`                            | ✅                                            |
| `isProtected()`                          | ✅                                            |
| `isPublic()`                             | ✅                                            |
| `isStatic()`                             | ✅                                            |
| `isUserDefined()`                        | ✅                                            |
| `isVariadic()`                           | ✅                                            |
| `returnsReference()`                     | ✅                                            |
| `setAccessible()`                        | ✅                                            |

| `ReflectionParameter`           | `Typhoon\Reflection\ParameterReflection` |
|---------------------------------|------------------------------------------|
| `$name`                         | ✅                                        |
| `__construct()`                 | ❌ `@internal`                            |
| `__toString()`                  | ✅ Via native reflection                  |
| `allowsNull()`                  | ✅                                        |
| `canBePassedByValue()`          | ✅                                        |
| `getAttributes()`               | ✅                                        |
| `getClass()`                    | ✅                                        |
| `getDeclaringClass()`           | ✅                                        |
| `getDeclaringFunction()`        | ✅                                        |
| `getDefaultValue()`             | ✅ Via native reflection                  |
| `getDefaultValueConstantName()` | ✅ Via native reflection                  |
| `getName()`                     | ✅                                        |
| `getPosition()`                 | ✅                                        |
| `getType()`                     | ✅ Via native reflection                  |
| `hasType()`                     | ✅                                        |
| `isArray()`                     | ✅                                        |
| `isCallable()`                  | ✅                                        |
| `isDefaultValueAvailable()`     | ✅                                        |
| `isDefaultValueConstant()`      | ✅ Via native reflection                  |
| `isOptional()`                  | ✅                                        |
| `isPassedByReference()`         | ✅                                        |
| `isPromoted()`                  | ✅                                        |
| `isVariadic()`                  | ✅                                        |

| `ReflectionAttribute` | `Typhoon\Reflection\AttributeReflection` |
|-----------------------|------------------------------------------|
| `__toString()`        | ✅ Via native reflection                  |
| `getArguments()`      | ✅ Via native reflection                  |
| `getName()`           | ✅                                        |
| `getTarget()`         | ✅                                        |
| `isRepeated()`        | ✅                                        |
| `newInstance()`       | ✅ Via native reflection                  |
