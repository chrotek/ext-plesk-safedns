
# UKFast SafeDNS Plesk Integration

## Info

The UKFast SafeDNS Plesk extension provides the ability to manage the SafeDNS Zone for any of the domains on your Plesk Server. 

It will synchronise the DNS Zones from Plesk with SafeDNS. 
It does not replace or modify Plesk’s internal DNS , so if you have specific domains which use an external DNS Service, you’re free to continue using that service.

Logs are stored in : /var/log/plesk/ext-plesk-safedns/

At present, this extension is **only compatible with linux systems**. 
Usage on windows will result in synchronise tasks erroring & no logs being written by the extension.

## Setup
There are some settings you need to check before use, to make sure the DNS records will be valid.
If you install the extension, the “Welcome” tab will guide you through this.

#### IP Address
The server’s Public IP Address needs to be set in Plesk.
This can be checked in Tools & Settings > IP Addresses. Make sure there is a Public 
IP set for all the IP’s your Domains will use.

#### Hostname
The server’s Hostname should have a valid FQDN set as it’s hostname. (e.g. server.domain.com)
If set incorrectly, your websites will still work, but sending email from the server will result in a poor sender rating and reduced mail deliverability.

You can check and change the Hostname in Plesk > Tools & Settings > Server Settings.
The Hostname should also resolve to the server’s Public IP Address, If it does not, an A record should be created in SafeDNS.


#### Nameservers
The Nameservers in Plesk’s DNS Template at should be set to: 
  - ns0.ukfast.net
  - ns1.ukfast.net
Go to Tools & Settings > General Settings > DNS Zone Template, and set the NS Records as above.
Then, click “Apply DNS Template Changes” , and select “Apply the changes to all zones.”

#### API Key

If you need to create or reset an API Key, Log into your MyUKFast account, go to “API Applications”.
The key should have Read & Write Access to SafeDNS, and DDoSX.

To add your API Key to Plesk, open the extension, and in the “Tasks & Config” tab , click Set API Key

NOTE: DDoSX Will be implemented in a future release.


## Extension Interface

#### Welcome Page
The welcome page will guide you through checking the settings explained in the “Setup” Section.

#### Manage DNS Zones
In The Manage DNS Zones tab, you can control which domains the extension will synchronise to SafeDNS.

| Column                               | Info                                                                                                                                                                                                                   |
|--------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Enabled                              | Domains must be enabled here to work with the extension.                                                                                                                                                               |
| Sync Now                             | This will push an enabled zone to SafeDNS. <br>If a record already exists, it will be updated to match plesk. <br>If a record exists on SafeDNS but has been removed, (or didn't exist) on Plesk, it will be deleted   |
| Last Synchronise                     | When a domain is Synchronised, this is updated with the date & time                                                                                                                                                    |                       
| Automatic Sync <br>{on zone change)  | When Plesk's internal DNS is updated, the domains that have this enabled, will be automatically Synchronised with SafeDNS. <br> For let’s encrypt to perform DNS CNAME Verification successfully, this must be enabled.| 
| Delete Zone <br>(From SafeDNS)       | Deletes the zone from SafeDNS. Only if the zone is enabled.                                                                                                                                                            |

#### Tasks & Config                                                                                                                      |
In Tasks & Config , you have the following options:                                                                                      |
| Button          | Function                                                                                                             |																																		 |
| Set API Key     | Open a form for API Key to be inputted. When a Key is saved, It will be tested to confirm it is valid. _             |
| Sync All        | This will synchronise all enabled zones to SafeDNS.                                                                  |
| Sync Domain     | Open a form with a Dropdown to select a Domain. <br>On Submit,  push only the selected zone to SafeDNS.              |
| Delete All      | This will immediately delete all enabled zones from SafeDNS.                                                         |
| Delete Domain   | Open a form with a Dropdown to select a Domain. <br>On Submit,  delete only the selected zone from SafeDNS.          |
| Clear All Tasks | Clears out the extension’s task queue. <br>If a task has errored and you cannot run another one, click this button.  |
