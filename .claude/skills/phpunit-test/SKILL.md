---
name: phpunit-test
description: Creates PHPUnit test methods for this plugin using reflection-based and file_get_contents source analysis patterns from tests/PluginTest.php and tests/SourceFileAnalysisTest.php. Use when user says 'add test', 'write test', 'test coverage', 'new test method', 'increase coverage'. Do NOT use for integration tests requiring framework bootstrap, database access, or mocking of external APIs.
---
# PHPUnit Test Creation

## Critical

- **No framework bootstrap**: Tests in this plugin use only `vendor/autoload.php` as bootstrap. You MUST NOT call any MyAdmin framework functions (`get_module_db`, `myadmin_log`, `get_service_define`, etc.) directly in tests — they do not exist in the test environment.
- **Two test patterns only**: This project uses exactly two test patterns:
  1. **Reflection-based** (`PluginTest.php`) — tests class structure, method signatures, properties, and hook registration using `ReflectionClass`
  2. **Source file analysis** (`SourceFileAnalysisTest.php`) — tests procedural files via `file_get_contents` + regex/string matching, verifying function declarations, parameter counts, required calls, and coding conventions
- **Never instantiate procedural code**: Procedural files in `src/` (everything except `Plugin.php`) depend on global framework state. Test them ONLY via `file_get_contents` string analysis.
- **Test file location**: All tests go in `tests/` with the `MyAdmin\Licenses\Cpanel\Tests\` namespace.
- **PHPUnit 9**: Use PHPUnit 9.x assertion methods. Do not use PHPUnit 10+ attributes.

## Instructions

### Step 1: Determine which test pattern to use

- If testing the `Plugin` class (structure, methods, hooks, properties) → add to `tests/PluginTest.php` using the **reflection pattern**
- If testing procedural source files in `src/` (function existence, parameters, required calls, file conventions) → add to `tests/SourceFileAnalysisTest.php` using the **file_get_contents pattern**
- If testing a new class added to `src/` → create a new `tests/{ClassName}Test.php` using the reflection pattern

Verify: Confirm the target file exists in `src/` before writing any test. Read the source file to understand its actual structure.

### Step 2: Write the test method

**For reflection-based tests** (Plugin class):

```php
/**
 * Tests that {methodName} is a public static method accepting a GenericEvent.
 * {Why this method matters in the plugin system.}
 */
public function test{MethodName}MethodSignature(): void
{
    $method = $this->reflection->getMethod('{methodName}');
    $this->assertTrue($method->isPublic());
    $this->assertTrue($method->isStatic());
    $this->assertSame(1, $method->getNumberOfRequiredParameters());

    $param = $method->getParameters()[0];
    $this->assertNotNull($param->getType());
    $this->assertSame(GenericEvent::class, $param->getType()->getName());
}
```

Key conventions for reflection tests:
- Use `$this->reflection` (set in `setUp()` as `new ReflectionClass(Plugin::class)`)
- Test visibility with `$method->isPublic()`, `$method->isStatic()`
- Test parameter types via `$param->getType()->getName()`
- Test property existence with `$this->reflection->hasProperty($prop)`
- Test static property values with `Plugin::$propertyName`
- Always include a PHPDoc block explaining what and why

**For source file analysis tests** (procedural files):

```php
/**
 * Tests that {filename} declares the {functionName} function.
 * {Why this function exists and what calls it.}
 */
public function test{FilePrefix}Declares{FunctionName}(): void
{
    $content = file_get_contents(self::$srcDir . '/{filename}');
    $this->assertMatchesRegularExpression(
        '/function\s+{functionName}\s*\(/',
        $content,
        '{functionName} function not found in {filename}'
    );
}
```

Key conventions for source analysis tests:
- Use `self::$srcDir` (set in `setUpBeforeClass()` as `dirname(__DIR__) . '/src'`)
- Use `file_get_contents()` to load the file content
- Use `assertMatchesRegularExpression` for function declarations
- Use `assertStringContainsString` for class references, function calls, string checks
- Use `preg_match` + `explode` to count function parameters:
  ```php
  preg_match('/function\s+{funcName}\s*\(([^)]*)\)/', $content, $matches);
  $this->assertNotEmpty($matches, 'Could not parse {funcName} signature');
  $params = array_filter(array_map('trim', explode(',', $matches[1])));
  $this->assertCount({N}, $params, '{funcName} should accept {N} parameters');
  ```
- Use `preg_match_all` to count total functions in a file:
  ```php
  preg_match_all('/^function\s+\w+\s*\(/m', $content, $matches);
  $this->assertCount({N}, $matches[0], '{filename} should declare exactly {N} functions');
  ```
- Group tests by file with a comment separator:
  ```php
  // ---------------------------------------------------------------
  // {filename}
  // ---------------------------------------------------------------
  ```

Verify: Each test method name starts with `test` and uses `PascalCase`. Each has a `@return void` or `: void` return type.

### Step 3: Follow the naming conventions

- Test method names: `test{SubjectDescription}` — e.g., `testCpanelIncDeclaresActivateCpanel`, `testGetActivateMethodSignature`
- For file existence: `test{FilePrefix}FileExists`
- For function declaration: `test{FilePrefix}Declares{FunctionName}`
- For parameter checks: `test{FunctionName}ParameterCount` or `test{FunctionName}HasDefaultParameter`
- For dependency checks: `test{FilePrefix}Uses{ClassName}` or `test{FilePrefix}Calls{FunctionName}`
- For property checks: `test{PropertyName}Property`

Verify: Method name matches the existing naming pattern in the target test file.

### Step 4: Add the test in the correct location

- In `PluginTest.php`: group with related tests (properties together, method signatures together, hook tests together)
- In `SourceFileAnalysisTest.php`: add under the correct file section separator. If testing a new file, create a new section with the comment separator pattern

Verify: The test is placed in the correct section, not at a random location in the file.

### Step 5: Run the tests

```bash
vendor/bin/phpunit
```

Or run a specific test:
```bash
vendor/bin/phpunit --filter testMethodName
```

Verify: All tests pass with 0 failures and 0 errors.

## Examples

### Example 1: Adding a test for a new function in cpanel.inc.php

User says: "add a test for the get_cpanel_accounts_for_license_ip function checking that it uses myadmin_log"

Actions:
1. Read `src/cpanel.inc.php` to confirm `get_cpanel_accounts_for_license_ip` exists and calls `myadmin_log`
2. Add to `tests/SourceFileAnalysisTest.php` under the `cpanel.inc.php` section:

```php
/**
 * Tests that get_cpanel_accounts_for_license_ip calls myadmin_log.
 * License lookups by IP must be audited for security tracking.
 */
