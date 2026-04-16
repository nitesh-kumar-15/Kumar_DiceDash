# DiceDash (Project 2 — Topic 03: Adventures of the Dice)

PHP + HTML5 + CSS3 + Scrum

This project implements a Snakes & Ladders-style race to cell `100`, using **server-side PHP** for game logic and **PHP Sessions** for authentication and a temporary leaderboard.

## Live URL / CODD
- Live app: `https://codd.cs.gsu.edu/~nkumar13/wp/pw/project2/index.php`
- The app is deployable as a standard PHP site with no database setup required.

## Local run (MAMP / XAMPP)
1. Clone/download this repository.
2. Place the repository contents in your local PHP server document root.
3. Ensure the web root points to the folder containing `index.php`, `login.php`, `game.php`, and `leaderboard.php`.
4. Ensure PHP sessions are enabled.
5. Start Apache.
6. Visit:
   - `http://localhost/<your-site>/index.php`

## What’s included
- `index.php` landing + redirect if already logged in
- `register.php` account creation (POST + validation + hashed passwords)
- `login.php` authentication (POST + password_verify + session start)
- `game.php` protected dice game (100-cell board rendered by PHP)
- `leaderboard.php` session leaderboard + event recap
- `logout.php` session destruction
- `bootstrap.php` shared helpers (sessions + redirects + input helpers)
- `auth.php` user storage + login/register logic (flat-file JSON, no DB)
- `game_logic.php` board logic + dynamic cell events + leaderboard logic
- `css/style.css` responsive styling (no JS logic)

## Topic 03: required mechanics
- Dice roll uses PHP `rand(1,6)` on the server (POST driven)
- Snakes & ladders are defined per difficulty and applied server-side
- Board is generated dynamically for a 100-cell grid
- Win condition triggers redirect to `leaderboard.php`

## Undergrad additional features: Dynamic Cell Events (3 of 4, implemented as 4)
This implementation includes all UG Dynamic Cell Events components (1–4), covering:
1. `$events_by_cell` map for specific board cells with event types + story messages
2. Deterministic event engine using a turn-based seed and storing the chosen event in `$_SESSION['last_event']`
3. Narrator story text after each roll/event
4. `$_SESSION['events_log']` + game-end "Adventure Recap" on the leaderboard

## No database / no JS logic
- No MySQL/PDO usage.
- No application logic in JavaScript. (This repo intentionally contains no `.js` files and no `<script>` tags in the app pages.)

## Rubric / prior-work notes
See [`docs/RUBIX_PRIOR_WORK.md`](docs/RUBIX_PRIOR_WORK.md).

## Scrum evidence
See [`docs/DEV_JOURNAL.md`](docs/DEV_JOURNAL.md).
