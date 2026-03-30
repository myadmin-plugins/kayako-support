---
name: phpunit-test
description: Writes PHPUnit 9 tests mirroring patterns in `tests/ApiFunctionsTest.php` and `tests/PluginTest.php`. Stubs MyAdmin globals in `setUpBeforeClass()`, uses `ReflectionFunction`/`ReflectionClass` for signature checks, tests validation failures before live calls. Use when user says 'write tests', 'add test coverage', 'test this function'. Do NOT use for integration tests requiring a live Kayako instance.
---
# PHPUnit Test

## Critical

- **Never call live Kayako SDK methods in tests.** Stub `function_requirements` so `kyConfig` is never loaded.
- **Never interpolate `$_GET`/`$_POST` in test assertions** — pass controlled scalar values directly.
- All test methods must be `public function testXxx(): void` with a single-sentence docblock.
- Test validation failures **before** any path that touches Kayako or the DB — these are the only safe paths to exercise without a live instance.
- Run with: `composer test` or target a specific file such as `tests/ApiFunctionsTest.php` or `tests/PluginTest.php`.

## Instructions

### For procedural functions in `src/api.php`

1. **Create** a new test file under `tests/` (e.g. `tests/ApiFunctionsTest.php`) with namespace `Detain\MyAdminKayako\Tests` and `use PHPUnit\Framework\TestCase; use ReflectionFunction;`.

2. **Stub globals in `setUpBeforeClass()`** — define each missing global function with `if (!function_exists(...))` guards, then `require_once dirname(__DIR__) . '/src/api.php'`:
   ```php
   public static function setUpBeforeClass(): void
   {
       if (!function_exists('myFunction')) {
           if (!function_exists('function_requirements')) {
               function function_requirements(string $name): void {}
           }
           if (!function_exists('myadmin_log')) {
               function myadmin_log(string $module, string $level, string $message, int $line, string $file, string $extra = ''): void {}
           }
           if (!function_exists('api_register')) {
               function api_register(string $name, array $params, array $returns, string $desc): void {}
           }
           if (!function_exists('api_register_array')) {
               function api_register_array(string $name, array $fields): void {}
           }
           if (!function_exists('api_register_array_array')) {
               function api_register_array_array(string $name, string $sub): void {}
           }
           require_once dirname(__DIR__) . '/src/api.php';
       }
   }
   ```
   Verify `function_exists('myFunction')` returns true before writing call tests.

3. **Write existence + signature tests** using `ReflectionFunction`:
   ```php
   public function testMyFunctionExists(): void
   {
       $this->assertTrue(function_exists('myFunction'));
   }

   public function testMyFunctionSignature(): void
   {
       $ref = new ReflectionFunction('myFunction');
       $this->assertCount(3, $ref->getParameters());
   }

   public function testMyFunctionParameterNames(): void
   {
       $ref = new ReflectionFunction('myFunction');
       $names = array_map(fn($p) => $p->getName(), $ref->getParameters());
       $this->assertSame(['param1', 'param2', 'param3'], $names);
   }
   ```

4. **Write validation-failure tests** — pass empty/invalid values, assert the failure array shape:
   ```php
   public function testMyFunctionFailsWithEmptyParam(): void
   {
       $result = myFunction('', 'valid', 'valid');
       $this->assertIsArray($result);
       $this->assertSame('Failed', $result['status']);
       $this->assertStringContainsString('param1', strtolower($result['status_text']));
   }

   public function testMyFunctionResultStructure(): void
   {
       $result = myFunction('', '', '');
       $this->assertArrayHasKey('status', $result);
       $this->assertArrayHasKey('status_text', $result);
   }
   ```
   Verify every `assertSame('Failed', ...)` maps to an actual early-return path in `src/api.php`.

5. **Check optional vs required parameters** when defaults exist:
   ```php
   public function testMyFunctionDefaultValues(): void
   {
       $ref = new ReflectionFunction('myFunction');
       $params = $ref->getParameters();
       $this->assertTrue($params[1]->isOptional());
       $this->assertSame(10, $params[1]->getDefaultValue());
   }
   ```

### For `Plugin` class in `src/Plugin.php`

6. **Create** `tests/PluginTest.php`. Use `ReflectionClass` in `setUp()`:
   ```php
   use Detain\MyAdminKayako\Plugin;
   use ReflectionClass;
   use Symfony\Component\EventDispatcher\GenericEvent;

   private ReflectionClass $reflection;

   protected function setUp(): void
   {
       $this->reflection = new ReflectionClass(Plugin::class);
   }
   ```

7. **Verify static properties** are public, static, and string-typed:
   ```php
   public function testNameProperty(): void
   {
       $this->assertSame('Expected Name', Plugin::$name);
   }

   public function testAllStaticPropertiesExist(): void
   {
       foreach (['name', 'description', 'help', 'type'] as $prop) {
           $this->assertTrue($this->reflection->hasProperty($prop));
           $this->assertTrue($this->reflection->getProperty($prop)->isStatic());
           $this->assertTrue($this->reflection->getProperty($prop)->isPublic());
       }
   }
   ```

8. **Verify hook handler methods** are public, static, and accept `GenericEvent`:
   ```php
   public function testHookHandlerMethodsAcceptGenericEvent(): void
   {
       foreach (Plugin::getHooks() as $event => $handler) {
           $method = $this->reflection->getMethod($handler[1]);
           $this->assertTrue($method->isPublic());
           $this->assertTrue($method->isStatic());
           $type = $method->getParameters()[0]->getType();
           $this->assertSame(GenericEvent::class, $type->getName());
       }
   }
   ```

## Examples

**User says:** "Write tests for a new `closeTicket($ticketID, $reason)` function I added to `src/api.php`"

**Actions taken:**
1. Add stubs for `function_requirements`, `myadmin_log`, `api_register*` in `setUpBeforeClass()` guarded by `if (!function_exists('closeTicket'))`
2. `require_once dirname(__DIR__) . '/src/api.php'`
3. Write `testCloseTicketFunctionExists`, `testCloseTicketSignature` (assertCount 2), `testCloseTicketParameterNames` (['ticketID','reason'])
4. Write `testCloseTicketFailsWithEmptyId`, `testCloseTicketFailsWithEmptyReason`, `testCloseTicketResultStructure`
5. Write `testCloseTicketAllParametersRequired` using `ReflectionFunction` loop

**Result:** Tests cover signature contract and all validation-failure paths without touching Kayako SDK.

## Common Issues

- **`Cannot redeclare function_requirements()`** — wrap every stub in `if (!function_exists(...))`. The stubs must be inside `setUpBeforeClass()` before the `require_once`, not at file scope.
- **`function_exists('myFunction') returns false`** — confirm `require_once` path is `dirname(__DIR__) . '/src/api.php'` (two levels up from `tests/`). Run `composer test -- --debug` to see which files load.
- **`ReflectionFunction: Function myFunction does not exist`** — the function was not defined because `require_once` was skipped by the outer `if (!function_exists(...))` guard. Rename the guard to match the actual function name in `src/api.php`.
- **`assertSame('Failed', ...) fails`** — the function has no validation guard for that input. Read `src/api.php` to confirm what falsy values trigger early returns before writing the test.
- **`Call to undefined method getType()->getName()`** — PHP < 8.0 returns `ReflectionNamedType`; call `(string) $type` instead of `$type->getName()` if the environment uses PHP 7.4.
- **Tests pass locally but fail in CI** — check `.github/workflows/` for the PHP version used in CI. PHPUnit 9 requires PHP >= 7.3. Confirm `phpunit.xml.dist` bootstrap path matches `tests/` layout.
