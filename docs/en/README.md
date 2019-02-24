# IPSymconBroadlinkRM
[![Version](https://img.shields.io/badge/Symcon-PHPModule-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-%3E%205.1-green.svg)](https://www.symcon.de/en/service/documentation/installation/)

Module for IP Symcon version 5.1 or higher enables communication with a Broadlink RM

## Documentation

**Table of Contents**

1. [Features](#1-features)
2. [Requirements](#2-requirements)
3. [Installation](#3-installation)
4. [Function reference](#4-functionreference)
5. [Configuration](#5-configuration)
6. [Annex](#6-annex)

## 1. Features

The module can learn commands to a Broadlink RM via IP-Symcon and send commands to a Broadlink RM.
Reading out the data of a Broadlink A1 sensor.

[BroadlinkRM](http://www.ibroadlink.com/rm/ "Broadlink RM")

### Features:

- Sending radio and IR commands via Broadlink RM
- Teaching radio commands to the Broadlink RM
- A1 sensor humidity
- A1 sensor volume
- Temperature A1 Sensor and Broadlink RM
- A1 sensor light
- A1 sensor air quality

## 2. Requirements

- IPS 5.1
- Broadlink RM, RM Mini, RM2 Pro Plus, RM2 Pro Plus2
- (optional) Broadlink A1 sensor if room data should be used

## 3. Installation

### a. Loading the module

Open the IP Console's web console with _http://<IP-Symcon IP>:3777/console/_.

Then click on the module store (IP-Symcon > 5.1) icon in the upper right corner.

![Store](img/store_icon.png?raw=true "open store")

In the search field type

```
Broadlink
```  


![Store](img/module_store_search_en.png?raw=true "module search")

Then select the module and click _Install_

![Store](img/install_en.png?raw=true "install")


#### Install alternative via Modules instance

_Open_ the object tree.

![Objektbaum](img/object_tree.png?raw=true "object tree")	

Open the instance _'Modules'_ below core instances in the object tree of IP-Symcon (>= Ver 5.x) with a double-click and press the _Plus_ button.

![Modules](img/modules.png?raw=true "modules")	

![Plus](img/plus.png?raw=true "Plus")	

![ModulURL](img/add_module.png?raw=true "Add Module")
 
Enter the following URL in the field and confirm with _OK_:


```	
https://github.com/Wolbolar/IPSymconBroadlinkRM 
```
    
and confirm with _OK_.    
    
Then an entry for the module appears in the list of the instance _Modules_

By default, the branch _master_ is loaded, which contains current changes and adjustments.
Only the _master_ branch is kept current.

![Master](img/master.png?raw=true "master") 

If an older version of IP-Symcon smaller than version 5.1 (min 4.3) is used, click on the gear on the right side of the list.
It opens another window,

![SelectBranch](img/select_branch_en.png?raw=true "select branch") 

here you can switch to another branch, for older versions smaller than 5.1 (min 4.2) select _Old-Version_ .

### b.  Setup in IP-Symcon

#### 1. Creating the Broadlink configurators

Switch to IP Symcon under Discovery Instances. Create a new instance with _Right mouse click-> Add object-> instance_ and select as Device __*Broadlink Discovery*__.

![ModulesIO](img/Broadlink_Discovery_en.png?raw=true "Broadlink IO")

#### 2. Creating the Broadlink Gateway

In IP-Symcon switch to the Broadlink Discovery instance and create the selected Broadlink gateway. You can find now a new configurator for the gateway under _Configurator instances_ in which the devices are created.

Open the Broadlink configurator and first select a category under which the Broadlink devices and optional scripts should be created.

Select _Broadlink scripts_ if in addition for each command from a device a separate script should be created.

To create a new device select a name for the devive below the list select as type IR (infrared) or radio (RF). Push _Create_ and the device appears in the list.
Then the device can now be created by the configurator by selecting the device in the list and push _Create_

##### Broadlink A1

If a Broadlink A1 has been found on Discovery, it will automatically be displayed in IP-Symcon in the Broadlink Configurator with the appropriate values. The instance can now be created in the Configurator with _Create_.

An interval can be selected how often the values of the A1 sensor are to be updated.

Objecttree:

![BroadlinkA1Objecttree](img/broadlink_a1.png?raw=true "Broadlink A1")

Webfront:

![BroadlinkA1WF](img/broadlink_a1_wf.png?raw=true "Broadlink A1 Webfront")

Read out from the Broadlink A1

* Humidity
* Volume
* Hue
* Air quality
* Temperature

#### 3. Teaching devices commands

##### a. Teaching

After a device instance has been generated by the Broadlink configurator, the device can be taught new codes or existing IR codes can be imported.

Open the instance in the form codes can be entered. To import already existed codes add the code in JSON format in the form or select the variable containing the corresponding codes.

Alternatively, one can also use a script.
To do this, create a script in the object tree and copy this content into the script.

```php
<?
$InstanceID = 12345; // Object ID of the Broadlink instance to which a command is to be trained
$command_name = "Power";
$result = Broadlink_LearnDeviceCode($InstanceID, $command_name);
var_dump($result);
?>
```   

Parameter _$command_name_ __*command name*__ under which the code is stored and then called again, e.g. _Power_

Parameter _$InstanceID_ __*ObjectID*__ from the Broadlink instance

Then press _Run_ .

![ModulesRun](img/run_en.png?raw=true "Ausführen")

You can see an orange LED on the Broadlink RM, i.E. this is ready to receive.

In IP Symcon the prossing icon is shown

![ModulesRun1](img/run1.png?raw=true "Ausführen Symbol")

Now press the button to be learned on the remote control and wait a moment.

After successful training, a new command is shown in the list in the Broadlink instance.


![ModulesDevice](img/device.png?raw=true "Device")

Webfront:

![ModulesWF](img/BroadlinkWebfront1.png?raw=true "Webfront Device")

![ModulesWF](img/BroadlinkWebfront2.png?raw=true "Webfront Device")

Further commands can now be taught in on the same device simply by adjusting the parameter _$command_name_ in the script. Then run the script again and press the new key to be learned.

##### b. Import of existing commands

If a code from Broadlink is already known, there is no need to train again directly on Broadlink. It then sufficent to import the code with
 
```php
<?
$command_name = "PowerOff";
$command = "b20834000c250b250c250c250c250c240c250c250c250c250c240c250b250c240c240c240c250c240c24240e0c25230e0c250c250c00017500000000";
$InstanceID = 12345; // Object ID of the Broadlink instance to which a command is to be trained
$result = Broadlink_ImportCode($InstanceID, $command_name, $command);
var_dump($result);
?>
```   

Parameter _$command_name_ __*command name*__ under which the code is stored and then called again, e.g. _Power_

Parameter _$InstanceID_ __*ObjectID*__ from the Broadlink instance

Parameter _$command_ __*command*__ for import


#### 4. Sending device commands

It can be pressed in the normal way in the Web front on the key then the command over the Broadlink is sent.

Alternatively, you can call the command via a script. To do this, you can first open the device instance with a double-click.
Here are all the commands are listed that have been previously taught in the device.

![ModulesCommands](img/commands_en.png?raw=true "Commands")

Now you can call a desired command with a script

```php
<?
$command = "Power";
$InstanceID = 23456 /*[Geräte\Broadlink\Broadlink Intertechno]*/;
BroadlinkDevice_SendCommand($InstanceID, $command);
?>
```   

Parameter _$command_ __*command*__ executing command name, corresponds to the name used when learning or importing

Parameter _$InstanceID_ __*ObjectID*__ from the Broadlink device instance

## 4. Function reference

### Broadlink

_**Teaching**_
 
```php
Broadlink_LearnDeviceCode($InstanceID, $command_name);
```   

Parameter _$command_name_ __*command name*__ under which the code is stored and then called again, e.g. _Power_

Parameter _$InstanceID_ __*ObjectID*__ from the Broadlink device instance

_**Importing existing codes**_

```php
Broadlink_ImportCode($InstanceID, $command_name, $command);
```   

Parameter _$command_name_ __*command name*__ under which the code is stored and then called again, e.g. _Power_

Parameter _$command_ __*command*__ command to import

Parameter _$InstanceID_ __*ObjectID*__ from the Broadlink device instance


### Broadlink Device

_**Send**_

```php
BroadlinkDevice_SendCommand($InstanceID, $command);
```   

Parameter _$command_ __*command*__ executing command name, corresponds to the name used when learning or importing

Parameter _$InstanceID_ __*ObjectID*__ from the Broadlink device instance for which a command is to be sent

## 5. Configuration:

### Broadlink IO: (Die Werte werden automatisch eingelesen)

| Property          | Type    | Value        | Function                                                  |
| :---------------: | :-----: | :----------: | :-------------------------------------------------------: |
| name              | string  | 		     | internal device name of Broadlink                         |
| host              | string  | 		     | IP Adress from the Broadlink                              |
| mac               | string  | 		     | MAC Adress from the Broadlink                             |
| modell            | string  | 		     | Modell Type Broadlink                                     |
| devicetype        | string  | 		     | internal device type designation Broadlink                |
| CategoryID        | integer | 		     | ObjectID of the import category for the Broadlink devices |

All values are read out via _Discover_ and can not be changed by the user.

## 6. Annex

###  a. GUIDs and data exchange:

#### Broadlink IO:

GUID: `{E58707E8-8E2C-26D4-A7A9-2D6D6D93AB04}` 


#### Broadlink Device:

GUID: `{B5A1F2D9-0530-6130-5933-9D0E916E8F8A}` 

#### Sources:

The PHP module uses the broadlink-device-php class of _tasict_

[tasict/broadlink-device-php](https://github.com/tasict/broadlink-device-php "Tasict")

The protocol is based on the analysis of mjg59 (MIT License)

[Broadlink RM2 network protocol](https://github.com/mjg59/python-broadlink/blob/master/protocol.md "mjg59")