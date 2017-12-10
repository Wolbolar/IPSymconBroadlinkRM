<?

require_once(__DIR__ . "/../bootstrap.php");

use Fonzo\Broadlink\Broadlink;

class BroadlinkGateway extends IPSModule
{

    public function Create()
    {
	//Never delete this line!
        parent::Create();
		
		//These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
        $this->RegisterPropertyString("name", "");
        $this->RegisterPropertyString("host", "");
        $this->RegisterPropertyString("mac", "");
        $this->RegisterPropertyString("modell", "");
        $this->RegisterPropertyString("devicetype", "");
        $this->RegisterPropertyInteger("CategoryID", 0);
        $this->RegisterPropertyString("devicename", "");
        $this->RegisterPropertyString("command", "");
        $this->RegisterPropertyInteger("a1interval", 0);
        $this->RegisterPropertyBoolean("a1device", false);
        $this->RegisterTimer('A1Update', 0, 'Broadlink_A1Timer('.$this->InstanceID.');');
    }

    public function ApplyChanges()
    {
	//Never delete this line!
        parent::ApplyChanges();
        $change = false;
						
		
		$ParentID = $this->GetParent();
		
			
		// Wenn I/O verbunden ist
		if ($this->HasActiveParent($ParentID))
			{
				//Instanz aktiv
			}
		$devicetype = $this->ReadPropertyString("devicetype");
		if($devicetype == "0x2712" || $devicetype == "0x272a")
        {
            $this->RegisterVariableFloat("Temperature", "Temperatur", "~Temperature");
        }

        //Import Kategorie
        $ImportCategoryID = $this->ReadPropertyInteger('CategoryID');
        if ( $ImportCategoryID === 0)
        {
            // Status Error Kategorie zum Import auswählen
            $this->SetStatus(211);
        }
        elseif ( $ImportCategoryID != 0)
        {
            // Status Error Kategorie zum Import auswählen
            $this->SetStatus(102);
        }
        $this->SetA1Interval();
    }

		/**
        * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
        * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:
        *
        *
        */
    public function A1Timer()
    {
        $this->Discover();
    }

    protected function SetA1Interval()
    {
        $a1device = $this->ReadPropertyBoolean("a1device");
        $a1interval = $this->ReadPropertyInteger("a1interval");
        $interval = $a1interval * 60 * 1000;
        if($a1device)
        {

            $this->SetTimerInterval("A1Update", $interval);
        }
        else
        {
            $this->SetTimerInterval("A1Update", 0);
        }

    }

	public function LearnDeviceCode(string $devicename, string $command_name)
    {
        $result = $this->LearnDevice($devicename, $command_name);
        return $result;
    }

    public function Learn()
    {
        $devicename = $this->ReadPropertyString("devicename");
        $command_name = $this->ReadPropertyString("command");
        $result = $this->LearnDevice($devicename, $command_name);
        return $result;
    }

    public function ImportCode($devicename, $command_name, $commandhex)
    {
        if($devicename == "" || $command_name == "")
        {
            $this->SendDebug("Broadlink Learn:", "Empty device name or command name",0);
            $result = "Empty device name or command name";
            return $result;
        }
        $deviceident = str_replace(" ", "_", $devicename);
        $ImportCategoryID = $this->ReadPropertyInteger('CategoryID');
        $iid = $this->CreateInstanceByIdent($ImportCategoryID, $deviceident, $devicename);
        $commandsid = $this->CreateVariableByIdent($iid, "Commands", "Commands", 3);
        IPS_SetHidden($commandsid, true);
        $valuesjson = GetValue(IPS_GetObjectIDByIdent("Commands", $iid));
        $values = json_decode($valuesjson, true);
        $values[$command_name] = $commandhex;
        $command = json_encode($values);
        SetValue($commandsid, $command);
        $this->CreateWFVariable($iid, $deviceident, $command);
        return $iid;
    }

