wwProtect
=========

MCPE protect plugin for new API based on PrivateAreaProtector by Omattyao

* work with mysql DB
* multiworld
* superadmin group (place, break in anywhere)
* users group (personal, and grouped private regions)

TODO:
* on/off PVP on region
* on/off Interact (Door, Chest, Fuel, etc. lock in region)

commands
 protect:
  description: Shows list wwProtector commands
  /protect <pos1|pos2|   > [<g> <Group Name>] [<share> <Group Name>] [<pvp> <on|off>] [<lock> <on|off>]

 sprotect:
  description: Special console command
  /sprotect <nick> <level> <x1> <y1> <z1> <x2> <y2> <z2>

 group:
  description: Shows list wwGroups commands
  /group <ls|add|rm> <user> <group>
  /group <ls|add|rm> <group>
