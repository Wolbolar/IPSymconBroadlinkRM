# IPSymconBroadlinkRM
[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-%3E%205.1-green.svg)](https://www.symcon.de/service/dokumentation/installation/)

Modul für IP-Symcon ab Version 5.1 ermöglicht die Kommunikation mit einem Broadlink RM

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)  
3. [Installation](#3-installation)  
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguration)  
6. [Anhang](#6-anhang)  

## 1. Funktionsumfang

Das Modul kann aus IP-Symcon Befehle an einem Broadlink RM anlernen und Befehle an einen Broadlink RM senden.
Auslesen der Daten eines Broadlink A1 Sensors.

[BroadlinkRM](http://www.ibroadlink.com/rm/ "Broadlink RM")

### Funktionen:  

 - Senden von Funk und IR Befehlen über Broadlink RM 
 - Anlernen von Funkbefehlen an den Broadlink RM
 - A1 Sensor Feuchtigkeit
 - A1 Sensor Lautstärke
 - Temperatur A1 Sensor und Broadlink RM
 - A1 Sensor Licht
 - A1 Sensor Luftqualität
	  
## 2. Voraussetzungen

 - IPS 5.1
 - Broadlink RM, RM Mini, RM2 Pro Plus, RM2 Pro Plus2
 - (optional) Broadlink A1 Sensor wenn Raumdaten genutzt werden sollen

## 3. Installation

### a. Laden des Moduls

Die Webconsole von IP-Symcon mit _http://<IP-Symcon IP>:3777/console/_ öffnen. 


Anschließend oben rechts auf das Symbol für den Modulstore (IP-Symcon > 5.1) klicken

![Store](img/store_icon.png?raw=true "open store")

Im Suchfeld nun

```
Broadlink
```  

eingeben

![Store](img/module_store_search.png?raw=true "module search")

und schließend das Modul auswählen und auf _Installieren_

![Store](img/install.png?raw=true "install")

drücken.


#### Alternatives Installieren über Modules Instanz

Den Objektbaum _Öffnen_.

![Objektbaum](img/objektbaum.png?raw=true "Objektbaum")	

Die Instanz _'Modules'_ unterhalb von Kerninstanzen im Objektbaum von IP-Symcon (>=Ver. 5.x) mit einem Doppelklick öffnen und das  _Plus_ Zeichen drücken.

![Modules](img/Modules.png?raw=true "Modules")	

![Plus](img/plus.png?raw=true "Plus")	

![ModulURL](img/add_module.png?raw=true "Add Module")
 
Im Feld die folgende URL eintragen und mit _OK_ bestätigen:

```
https://github.com/Wolbolar/IPSymconBroadlinkRM  
```  
	
Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_    

Es wird im Standard der Zweig (Branch) _master_ geladen, dieser enthält aktuelle Änderungen und Anpassungen.
Nur der Zweig _master_ wird aktuell gehalten.

![Master](img/master.png?raw=true "master") 

Sollte eine ältere Version von IP-Symcon die kleiner ist als Version 5.1 (min 4.2) eingesetzt werden, ist auf das Zahnrad rechts in der Liste zu klicken.
Es öffnet sich ein weiteres Fenster,

![SelectBranch](img/select_branch.png?raw=true "select branch") 

hier kann man auf einen anderen Zweig wechseln, für ältere Versionen kleiner als 5.1 (min 4.3) ist hier
_Old-Version_ auszuwählen. 


### b. Einrichtung in IP-Symcon

#### 1. Anlegen der Broadlink Konfiguartoren

In IP-Symcon unter Discovery Instanzen wechseln. Hier eine neue Instanz mit _Rechter Mausklick->Objekt hinzufügen->Instanz _ erzeugen und als Gerät __*Broadlink Discovery*__ wählen.

![ModulesIO](img/Broadlink_Discovery.png?raw=true "Broadlink IO")

	
#### 2. Anlegen der Broadlink Gateways

In IP-Symcon in die Broadlink Discovery Instanz wechseln und die gewünschten Broadlink Gateways anlegen lassen. Es befindet sich dann ein neuer Konfigurator für das Gateway unter _Konfigurator Instanzen_ in dem die Geräte angelegt werden.

Den Broadlink Konfigurator öffnen und zunächst eine Kategorie auswählen unter der die Broadlink Geräte und optional Skripte angelgt werden sollen.

_Broadlink Skripte_ auswählen wenn zusätzlich für jeden Befehl von einem Gerät ein seperates Skript angelegt werden soll.

Wenn ein neues Gerät angelegt werden soll dem dann Befehle angelernt oder importiert werden können ist unterhalb der Liste ein name für das Gerät zu wählen als Typ IR (Infrarot) oder Funk (RF) zu wählen und mit _Anlegen erscheint dann das gerät in der Liste.
Anschließend kann das Gerät nun durch der Konfigurator angelegt werden durch auswahll des geräts in der Liste und _Erstellen_

##### Broadlink A1

Sollte ein Broadlink A1 beim Discovery gefunden wurden sein, wird dieser automatisch in IP-Symcon im Broadlink Konfigurator mit den passenden Werten angezeigt. Dieser kann nun im Konfigurator mit _Erstellen_ erzeugt werden. 

Ein Intervall kann gewählt werden wie oft die Werte des A1 Sensors aktualisiert werden sollen.

Objektbaum:

![BroadlinkA1Objecttree](img/broadlink_a1.png?raw=true "Broadlink A1")

Webfront:

![BroadlinkA1WF](img/broadlink_a1_wf.png?raw=true "Broadlink A1 Webfront")

Ausgelesen werden aus dem Broadlink A1

* Luftfeuchtigkeit
* Lautstärke
* Licht
* Luftqualität
* Temperatur

#### 3. Anlernen von Geräte Befehlen

##### a. Anlernen

Nachdem ein Geräte Instanz durch den Broadlink Konfigurator erzeugt worden ist kann dem Gerät Codes angelernt werden oder beriets vorhandene IR Codes importiert werden.

Die Instanz öffnen hier können nun Codes eingetragen werden. Um bereits existiernde Codes zu übernehmen ist entweder der Codetext als JSON einzutragen oder aber die Variable auszuwählen die die entsprechenden codes enthält.

Alternativ kann man auch ein Skript benutzten.
Dazu legt man ein Skript im Objektbaum an und kopiert diesen Inhalt in das Skript.

```php
<?
$InstanceID = 12345; // Objekt ID der Broadlink Instanz, an die ein Befehl angelernt werden soll
$command_name = "Power";
$result = Broadlink_LearnDeviceCode($InstanceID, $command_name);
var_dump($result);
?>
```   

Parameter _$command_name_ __*Befehlsname*__ unter der der Code abgelegt wird und dann wieder aufgerufen werden kann z.B. Power

Parameter _$InstanceID_ __*ObjektID*__ der Broadlink Instanz

Nachdem die Paramter angepasst wurden auf _Ausführen_ drücken.

![ModulesRun](img/run.png?raw=true "Ausführen")

Man sieht am Broadlink RM nun eine orangene LED leuchten, d.h. dieser ist empfangsbereit.

In IP-Symcon steht das Ausführen Symbol

![ModulesRun1](img/run1.png?raw=true "Ausführen Symbol")

jetzt die anzulernende Taste auf der Fernbedienung drücken und ein Moment abwarten.

Nach erfolgreichem Anlernen erscheint ein neuer Befehl im Broadlink Gerät.

![ModulesDevice](img/device.png?raw=true "Device")

Webfront:

![ModulesWF](img/BroadlinkWebfront1.png?raw=true "Webfront Device")

![ModulesWF](img/BroadlinkWebfront2.png?raw=true "Webfront Device")

Es können nun weitere Befehle am gleichen Gerät angelernt werden indem einfach der Parameter _$command_name_ im Skript angepasst wird. Anschließend das Skript erneut ausführen und die neu anzulernende Taste drücken.

##### b. Import von existierenden Befehlen

Wenn bereits ein Code von Broadlink bekannt ist muss nicht erneut direkt am Broadlink angelernt werden. Es reicht dann aus den Code einfach zu importieren mit
 
```php
<?
$command_name = "PowerOff";
$command = "b20834000c250b250c250c250c250c240c250c250c250c250c240c250b250c240c240c240c250c240c24240e0c25230e0c250c250c00017500000000";
$InstanceID = 12345; // Objekt ID der Broadlink Instanz, an die ein Befehl angelernt werden soll
$iid = Broadlink_ImportCode($InstanceID, $command_name, $command);
var_dump($iid);
?>
```   

Parameter _$command_name_ __*Befehlsname*__ unter der der Code abgelegt wird und dann wieder aufgerufen werden kann z.B. Power

Parameter _$command_ __*Befehl*__ zu importierender Befehl

Parameter _$InstanceID_ __*ObjektID*__ der Broadlink Instanz

#### 4. Absenden von Gerätebefehlen

Es kann ganz normal im Webfront auf die Taste gedrückt werden dann wird der Befehl über den Broadlink verschickt.

Alternativ kann man den Befehl auch über ein Skript aufrufen. Dazu kann man zunächst die Geräte Instanz mit einem Doppelklick öffnen.
Hier stehen alle Befehle die bisher bei dem Gerät angelernt worden sind.

![ModulesCommands](img/commands.png?raw=true "Commands")

Aufrufen kann man nun einen gewünschten Befehl auch mit einem Skript 

```php
<?
$command = "Power";
$InstanceID = 23456 /*[Geräte\Broadlink\Broadlink Intertechno]*/;
BroadlinkDevice_SendCommand($InstanceID, $command);
?>
```   

Parameter _$command_ __*Befehl*__ ausführender Befehlname, entspricht dem Namen der beim Anlernen bzw. Import verwendet worden ist

Parameter _$InstanceID_ __*ObjektID*__ der Broadlink Geräte Instanz für die ein Befehl verschickt werden soll



## 4. Funktionsreferenz

### Broadlink
Ein Gerät wird mit der entsprechenden Funktion und Übergabe der InstanzID angesteuert.

_**Anlernen**_
 
```php
Broadlink_LearnDeviceCode($InstanceID, $command_name);
```   

Parameter _$command_name_ __*Befehlsname*__ unter der der Code abgelegt wird und dann wieder aufgerufen werden kann z.B. _Power_

Parameter _$InstanceID_ __*ObjektID*__ der Broadlink Instanz

_**Importieren von existierenden Codes**_

```php
Broadlink_ImportCode($InstanceID, $command_name, $command);
```   

Parameter _$command_name_ __*Befehlsname*__ unter der der Code abgelegt wird und dann wieder aufgerufen werden kann z.B. Power

Parameter _$command_ __*Befehl*__ zu importierender Befehl

Parameter _$InstanceID_ __*ObjektID*__ der Broadlink I/O Instanz


### Broadlink Device

_**Senden**_

```php
BroadlinkDevice_SendCommand($InstanceID, $command);
```   

Parameter _$command_ __*Befehl*__ ausführender Befehlname, entspricht dem Namen der beim Anlernen bzw. Import verwendet worden ist

Parameter _$InstanceID_ __*ObjektID*__ der Broadlink Geräte Instanz für die ein Befehl verschickt werden soll


## 5. Konfiguration:

### Broadlink IO:

| Eigenschaft       | Typ     | Standardwert | Funktion                                                  |
| :---------------: | :-----: | :----------: | :-------------------------------------------------------: |
| name              | string  | 		     | interner Gerätename des Broadlink                         |
| host              | string  | 		     | IP Adresse des Broadlink                                  |
| mac               | string  | 		     | MAC Adresse des Broadlink                                 |
| modell            | string  | 		     | Modell Typ Broadlink                                      |
| devicetype        | string  | 		     | interne Gerätetyp Bezeichnung Broadlink                   |
| CategoryID        | integer | 		     | ObjektId der Importkategorie für die Broadlink Geräte     |

Alle Werte werden über _Discover_ ausgelesen und sind _nicht_ vom Nutzer zu ändern.


## 6. Anhang


###  a. GUIDs und Datenaustausch:

#### Broadlink IO:

GUID: `{E58707E8-8E2C-26D4-A7A9-2D6D6D93AB04}` 


#### Broadlink Device:

GUID: `{B5A1F2D9-0530-6130-5933-9D0E916E8F8A}` 

#### Quellen:

Das PHP Modul nutzt die broadlink-device-php Klasse von _tasict_

[tasict/broadlink-device-php](https://github.com/tasict/broadlink-device-php "Tasict")

Das Protokoll basiert auf auf der Analyse von mjg59 (MIT License)

[Broadlink RM2 network protocol](https://github.com/mjg59/python-broadlink/blob/master/protocol.md "mjg59")