	protected function LearnDevice($devicename, $command_name)
    {

        if($devicename == "" || $command_name == "")
        {
            $this->SendDebug("Broadlink Learn:", "Empty device name or command name",0);
            $result = "Empty device name or command name";
            return $result;
        }
        $deviceident = str_replace(" ", "_", $devicename);
        $ImportCategoryID = $this->ReadPropertyInteger('CategoryID');
        $iid = $this->CreateInstanceByIdent($ImportCategoryID, $deviceident, $devicename);
        $commandsid = $this->CreateVariableByIdent($iid, "Commands", "Commands", 3);
        IPS_SetHidden($commandsid, true);
        $json = array();
        $info = array("devtype" => $this->ReadPropertyString("devicetype"), "name" => json_decode($this->ReadPropertyString("name")), "mac" => $this->ReadPropertyString("mac"), "host" => $this->ReadPropertyString("host"), "model" => $this->ReadPropertyString("modell"));
        $json['code'] = -1;
        $devtype = Broadlink::getdevtype($info['devtype']);
        if($devtype == 2)
        {

            $rm = Broadlink::CreateDevice($info['host'], $info['mac'], 80, $info['devtype']);

            $rm->Auth();
            $rm->Enter_learning();

            sleep(10);

            $json['hex'] = $rm->Check_data();

            $json['code'] = 1;

            $json['hex_number'] = '';

            foreach ($json['hex'] as $value) {
                $json['hex_number'] .= sprintf("%02x", $value);
            }

            if(strlen($command_name) > 0 && count($json['hex']) > 0)
            {
                $valuesjson = GetValue(IPS_GetObjectIDByIdent("Commands", $iid));
                $values = json_decode($valuesjson, true);
                $values[$command_name] = $json['hex_number'];
                $command = json_encode($values);
                SetValue($commandsid, $command);
                $this->CreateWFVariable($iid, $deviceident, $command);
            }
        }
        $result = json_encode($json, JSON_NUMERIC_CHECK);
        $this->SendDebug("Broadlink Learn:", $result,0);
        IPS_LogMessage("Broadlink Learn:", $result);
        return $result;
    }

    protected function CreateWFVariable($iid, $deviceident, $command)
    {
        $wfcommandid = $this->CreateVariableByIdent($iid, "WFCommands", "Command", 1);
        $values = json_decode($command, true);
        $valuescount = count($values);
        $commandass =  Array();
        $profilecounter = 0;
        foreach ($values as $key => $value)
        {
            $commandass[$profilecounter] = Array($profilecounter, $key,  "", -1);
            $profilecounter = $profilecounter + 1;
        }
        $profilename = "Broadlink.".$deviceident.".Command";
        $this->RegisterProfileAssociation($profilename, "Execute", "", "", 0, ($valuescount-1), 0, 0, 1, $commandass);
        IPS_SetVariableCustomProfile($wfcommandid, $profilename);
        BroadlinkDevice_EnableWFVariable($iid);
    }

    public function ForwardData($JSONString)
    {

        // Empfangene Daten von der Device Instanz
        $data = json_decode($JSONString);
        $datasend = $data->Buffer;
        $datasend = json_encode($datasend);
        $this->SendDebug("Broadlink Forward Data:",$datasend,0);

        // Hier würde man den Buffer im Normalfall verarbeiten
        // z.B. CRC prüfen, in Einzelteile zerlegen
        $payload = json_decode($datasend);
        $name = $payload->name;
        $command = $payload->command;
        if($command == "UpdateA1")
        {
            $this->Discover();
            $result = true;
        }
        else
        {
            $this->SendDebug("Broadlink Device:",$name,0);
            $this->SendDebug("Broadlink Command:",$command,0);
            $result = $this->SendCommand($command);
            //$this->SendDebug("Send Command Result:",$result,0);
        }
        return $result;
    }

    protected function SendCommand($command)
    {
        $this->SendDebug("Broadlink Send:", $command,0);
        $json = array();
        $info = array("devtype" => $this->ReadPropertyString("devicetype"), "name" => json_decode($this->ReadPropertyString("name")), "mac" => $this->ReadPropertyString("mac"), "host" => $this->ReadPropertyString("host"), "model" => $this->ReadPropertyString("modell"));
        $json['code'] = -1;
        $devtype = Broadlink::getdevtype($info['devtype']);

        if($devtype == 2)
        {

            $rm = Broadlink::CreateDevice($info['host'], $info['mac'], 80, $info['devtype']);

            $rm->Auth();
            $rm->Send_data($command);

            $json['code'] = 1;

        }
        $result = json_encode($json, JSON_NUMERIC_CHECK);
        $this->SendDebug("Broadlink Response:", $result,0);
        return $result;
    }

