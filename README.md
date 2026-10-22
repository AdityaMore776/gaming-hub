# Gaming Hub — Find Your Next Game

Gaming Hub is a game discovery and comparison website built for a college Web Technology course by a team of
four CSE students. It helps players answer three questions:

1. **What game should I play?** A seven-question quiz ranks every game by how well it fits you, and explains why.
2. **Which game suits me better?** Compare two or three games side by side, with a written verdict.
3. **Why should I play this?** Every game page lists reasons you might like it and things to consider.

Everything runs locally on PHP and MySQL. There are no external APIs, API keys, AI services or paid services.

---

## Features

| Area | What it does |
|---|---|
| **Home** | Search, featured games, "Discover by mood" shortcuts, Game DNA explainer, recent community reviews, top-rated games |
| **Game Explorer** | Search by title/studio; filter by genre, platform, players, difficulty, focus (story/combat/exploration), session length and minimum rating; sort; pagination. Results update instantly without reloading, and the URL stays shareable |
| **Game DNA** | Five 1–10 scores (story, combat, exploration, difficulty, replayability) per game, shown as bars and a radar chart. Labelled clearly as editorial estimates |
| **Comparison** | Select up to 3 games from any page (selection survives page changes), compare 15+ attributes in aligned rows, highlights only where differences are clear, rule-based written verdict, remove / replace / add games |
| **What Should I Play?** | 7-step quiz with "no preference" options and a "Surprise me" button. A transparent weighted score (weights add up to 100) ranks all games; the top 3 show match %, reasons and one drawback |
| **Why play this?** | On every game page: "You might like this game because…" and "Consider this before playing", built from stored attributes and your quiz answers |
| **Reviews** | 1–5 star ratings with text; one review per user per game; edit/delete your own; mark helpful (one vote per user); report; sort by newest / highest / most helpful |
| **Wishlist** | Save and remove games (no duplicates), view saved games, compare them |
| **Accounts** | Register, log in, log out, profile with favourite genres/platforms, saved quiz answers, your reviews and favourites, change password |
| **Admin panel** | Live statistics; add / edit / delete games; upload cover images; feature games; manage genres and platforms; view users; moderate reported reviews (hide, unhide, delete, dismiss) |

## Technology

* HTML5, CSS3, **Bootstrap 5.3.8** (official jsDelivr CDN), Google Fonts (Archivo, Figtree)
* **Vanilla JavaScript**: no frameworks
* **PHP** (developed and tested on **PHP 8.5.12**; the code uses nothing newer than PHP 8.1)
* **MySQL** (developed and tested on **MySQL 8.4.9 LTS**)
* PDO with prepared statements, PHP sessions, `password_hash()` / `password_verify()`, GD for image processing

No React/Vue/Angular, Node.js, Tailwind, Firebase/Supabase, game APIs or AI services are used.

---

## Setup

### 1. Install PHP and MySQL

**Option A — XAMPP (easiest on Windows/macOS/Linux)**

1. Download XAMPP from <https://www.apachefriends.org> (choose the newest PHP 8.x build) and install it.
2. Open the XAMPP Control Panel and start **Apache** and **MySQL**.

> XAMPP ships MariaDB instead of Oracle MySQL. The schema only uses standard features (foreign keys, CHECK
> constraints, a view), so it is expected to work, but our own testing was done with MySQL 8.4.9 and PHP 8.5.12.

**Option B — standalone PHP + MySQL**

Install PHP 8.1 or newer and MySQL 8.x, and make sure these PHP extensions are enabled in `php.ini`:
`pdo_mysql`, `mbstring`, `fileinfo`, `gd`.

Check your versions with `php -v` and `mysql --version`.

### 2. Put the project in place

