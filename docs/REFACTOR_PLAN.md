# Refactor and Hardening Map

Last updated: 25 March 2026

This file is the current hardening roadmap, based on the live codebase rather than an empty future plan.

## 1. Current Module Status
| Module | Status | Notes |
|---|---|---|
| Circulars | active / mostly modularized | internal and external flows are already centered in `app/modules/circulars` |
| Memos | active / modularized | repository and service layer exist, workflow is explicit |
| Orders | active / modularized | owner/inbox/archive flow is separated well |
| Outgoing | active / modularized | controller split exists; attachment joins still deserve careful regression coverage |
| Repairs | active / mixed | repository/service exist, but controller remains a large coordination point |
| Room | active / legacy-heavy | still depends on `src/Services/room`; production-safe but not yet fully consolidated |
| Vehicle | active / legacy-heavy | hybrid module/service structure; approval and PDF flows are sensitive |
| Settings / System | active / mixed | stable but still spread across app and legacy service helpers |

## 2. Highest-Value Hardening Work
### A. Database safety
- normalize collation strategy across legacy tables and connection/session defaults
- document tables that require explicit collation or numeric casting at query boundaries

### B. Regression coverage
- add automated checks for high-risk flows:
  - circular internal forward and recall
  - memo submit / return / sign
  - outgoing attachment handling
  - room booking approval and conflict check
  - vehicle assignment / approval / PDF generation

### C. Legacy extraction
- move room workflow logic from `src/Services/room` into `app/modules/room` incrementally
- move vehicle workflow logic from `src/Services/vehicle` into `app/modules/vehicle` incrementally
- keep route and response compatibility while doing this

### D. Repository and schema discipline
- keep file attachment lookups centralized
- keep soft-delete predicates consistent in repositories
- avoid controller-side ad hoc SQL growth

## 3. Rules for Future Refactors
- do not redesign UI unless explicitly requested
- do not break root entry routes
- refactor in thin vertical slices: route -> controller -> service/repository -> validation -> audit
- add manual verification notes for every risky workflow change

## 4. Done Criteria for a Hardened Module
A module can be considered stable when all of the following are true:
- controller is thin and deterministic
- business rules live in repository/service helpers
- state transitions are explicit
- file access is authorized centrally
- syntax + smoke checks pass
- there is a repeatable manual verification checklist