    public function Discover()
    {
        $result = array();

        $devices = Broadlink::Discover();
        foreach ($devices as $device)
        {

            $obj = array();

            $obj['devtype'] = $device->devtype();
            $obj['name'] = $device->name();
            $obj['mac'] = $device->mac();
            $obj['host'] = $device->host();
            $obj['model'] = $device->model();

            if($obj['model'] == "RM2" || $obj['model'] == "RM2 Pro Plus")
            {

                $device->Auth();
                $temperature = $device->Check_temperature();
                $obj['temperature'] = $temperature;
                $this->CheckExistingIdent($obj);
            }
            if($obj['model'] == "RM Mini")
            {

                // $device->Auth();
                $this->CheckExistingIdent($obj);
            }
            else if($obj['model'] == "A1")
            {

                $device->Auth();
                $data = $device->Check_sensors();

                $obj = array_merge($obj, $data);
                $iid = $this->CreateA1($obj);
                $this->UpdateA1($iid, $obj);
                IPS_SetProperty($this->InstanceID, "a1device", true);
                $this->SendDebug("Broadlink Discover:", "A1 Device found",0);
                IPS_ApplyChanges($this->InstanceID); //Neue Konfiguration übernehmen
            }
            array_push($result, $obj);
        }
        return $result;
    }

    protected function CheckExistingIdent($device)
    {
        $ident = IPS_GetObject($this->InstanceID)["ObjectIdent"];
        $mac = $device["mac"];
        $deviceident = str_replace(":", "_", $mac);
        if($ident == "")
        {
            // set ident
            IPS_SetIdent($this->InstanceID, $deviceident);
            $this->UpdateGatewayData($this->InstanceID, $device);
        }
        elseif($ident == $deviceident)
        {
            $this->UpdateGatewayData($this->InstanceID, $device);
        }
        else
        {
            $iid = $this->CreateNewGateway($device);
            $this->UpdateGatewayData($iid, $device);
        }
        return $deviceident;
    }

    protected function UpdateGatewayData($iid, $device)
    {
        $type = $device["devtype"];
        $host = $device["host"];
        $mac = $device["mac"];
        $model = $device["model"];
        $name = $device["name"];
        if(isset($device["temperature"]))
        {
            $temperature = floatval($device["temperature"]);
        }
        IPS_SetProperty($iid, "name", json_encode($name));
        $this->SendDebug("Broadlink Discover:", "Name ".json_encode($name),0);
        IPS_SetProperty($iid, "host", $host);
        $this->SendDebug("Broadlink Discover:", "Host ".$host,0);
        IPS_SetProperty($iid, "mac", $mac);
        $this->SendDebug("Broadlink Discover:", "Mac ".$mac,0);
        IPS_SetProperty($iid, "modell", $model);
        $this->SendDebug("Broadlink Discover:", "Model ".$model,0);
        IPS_SetProperty($iid, "devicetype", $type);
        $this->SendDebug("Broadlink Discover:", "Device type ".$type,0);
        IPS_ApplyChanges($iid); //Neue Konfiguration übernehmen

        if($model == "RM2" || $model == "RM2 Pro Plus")
        {
            $temperatureid = $this->CreateVariableByIdent($iid, "Temperature", "Temperatur", 2);
            $this->SendDebug("Broadlink Discover:", "Temperature ".$temperature,0);
            IPS_SetVariableCustomProfile($temperatureid, "~Temperature");
            SetValue($this->GetIDForIdent("Temperature"), $temperature);
        }
    }

    protected function CreateNewGateway($device)
    {
        $mac = $device["mac"];
        $deviceident = str_replace(":", "_", $mac);
        $iid = @IPS_GetObjectIDByIdent($deviceident, 0);
        if($iid === false)
        {
            $moduleid = "{E58707E8-8E2C-26D4-A7A9-2D6D6D93AB04}"; // Broadlink Gateway
            $iid = IPS_CreateInstance($moduleid);
            IPS_SetName($iid, "Broadlink Gateway");
            IPS_SetIdent($iid, $deviceident);
        }
        return $iid;
    }

    protected function CreateA1($device)
    {
        $mac = $device["mac"];
        $deviceident = str_replace(":", "_", $mac);
        $ImportCategoryID = $this->ReadPropertyInteger('CategoryID');
        $iid = @IPS_GetObjectIDByIdent($deviceident, $ImportCategoryID);
        if($iid === false)
        {
            $moduleid = "{1A1402D6-B4BD-F179-444E-9E351075D937}"; // Broadlink A1
            $iid = IPS_CreateInstance($moduleid);
            IPS_SetParent($iid, $ImportCategoryID);
            IPS_SetName($iid, "Broadlink A1 Sensor");
            IPS_SetIdent($iid, $deviceident);
        }
        return $iid;
    }

