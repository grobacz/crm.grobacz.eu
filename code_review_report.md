# Code Review Report - CRM Project

## 1. Bugs & Architectural Issues

### A. Missing `Deals.vue` View
*   **Issue:** The `Deals` view (`frontend/src/views/Deals.vue`) does not exist, yet it is referenced globally across the app. 
*   **Impact:** 
    *   Global search (`frontend/src/components/GlobalSearch.vue`) links results of type "deal" to `/customers` rather than a dedicated `/deals` page.
    *   The GraphQL schema exposes Deal types, queries, and mutations (`createDeal`, `updateDeal`, `deleteDeal`).
    *   The Symfony backend `DealResolver` handles full CRUD operations, but there is no UI to manage them.

### B. Incomplete `Contacts.vue` View
*   **Issue:** The `frontend/src/views/Contacts.vue` file is merely a scaffold/skeleton. It declares a script setup with `graphqlRequest` and `onMounted`, but the fetch logic and mutation logics are completely absent (just a `// Fetch contacts` comment exists).
*   **Impact:** Users cannot actually view, create, edit, or delete contacts.

### C. Hardcoded Authentication / User State
*   **Issue:** The system uses `UserManagement.vue` and an `AppUser` entity with roles (`admin`, `manager`, `user`), but there is no actual authentication system (no login page, no JWT, no session management). The frontend simply "loads users" and appears to blindly use the first available or cached user in `userStore.js`.
*   **Impact:** Zero security. Anyone can access the dashboard.
*   **Proof:** `UserResolver.php` includes a `passwordHash` field that is completely unused in the codebase (apart from being seeded in `SeedDemoDataCommand.php`).

### D. Hardcoded Pagination/Limit in Search Resolver
*   **Issue:** In `src/GraphQL/Resolver/SearchResolver.php`, the global search results are strictly limited to a hardcoded default `$limit = $args['limit'] ?? 5`.
*   **Impact:** The UI cannot fetch more than 5 results per category in the global search dropdown, even if it might need to display more.

---

## 2. Inconsistencies & Duplications

### A. Redundant Validation Logic (Frontend)
*   **Issue:** Form validation is duplicated aggressively across `Customers.vue` and `Leads.vue`. Functions like `validateEmail`, `validatePhone`, `validateName`, `validateCompany`, `clearFormErrors`, `getGraphqlErrorMessage`, and `getGraphqlFieldErrors` are nearly identical copies.
*   **Recommendation:** Extract these into a shared composable, e.g., `frontend/src/composables/useFormValidation.js`.

### B. Redundant Input Normalization (Backend)
*   **Issue:** Almost every backend GraphQL Resolver (`CustomerResolver`, `LeadResolver`, `CategoryResolver`, `UserResolver`, etc.) implements its own private `normalizeInput()` and `validateInput()` methods for generic operations like `trim()` and `strtolower()`.
*   **Recommendation:** Move basic input sanitization and standard validation (like email regex, string length) into generic DTOs or a shared Validation Service.

### C. Missing Type Declarations in Resolvers
*   **Issue:** While PHP 8.2 features are used in some areas, many resolver methods rely heavily on the generic `array $args`.
*   **Impact:** There's no type safety on the GraphQL input arguments within the PHP layer until they are manually unpacked inside the resolver.

---

## 3. Dead Code & Unused Features

### A. Legacy Directories
*   The `src/` root directory contains empty, dead directories that mirror the frontend structure (`src/Views`, `src/assets`, `src/components`, `src/graphql`, `src/public`, `src/router`). 
*   **Recommendation:** These should be deleted as they create confusion and clutter the backend folder structure.

### B. Unused `passwordHash` in `AppUser`
*   **Issue:** `src/Entity/AppUser.php` has a `$passwordHash` field. However, there is no login/auth logic in the `UserResolver` to verify passwords. The frontend simply fetches users and bypasses any authentication.

---

## 4. UI/UX & Quality of Life Issues

### A. Global Search Dropdown Behavior
*   **Issue:** The global search dropdown (`frontend/src/components/GlobalSearch.vue`) has `deals` hardcoded to navigate to `/customers`.
*   **Code:** `@click="navigateTo('/customers')"` under the Deals section.

### B. Missing GraphQL Error Handling Fallbacks
*   **Issue:** The `graphqlRequest` helper in `frontend/src/graphql/client.js` simply logs errors to `console.error` and throws. If a network request fails entirely (e.g., backend is down), the UI does not gracefully handle the generic network error, only displaying errors if the GraphQL response contains an `errors` array.

## 5. Summary of Recommendations

1.  **Implement `Deals.vue`:** Build the missing frontend view for managing Deals.
2.  **Complete `Contacts.vue`:** Finish the component to actually fetch and mutate contact data.
3.  **Refactor Frontend Validation:** Move duplicated validation functions from `Customers.vue` and `Leads.vue` into a shared composable.
4.  **Clean up Legacy Folders:** Remove `src/Views`, `src/components`, etc., from the backend root.
5.  **Fix Global Search Routing:** Update the `GlobalSearch.vue` component to properly link Deals to a Deals page.
6.  **Implement Real Authentication:** If user roles matter, implement a proper JWT login flow instead of just hardcoding roles.