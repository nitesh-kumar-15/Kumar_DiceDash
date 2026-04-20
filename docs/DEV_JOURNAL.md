# DiceDash Development Journal

Project: DiceDash (Project 2 - Topic 03: Adventures of the Dice)  
Course: CSC 4370/6370  
Author: Nitesh Kumar  
Sprint Board: <https://www.notion.so/Project-2-Sprint-Board-33ace26d33af80398d73c1a0b227fd86?source=copy_link>

## 1) Project approach

I implemented DiceDash as a server-rendered PHP application with HTML/CSS and PHP sessions. I intentionally avoided JavaScript game logic and database usage to match Topic 03 and rubric constraints.  

Primary architectural decisions:
- Keep gameplay state in `$_SESSION` for predictable server-side flow.
- Store user accounts in `data/users.json` with hashed passwords.
- Split code into focused files (`bootstrap.php`, `auth.php`, `game_logic.php`, route pages) so responsibilities are clear.

## 2) Sprint structure and Scrum usage

I used a simple Scrum workflow with backlog grooming, short implementation cycles, and periodic review:
- **Backlog:** feature cards grouped by rubric area (auth, game loop, leaderboard, additional features, docs, QA).
- **In Progress:** one to two active tasks at a time to avoid context switching.
- **Review/Verification:** manual validation on local server and CODD after each major change.
- **Done:** moved only after successful run-through of linked user flow.

For a solo project, "standups" were daily self-checks recorded in sprint notes:
- What was completed since last session.
- What is next.
- Any blocker/risk and mitigation.

## 3) Sprint log (summary)

### Sprint 1 - Foundation and auth flow
**Goal:** Build stable app skeleton and account system.

Completed:
- Project skeleton and shared bootstrap/session helpers.
- Registration and login handlers (`POST` only) with server-side validation.
- Password hashing and verification.
- Route protection and logout session destruction.

Evidence:
- Working route flow: `index.php` -> `register.php`/`login.php` -> `game.php`.
- User records persisted to `data/users.json`.

Retrospective:
- Centralizing helpers in `bootstrap.php` reduced duplication quickly.
- Early session guard implementation prevented later access-control bugs.

### Sprint 2 - Core gameplay and board
**Goal:** Complete Topic 03 base game loop.

Completed:
- 100-cell board rendering and serpentine numbering.
- Difficulty profiles in `config.php` with snakes/ladders maps.
- Dice roll processing with `rand(1,6)` on the server.
- Win handling and redirect to leaderboard.

Evidence:
- End-to-end play from start to win in a single session.
- Consistent move counting and turn progression.

Retrospective:
- Keeping all move calculations in `game_logic.php` made behavior easier to test.
- Session reset logic was needed to avoid stale state between runs.

### Sprint 3 - Additional features and UX polish
**Goal:** Implement UG dynamic event extension and improve clarity/usability.

Completed:
- Dynamic Cell Events map by board cell with typed outcomes.
- Deterministic event variant selection for reproducible behavior.
- AI narrator updates after rolls/events.
- Event logging and Adventure Recap table on leaderboard.
- Added 2-player and vs CPU modes (server-driven flow).

Evidence:
- `$_SESSION['last_event']` updated after triggers.
- `$_SESSION['events_log']` rendered on leaderboard recap section.

Retrospective:
- Deterministic seeding made debugging much easier for event outcomes.
- Introducing CPU mode required careful sequencing to avoid confusing turn state.

### Sprint 4 - Stabilization, bug fixes, docs, and submission prep
**Goal:** Eliminate regressions and align with rubric + delivery expectations.

Completed:
- Stabilized auth/login/register error handling and final edge-case behavior.
- Fixed extra output/tag artifacts and cleanup in game page rendering.
- Corrected play-again/new-run behavior after win (`game.php?new=1` flow).
- Updated README with CODD URL, setup instructions, and constraints.
- Prepared presentation notes, QA mapping, and submission links file.

Evidence:
- Successful complete flow on CODD: register/login -> play -> win -> leaderboard -> play again.
- No JavaScript game logic and no database code in app layer.

Retrospective:
- Most defects came from state transitions, not single-page rendering.
- Explicitly testing "edge route access" and "post-win replay path" caught key issues.

## 4) Key blockers and resolutions

1. **Session-state edge cases after win**  
   - Issue: replay/navigation behavior could loop incorrectly after a finished game.  
   - Resolution: enforce `just_won` redirect with explicit `new=1` override path and state reset.

2. **Auth error variable handling**  
   - Issue: by-reference error variables were not consistently initialized before function calls.  
   - Resolution: initialize variables early and keep error flow explicit in auth pages.

3. **No-JS UX constraints for CPU turn sequencing**  
   - Issue: had to provide understandable AI-turn progression without client-side scripts.  
   - Resolution: staged server redirects/meta refresh style transitions with clear state flags.

## 5) Quality checks performed

- Manual checks for invalid login, duplicate username, and missing fields.
- CSRF token verification path tested on all state-changing forms.
- Protected-route checks while logged out (`game.php`, `leaderboard.php`).
- Session persistence checks across roll history, narrator updates, and leaderboard entries.
- Responsive behavior spot-checks on multiple viewport sizes.

## 6) Commit evidence map (GitHub traceability)

To make sprint evidence easy to verify during grading, this is the direct commit-to-sprint mapping from the main branch:

- **Sprint 1 (foundation + auth + security):**
  - `99f4a1f` - bootstrap php session + shared helpers
  - `c48f76a` - register + login + logout with flat-file users
  - `b7b05be` - csrf tokens + input sanitization + output escaping

- **Sprint 2 (core game + leaderboard):**
  - `140ac94` - 100-cell board rendering + difficulty presets
  - `3e37b09` - post-driven dice roll + snakes/ladders + win redirect
  - `46dace7` - session leaderboard sorting + recap

- **Sprint 3 (UG events + modes + UI polish):**
  - `f5b4b4d` - dynamic cell events map + deterministic selection
  - `928e31d` - narrator updates + events_log
  - `37afe3a` - two-player turn tracking
  - `ad30d2c` - vs cpu flow + staged cpu turn handling
  - `a17a2ed` - play again / nav redirects with just_won state
  - `cb4fd1b` - board grid layout + sidebar spacing polish
  - `27aaaf1` - prevent grid stretch whitespace in board layout

- **Sprint 4 (docs + final polish + submission prep):**
  - `5cb77de` - rubix prior-work notes + dev journal sprint evidence
  - `d0ea7e4` - finalize readme + deployment url
  - `7978eef` - update README
  - `25a4401` - improve code comments

## 7) Final reflection

This project reinforced core server-side web engineering practices: request validation, session state management, secure auth handling, and clean separation of concerns. The most valuable technical lesson was that state-transition correctness matters more than individual page correctness in multi-step web apps.

From a process perspective, the backlog + sprint review rhythm helped keep development aligned to rubric outcomes while still allowing room for additional features and polish.
