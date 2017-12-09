# IPSymconBroadlinkRM
===

Modul für IP-Symcon ab Version 4.2 ermöglicht die Kommunikation mit einem Broadlink RM.

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

## 2. Voraussetzungen

 - IPS 4.2
 - Broadlink RM
 - (optional) Broadlink A1 Sensor wenn Raumdaten genutzt werden sollen

## 3. Installation

### a. Laden des Moduls

Die IP-Symcon (min Ver. 4.2) Konsole öffnen. Im Objektbaum unter Kerninstanzen die Instanz __*Modules*__ durch einen doppelten Mausklick öffnen.

![Modules](docs/Modules.png?raw=true "Modules")

In der _Modules_ Instanz rechts oben auf den Button __*Hinzufügen*__ drücken.

![ModulesAdd](docs/Hinzufuegen.png?raw=true "Hinzufügen")
 
In dem sich öffnenden Fenster folgende URL hinzufügen:

	
    `https://github.com/Wolbolar/IPSymconBroadlinkRM`  
    
und mit _OK_ bestätigen.    
    
Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_ 

### b. Einrichtung in IPS

#### 1. Anlegen des Broadlink Gateways

In IP-Symcon unter I/O Instanzen wechseln. Hier eine neue Instanz mit _Rechter Mausklick->Objekt hinzufügen->Instanz hinzufügen_ oder _CTRL+1_ erzeugen und als Gerät __*Broadlink I/O*__ wählen.

![ModulesIO](docs/Broadlink_IO.png?raw=true "Broadlink IO")

Es wird ein Broadlink Gateway I/O angelegt. Jetzt erstellen wir im Objektbaum von IP-Symcon eine Kategorie _CTRL+0_ und benennen diese z.B. _Broadlink_ unter der später die Broadlink Geräte angelegt werden sollen.
Nun unter _I/O Instanzen_ zum _**Broadlink I/O**_ wechseln und mit Doppelklick öffnen.

![ModulesDiscover](docs/Discover.png?raw=true "Discover Kategorie leer")

Hier wählen wir zunächst die Kategorie aus die zurvor angelegt wurde und bestätigen mit _Übernehmen_.

![ModulesDiscover1](docs/BroadlinkIOConfig.png?raw=true "Kategorie")

Anschließend muss nun der Broadlink bereits im Netzwerk in Betrieb genommen worden sein, im WLAN erreichbar sein und am Strom hängen.
Jetzt kann dann auf _Discover_ gedrückt und kurzen Moment (10 Sekunden) abwarten. Die Instanz sollte dann geschlossen und neu geöffnet werden dann sind die Werte des Broadlink sichtbar, hier muss nichts angepasst werden die Werte bleiben unverändert.

Sollte es mehrere Broadlink Gateways im Netzwerk geben werden diese automatisch neu angelegt. Wenn ein Broadlink A1 Sensor vorhanden sein sollte wird dieser auch automatisch neu unterhalb der Broadlink Kategorie angelegt.

##### Broadlink A1

Sollte ein Broadlink A1 beim Discovery gefunden wurden sein, wird dieser automatisch in IP-Symcon mit den passenden Werten angelegt. In der Instanz sind die Werte des Broadlink A1 zu sehen, hier soll nichts verstellt werden, diese Werte sind für die Kommunikation notwendig.

Objektbaum:

![BroadlinkA1Objecttree](docs/broadlink_a1.png?raw=true "Broadlink A1")

Webfront:

![BroadlinkA1WF](docs/broadlink_a1_wf.png?raw=true "Broadlink A1 Webfront")

Ausgelesen werden aus dem Broadlink A1

* Luftfeuchtigkeit
* Lautstärke
* Licht
* Luftqualität
* Temperatur

#### 2. Anlernen von Geräte Befehlen

##### a. Anlernen

Nachdem das Broadlink Gateway angelegt worden ist und die Werte per _Discover_ abgeholt wurden kann man Geräte anlernen.
Dazu legt man ein Skript im Objektbaum an CTRL+3 und kopiert diesen Inhalt in das Skript.

```php
<?
$devicename = "Broadlink Samsung TV";
$command_name = "Power";
$InstanceID = 12345 /*[Broadlink I/O]*/;
$result = Broadlink_LearnDeviceCode($InstanceID, $devicename, $command_name);
var_dump($result);
?>
```   

Parameter _$devicename_ __*Gerätename*__ unter der das Broadlink Gerät angelegt wird, Befehle die zum Gleichen gerät gehören sollten den gleichen Gerätenamen nutzten

Parameter _$command_name_ __*Befehlsname*__ unter der der Code abgelegt wird und dann wieder aufgerufen werden kann z.B. Power

