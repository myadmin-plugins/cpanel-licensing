---
name: plugin-event-handler
description: Adds a new event handler to src/Plugin.php following the static GenericEvent pattern. Registers hook in getHooks(), implements handler with category check, myadmin_log, function_requirements, and stopPropagation. Use when user says 'add hook', 'new event handler', 'handle event', or 'add plugin method'. Do NOT use for modifying existing handlers.
---
# Plugin Event Handler

## Critical

- Every handler method MUST be `public static` and accept exactly one parameter: `GenericEvent $event`.
- Every module-specific handler (activate, deactivate, change_ip, etc.) MUST guard with `if ($event['category'] == get_service_define('CPANEL'))` before doing any work.
- Every handler MUST call `$event->stopPropagation()` as the last statement inside the category guard block.
- Every handler MUST be registered in `getHooks()` — an unregistered method will never be called.
- The hook key format for module-specific events is `self::$module . '.event_name'` (e.g., `licenses.verify`). System-wide hooks use a fixed string (e.g., `function.requirements`, `ui.menu`).
- After adding a handler, update `tests/PluginTest.php` to cover the new method's signature and hook registration — the test suite validates hook count and expected keys.

## Instructions

### Step 1: Determine the event name and handler method name

Event names follow the pattern `{module}.{action}` where module is `licenses` (from `self::$module`). Handler method names use camelCase prefixed with `get`: e.g., event `licenses.verify` → method `getVerify`.

Verify the event name is not already registered by checking the `getHooks()` array in `src/Plugin.php:31-43`.

### Step 2: Register the hook in `getHooks()`

Add an entry to the array returned by `getHooks()` in `src/Plugin.php`. Place module-specific hooks with the other module hooks (before `function.requirements` and `ui.menu`).

```php
public static function getHooks()
{
    return [
        self::$module.'.settings' => [__CLASS__, 'getSettings'],
        self::$module.'.activate' => [__CLASS__, 'getActivate'],
        // ... existing hooks ...
        self::$module.'.your_event' => [__CLASS__, 'getYourEvent'],  // <-- add here
        'function.requirements' => [__CLASS__, 'getRequirements'],
        'ui.menu' => [__CLASS__, 'getMenu']
    ];
}
```

Verify: the new key follows `self::$module.'.event_name'` format and the value is `[__CLASS__, 'methodName']`.

### Step 3: Implement the handler method

Add the method to `src/Plugin.php` after the existing handlers (before `getMenu`). Follow this exact structure:

```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 */
public static function getYourEvent(GenericEvent $event)
{
    $serviceClass = $event->getSubject();
    if ($event['category'] == get_service_define('CPANEL')) {
        myadmin_log(self::$module, 'info', 'cPanel YourEvent Description', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        function_requirements('your_function_name');
        // Call the procedural function loaded by function_requirements
        $result = your_function_name($serviceClass->getIp());
        $event->stopPropagation();
    }
}
```

Key elements in order:
1. `$serviceClass = $event->getSubject();` — get the service ORM object
2. Category guard: `if ($event['category'] == get_service_define('CPANEL'))`
3. `myadmin_log()` with `self::$module`, log level, description, `__LINE__`, `__FILE__`, `self::$module`, `$serviceClass->getId()`
4. `function_requirements()` to lazy-load the procedural function file
5. Call the procedural function
6. `$event->stopPropagation();` — MUST be last inside the if block

Verify: method is `public static`, accepts `GenericEvent $event`, has the PHPDoc block, and calls `stopPropagation()` inside the guard.

### Step 4: If the handler calls a new procedural function, register it in `getRequirements`

Add a `$loader->add_requirement()` or `$loader->add_page_requirement()` call in `getRequirements()` (around line 139-156):

```php
$loader->add_requirement('your_function_name', '/../vendor/detain/myadmin-cpanel-licensing/src/cpanel.inc.php');
```

- Use `add_requirement()` for functions callable from other plugins
- Use `add_page_requirement()` for functions that render pages or are only called directly

Verify: the file path is correct relative to the plugin's vendor install location.

