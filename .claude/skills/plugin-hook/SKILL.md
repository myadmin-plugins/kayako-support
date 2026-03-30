---
name: plugin-hook
description: Adds a new event hook to src/Plugin.php — registers handler in getHooks(), implements the static method accepting GenericEvent, and wires up api_register/add_requirement/add_setting calls. Use when user says 'add hook', 'register new event', 'add plugin method', or needs to expose a new setting via getSettings(). Do NOT use for changes to src/api.php procedural functions.
---
# plugin-hook

## Critical

- Every hook handler MUST be `public static function methodName(GenericEvent $event)` — tests use `ReflectionClass` to assert this.
- Always use `__CLASS__` (not the full string) in `getHooks()` entries.
- `getHooks()` must return exactly one entry per handler. Count is asserted in `tests/PluginTest.php`.
- Never add a hook handler that isn't registered in `getHooks()` (except `getMenu`, which is intentionally commented out).

## Instructions

1. **Choose the event name and method name.** Map:
   - Registering SOAP types → `'api.register'` / `apiRegister()`
   - Lazy-loading files → `'function.requirements'` / `getRequirements()`
   - Exposing admin settings → `'system.settings'` / `getSettings()`
   - New custom event → `'your.event'` / `yourMethodName()`

2. **Add entry to `getHooks()` in `src/Plugin.php`:**
   ```php
   public static function getHooks()
   {
       return [
           'api.register'          => [__CLASS__, 'apiRegister'],
           'function.requirements' => [__CLASS__, 'getRequirements'],
           'system.settings'       => [__CLASS__, 'getSettings'],
           'your.event'            => [__CLASS__, 'yourMethodName'], // add here
       ];
   }
   ```
   Verify: the value is `[__CLASS__, 'methodName']` — a two-element array.

3. **Implement the handler method** in `src/Plugin.php` after the last existing handler:

   **For `api.register`** — extract subject from event, call `api_register*` helpers:
   ```php
   public static function apiRegister(GenericEvent $event)
   {
       api_register_array('myMethod_return', ['status' => 'string', 'status_text' => 'string']);
       api_register('myMethod', ['param' => 'string'], ['return' => 'myMethod_return'], 'Description.');
   }
   ```

   **For `function.requirements`** — get `$loader` from subject, call `add_requirement()`:
   ```php
   public static function getRequirements(GenericEvent $event)
   {
       $loader = $event->getSubject();
       $loader->add_requirement('myFunction', '/../vendor/detain/myadmin-kayako-support/src/api.php');
   }
   ```

   **For `system.settings`** — get `$settings` from subject, call `add_text_setting()` or `add_password_setting()`:
   ```php
   public static function getSettings(GenericEvent $event)
   {
       $settings = $event->getSubject();
       $settings->add_text_setting(_('Support'), _('Kayako'), 'my_constant_key', _('Label'), _('Description'), MY_CONSTANT);
       $settings->add_password_setting(_('Support'), _('Kayako'), 'my_secret_key', _('Label'), _('Description'), MY_SECRET);
   }
   ```

   **For a generic/custom event:**
   ```php
   public static function yourMethodName(GenericEvent $event)
   {
       $subject = $event->getSubject();
       // implementation
   }
   ```

4. **Update `tests/PluginTest.php`** — add a mapping assertion for your new hook:
   ```php
   public function testYourMethodNameHookMapping(): void
   {
       $hooks = Plugin::getHooks();
       $this->assertSame(
           ['Detain\\MyAdminKayako\\Plugin', 'yourMethodName'],
           $hooks['your.event']
       );
   }
   ```
   Also update `testGetHooksReturnsExactlyThreeHooks` count to match the new total.

5. **Run tests to verify:**
   ```bash
   vendor/bin/phpunit tests/PluginTest.php
   ```
   All assertions about hook count, callable arrays, public static methods, and `GenericEvent` type hint must pass.

## Examples

**User says:** "Add a hook to expose a new `KAYAKO_QUEUE_SIZE` setting."

**Actions taken:**
1. Add `'system.settings' => [__CLASS__, 'getSettings']` is already present — add the new `add_text_setting` call inside the existing `getSettings()` body:
   ```php
   $settings->add_text_setting(_('Support'), _('Kayako'), 'kayako_queue_size', _('Queue Size'), _('Max tickets per batch'), KAYAKO_QUEUE_SIZE);
   ```
2. If adding a brand-new event `'ticket.close'` → `closeTicket()`:
   - Add `'ticket.close' => [__CLASS__, 'closeTicket']` to `getHooks()`.
   - Implement `public static function closeTicket(GenericEvent $event) { ... }`.
   - Update hook count assertion from 3 → 4 in `PluginTest.php`.
   - Run `vendor/bin/phpunit tests/PluginTest.php`.

**Result:** `getHooks()` returns the new entry; handler is public static with `GenericEvent` param; all PluginTest assertions pass.

## Common Issues

- **"Method closeTicket referenced by hook 'ticket.close' does not exist"** — you added the entry to `getHooks()` but forgot to implement the method body. Add the `public static function closeTicket(GenericEvent $event)` method.
- **"Failed asserting that 3 matches expected 4"** — you added a hook but didn't update `testGetHooksReturnsExactlyThreeHooks()` in `tests/PluginTest.php`. Change the `assertCount` value.
- **"first parameter should be GenericEvent"** — handler signature is missing the type hint. Change `function myMethod($event)` to `function myMethod(GenericEvent $event)`.
- **"Handler for 'x' should reference Plugin class"** — used a string `'Plugin'` instead of `__CLASS__` in `getHooks()`. Always use `__CLASS__`.
- **`add_text_setting` / `add_password_setting` not found** — these are MyAdmin framework methods on the `\MyAdmin\Settings` object from `$event->getSubject()`. Ensure you're inside `getSettings()`, not a different handler.