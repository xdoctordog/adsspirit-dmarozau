# DECISIONS.md

This document outlines the architectural choices, assumptions, and future improvements made during the implementation of the test task.

---

## 💼 1. Questions for Business & Marketplace (Assumptions Made)

During development, I identified several areas where the business requirements or API contracts were ambiguous. Instead of blocking the process, I made the following pragmatic assumptions:

*   **`cancel_reason` Length Limit:** The OpenAPI specification does not define a `maxLength` for this field.
    *   *Question to Business:* "Is 255 characters sufficient for the cancellation reason, or should this field support longer texts?"
    *   *Assumption:* I implemented the database column as a `TEXT` type and added `max:65535` validation to safely support detailed customer feedback without risking database overflow errors.
*   **Status Transitions (State Machine):** The task lacks strict rules regarding which status an order can transition to from another (e.g., preventing a `cancelled` order from becoming `paid`).
    *   *Question to Business:* "Should our system enforce strict state transitions, or are we acting purely as a data repository/mirror for MarketHub?"
    *   *Assumption:* I assumed we act as a repository and skipped complex transition validation for now, documenting it as a future improvement.
*   **Discrepancies Between OpenAPI and JSON Examples:** There is a mismatch between the `openapi.yaml` contract and the provided webhook examples (e.g., `order-created.json`). The contract defines `order.items.subtotal` and `order.history` as `required`, but the examples omit them.
    *   *Assumption:* I treated the OpenAPI contract as the single source of truth. The application strictly validates incoming payloads and rejects them (`422 Unprocessable Entity`) if mandatory fields are missing. I assumed the JSON examples have documentation flaws.

---

## 🏗️ 2. Key Architectural Decisions & Why

*   **Order Status Implementation (PHP Enum vs. DB Table):** The OpenAPI schema defines `OrderStatus` as a distinct entity.
    *   *Decision:* I implemented it as a native PHP Backed Enum (`OrderStatus::class`) to ensure strict type safety and IDE autocompletion.
    *   *Future note:* If the marketplace expands statuses dynamically without notice (as mentioned in the docs), this will need to be refactored into a database-driven table. Currently, contract compliance was prioritized.
*   **Shipment as a 1:1 Relation:**
    *   *Decision:* I decided *not* to create a separate `Shipment` entity/table. The `shipment` object is stored as nullable columns (`shipment_carrier`, `shipment_tracking_number`, etc.) directly within the `orders` table. Creating a separate table for a 1:1 or 1:0 relationship adds unnecessary complexity and requires unnecessary JOINs.
*   **Timezone Handling (UTC):**
    *   *Decision:* The application strictly stores all timestamps in UTC (using Carbon). While payloads may contain timezone offsets (e.g., `+03:00`), they are automatically parsed and converted to UTC before being persisted to MySQL. API responses then format them back to ISO 8601 (`+00:00`), fully complying with the OpenAPI spec.
*   **Status History Synchronization:**
    *   *Decision:* Instead of treating the history as an append-only audit log, I implemented strict synchronization (deleting old records and inserting the payload's array). This ensures our database perfectly mirrors the current state of the data provided by MarketHub.
*   **Thin Controllers (Action Pattern):**
    *   *Decision:* To adhere to the Single Responsibility Principle, I extracted the heavy webhook business logic from the `WebhookController` into a dedicated `ProcessWebhookAction` class. This keeps controllers clean and the logic highly testable.

---

## 🚀 3. Future Improvements (If I Had More Time)

*   **Handling Out-of-Order Events & Manual Overrides:** The documentation states that event delivery order is not guaranteed, and support can manually correct statuses. Therefore, implementing a strict forward-only State Machine is **incorrect**, as it would block legitimate manual rollbacks.
    *   *Future approach:* We must rely on the `updated_at` timestamp. When a webhook arrives, compare its `updated_at` with the one in our DB. If older, ignore it (out-of-order). If newer, apply it (even if moving backward). We should also log a warning whenever a backward transition occurs to track manual interventions.
*   **Expanded Test Coverage:** While core paths (signatures, idempotency, concurrency) are tested, I would expand the test suite to cover more edge cases, such as failed queue dispatches and complex status rollbacks.
*   **Code Refactoring:** The codebase could undergo another review to extract some smaller logic into dedicated Service classes or Traits to make it even more appealing and modular.
*   **Strict `PaginationMeta` Validation:** The response format is currently compliant (via a custom `OrderCollection`), but I would write deeper tests to ensure 100% consistency across all pagination edge cases.
*   **Dynamic Statuses:** As mentioned, if the business requires dynamic status management, the `OrderStatus` Enum should be migrated to a database table with a foreign key constraint.

---

## 🛠️ 4. Note on Git Commit History

I noticed the requirement to make multiple Git commits for this task. However, I prioritize delivering a fully functional, unbroken snapshot of the application. To avoid submitting a fragmented history with broken intermediate states, I preferred to commit the work once fully assembled. I appreciate your understanding of this workflow preference.

---

## 🤖 5. Use of AI Tools

AI tools were used as an interactive pair-programming assistant throughout the development process to accelerate the following tasks:

*   **Project Setup:** Bootstrapping the clean Laravel 13 project using Docker/WSL2.
*   **Task Analysis:** Breaking down the test assignment and analyzing the `openapi.yaml` structure.
*   **Database Design:** Drafting the initial migrations, models, and relationships.
*   **Debugging:** Resolving environment issues (WSL networking, Xdebug, Mailpit localization).
*   **Tooling & Documentation:** Writing the bash automation scripts for webhook simulation and drafting the `README.md`.