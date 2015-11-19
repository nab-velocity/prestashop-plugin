Velocity Prestashop Module Installation Documentation 

1.	Configuration Requirement: Prestashop site Version 1.6.0 or above version must be required for our velocity payment module installation.

2.	Download velocity Prestashop Module by clicking on Download zip button on the right bottom of this page.

3.  Extract the zip and re-zip the folder 'velocity' (inside the extracted folder). 

4.	Installation & Configuration of Module from Admin Panel:

    i.	Login Prestashop admin panel and goto Dashboard Modules option, click on “modules” menu on left side of dashboard menus then display modules panel shows all uploaded module listed below.

    ii. Top Right of the dashboard click on “Add a new module” button. Show Upload Module Panel.

    iii. Click on “Choose a file” button and select velocity zipped module file from system then Click on “Upload this module” for upload the module in prestashop module section and listed in “MODULES LIST” below.

    iv. After successful upload Module listed in Prestashop “MODULES LIST” and ready for installation just click on “install” button.

    v. After successful installation, module configuration page open for save your configuration and test the module.

5. VELOCITY CREDENTIAL DETAILS


   	        IdentityToken: - This is long lived security token provided by velocity to merchant.    	
   	        WorkFlowId/ServiceId: - This is service id provided by velocity to merchant.
    	    ApplicationProfileId: - This is application id provided by velocity to merchant.
    	    MerchantProfileId: - This is merchant id provided by velocity to merchant.
            Test Mode :- This is for test the module, if checked the checkbox then test mode enable and no need to save “WorkFlowId/ServiceId & MerchantProfileId” otherwise unchecked the checkbox and save  “WorkFlowId/ServiceId & MerchantProfileId” for live payment.

6.  We have saved the raw request and response objects in &lt;prefix&gt;_velocity_transaction table.