    protected function UpdateA1($iid, $device)
    {
        $devicename = IPS_GetName($iid);
        $type = $device["devtype"];
        $host = $device["host"];
        $mac = $device["mac"];
        $modell = $device["model"];
        $name = $device["name"];
        $temperature = floatval($device["temperature"]);
        $humidity = floatval($device["humidity"]);
        $light = intval($device["light"]);
        $air_quality = intval($device["air_quality"]);
        $noise = intval($device["noise"]);
        IPS_SetProperty($iid, "name", json_encode($name));
        $this->SendDebug("Broadlink Discover:", "Device ".$devicename."(".$iid."): Name ".json_encode($name),0);
        IPS_SetProperty($iid, "host", $host);
        $this->SendDebug("Broadlink Discover:", "Device ".$devicename."(".$iid."): Host ".$host,0);
        IPS_SetProperty($iid, "mac", $mac);
        $this->SendDebug("Broadlink Discover:", "Device ".$devicename."(".$iid."): Mac ".$mac,0);
        IPS_SetProperty($iid, "modell", $modell);
        $this->SendDebug("Broadlink Discover:", "Device ".$devicename."(".$iid."): Model ".$modell,0);
        IPS_SetProperty($iid, "devicetype", $type);
        $this->SendDebug("Broadlink Discover:", "Device ".$devicename."(".$iid."): Device type ".$type,0);
        IPS_ApplyChanges($iid); //Neue Konfiguration übernehmen
        $temperatureid = IPS_GetObjectIDByIdent("Temperature", $iid);
        SetValue($temperatureid, $temperature);
        $this->SendDebug("Broadlink A1:", "Device ".$devicename."(".$iid."): Temperature ".$temperature,0);
        $humidityid = IPS_GetObjectIDByIdent("Humidity", $iid);
        SetValue($humidityid, $humidity);
        $this->SendDebug("Broadlink A1:", "Device ".$devicename."(".$iid."): Humidity ".$humidity,0);
        $lightid = IPS_GetObjectIDByIdent("Light", $iid);
        SetValue($lightid, $light);
        $this->SendDebug("Broadlink A1:", "Device ".$devicename."(".$iid."): Light ".$light,0);
        $air_qualityid = IPS_GetObjectIDByIdent("Air_quality", $iid);
        SetValue($air_qualityid, $air_quality);
        $this->SendDebug("Broadlink A1:", "Device ".$devicename."(".$iid."): Air quality ".$air_quality,0);
        $noiseid = IPS_GetObjectIDByIdent("Noise", $iid);
        SetValue($noiseid, $noise);
        $this->SendDebug("Broadlink A1:", "Device ".$devicename."(".$iid."): Noise ".$noise,0);
    }

    protected function CreateVariableByIdent($id, $ident, $name, $type, $profile = "")
    {
        $vid = @IPS_GetObjectIDByIdent($ident, $id);
        if($vid === false) {
            $vid = IPS_CreateVariable($type);
            IPS_SetParent($vid, $id);
            IPS_SetName($vid, $name);
            IPS_SetIdent($vid, $ident);
            if($profile != "")
                IPS_SetVariableCustomProfile($vid, $profile);
        }
        return $vid;
    }

    // Create Broadlink Instance
    protected function CreateInstanceByIdent($id, $ident, $name, $moduleid = "{B5A1F2D9-0530-6130-5933-9D0E916E8F8A}")
    {
        $iid = @IPS_GetObjectIDByIdent($ident, $id);
        if($iid === false) {
            $iid = IPS_CreateInstance($moduleid);
            IPS_SetParent($iid, $id);
            IPS_SetName($iid, $name);
            IPS_SetIdent($iid, $ident);
        }
        return $iid;
    }

	################## DUMMYS / WOARKAROUNDS - protected

    protected function GetParent()
    {
        $instance = IPS_GetInstance($this->InstanceID);
        return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;
    }

    protected function HasActiveParent($ParentID)
    {
        if ($ParentID > 0)
        {
            $parent = IPS_GetInstance($ParentID);
            if ($parent['InstanceStatus'] == 102)
            {
                $this->SetStatus(102);
                return true;
            }
        }
        $this->SetStatus(203);
        return false;
    }

    protected function SetStatus($InstanceStatus)
    {
        if ($InstanceStatus <> IPS_GetInstance($this->InstanceID)['InstanceStatus'])
            parent::SetStatus($InstanceStatus);
    }

    protected function SendJSON ($data)
    {
        // Weiterleitung zu allen Gerät-/Device-Instanzen
        $this->SendDataToChildren(json_encode(Array("DataID" => "{A05B41B1-7478-8E54-296E-17F406FD3876}", "Buffer" => $data))); //  I/O RX GUI
    }

    //Configuration Form
    public function GetConfigurationForm()
    {
        $formhead = $this->FormHead();
        $formselection = $this->FormSelection();
        $formstatus = $this->FormStatus();
        $formactions = $this->FormActions();
        $formelementsend = '{ "type": "Label", "label": "__________________________________________________________________________________________________" }';

        return	'{ '.$formhead.$formselection.$formelementsend.'],'.$formactions.$formstatus.' }';
    }