### Step 5: Update tests in `tests/PluginTest.php`

Three changes are required:

**5a.** Update `testGetHooksContainsExpectedEventKeys` — add the new event key to `$expectedKeys`:
```php
$expectedKeys = [
    // ... existing keys ...
    'licenses.your_event',
];
```

**5b.** Update `testGetHooksCount` — increment the expected count:
```php
$this->assertCount(9, $hooks); // was 8
```

**5c.** Add a method signature test following the existing pattern:
```php
public function testGetYourEventMethodSignature(): void
{
    $method = $this->reflection->getMethod('getYourEvent');
    $this->assertTrue($method->isPublic());
    $this->assertTrue($method->isStatic());
    $this->assertSame(1, $method->getNumberOfRequiredParameters());

    $param = $method->getParameters()[0];
    $this->assertNotNull($param->getType());
    $this->assertSame(GenericEvent::class, $param->getType()->getName());
}
```

**5d.** Add the method name to `testExpectedPublicMethodCount`'s `$expectedMethods` array and to `testEventHandlerMethodsAreStatic`'s `$eventHandlers` array.

Verify: run `vendor/bin/phpunit` and confirm all tests pass.

### Step 6: Run tests

```bash
vendor/bin/phpunit
```

All tests must pass before the handler is complete.

## Examples

### Example: Add a `licenses.verify` handler

**User says:** "Add a verify event handler to the cPanel plugin"

**Actions taken:**

1. In `src/Plugin.php` `getHooks()`, add:
   ```php
   self::$module.'.verify' => [__CLASS__, 'getVerify'],
   ```

2. In `src/Plugin.php`, add the handler method:
   ```php
   /**
    * @param \Symfony\Component\EventDispatcher\GenericEvent $event
    */
   public static function getVerify(GenericEvent $event)
   {
       $serviceClass = $event->getSubject();
       if ($event['category'] == get_service_define('CPANEL')) {
           myadmin_log(self::$module, 'info', 'cPanel Verification', __LINE__, __FILE__, self::$module, $serviceClass->getId());
           function_requirements('verify_cpanel');
           $response = verify_cpanel($serviceClass->getIp());
           $event['success'] = $response;
           $event->stopPropagation();
       }
   }
   ```

3. In `tests/PluginTest.php`:
   - Add `'licenses.verify'` to `$expectedKeys` in `testGetHooksContainsExpectedEventKeys`
   - Change `assertCount(8, ...)` to `assertCount(9, ...)` in `testGetHooksCount`
   - Add `testGetVerifyMethodSignature` test method
   - Add `'getVerify'` to `$expectedMethods` and `$eventHandlers` arrays

4. Run `vendor/bin/phpunit` — all tests pass.

**Result:** New `getVerify` handler is registered, implemented with the standard pattern, and fully tested.

## Common Issues

### Tests fail with "Missing hook key: licenses.your_event"
You added the test expectation but forgot to register the hook in `getHooks()`. Add the entry to the return array in `src/Plugin.php:33-43`.

### Tests fail with "Expected count 8, got 9" (or similar count mismatch)
You added a hook but forgot to update `testGetHooksCount`. Change the `assertCount` argument in `tests/PluginTest.php` to match the new total number of hooks.

### Tests fail with "Method 'getYourEvent' referenced in hook does not exist"
The hook references a method name that doesn't exist on the Plugin class. Check for typos — the method name in `getHooks()` must exactly match the method you defined (case-sensitive).

### "Call to undefined function your_function_name()"
The procedural function hasn't been loaded. Ensure you called `function_requirements('your_function_name')` before invoking the function, AND that the function is registered in `getRequirements()` with the correct file path.

### Handler runs for wrong license types
The category guard `$event['category'] == get_service_define('CPANEL')` is missing or uses the wrong constant. Every module-specific handler must check this before acting.

### `$event->stopPropagation()` not called — other plugins' handlers also fire
The `stopPropagation()` call must be inside the `if` block. If placed outside, it runs unconditionally and blocks handlers for non-cPanel categories. If missing inside, other plugins for the same event will also execute.