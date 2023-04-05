---
name: MR Carrier Method
category: Object models
---

### Mondial Relay Carrier Method 

Table : mondialrelay_carrier_method

Multilang : false

|Name|Type|Description|Validateur|
|------|------|------|------|
|id_mondialrelay_carrier_method|int(10)|ID of the record|isUnsignedId|
|~~name~~|~~varchar(255)~~|~~Carrier name~~ Already available in native PS table||
|~~country_list~~|~~varchar(1000)~~|~~List of available countries~~ Unused||
|~~col_mode~~|~~varchar(3)~~|Unused|~~CCC,CDR,CDS,REL~~|
|delivery_mode|varchar(3)|-|~~LCC~~,LD1,LDS,24R,~~24L~~,~~24X~~,~~ESP~~,DRI,HOM|
|insurance|varchar(3)||[0-9A-Z]{1}|
|id_carrier|int(10)|PS carrier id||
|is_deleted|int(10)|Carrier is deleted, not available in PS||
|~~id_shop~~|~~int(10)~~|~~PS shop ID~~ Already available in native PS table||

**Upgrade 3.0.0 :** 
* ~~ADD id_shop to table~~ Already available in native PS table
* During upgrade, update table mr_method with existing values from mr_method_shop
* Remove table mr_method_shop
