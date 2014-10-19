=========
Upgrading
=========

**Always do a database backup before upgrading.**

Download latest version using *Git*::

    cd your/webroot/folder;
    git pull origin master;

Use *Composer* to update dependancies::

    composer update;

Then run database schema update::

    bin/renzo schema --update;

If migration summary is OK, perform the changes::

    bin/renzo schema --update --execute;
    bin/renzo schema --refresh;

Upgrading Node-types source entities
------------------------------------

If some Doctrine errors occur about some fields missing in your *NodesSources*,
you must *regenerate all entities* source files::

    bin/renzo core:node:types --regenerateAllEntities;
    bin/renzo schema --update;

Verify here that no data field will be removed and apply changes::

    bin/renzo schema --update --execute;
    bin/renzo schema --refresh;
