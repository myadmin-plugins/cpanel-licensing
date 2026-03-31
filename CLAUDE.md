# MyAdmin cPanel Licensing Plugin

MyAdmin plugin for cPanel license management via the cPanel Manage2 API. Part of the `detain/myadmin-*` plugin ecosystem.

## Commands

```bash
composer install                          # install PHP deps
vendor/bin/phpunit                        # run all tests
vendor/bin/phpunit --coverage-html coverage/  # HTML coverage report
php -l src/Plugin.php                     # lint a single file
```

## Architecture

**Namespace**: `MyAdmin\Licenses\Cpanel\` → `src/` · **Tests**: `MyAdmin\Licenses\Cpanel\Tests\` → `tests/`

**Plugin entry**: `src/Plugin.php` — static class with `getHooks()` returning event→callable map for Symfony `GenericEvent` dispatch

**Core files**:
- `src/Plugin.php` — event handlers: `getActivate`, `getDeactivate`, `getDeactivateIp`, `getChangeIp`, `getMenu`, `getRequirements`, `getSettings`
- `src/cpanel.inc.php` — procedural functions: `activate_cpanel()`, `deactivate_cpanel()`, `verify_cpanel()`, `get_cpanel_license_data_by_ip()`, `get_cpanel_licenses()`, `get_cpanel_accounts_for_license_ip()`
- `src/cpanel_kcare_addon.php` — KernelCare addon via `\Detain\Cloudlinux\Cloudlinux`
- `src/cpanel_ksplice_addon.php` — Ksplice addon via `\Detain\MyAdminKsplice\Ksplice`
- `src/cpanel_list.php` — admin-only license list page using `TFTable`
- `src/unbilled_cpanel.php` — unbilled license detection, cross-references licenses/VPS/servers DBs

**CLI scripts** (`bin/`):
- `bin/activate_cpanel.php` — manual license activate/deactivate
- `bin/find_unbilled_cpanel.php` — find unbilled licenses with detailed error reporting
- `bin/unbilled_cpanel.php` — web/CLI unbilled license report
- `bin/update_cpanel_data.php` — sync license data, update `repeat_invoices` costs via `\MyAdmin\Orm\Repeat_Invoice`

**Tests** (`tests/`):
- `tests/PluginTest.php` — reflection-based tests on `Plugin` class structure, hooks, method signatures
- `tests/SourceFileAnalysisTest.php` — static analysis of `src/` files via `file_get_contents` + regex (no framework bootstrap needed)

**Config**: `phpunit.xml.dist` — PHPUnit 9, bootstrap `vendor/autoload.php`, coverage on `src/`

## Key Dependencies

- `detain/cpanel-licensing` — `\Detain\Cpanel\Cpanel` API wrapper (credentials: `CPANEL_LICENSING_USERNAME`, `CPANEL_LICENSING_PASSWORD`)
- `symfony/event-dispatcher` ^5.0 — `GenericEvent` for hook dispatch
- `ext-soap` — required by cPanel API

## Plugin Hook Pattern

All handlers in `src/Plugin.php` are `public static` methods accepting `GenericEvent`. They check `$event['category'] == get_service_define('CPANEL')` before acting, then call `$event->stopPropagation()`.

```php
public static function getActivate(GenericEvent $event)
{
    $serviceClass = $event->getSubject();
    if ($event['category'] == get_service_define('CPANEL')) {
        myadmin_log(self::$module, 'info', 'cPanel Activation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        function_requirements('activate_cpanel');
        $response = activate_cpanel($serviceClass->getIp(), $event['field1']);
        $serviceClass->setKey($response['licenseid'])->save();
        $event->stopPropagation();
    }
}
```

## Database Pattern

Uses MyAdmin DB abstraction — never PDO:
```php
$db = get_module_db('licenses');
$db->query("SELECT * FROM licenses WHERE license_ip='{$ip}'", __LINE__, __FILE__);
if ($db->num_rows() > 0) {
    $db->next_record(MYSQL_ASSOC);
    $row = $db->Record;
}
```

## Conventions

- Procedural functions in `src/*.inc.php` and `src/*.php` — loaded via `function_requirements()`
- Admin-only pages check `$GLOBALS['tf']->ima == 'admin'` and/or `has_acl('view_service')`
- Logging: `myadmin_log('licenses', $level, $message, __LINE__, __FILE__)`
- API requests logged via `request_log($module, $accountId, $function, 'cpanel', $method, $request, $response)`
- License operations use `$cpl = new \Detain\Cpanel\Cpanel(CPANEL_LICENSING_USERNAME, CPANEL_LICENSING_PASSWORD)`
- ORM updates via `\MyAdmin\Orm\Repeat_Invoice` and `\MyAdmin\Orm\License` — `load_real()`, setters, `save()`
- Tests use reflection and `file_get_contents` regex — no framework globals needed
- Indentation: tabs, size 4
- Commit messages: lowercase, descriptive
- No closing `?>` tag in source files

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md .agents/ .opencode/ 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->

<!-- caliber:managed:sync -->
## Context Sync

This project uses [Caliber](https://github.com/caliber-ai-org/ai-setup) to keep AI agent configs in sync across Claude Code, Cursor, Copilot, and Codex.
Configs update automatically before each commit via `caliber refresh`.
If the pre-commit hook is not set up, run `/setup-caliber` to configure everything automatically.
<!-- /caliber:managed:sync -->
