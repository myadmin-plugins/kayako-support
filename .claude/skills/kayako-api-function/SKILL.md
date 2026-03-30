---
name: kayako-api-function
description: Adds a new procedural API function to `src/api.php` following the project's validation-first, try/catch-per-operation pattern. Initializes Kayako SOAP config, wraps each Kayako call in its own try/catch, returns status/status_text arrays. Use when user says 'add API function', 'new ticket operation', 'create endpoint in api.php', or 'add support function'. Do NOT use for modifying `src/Plugin.php` hook registration or for class-based API work.
---
# kayako-api-function

## Critical

- **Never** interpolate `$_GET`/`$_POST` directly into queries — always `$db->real_escape($input)`.
- **Every** Kayako SDK call must be in its own `try/catch (Exception $e)` block — one operation per block.
- **Always** check `$GLOBALS['tf']->ima != 'admin'` AND `account_lid` before any mutation that touches another user's ticket.
- **Always** call `function_requirements('class.kyConfig')` before the SOAP init try/catch — not inside it.
- Return the `$result` array on every path — never let the function fall off the end without returning.

## Instructions

1. **Define the function** in `src/api.php` with a PHPDoc block. Use camelCase names matching existing functions (`openTicket`, `viewTicket`, `ticketPost`, `getTicketList`).

   ```php
   /**
    * One-line description.
    *
    * @param int    $ticketID  the ticket id
    * @param string $content   the reply body
    * @return array status/status_text result
    */
   function myNewFunction($ticketID, $content)
   {
   ```

2. **Initialize the result array** as the first statement. Include only keys this function actually returns — do not add speculative keys.

   ```php
       $result = [
           'status'      => 'incomplete',
           'status_text' => '',
       ];
   ```

3. **Validate all required inputs** with early-return guards before touching Kayako. Return `'Failed'` (capital F) for user-input errors.

   ```php
       if (!$ticketID) {
           $result['status']      = 'Failed';
           $result['status_text'] = 'Ticket Reference ID is required. Please try again!';
           return $result;
       }
       if (!$content) {
           $result['status']      = 'Failed';
           $result['status_text'] = 'Content is required. Please try again!';
           return $result;
       }
   ```

   Verify all validation guards return before proceeding to Step 4.

4. **Check ownership** for any read/mutate of another user's ticket (skip for creation functions):

   ```php
       if ($GLOBALS['tf']->ima != 'admin' &&
           $GLOBALS['tf']->accounts->data['account_lid'] != kyTicket::get($ticketID)->getUser()->getEmail()) {
           $result['status']      = 'Failed';
           $result['status_text'] = 'Access denied. Please try again!';
           myadmin_log('api', 'info', 'Denied: ' . $GLOBALS['tf']->accounts->data['account_lid'], __LINE__, __FILE__);
           return $result;
       }
   ```

   Wrap the ownership check itself in `try/catch` — `kyTicket::get()` can throw.

5. **Initialize Kayako SOAP** — call `function_requirements` outside the try, init inside:

   ```php
       function_requirements('class.kyConfig');
       try {
           kyConfig::set(new kyConfig(KAYAKO_API_URL, KAYAKO_API_KEY, KAYAKO_API_SECRET));
           kyConfig::get()->setDebugEnabled(false)->setTimeout(120);
       } catch (Exception $e) {
           $result['status']      = 'failed';
           $result['status_text'] = 'Kayako exception occurred setting config options. Please try again!';
           myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
           return $result;
       }
   ```

6. **Wrap each subsequent Kayako operation** in its own `try/catch`. Set `'status_text'` to a human message naming the failing operation:

   ```php
       try {
           $ticket = kyTicket::get($ticketID);
           $user   = $ticket->getUser();
       } catch (Exception $e) {
           $result['status']      = 'Failed';
           $result['status_text'] = 'Kayako exception occurred getting ticket detail. Please try again!';
           myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
           return $result;
       }
       try {
           $post = $ticket->newPost($user, $content)->create();
           if ($post) {
               $result['status']      = 'Success';
               $result['status_text'] = 'Post added successfully';
           } else {
               $result['status']      = 'Failed';
               $result['status_text'] = 'Exception occurred adding post.';
           }
           return $result;
       } catch (Exception $e) {
           $result['status']      = 'Failed';
           $result['status_text'] = 'Kayako exception occurred adding post. Please try again!';
           myadmin_log('api', 'info', $e->getMessage(), __LINE__, __FILE__);
           return $result;
       }
   }
   ```

7. **For DB-only functions** (no Kayako, e.g. listing from `swtickets`), use `clone $GLOBALS['helpdesk_dbh']` and always escape:

   ```php
       $db = clone $GLOBALS['helpdesk_dbh'];
       $db->query("SELECT * FROM swtickets WHERE ticketmaskid = '" . $db->real_escape($ticketID) . "'", __LINE__, __FILE__);
   ```

8. **Run tests** to verify the function exists and its signature is correct:

   ```bash
   vendor/bin/phpunit tests/ApiFunctionsTest.php
   ```

   Add tests to `tests/ApiFunctionsTest.php` following the `ReflectionFunction` pattern: verify function exists, parameter count, parameter names, and validation-failure return shape.

## Examples

**User says:** "Add a function to close a ticket by ID."

**Actions taken:**
1. Add `closeTicket($ticketID)` to `src/api.php`
2. `$result = ['status' => 'incomplete', 'status_text' => '']`
3. Guard: `if (!$ticketID)` → return `'Failed'`
4. Ownership check in try/catch
5. SOAP init block
6. `try { kyTicket::get($ticketID)->close()->save(); $result['status'] = 'Success'; ... return $result; } catch ...`
7. Add `testCloseTicketFunctionExists`, `testCloseTicketSignature`, `testCloseTicketFailsWithEmptyId` to `tests/ApiFunctionsTest.php`
8. Run `vendor/bin/phpunit tests/ApiFunctionsTest.php`

**Result:** New function in `src/api.php`, matching shape of `ticketPost`; tests green.

## Common Issues

- **`Call to undefined function kyConfig::set()`**: `function_requirements('class.kyConfig')` was not called before the SOAP init block. Add it immediately before the try/catch.
- **`Undefined variable $result` in catch block**: `$result` array was not initialized before the first if-guard. Move the `$result = [...]` declaration to the very first line of the function body.
- **Tests fail with `openTicket not found`**: `setUpBeforeClass` only calls `require_once` when `openTicket` doesn't exist. Add a stub for any new global functions your function calls (e.g. `ticket_status_all`) inside the `if (!function_exists('openTicket'))` block in `ApiFunctionsTest::setUpBeforeClass()`.
- **`status` key returns lowercase `'failed'` instead of `'Failed'`**: Validation-path failures use capital-F `'Failed'`; only SOAP init failures in `openTicket`/`getTicketList` use lowercase. Match the convention of the nearest sibling function.
- **`Undefined index: account_lid`**: `$GLOBALS['tf']->accounts->data` is not available in unit tests — wrap ownership checks in try/catch and confirm the test only exercises the pre-ownership validation paths.