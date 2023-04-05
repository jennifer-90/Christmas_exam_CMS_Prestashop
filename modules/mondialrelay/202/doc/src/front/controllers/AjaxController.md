---
name: AjaxFrontController
category: Front Controllers
---

## AjaxFrontController

Properties :
* json_response
* redirect_url

### init

set parameters

### postProcess

Call webservice classes getService($method).
Return $this->json_response

### run

die ($this->json_response) or redirect($this->redirect_url)