---
name: license-function
description: Adds a new procedural license function to src/cpanel.inc.php following the existing pattern: create Cpanel instance, make API call, log with request_log and myadmin_log, return result. Use when user says 'add license function', 'new cpanel function', 'add API wrapper'. Do NOT use for Plugin.php class methods.
---
# License Function

Add a new procedural license function to `src/cpanel.inc.php` following the established patterns in this cPanel licensing plugin.

## Critical

- All functions go in `src/cpanel.inc.php` — NEVER in `src/Plugin.php` (that file is for event handlers only).
- Every function that calls the cPanel API MUST log via `request_log()` with the exact 7-argument signature used in existing code.
- Always instantiate `\Detain\Cpanel\Cpanel` with the constants `CPANEL_LICENSING_USERNAME` and `CPANEL_LICENSING_PASSWORD` — never hardcode or pass credentials.
- If the function accepts an IP address, validate it with `validIp($ipAddress, false)` before making any API call, returning `false` on invalid input.
- The module is always `'licenses'` — use `$module = 'licenses';` or the string literal directly.
- After adding the function, register it in `Plugin.php` → `getRequirements()` so the lazy-loader can find it.

## Instructions

1. **Read existing functions in `src/cpanel.inc.php`** to confirm the current patterns and avoid name collisions. Every function in this file follows a naming convention: `{action}_cpanel` or `get_cpanel_{thing}`. Name your function to match.
   - Verify: your function name does not already exist in the file.

2. **Write the PHPDoc block** above the function. Follow the existing format:
   ```php
   /**
    * function_name()
    * brief description of what it does
    *
    * @param type $paramName description
    * @return type description
    */
   ```
   - Verify: the `@param` and `@return` types match actual usage.

3. **Write the function body** using this exact skeleton (derived from `activate_cpanel`, `verify_cpanel`, `get_cpanel_license_data_by_ip`):

   ```php
   function your_function_name($ipAddress)
   {
       // Step A: (If IP-based) Validate input
       if (!validIp($ipAddress, false)) {
           return false;
       }

       // Step B: Instantiate the cPanel API client
       $cpl = new \Detain\Cpanel\Cpanel(CPANEL_LICENSING_USERNAME, CPANEL_LICENSING_PASSWORD);

       // Step C: Build the request array
       $request = ['ip' => $ipAddress];

       // Step D: Call the appropriate API method
       $response = $cpl->apiMethodName($request);

       // Step E: Log the API call with request_log
       request_log('licenses', false, __FUNCTION__, 'cpanel', 'apiMethodName', $request, $response);

       // Step F: (Optional) Log additional info with myadmin_log
       myadmin_log('licenses', 'info', json_encode($response), __LINE__, __FILE__);

       // Step G: Process and return the result
       return $response;
   }
   ```

   Key details for each step:
   - **Step A**: Only needed for IP-based functions. Use `validIp($ipAddress, false)` — the `false` second arg disables IPv6-only check.
   - **Step B**: Always use `\Detain\Cpanel\Cpanel` with the two constant args. Never `use` import it at file level (this is a procedural file with no namespace).
   - **Step C**: Build request as associative array. Keys match cPanel Manage2 API parameters.
   - **Step E**: `request_log` signature: `request_log($module, $accountId, $functionName, $provider, $apiMethod, $request, $response)`. Use `$GLOBALS['tf']->session->account_id` for the account ID when the function runs in an authenticated user context, or `false` when it runs in admin/background context.
   - **Step F**: Use `myadmin_log('licenses', $level, $message, __LINE__, __FILE__)` — level is typically `'info'` or `'error'`.

   - Verify: the function uses `\Detain\Cpanel\Cpanel` (not `Cpanel` unqualified), calls `request_log` with all 7 args, and returns a value.

4. **Register the function in `src/Plugin.php` → `getRequirements()`**. Add a line using the same path pattern as existing entries:

   ```php
   $loader->add_requirement('your_function_name', '/../vendor/detain/myadmin-cpanel-licensing/src/cpanel.inc.php');
   ```

   Use `add_requirement` for functions callable from other modules. Use `add_page_requirement` for functions that render pages or are only used in web context.

   - Verify: the string passed to `add_requirement` exactly matches the function name.

