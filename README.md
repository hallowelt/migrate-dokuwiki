# Migrate Dokuwiki data to MediaWiki import data

This is a command line tool to convert the contents of a Dokuwiki into a MediaWiki import data format.

## Prerequisites
1. PHP >= 8.2 with the `xml` extension must be installed
2. `pandoc` >= 3.1.6. The `pandoc` tool must be installed and available in the `PATH` (https://pandoc.org/installing.html).

## Installation
1. Download `migrate-dokuwiki.phar` from https://github.com/hallowelt/migrate-dokuwiki/releases/latest/download/migrate-dokuwiki.phar
2. Make sure the file is executable. E.g. by running `chmod +x migrate-dokuwiki.phar`
3. Move `migrate-dokuwiki.phar` to `/usr/local/bin/migrate-dokuwiki` (or somewhere else in the `PATH`)

## Workflow
### Prepare migration
1. Create a directory for the migration (e.g. `/tmp/migration/workspace/` ).
2. Create a directory for the migration (e.g. `/tmp/migration/workspace/input` ).
3. Copy the `data` directory from dokuwiki to `/tmp/migration/workspace/input`.
4. Remove unused directories before copying. Only `attic`,`media`, `media-attic`, `media-meta`, `meta`, `pages`, can be used for migration. `attic` and `media-attic` contain older versions and are not required for migration.
5. If attic versions should be migrated all archived versions have to be extracted (e.g. `find . -name "*.gz" -exec gunzip {} \;`  on linux systems).

### Migrate the contents
1. Create the "workspace" directory (e.g. `/tmp/migration/workspace/` )
2. From the parent directory (e.g. `/tmp/migration/` ), run the migration commands
	1. Run `migrate-dokuwiki analyze --src input/ --dest workspace/` to create "working files". After the script has run you can check those files and maybe apply changes if required (e.g. when applying structural changes).
	2. Run `migrate-dokuwiki extract --src input/ --dest workspace/` to extract all contents, like wikipage contents, attachments and images into the workspace
	3. Run `migrate-dokuwiki convert --src workspace/ --dest workspace/` (yes, `--src workspace/` ) to convert the wikipage contents from Confluence Storage XML to MediaWiki WikiText
	4. Run `migrate-dokuwiki compose --src workspace/ --dest workspace/` (yes, `--src workspace/` ) to create importable data

If you re-run the scripts you will need to clean up the "workspace" directory!

### Import into MediaWiki
1. Copy the diretory "workspace/result" directory (e.g. `/tmp/migration/workspace/result/`) to your target wiki server (e.g. `/tmp/result`)
2. Go to your MediaWiki installation directory
3. Make sure you have the target namespaces set up properly. See `workspace/namespaces-map.php` for reference.
4. Make sure [$wgFileExtensions](https://www.mediawiki.org/wiki/Manual:$wgFileExtensions) is setup properly. See `workspace/result/images` for reference.
5. Move the `workspace/result` directory to your MediaWiki server (e.g. `/tmp` ).
6. Use `php maintenance/importImages.php /tmp/result/images/` to first import all attachment files and images
7. Use `php maintenance/importDump.php /tmp/result/output.xml` to import the actual pages

You may need to update your MediaWiki search index afterwards.
