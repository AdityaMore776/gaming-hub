# Run Gaming Hub on your computer with XAMPP

This guide takes you from a fresh Windows PC to Gaming Hub running at **http://localhost/gaming-hub/**.
It takes about 15 minutes. You don't need to know PHP or MySQL to follow it.

> ✅ **Tested** on **XAMPP 8.2.12** for Windows (Apache 2.4.58, PHP 8.2.12, MariaDB 10.4.32): every page,
> login, registration, reviews, wishlist, comparison, quiz, admin panel and cover upload.

---

## What you need

| Item | Details |
|---|---|
| A Windows 10 or 11 PC | macOS/Linux work too; folder paths differ (see the end of this guide) |
| About 1 GB of free disk space | XAMPP is ~800 MB once installed |
| **XAMPP 8.2.12** | Free. Includes Apache (web server), PHP 8.2, MariaDB (MySQL) and phpMyAdmin |
| The Gaming Hub files | From this GitHub repository |
| A web browser | Chrome, Edge or Firefox |
| Internet connection while browsing | The pages load Bootstrap and Google Fonts from their CDNs. Everything else is local |

> **Which XAMPP version?** Use **8.2.12**. Gaming Hub needs **PHP 8.1 or newer**: XAMPP 8.1.25 also works,
> **XAMPP 8.0.x and older will not**.

---

## Step 1 — Install XAMPP

1. Download the installer: <https://www.apachefriends.org/download.html> → **XAMPP for Windows 8.2.12**.
2. Run the installer.
   * If Windows warns about **User Account Control (UAC)**, click OK. It's a reminder not to install into
     `C:\Program Files`.
   * On **Select Components**, keep **Apache**, **MySQL**, **PHP** and **phpMyAdmin** ticked.
     The others (FileZilla, Mercury, Tomcat, Perl, Webalizer, Fake Sendmail) are not needed.
   * Install folder: keep **`C:\xampp`**.
   * Allow Apache through **Windows Defender Firewall** if asked (private networks are enough).
3. When it finishes, open the **XAMPP Control Panel**.

## Step 2 — Start Apache and MySQL

In the XAMPP Control Panel, click **Start** next to **Apache** and next to **MySQL**.
Both module names should turn **green** and show port numbers (Apache `80, 443`, MySQL `3306`).

Check it worked: open <http://localhost/> in your browser. You should see the XAMPP dashboard.