5. **Run lint and tests**:
   ```bash
   php -l src/cpanel.inc.php
   php -l src/Plugin.php
   vendor/bin/phpunit
   ```
   - Verify: no syntax errors and all tests pass.

## Examples

### User says: "Add a function to change the package of a cPanel license by IP"

**Actions taken:**

1. Read `src/cpanel.inc.php` to confirm no `change_cpanel_package` function exists.
2. Add the function at the end of `src/cpanel.inc.php` (before the closing `?>` if present, or at end of file):

```php
/**
 * change_cpanel_package()
 * changes the package on an existing cpanel license
 *
 * @param string $ipAddress ip address of the license
 * @param integer $packageId the new package id
 * @return array|bool the response attributes or false on failure
 */
function change_cpanel_package($ipAddress, $packageId)
{
    if (!validIp($ipAddress, false)) {
        return false;
    }
    $packageId = (int) $packageId;
    $cpl = new \Detain\Cpanel\Cpanel(CPANEL_LICENSING_USERNAME, CPANEL_LICENSING_PASSWORD);
    $request = ['ip' => $ipAddress, 'packageid' => $packageId];
    $response = $cpl->changePackage($request);
    request_log('licenses', false, __FUNCTION__, 'cpanel', 'changePackage', $request, $response);
    myadmin_log('licenses', 'info', "change_cpanel_package({$ipAddress}, {$packageId}) returned " . json_encode($response['attr']), __LINE__, __FILE__);
    if (isset($response['attr']['reason']) && $response['attr']['reason'] == 'OK') {
        return $response['attr'];
    }
    return false;
}
```

3. Register in `src/Plugin.php` → `getRequirements()`:
```php
$loader->add_requirement('change_cpanel_package', '/../vendor/detain/myadmin-cpanel-licensing/src/cpanel.inc.php');
```

4. Run `php -l src/cpanel.inc.php && php -l src/Plugin.php && vendor/bin/phpunit`.

**Result:** New function follows exact project patterns — procedural, uses constants for auth, logs both via `request_log` and `myadmin_log`, validates IP input, casts integer params.

## Common Issues

- **`Fatal error: Class 'Detain\Cpanel\Cpanel' not found`**: The `detain/cpanel-licensing` package is not installed. Run `composer install` or `composer require detain/cpanel-licensing:dev-master`.

- **`Call to undefined function request_log()`**: This function is defined in the main MyAdmin framework, not in this plugin. The function is only available when running within the full MyAdmin application context. Tests in this plugin use reflection/static analysis and do NOT bootstrap the framework — do not expect `request_log` to be callable in unit tests.

- **`Call to undefined function validIp()`**: Same as above — `validIp()` is from `include/validations.php` in the main MyAdmin repo. Available at runtime but not in isolated plugin tests.

- **`Undefined constant CPANEL_LICENSING_USERNAME`**: The constants are set via the settings system at runtime. For CLI scripts in `bin/`, ensure you include the appropriate bootstrap. For tests, mock or skip — existing tests use static file analysis to avoid needing these constants.

- **Function not found at runtime after adding it**: You forgot to register it in `Plugin.php` → `getRequirements()`. The MyAdmin lazy-loader only knows about functions listed there. Also verify the `function_requirements('your_function_name')` call is made before invoking your function from Plugin event handlers.

- **`request_log` second argument confusion**: Use `$GLOBALS['tf']->session->account_id` when the function is called during an authenticated user action (like `activate_cpanel` does). Use `false` when running in admin/background/CLI context (like `deactivate_cpanel`, `verify_cpanel` do).

- **Tests fail after adding function**: The `SourceFileAnalysisTest.php` reads `src/` files via `file_get_contents` and checks for patterns. If your function has syntax errors or doesn't follow conventions, it may trigger test failures. Run `php -l src/cpanel.inc.php` first to catch syntax issues.