* **XAMPP:** copy the `gaming-hub` folder into `C:\xampp\htdocs\` (or `/Applications/XAMPP/htdocs/` on macOS).
  The site will be at <http://localhost/gaming-hub/>.
* **PHP built-in server:** keep the folder anywhere (see step 5).

### 3. Create the database

The SQL file creates a database called `gaming_hub` with all tables, a view and the demo data.

* **phpMyAdmin:** open <http://localhost/phpmyadmin> → **Import** → choose `database/gaming_hub.sql` → **Go**.
* **Command line:**

  ```bash
  mysql -u root -p < database/gaming_hub.sql
  ```

⚠️ Importing again **drops and recreates** all Gaming Hub tables. That's useful for resetting the demo.

### 4. Configure the database connection

1. Copy `config/config.example.php` to `config/config.php`.
2. Edit `config/config.php` with your MySQL username and password.

If `config/config.php` does not exist, the site uses `config.example.php`, which matches a default XAMPP install
(user `root`, empty password). `config/config.php` is in `.gitignore`, so real passwords never reach Git, and the
`config/`, `includes/` and `database/` folders are blocked from web access.

For anything beyond your own computer, create a dedicated MySQL user with only the permissions the site needs:

```sql
CREATE USER 'gaminghub'@'localhost' IDENTIFIED BY 'choose-a-strong-password';
GRANT SELECT, INSERT, UPDATE, DELETE ON gaming_hub.* TO 'gaminghub'@'localhost';
```

### 5. Run it

* **XAMPP:** with Apache and MySQL running, open <http://localhost/gaming-hub/>.
* **PHP built-in server:** from the project folder run

  ```bash
  php -S localhost:8000 router.php
  ```

  and open <http://localhost:8000>. (`router.php` blocks the private folders, like `.htaccess` does for Apache.)

### 6. Administrator account

The demo database includes a development admin. To create your own admin (or promote an existing account),
use the command-line script; there is deliberately **no web form** that can create admins:

```bash
php database/create_admin.php yourname you@example.com
```

It asks for a password (min. 10 characters, with a letter and a number). If the email already exists, that
account becomes an admin and its password is reset.

### Demo accounts (development only)

| Role | Email | Username | Password |
|---|---|---|---|
| Admin | admin@gaminghub.local | admin | `Admin@Dev2026` |
| User with saved data | demo@gaminghub.local | demo_player | `Demo@2026` |
| Other reviewers | e.g. pixel@gaminghub.local | pixel_pilgrim, lanternfox, … | `Player@2026` |

**Change the admin password before putting the site anywhere public.** The admin dashboard shows a warning while
any admin still uses `Admin@Dev2026`. Change it at *Profile → Change password*, or re-run `create_admin.php`.

---

## How to test the main features

### Comparison system
1. Go to **Explore Games** and press **Compare** on two or three cards. A tray appears at the bottom of the screen and the navbar shows the count.
2. Try a fourth game: you'll see a message that the limit is 3. Pressing Compare again on a selected game removes it.
3. Open another page. The selection stays (it's saved in the browser's localStorage).
4. Press **Compare now**. Each game gets a column; values come from MySQL. "Highest" only appears when the
   difference is clear (2+ points on Game DNA, 0.3+ stars with at least 2 reviews each).
5. Read the **Quick verdict**, generated by rules in `assets/js/compare.js` from the values in the table.
6. Use **Remove**, **Replace with…** and **Add a third game**. Open `compare.php` with only one game to see the empty state.

### Recommendation quiz
1. Open **What Should I Play?** → **Start the quiz**. Clicking an answer moves to the next question.
2. After question 7 you get three ranked matches with match %, Game DNA, reasons and one drawback.
3. Open **How the match score works** to see the weights. Try answering "no preference" to everything: you get the
   highest-rated games instead, with no match %.
4. **Surprise me** picks random answers and shows the results.
5. Open any game page: it now shows "% match with your quiz answers" and personal reasons.
6. Logged in? Press **Save these preferences**. They are stored in MySQL and appear on your profile.

### Login, reviews and wishlist
1. **Register** a new account (try a weak password or mismatched passwords to see validation), or log in as `demo@gaminghub.local` / `Demo@2026`.
2. On a game page, press the heart. It saves without reloading. Check **Wishlist**.
3. Write a review. Try submitting without stars or with very short text to see validation. Post it, then edit it
   (the form changes to "Update review"), then delete it. The average rating and the distribution bars update.
4. On someone else's review, press **Helpful** (press again to undo) and **Report**.
5. Log in as admin → **Admin → Reviews & reports**. The seeded spam review on *Genshin Impact* has two open
   reports. **Hide** it and notice that Genshin Impact's rating changes, because hidden reviews don't count.

### Admin panel
* **Games → Add a game**: fill in the form, move the Game DNA sliders and optionally upload a JPG/PNG/WebP cover (max 2 MB).
* **Edit**, **Feature** and **Delete** (with a confirmation that says how many reviews will be deleted).
* **Genres & platforms**: add, rename, delete.
* Log in as a normal user and open `/admin/index.php`: you get *Admins only* (HTTP 403).

---

## Security measures

* **SQL injection:** every query that uses user input is a PDO prepared statement (`includes/db.php`); sort orders
  and filters are picked from fixed allow-lists.
* **XSS:** all output goes through `e()` (`htmlspecialchars`); JSON for scripts is encoded with `JSON_HEX_*` flags.
* **Passwords:** `password_hash()` (bcrypt) and `password_verify()`; hashes upgrade automatically if PHP's default changes.
* **Sessions:** HttpOnly + SameSite=Lax cookie, strict mode, `session_regenerate_id()` on login and password change.
* **CSRF:** every state-changing form and fetch request carries a per-session token checked with `hash_equals()`.
  Logout is POST-only for the same reason.
* **Authorization:** `require_login()` / `require_admin()` run on the server for every protected page and action.
  Review edits/deletes include `AND user_id = ?`, so users can only change their own.
* **Duplicates:** primary keys / unique keys on wishlists, review votes, reports and one-review-per-user-per-game,
  plus server-side checks.
* **Uploads:** size limit, real MIME type from file contents, dimension check, then **re-encoded with GD** into a
  new JPEG with a random name. `uploads/` cannot run scripts (`.htaccess` and `router.php`).
* **Redirects:** "return to" targets must be local paths (no open redirects).
* **Errors:** users see a friendly page; technical details only appear when `debug` is `true` in the config. Set
  it to `false` outside development.

### Known limitations
* Login throttling is basic (a short delay after 5 failed attempts in the same session). There is no
  site-wide rate limit.
* No "forgot password" email, because the project has no mail server. Admins can reset a password with
  `create_admin.php`.
* Email addresses can't be changed after registration.
* Platforms are stored as families (PC, PlayStation, Xbox, Nintendo Switch, Mobile), not individual console
  generations.

---

## About the data

* **Games:** 50 real games across 15 genres. Titles, studios, years and platforms come from our own seed data
  (no APIs). Descriptions, pros and cons were written by the team.
* **Game DNA scores and completion times are editorial estimates**, not official statistics.
* **Cover art is generated** by `includes/cover_art.php` from simple shapes (no publisher box art). Admins can upload their own images.
* **Demo reviews and accounts are sample data** written by the team. Ratings are always calculated from the
  `reviews` table (via the `v_game_ratings` view), never hard-coded.

To change the seed data, edit `database/seed_data.php` and run `php database/build_sql.php` to regenerate `gaming_hub.sql`.

## Project structure

```
gaming-hub/
├── index.php, explore.php, game.php, compare.php, recommend.php,
│   wishlist.php, profile.php, reviews.php, about.php, login.php, register.php
├── cover.php              generated SVG cover art
├── router.php             router for `php -S` (blocks private folders)
├── actions/               POST handlers: wishlist, review, preferences, logout
├── admin/                 index (dashboard), games, game_edit, taxonomy, users, reviews
├── includes/              init, db, auth, functions, header, navbar, footer, admin helpers, cover_art
├── config/                config.example.php (copy to config.php)
├── assets/css/style.css   design system on top of Bootstrap
├── assets/js/             main.js (tray, wishlist, explorer), gamelogic.js (scoring rules),
│                          recommend.js (quiz), compare.js (verdict), game.js, admin.js
├── database/              gaming_hub.sql, seed_data.php, build_sql.php, create_admin.php
└── uploads/covers/        uploaded cover images
```

### Database tables
`users`, `genres`, `platforms`, `games`, `game_attributes` (Game DNA), `game_genres`, `game_platforms`,
`reviews`, `review_votes`, `review_reports`, `wishlists`, `user_preferences` (quiz answers), `user_genres`,
`user_platforms`, and the view `v_game_ratings`.

---

## Common problems

| Problem | Fix |
|---|---|
| "Something went wrong… Could not connect to the database" | MySQL isn't running, or the details in `config/config.php` are wrong. Check host, port, user and password. |
| `Unknown database 'gaming_hub'` | Import `database/gaming_hub.sql` first. |
| `could not find driver` | Enable `extension=pdo_mysql` in `php.ini` and restart Apache / the PHP server. |
| Page shows PHP code instead of the website | You opened the file directly (`file://…`). Use `http://localhost/...` through Apache or `php -S`. |
| Styles or links broken when the project is in a sub-folder | Set `'base_url' => '/your-folder-name'` in `config/config.php`. |
| Cover upload: "This server cannot process that image type" | Enable `extension=gd` in `php.ini`. |
| Cover upload fails silently | Check `upload_max_filesize` (≥ 2M) and `post_max_size` in `php.ini`, and that `uploads/covers` is writable. |
| "Your session expired" after submitting a form | The page was open a long time or you logged out in another tab. Reload and try again. |
| Port 3306 or 80 already in use | Stop the other MySQL/web server, or change the port in XAMPP and in `config/config.php`. |
| Import error about the view or `CHECK` | Use MySQL 8.0.16+ or MariaDB 10.2+. |

---

*Gaming Hub is an independent student project and is not affiliated with any game publisher, developer or
platform holder. Game titles are trademarks of their respective owners.*