    protected function FormSelection()
    {
        $a1device = $this->ReadPropertyBoolean("a1device");
        if($a1device)
        {
            $form = '{ "type": "Label", "label": "Category for Broadlink devices" },
                    { "type": "SelectCategory", "name": "CategoryID", "caption": "Category" },';
        }
        else
        {
            $form = '{ "type": "Label", "label": "Category for Broadlink devices" },
                    { "type": "SelectCategory", "name": "CategoryID", "caption": "Category" },
                    { "type": "Label", "label": "Update inteval A1 Sensor in minutes" },
                    { "type": "IntervalBox", "name": "a1interval", "caption": "minutes" },';
        }
        return $form;
    }

    protected function FormHead()
    {
        $mac = $this->ReadPropertyString("mac");
        if($mac == "")
        {
            $form = '"elements":
            [
				{ "type": "Label", "label": "Broadlink" },
				{ "type": "Label", "label": "Discover Device" },
				{ "type": "Button", "label": "Discover", "onClick": "Broadlink_Discover($id);" },';
        }
        else
        {
            $form = '"elements":
            [
				{ "type": "Label", "label": "Broadlink" },
				{ "type": "Label", "label": "Broadlink Name" },
				{
					"name": "name",
					"type": "ValidationTextBox",
					"caption": "Name"
				},
				{ "type": "Label", "label": "Broadlink IP address" },
				{
					"name": "host",
					"type": "ValidationTextBox",
					"caption": "IP address"
				},
				{ "type": "Label", "label": "Broadlink MAC address" },
				{
					"name": "mac",
					"type": "ValidationTextBox",
					"caption": "MAC address"
				},
				{ "type": "Label", "label": "Broadlink Modell" },
				{
					"name": "modell",
					"type": "ValidationTextBox",
					"caption": "Modell"
				},
				{ "type": "Label", "label": "Broadlink Device type" },
				{
					"name": "devicetype",
					"type": "ValidationTextBox",
					"caption": "Device type"
				},';
        }
        return $form;
    }

    protected function FormActions()
    {
        $mac = $this->ReadPropertyString("mac");
        if($mac == "")
        {
            $form = '"actions":
			[
				{ "type": "Label", "label": "Discover Device" },
				{ "type": "Button", "label": "Discover", "onClick": "Broadlink_Discover($id);" }
			],';
        }
        else
        {
            $form = '"actions":
			[
				{ "type": "Label", "label": "Discover Device" },
				{ "type": "Button", "label": "Discover", "onClick": "Broadlink_Discover($id);" }
			],';
        }

        return  $form;
    }

    protected function FormStatus()
    {
        $form = '"status":
            [
                {
                    "code": 101,
                    "icon": "inactive",
                    "caption": "Creating instance."
                },
				{
                    "code": 203,
                    "icon": "error",
                    "caption": "No active Broadlink I/O."
                },
				{
                    "code": 102,
                    "icon": "active",
                    "caption": "Broadlink created."
                },
                {
                    "code": 104,
                    "icon": "inactive",
                    "caption": "Interface closed."
                },
                {
                    "code": 211,
                    "icon": "error",
                    "caption": "choose category for Broadlink devices."
                }
            ]';
        return $form;
    }

	################## SEMAPHOREN Helper  - private

    private function lock($ident)
    {
        for ($i = 0; $i < 3000; $i++)
        {
            if (IPS_SemaphoreEnter("Broadlink_" . (string) $this->InstanceID . (string) $ident, 1))
            {
                return true;
            }
            else
            {
                IPS_Sleep(mt_rand(1, 5));
            }
        }
        return false;
    }

    private function unlock($ident)
    {
          IPS_SemaphoreLeave("Broadlink_" . (string) $this->InstanceID . (string) $ident);
    }

    //Profile
    protected function RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Vartype)
    {

        if(!IPS_VariableProfileExists($Name))
        {
            IPS_CreateVariableProfile($Name, $Vartype); // 0 boolean, 1 int, 2 float, 3 string,
        }
        else
        {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != $Vartype)
                throw new Exception("Variable profile type does not match for profile ".$Name);
        }

        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize); // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite
    }

    protected function RegisterProfileAssociation($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Vartype, $Associations)
    {
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        }
        /*
        else {
            //undefiened offset
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        */
        $this->RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Vartype);

        //boolean IPS_SetVariableProfileAssociation ( string $ProfilName, float $Wert, string $Name, string $Icon, integer $Farbe )
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }

    }
}
?>