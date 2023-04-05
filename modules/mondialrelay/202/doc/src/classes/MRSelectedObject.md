---
name: MR selected point relais
category: Object models
---

### Mondial Relay Selected points 

Table : mr_selected

Multilang : false

|Name|Type|Description|Validateur|
|------|------|------|------|
|id_mr_selected|int(10)|ID of the record|isUnsignedId|
|id_address_delivery|int(10)|PS Address delivery ID (**NEW**)|isUnsignedId|
|id_customer|int(10)|PS customer ID|isUnsignedId|
|id_method|int(10)|MR method ID|isUnsignedId|
|id_cart|int(10)|PS cart ID|isUnsignedId|
|id_order|int(10)|PS order ID|isUnsignedId|
|MR_poids|varchar(7)|Weigth||
|MR_insurance|varchar(1000)||
|MR_Selected_Num|varchar(3)|-||
|MR_Selected_LgAdr1|varchar(36)|-||
|MR_Selected_LgAdr2|varchar(36)|||
|MR_Selected_LgAdr3|varchar(36)|||
|MR_Selected_LgAdr4|varchar(36)|||
|MR_Selected_CP|int(10)|||
|MR_Selected_Ville|varchar(32)|||
|MR_Selected_Pays|varchar(2)|||
|url_suivi|varchar(1000)|-|-|
|url_etiquette|varchar(1000)|-|-|
|exp_number|varchar(8)|-|-|
|date_label_generation|datetime|-|-|
|hide_history|bool|Show or not expedition in history (**NEW**)|boolean|
|date_add|datetime|||
|date_upd|datetime|||

**Upgrade 3.0.0 :**
* Create new delivery address when point relay is selected
* Alter table : ADD id_address_delivery to table mr_selected
* MR_Selected_ADDR_fileds is not more useful but we can't delete it because of old orders.

If merchant change point relay, we update "id_address_delivery" value. 
If "exp_number" is not empty, we can't more change point relay.

**MR history**
* Add new field hide_history to mr_selected and set to 0 for all orders from mr_history.
  * Interesting sidenote : if a field has a non-zero default value (weak comparison),
we'll never be able to set it to 0 (weak comparison); see ObjectModel::validateField()
* Remove table mr_history. We can get different pdf size by replacing *format* value in url_etiquette (A4 by default). 
Like it was done already for "10 * 15" size.

|Function name|Type|Description|Params|Return|
|------|------|------|------|------|
|deleteHistory|public|Set hide_history to 1|id_order|-|