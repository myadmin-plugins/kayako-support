# MyAdmin Kayako Support Plugin

## Overview

Composer plugin integrating Kayako helpdesk with MyAdmin. Namespace: `Detain\MyAdminKayako\` → `src/` · Tests: `Detain\MyAdminKayako\Tests\` → `tests/`.

## Commands

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpunit tests/PluginTest.php
vendor/bin/phpunit tests/ApiFunctionsTest.php
```

## Architecture

**Plugin Entry** (`src/Plugin.php`): Static class registering 3 event hooks via `Plugin::getHooks()`:
- `api.register` → `apiRegister()` — registers SOAP types via `api_register()` / `api_register_array()` / `api_register_array_array()`
- `function.requirements` → `getRequirements()` — lazy-loads files via `$loader->add_requirement()`
- `system.settings` → `getSettings()` — exposes `KAYAKO_API_URL`, `KAYAKO_API_KEY`, `KAYAKO_API_SECRET` as admin settings

**API Functions** (`src/api.php`): Procedural functions registered via `function.requirements`:
- `openTicket($user_email, $user_ip, $subject, $product, $body, $box_auth_value)` — creates ticket via `kyTicket::createNewAuto()`
- `getTicketList($page, $limit, $status)` — queries `swtickets` via `clone $GLOBALS['helpdesk_dbh']`
- `viewTicket($ticketID)` — fetches ticket via `kyTicket::get()`
- `ticketPost($ticketID, $content)` — replies via `$ticket->newPost($user, $content)->create()`

**Utility Scripts** (`bin/`):
- `bin/merge_staff.php` — merges duplicate staff records across `swstaff`, `swtickets`, and 50+ `sw*` tables
- `bin/recrypt_helpdesk.php` — re-encrypts `swcustomfieldvalues.fieldvalue` with current key

**CI/CD** (`.github/workflows/`): GitHub Actions workflows for automated testing and deployment pipelines.

**IDE Configuration** (`.idea/`): PhpStorm project settings including `inspectionProfiles/` for code inspections, `deployment.xml` for server deployment mappings, and `encodings.xml` for file encoding configuration.

## Conventions

**Error returns** (all API functions):
```php
return ['status' => 'Failed', 'status_text' => 'Descriptive message. Please try again!'];
```

**Kayako SOAP init** (every function that calls Kayako):
```php
function_requirements('class.kyConfig');
kyConfig::set(new kyConfig(KAYAKO_API_URL, KAYAKO_API_KEY, KAYAKO_API_SECRET));
kyConfig::get()->setDebugEnabled(false)->setTimeout(120);
```

**Logging exceptions**:
```php
myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
```

**DB access**: use `get_module_db('helpdesk')` or `clone $GLOBALS['helpdesk_dbh']` · always `$db->real_escape($userInput)` · never interpolate `$_GET`/`$_POST`

**Access control**: check `$GLOBALS['tf']->ima != 'admin'` and `account_lid` before mutations

**Wrap all Kayako calls** in `try/catch (Exception $e)` — return failure array and log in catch

## Testing

`tests/ApiFunctionsTest.php` pattern:
- `setUpBeforeClass()` stubs `function_requirements`, `myadmin_log`, `api_register*` then `require_once` `src/api.php`
- `ReflectionFunction` verifies parameter names, counts, default values
- Test validation paths before any live Kayako calls

`tests/PluginTest.php` pattern:
- `ReflectionClass` verifies static properties (`$name`, `$description`, `$type`) and hook method signatures
- Assert `GenericEvent` type hint on all hook handler parameters

## Configuration Constants

| Constant | Purpose |
|---|---|
| `KAYAKO_API_URL` | Kayako REST API base URL |
| `KAYAKO_API_KEY` | API authentication key |
| `KAYAKO_API_SECRET` | API authentication secret |

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
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->
