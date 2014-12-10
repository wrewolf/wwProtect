wwProtect
=========

MCPE protect plugin for new API based on PrivateAreaProtector by Omattyao

* work with mysql DB
* multiworld
* superadmin group (place, break in anywhere)
* users group (personal, and grouped private regions)
* Door, Chest, locked in region
TODO:
* on/off PVP on region
* on/off Interact ( Fuel, etc. lock in region)

#commands#

###protect###
 
Shows list wwProtector commands
  
 /protect \<pos1|pos2|   \> \[\<g\> \<Group Name\>\] \[for future \[\<share> <Group Name\>\] \[\<pvp\> \<on|off\>\] \[\<lock\>\] \<on|off\>\]

###sprotect###
 
Special console command
  
/sprotect \<nick\> \<level\> \<x1\> \<y1\> \<z1\> \<x2\> \<y2\> \<z2\>

###group###

Shows list wwGroups commands
  
 /group \<ls|add|rm\> \<user\> \<group\>
  
 /group \<ls|add|rm\> \<group\>



#DDL
```
CREATE TABLE `Protect` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `members` blob NOT NULL,
  `isPvp` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `level` varchar(60) NOT NULL,
  `x1` tinyint(3) unsigned NOT NULL,
  `y1` tinyint(3) unsigned NOT NULL,
  `z1` tinyint(3) unsigned NOT NULL,
  `x2` tinyint(3) unsigned NOT NULL,
  `y2` tinyint(3) unsigned NOT NULL,
  `z2` tinyint(3) unsigned NOT NULL,
  `isDoor` tinyint(3) unsigned NOT NULL,
  `enabled` tinyint(3) unsigned NOT NULL,
  `creationTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `nl` (`name`,`level`) USING BTREE,
  KEY `level` (`level`) USING HASH,
  KEY `name` (`name`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=780 DEFAULT CHARSET=utf8;

CREATE TABLE `groups` (
  `name` varchar(30) NOT NULL,
  `members` varchar(255) NOT NULL,
  PRIMARY KEY (`name`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


```
