## What did we do

- Created an array of database credentials instead of using define's from wp-config.php, added a hook to change it.
- Modifed the job runner to minimize database connections. The runner now waits until workers are available.
- Modified the construct of the Runner. Hooks are now loaded first, to allow for max workers hook.
- Removed the out of workers message.
- Minimized and humanized the log output, added a debug mode to show all the expanded information.

## Hooks added
- `Runner.initialize.options` - Modify an options array. Used to modify the maximum number of workers and active the debug mode. Has 2 keys: `max_workers` => int (default 4), `debug_mode` => bool (default false).
- `Runner.connect_to_db.credentials` - Modify a database credentials array. Has 2 keys: `username` => string, `password` => string.

## Planned
- Include another file besides wp-config.php to leave the wp-config.php native.
- Modify the Cavalcade plugin to override all wordpress cron functions.
- Modify the linux service and error handling to minimize interaction with the runner

## Forked from
- [humanmade/Cavalcade-Runner](https://github.com/humanmade/Cavalcade-Runner) - Daemon for Cavalcade, a scalable WordPress jobs system. https://engineering.hmn.md/projects/cavalcade