public function testGetCpanelAccountsForLicenseIpCallsMyadminLog(): void
{
    $content = file_get_contents(self::$srcDir . '/cpanel.inc.php');
    // First verify the function exists
    $this->assertMatchesRegularExpression(
        '/function\s+get_cpanel_accounts_for_license_ip\s*\(/',
        $content
    );
    // Then check it contains myadmin_log
    // Note: This checks the whole file, not just the function body.
    // For per-function checks, extract the function body first.
    $this->assertStringContainsString('myadmin_log(', $content);
}
```

3. Run `vendor/bin/phpunit --filter testGetCpanelAccountsForLicenseIpCallsMyadminLog`

### Example 2: Adding a test for a new Plugin static property

User says: "test that the Plugin has a $type property set to 'service'"

Actions:
1. Read `src/Plugin.php` to confirm `$type = 'service'`
2. Add to `tests/PluginTest.php` near other property tests:

```php
/**
 * Tests that the $type static property is set to 'service'.
 * This categorizes the plugin within the framework.
 */
public function testTypeProperty(): void
{
    $this->assertSame('service', Plugin::$type);
}
```

3. Run `vendor/bin/phpunit --filter testTypeProperty`

### Example 3: Adding a test for a new source file

User says: "add tests for the new cpanel_addon_manager.php file"

Actions:
1. Read `src/cpanel_addon_manager.php` to understand its functions
2. Add a new section to `tests/SourceFileAnalysisTest.php`:

```php
// ---------------------------------------------------------------
// cpanel_addon_manager.php
// ---------------------------------------------------------------

/**
 * Tests that cpanel_addon_manager.php exists and is readable.
 */
public function testCpanelAddonManagerFileExists(): void
{
    $file = self::$srcDir . '/cpanel_addon_manager.php';
    $this->assertFileExists($file);
    $this->assertFileIsReadable($file);
}

/**
 * Tests that cpanel_addon_manager.php declares the cpanel_addon_manager function.
 */
public function testCpanelAddonManagerDeclaresFunction(): void
{
    $content = file_get_contents(self::$srcDir . '/cpanel_addon_manager.php');
    $this->assertMatchesRegularExpression(
        '/function\s+cpanel_addon_manager\s*\(/',
        $content
    );
}
```

3. Update `testSourceFileList` to include the new file in the `$expected` array
4. Run `vendor/bin/phpunit`

## Common Issues

**Error: `Class "MyAdmin\Licenses\Cpanel\Plugin" not found`**
1. Run `composer install` to ensure autoloader is generated
2. Verify `composer.json` has the correct PSR-4 mapping: `"MyAdmin\\Licenses\\Cpanel\\Tests\\": "tests/"`
3. Run `composer dump-autoload`

**Error: `Call to undefined function get_service_define()`**
You are trying to call a framework function in the test. This plugin's tests do NOT bootstrap the MyAdmin framework. Use reflection or `file_get_contents` patterns instead. Never `require` or `include` procedural source files directly in tests.

**Error: `Failed asserting that string matches pattern`**
The function name or signature has changed. Read the actual source file first:
```bash
grep -n 'function function_name' src/filename.php
```
Update your regex pattern to match the current signature.

**Error: `testSourceFileList failed — unexpected file count`**
When adding a new `src/*.php` file, you MUST also update the `testSourceFileList` test in `SourceFileAnalysisTest.php` to include the new filename in the `$expected` array. Similarly, when adding a new function to an existing file, update the `testCpanelIncFunctionCount` (or equivalent) assertion.

**Error: `This test did not perform any assertions`**
PHPUnit 9 with `failOnRisky="true"` (set in `phpunit.xml.dist`) will fail tests with no assertions. Every test method must contain at least one `$this->assert*()` call.

**Error: `Test method name does not start with 'test'`**
PHPUnit only runs methods prefixed with `test`. Do not use `@test` annotations — this project uses the method prefix convention exclusively.