# BeBot

[![CI](https://github.com/zznathans/BeBot/actions/workflows/ci.yml/badge.svg)](https://github.com/zznathans/BeBot/actions/workflows/ci.yml)
[![Release](https://img.shields.io/github/v/release/zznathans/BeBot)](https://github.com/zznathans/BeBot/releases)
[![License](https://img.shields.io/badge/license-GPL--2.0-blue)](Licence.txt)

BeBot is a chat automaton for **Anarchy Online** (AO) and **Age of Conan**
(AoC): guild/raid bots, relaying, and a large module system for automating
things a guild/raid leader would otherwise do by hand.

This is a fork of [J-Soft/BeBot](https://github.com/J-Soft/BeBot) with
custom additions on top of upstream, most notably:

- **`Market`** ([`Modules/Ao/Market.php`](Modules/Ao/Market.php)) - live GMI
  price search, per-item price history, personal watchlists with
  price/QL-range alert filters, and automatic tracking of the most
  actively-traded items (sourced from ao-stonks.com).
- **Redis-backed caching** ([`Sources/Redis.php`](Sources/Redis.php)) -
  optional, fails soft if unconfigured/unreachable.
- **Structured (JSON) logging** - see [`Sources/Log/`](Sources/Log/).

Upstream documentation still applies for everything else: see the
[BeBot wiki](http://wiki.bebot.link) for module reference, command syntax,
and general usage.

## How this fork ships

Unlike upstream, this repo isn't installed by downloading a zip - it's built
and versioned automatically:

```
PR merges to main
  -> CI (PHP lint + PHPUnit across supported PHP versions)
  -> semantic-release cuts a version tag + GitHub Release
  -> a PR automatically opens against zznathans/bebot-helm bumping its
     vendored copy of this repo to the new release
```

Commit messages follow [Conventional Commits](https://www.conventionalcommits.org/)
(`feat:`, `fix:`, etc.) - that's what drives versioning, so please keep using
that format in PR titles/commits.

## Running BeBot

**Recommended: Kubernetes, via [bebot-helm](https://github.com/zznathans/bebot-helm).**
That chart builds a Docker image from this repo (as a git submodule) on
every release and publishes a Helm chart for deploying it - see that repo's
README for values/setup.

**Manual / local install** is also fully supported (e.g. for local
development against a throwaway database - no Docker/Kubernetes required)
and documented below.

### Prerequisites

- Anarchy Online (free or paid) or Age of Conan
- PHP 7.1 - 8.5 (older versions crash on startup)
- MySQL (4.1 - 8.0) or MariaDB (10.0 - 11.2)
- `git clone https://github.com/zznathans/BeBot.git` (or grab a
  [release tarball](https://github.com/zznathans/BeBot/releases))

Optional: an always-on connection and a dedicated machine to run the bot on.

### Create a player

A bot is just a regular player character - start AO/AoC and create (or
reuse) a character on the dimension/server you want to run on. Breed,
gender, profession/class/race are irrelevant to the bot's function. The
character's name is the bot's name.

- **Guild bot**: must be a member of the guild (same faction, for AO).
- **Towers (AO only)**: for tower-war tracking to work, the bot needs to be
  in the org's top three ranks - the `[ALL TOWERS]` channel is restricted to
  those ranks.

### Configuring the bot

#### `StartBot.php`

Set `$php_bin` to your PHP binary's path (just the name, e.g. `php`, if
you're running from the same directory as the binary). Set `$main_php` to
the path of `Main.php` similarly.

On Linux, install PHP with the extensions BeBot needs:

```sh
apt install phpX.Y phpX.Y-mysqli phpX.Y-bcmath phpX.Y-curl phpX.Y-mbstring
# (X.Y = your target PHP version, e.g. 8.3)
```

Required extensions either way: `curl`, `mbstring`, `mysqli`, `openssl`,
`pdo_mysql`, `sockets`. The bot will tell you what's missing at startup -
check the console output.

Set a sane `memory_limit` in `php.ini` (e.g. `50M`) - raise it if you see
"exhausted allowed memory size" errors.

#### `Conf/Bot.conf`

Copy `Conf/Bot.conf.dist` to `Conf/Bot.conf` and fill in:

- AO/AoC username, password, bot name, and dimension (AO) or server (AoC).
- `$owner` - the super-admin character. Prefer adding further admins
  in-game via `!adduser <name> SUPERADMIN` over hardcoding many here.
- `$other_bots` - other bots the guild/raid uses, so this bot ignores their
  tells instead of spam-warring with them.
- `$log` - `off` / `chat` (default) / `all`, plus `$log_path`.
- `$command_prefix` - a regex-escaped prefix (e.g. `\.` for `.`, since `.`
  is a regex metacharacter).

**Guild bot**: set `$guildbot = true`, `$guild_name`, and `$guild_id` (found
via the guild's `people.anarchy-online.com` roster page -> "XML version of
this membership roster"). Set `$guild_relay_target = False;` (unquoted) to
disable inter-bot relay, or a bot name to enable it.

**Raid bot**: set `$guildbot = false`, leave `$guild_name` blank,
`$guild_id = 0`.

#### `Conf/Mysql.conf`

Copy `Conf/Mysql.conf.dist` to `Conf/Mysql.conf` and set the MySQL/MariaDB
username, password, host, and database name (see below).

### Setting up the database

```sql
SET GLOBAL default_storage_engine = 'InnoDB';
CREATE DATABASE databasename CHARACTER SET utf8;
CREATE USER username@localhost;
SET PASSWORD FOR username@localhost = PASSWORD("newpassword");
GRANT ALL ON databasename.* TO username@localhost;
```

Then in `Conf/Mysql.conf`:

```php
$dbase = "databasename";
$user = "username";
$pass = "newpassword";
$server = "localhost";
```

**Backup**: `mysqldump -u username -p --databases databasename --add-drop-table -a -f > filename.sql`

**Restore**: `mysql -u username -p databasename < filename.sql` (this
overwrites any changes made since the backup).

### Starting the bot

```sh
php StartBot.php
```

(or `StartBot.bat` on Windows). Once it's connected, log on with the
character configured as `owner`/`superadmin` to start controlling it.

### Frozen account recovery (AO only)

If an AO account ends up frozen, either fix it manually at
[account.anarchy-online.com](https://account.anarchy-online.com/), or set up
self-defreeze: copy `Conf/Aoaccount.ini` to `Conf/<youraccountname>.ini`
(lowercase) and set `$ao_account_pass`. The bot will detect a frozen login,
fix it, and restart automatically (limited to 5 attempts per 24h per
machine). Force a check by creating an empty `.defreeze` file at the repo
root.

## In-game setup

Most modules are configured via the settings module: `!settings` lists
configurable modules, `!settings <module>` opens one (e.g.
`!settings security`, `!settings market`).

## Adding/removing modules

Prefix a file in `Modules/` with `_` to disable it (see `_ExampleModule.php`
for a module template). Put third-party modules in `Custom/` instead of
`Modules/`/`Core/` - that directory is never touched by upstream updates.

## Bypassing the 1,000-friendlist limit

Any character is capped at 1,000 buddies - a problem for large guild/raid
bots. Both games support chaining "slave" accounts to redistribute the
list; see the upstream wiki for full setup:
[AO chat proxy](https://wiki.bebot.link/index.php/Docker) (via
[bitnykk/aochatproxy2](https://github.com/bitnykk/aochatproxy2)) or the AoC
`$slave`-chain approach (`!set Online OtherBots ...` + `!adduser ... leader`
chain).

## External relays

- **IRC / Twitch** (Twitch chat is IRC-compatible): `!settings irc`, then
  `!irc connect`. See `!help irc`.
- **Discord**: create an application + bot at
  [discord.com/developers/applications](https://discord.com/developers/applications),
  enable "Message Content Intent", invite it to your server, then
  `!settings discord` (Bot Token + Channel ID) and `!discord connect`. See
  `!help discord`.

## License

GPLv2 - see [`Licence.txt`](Licence.txt) and [`Credits.txt`](Credits.txt).
