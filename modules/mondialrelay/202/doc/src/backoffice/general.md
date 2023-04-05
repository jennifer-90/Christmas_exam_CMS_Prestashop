---
name: General
category: BackOffice
---

**General info**

All pages in BO represents the admin controllers using Helper elements.

Classlib extentions used in module:
* ProcessLogger
* ProcessMonitor

**displayInfoByCart**

Magic function to show any info on the BO order page in Delivery block.

**Ajax**

Check requirements and Check connection are realised with ajax call. 

**ProcessMonitor - Advanced Settings**

This AdminMondialrelayProcessMonitorController must be overrided to correspond to page Advanced Settings.

In new module cron task must be realised with front controllers. 
Cron tasks : 

| Technique Name | Title     |  Frequency                | URL                |  
|---------|------------|---------------------|---------------------|
| mondialrelay:UpdateStatuses    | Orders status update       |  6h| https://yoursite.com/module/mondialrelay/UpdateStatuses |


In version 3.0 we left old cron file cron.php for retrocompability.
In cron.php we add log about deprecated cron task and add redirection to new cron task.
Add new level in classlib "deprecated".
This file will be removed in next version 3.1. We need add multiple warnings 
to prevent merchants to update cron on the server (like in check requirements, warnings on settings, etc.).  

**PocessLogger**

Actions to log : 
* point relay selected (info)
* Ticket generated (info)
* Cron activity
* all webservice error response