> Red text or a module that stops straight away? See [Troubleshooting](#troubleshooting).

## Step 3 — Turn on the GD image extension

XAMPP ships with PHP's **GD** image library switched **off**. Gaming Hub needs it for one thing: uploading
cover images in the admin panel. Everything else works without it, but switch it on now so the demo works fully.

1. In the XAMPP Control Panel, on the **Apache** row, click **Config** → **PHP (php.ini)**. The file opens in Notepad.
2. Press **Ctrl + F** and search for `;extension=gd`
3. Delete the semicolon at the start of the line so it reads exactly:

   ```ini
   extension=gd
   ```

4. Save the file (**Ctrl + S**) and close Notepad.
5. In the Control Panel, click **Stop** and then **Start** on **Apache**.

The other extensions Gaming Hub uses (`pdo_mysql`, `mbstring`, `fileinfo`) are already on in XAMPP.

## Step 4 — Put the Gaming Hub files in `htdocs`

`C:\xampp\htdocs` is the folder Apache serves websites from.

**Option A — Download ZIP (no Git needed)**

1. On the GitHub page of this repository, click the green **Code** button → **Download ZIP**.
2. Extract the ZIP. You get a folder called **`gaming-hub-main`**.
3. **Rename** it to **`gaming-hub`**.
4. Move it into `C:\xampp\htdocs\`.

**Option B — Git**

```powershell
cd C:\xampp\htdocs
git clone https://github.com/AdityaMore776/gaming-hub.git
```

**Check the folder is in the right place.** This file must exist:

```
C:\xampp\htdocs\gaming-hub\index.php
```

If you see `C:\xampp\htdocs\gaming-hub\gaming-hub-main\index.php` instead, the folder is nested one level too
deep: move the inner folder's contents up.

> If you open <http://localhost/gaming-hub/> now, you'll see *"Unknown database 'gaming_hub'"*. That's
> expected: the next step creates the database.

## Step 5 — Create the database (phpMyAdmin)

1. Open <http://localhost/phpmyadmin/>.
2. Click the **Import** tab at the top. (Don't create a database first; the file does that.)
3. Under **File to import**, click **Choose File** and select
   `C:\xampp\htdocs\gaming-hub\database\gaming_hub.sql`.
4. Leave every other option as it is and click **Import** (bottom of the page).
5. You should see a green message ending in *"Import has been successfully finished"*.
6. In the left sidebar, click **gaming_hub**. You should see **14 tables** (`games`, `reviews`, `users`, …) plus
   one view, **`v_game_ratings`** (15 items in total).

**Prefer the command line?** In the XAMPP Control Panel click **Shell**, then run:

```bash
mysql -u root < C:\xampp\htdocs\gaming-hub\database\gaming_hub.sql
```

## Step 6 — Database settings (usually nothing to do)

Gaming Hub's default settings match a fresh XAMPP install: user **`root`**, **no password**, database
**`gaming_hub`** on `127.0.0.1:3306`. If you never changed XAMPP's MySQL password, **skip this step**.

If you did set a MySQL password (or use a different port):

1. In `C:\xampp\htdocs\gaming-hub\config\`, copy **`config.example.php`** and name the copy **`config.php`**.
2. Open `config.php` in a text editor (Notepad or VS Code) and change `db_user` / `db_pass` / `db_port`.

`config.php` is ignored by Git, so your password is never committed.

## Step 7 — Open the website

Go to **<http://localhost/gaming-hub/>**.

Log in with a demo account (Log in, top right):

| Role | Email | Password |
|---|---|---|
| Admin | `admin@gaminghub.local` | `Admin@Dev2026` |
| User with a wishlist, reviews and saved quiz answers | `demo@gaminghub.local` | `Demo@2026` |
| Other demo reviewers (e.g. `pixel@gaminghub.local`) | see README | `Player@2026` |

You can also click **Sign up** and create your own account.

The admin panel is at <http://localhost/gaming-hub/admin/> (admin accounts only).

## Step 8 (optional) — Create your own admin account

Admins can't be created from the website, on purpose. In the XAMPP Control Panel click **Shell**, then run:

```bash
cd C:\xampp\htdocs\gaming-hub
php database\create_admin.php yourname you@example.com
```

Type a password when asked (at least 10 characters, including a letter and a number). If the email already
belongs to an account, that account becomes an admin.

---

## Quick demo checklist

Use this before a presentation to check everything works:

- [ ] Home page shows featured games and recent reviews
- [ ] **Explore Games**: change a filter and the results update at once
- [ ] Press **Compare** on 3 games → **Compare now** → table and *Quick verdict* appear
- [ ] **What Should I Play?** → answer 7 questions → 3 matches with reasons
- [ ] Log in as `demo@gaminghub.local` → heart a game → it appears in **Wishlist**
- [ ] Write, edit and delete a review on any game page
- [ ] Log in as admin → **Admin → Reviews & reports** → hide the reported spam review on *Genshin Impact*
- [ ] Admin → **Games → Add a game** with a cover image (needs Step 3)

## Everyday use

* **Start:** open the XAMPP Control Panel → Start **Apache** and **MySQL** → visit <http://localhost/gaming-hub/>.
* **Stop:** click **Stop** on both, then close the Control Panel. (Always stop MySQL before shutting down the PC.)
* **Reset the demo data** (undo test reviews, wishlists and added games): import `database/gaming_hub.sql`
  again (Step 5). This **deletes all Gaming Hub data** and restores the original demo data. Uploaded cover images
  stay in `uploads/covers/` and can be deleted by hand.
* **Each team member's database is separate.** Reviews or games added on one PC don't appear on another. To share
  changes to the starting data, edit `database/seed_data.php`, run `php database\build_sql.php` in the XAMPP
  Shell (from the `gaming-hub` folder), and commit the regenerated `gaming_hub.sql`.

---

## Troubleshooting

| What you see | What to do |
|---|---|
| **Apache won't start**, "Port 80 in use by…" | Another program is using port 80 (often IIS, Skype or another web server). Close it, or in XAMPP click Apache **Config → Apache (httpd.conf)**, change `Listen 80` to `Listen 8080`, restart Apache, and use <http://localhost:8080/gaming-hub/>. |
| **MySQL won't start**, "Port 3306 in use" | Another MySQL/MariaDB is running (for example from MySQL Installer). Stop it in *Services* (`services.msc`), or change XAMPP's port in MySQL **Config → my.ini** (`port=3307`) and set `'db_port' => 3307` in `config/config.php` (Step 6). |
| **MySQL shuts down unexpectedly** right after starting | Usually caused by closing XAMPP or the PC without stopping MySQL. A common fix: stop everything, back up `C:\xampp\mysql\data`, then copy the contents of `C:\xampp\mysql\backup\` into `C:\xampp\mysql\data\` **except** `ibdata1`, and start MySQL again. Re-import the database (Step 5) if needed. |
| **"Unknown database 'gaming_hub'"** | The import (Step 5) hasn't run or failed. Do it again. |
| **"Something went wrong … Could not connect to the database"** | MySQL isn't started (is it green in the Control Panel?), or your MySQL password isn't empty: see Step 6. |
| **"Not Found"** at `localhost/gaming-hub` | The folder isn't at `C:\xampp\htdocs\gaming-hub\index.php` (Step 4), or it's still named `gaming-hub-main`. |
| **The page shows PHP code** | You opened the file by double-clicking it. Always use `http://localhost/gaming-hub/` in the browser. |
| **Page looks unstyled** (plain text, no colours) | Bootstrap loads from the internet. Check your connection and refresh. |
| Cover upload says **"Enable the GD extension in php.ini"** | Do Step 3, and remember to restart Apache afterwards. |
| **Cover upload rejected** | Use a JPG, PNG or WebP image under 2 MB, at least 150 × 150 px. |
| **phpMyAdmin import error** | Make sure you chose `gaming_hub.sql` (not `seed_data.php`) and left the format as **SQL**. |
| **"Your session expired"** after submitting a form | The page was open a long time, or you logged in/out in another tab. Refresh and try again. |
| `php` is not recognised in a normal Command Prompt | Use the **Shell** button in the XAMPP Control Panel, or run `C:\xampp\php\php.exe` with the full path. |

## macOS and Linux

* macOS: install XAMPP from the same download page; the web folder is `/Applications/XAMPP/htdocs/`. Start
  Apache and MySQL in the *manager-osx* app (*Manage Servers* tab). php.ini is in `/Applications/XAMPP/etc/`.
* Linux: the folder is `/opt/lampp/htdocs/`; start with `sudo /opt/lampp/lampp start`. php.ini is
  `/opt/lampp/etc/php.ini`. Make the upload folder writable: `chmod 775 uploads/covers`.
* Everything else (phpMyAdmin import, URL, demo logins) is the same.

## Before showing the site to anyone outside your PC

* Change the admin password (*Profile → Change password*). The admin dashboard warns you until you do.
* Set `'debug' => false` in `config/config.php` so error details aren't shown.
* XAMPP itself is a development tool and isn't meant for public hosting.
