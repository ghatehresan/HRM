<?php

// M1: the schedule is intentionally empty — jobs arrive in M10.
// Per AUDIT.md §22.5 (X-2) the scheduler MUST be dual-mode:
//
//   1) system cron where available;
//   2) a token-protected HTTP route otherwise (shared hosts without cron/SSH).
//
// Every scheduled job MUST be idempotent.