Parameter _$InstanceID_ __*ObjektID*__ der Broadlink I/O Instanz

Nachdem die Paramter angepasst wurden auf _Ausführen_ drücken.

![ModulesRun](docs/run.png?raw=true "Ausführen")

Man sieht am Broadlink RM nun eine orangene LED leuchten, d.h. dieser ist Empfangsbereit.

In IP-Symcon steht das Ausführen Symbol

![ModulesRun1](docs/run1.png?raw=true "Ausführen Symbol")

jetzt die anzulernende Taste auf der Fernbedienung drücken und ein Moment abwarten.

Nach erfolgreichem Anlernen erscheint ein neues Gerät unterhalb der Broadlink Kategorie die im IO festgelegt worden ist.

![ModulesDevice](docs/device.png?raw=true "Device")

Webfront:

![ModulesWF](docs/wfdevice.png?raw=true "Webfront Device")

Es können nun weitere Befehle am gleichen Gerät angelernt werden indem einfach der Parameter _$command_name_ im Skript angepasst wird. Anschließend das Skript erneut ausführen und die neu anzulernende Taste drücken.

##### b. Import von existierenden Befehlen

Wenn bereits ein Code von Broadlink bekannt ist muss nicht erneut direkt am Broadlink angelernt werden. Es reicht dann aus den Code einfach zu importieren mit
 
```php
<?
$devicename = "Broadlink Intertechno";
$command_name = "PowerOff";
$command = "b20834000c250b250c250c250c250c240c250c250c250c250c240c250b250c240c240c240c250c240c24240e0c25230e0c250c250c00017500000000";
$InstanceID = 12345 /*[Broadlink I/O]*/;
$iid = Broadlink_ImportCode($InstanceID, $devicename, $command_name, $command);
var_dump($iid);
?>
```   

Parameter _$devicename_ __*Gerätename*__ unter der das Broadlink Gerät angelegt wird, Befehle die zum Gleichen gerät gehören sollten den gleichen Gerätenamen nutzten

Parameter _$command_name_ __*Befehlsname*__ unter der der Code abgelegt wird und dann wieder aufgerufen werden kann z.B. Power

Parameter _$command_ __*Befehl*__ zu importierender Befehl

Parameter _$InstanceID_ __*ObjektID*__ der Broadlink I/O Instanz

#### 3. Absenden von Gerätebefehlen

Es kann ganz normal im Webfront auf die Taste gedrückt werden dann wird der Befehl über den Broadlink verschickt.

Alternativ kann man den Befehl auch über ein Skript aufrufen. Dazu kann man zunächst die Geräte Instanz mit einem Doppelklick öffnen.
Hier stehen alle Befehle die bisher bei dem Gerät angelernt worden sind.

![ModulesCommands](docs/commands.png?raw=true "Commands")

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
Broadlink_LearnDeviceCode($InstanceID, $devicename, $command_name);
```   

Parameter _$devicename_ __*Gerätename*__ unter der das Broadlink Gerät angelegt wird, Befehle die zum gleichen Gerät gehören sollten den gleichen Gerätenamen nutzten

Parameter _$command_name_ __*Befehlsname*__ unter der der Code abgelegt wird und dann wieder aufgerufen werden kann z.B. Power

Parameter _$InstanceID_ __*ObjektID*__ der Broadlink I/O Instanz

_**Importieren von existierenden Codes**_

```php
Broadlink_ImportCode($InstanceID, $devicename, $command_name, $command);
```   

Parameter _$devicename_ __*Gerätename*__ unter der das Broadlink Gerät angelegt wird, Befehle die zum gleichen Gerät gehören sollten den gleichen Gerätenamen nutzten

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

### Broadlink IO: (Die Werte werden automatisch eingelesen nicht verändern)

| Eigenschaft       | Typ     | Standardwert | Funktion                                                  |
| :---------------: | :-----: | :----------: | :-------------------------------------------------------: |
| name              | string  | 		     | interner Gerätename des Broadlink                         |
| host              | string  | 		     | IP Adresse des Broadlink                                  |
| mac               | string  | 		     | MAC Adresse des Broadlink                                 |
| modell            | string  | 		     | Modell Typ Broadlink                                      |
| devicetype        | string  | 		     | interne Gerätetyp Bezeichnung Broadlink                   |
| CategoryID        | integer | 		     | ObjektId der Importkategorie für die Broadlink Geräte     |

Alle Werte werden über _Discover_ ausgelesen und sind _nicht_ vom Nutzer zu ändern um die Kommunikation zu gewährleisten.

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






