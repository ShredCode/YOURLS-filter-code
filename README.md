YOURLS-filter-code
==================

Yourls plugin to allow you to select 3XX Status Code to return per keyword.

Plugin tested for YOURLS 1.7


#### Installation
In `/user/plugins`, create a new folder named `filter-code`.  
Drop these files in that directory.  
Go to the Plugins administration page (*Manage Plugins*)->and activate the plugin: *Status Code by Keyword*.  
After activation, you should see *Status Code Page* listed as a page under *Manage Plugins*

#### Status Code Page Usage
Add a keyword to 3XX status codes associations from the dropdowns presented.  The 
table will show any existing mapping.  If no mapping is shown or defined, `301` is used as the default code.

#### Screen Shot - Admin Page
![Plugin Admin Page](img/Shred_Code_Plugin_page.jpg)
