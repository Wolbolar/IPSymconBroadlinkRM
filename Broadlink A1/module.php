<?

require_once(__DIR__ . "/../bootstrap.php");

use Fonzo\Broadlink\Broadlink;

class BroadlinkA1 extends IPSModule
{

    public function Create()
    {
	//Never delete this line!
        parent::Create();
		
		//These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
        $this->ConnectParent("{E58707E8-8E2C-26D4-A7A9-2D6D6D93AB04}"); // Broadlink I/O
        $this->RegisterPropertyString("name", "");
        $this->RegisterPropertyString("host", "");
        $this->RegisterPropertyString("mac", "");
        $this->RegisterPropertyString("modell", "");
        $this->RegisterPropertyString("devicetype", "");
        $this->RegisterVariableFloat("Temperature", "Temperatur", "~Temperature");
        $this->RegisterVariableFloat("Humidity", "Feuchtigkeit", "~Humidity.F");
        $lightass =  Array(
            Array(0, "dunkel",  "Light", -1),
            Array(1, "gedimmt",  "Light", -1),
            Array(2, "normal",  "Light", -1),
            Array(3, "hell",  "Light", -1),
            Array(4, "unkown",  "Light", -1)
        );
        $airqualityass =  Array(
            Array(0, "hervorragend",  "Factory", -1),
            Array(1, "gut",  "Factory", -1),
            Array(2, "normal",  "Factory", -1),
            Array(3, "schlecht",  "Factory", -1),
            Array(4, "unkown",  "Factory", -1)
        );
        $noiseass =  Array(
            Array(0, "ruhig",  "Speaker", -1),
            Array(1, "normal",  "Speaker", -1),
            Array(2, "noisy",  "Speaker", -1),
            Array(3, "unkown",  "Speaker", -1)
        );
        $this->RegisterProfileAssociation("Broadlink.A1.Light", "Light", "", "", 0, 4, 0, 0, 1, $lightass);
        $this->RegisterProfileAssociation("Broadlink.A1.Airquality", "Factory", "", "", 0, 4, 0, 0, 1, $airqualityass);
        $this->RegisterProfileAssociation("Broadlink.A1.Noise", "Speaker", "", "", 0, 3, 0, 0, 1, $noiseass);
        $this->RegisterVariableInteger("Light", "Licht", "Broadlink.A1.Light");
        $this->RegisterVariableInteger("Air_quality", "Luftqualität", "Broadlink.A1.Airquality");
        $this->RegisterVariableInteger("Noise", "Lautstärke", "Broadlink.A1.Noise");
    }

    public function ApplyChanges()
    {
	//Never delete this line!
        parent::ApplyChanges();
        $change = false;

        $this->SetStatus(102);
    }

		/**
        * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
        * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:
        *
        *
        */


    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString);
        $objectid = $data->Buffer->objectid;
        $values = $data->Buffer->values;
        $valuesjson = json_encode($values);
        if (($this->InstanceID) == $objectid)
        {
            //Parse and write values to our variables
            //$this->WriteValues($valuesjson);
        }
    }

    public function Update()
    {
        $deviceident = IPS_GetObject($this->InstanceID)["ObjectIdent"];
        $payload = array("name" => $deviceident, "command" => "UpdateA1");
        $this->SendDebug("Send Data:",json_encode($payload),0);

        //an Splitter schicken
        $result = $this->SendDataToParent(json_encode(Array("DataID" => "{EFC61574-A0BC-2FBB-065A-8C6B42FC2646}", "Buffer" => $payload))); // Interface GUI
        $this->SendDebug("Send Data Result:",$result,0);
        return $result;
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
        $form = '';
        return $form;
    }

    protected function FormHead()
    {
        $mac = $this->ReadPropertyString("mac");
        if($mac == "")
        {
            $form = '"elements":
            [
				{ "type": "Label", "label": "This device is created by the Broadlink gateway, please go to the Broadlink gateway and press Discover" },';
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
				{ "type": "Button", "label": "Discover", "onClick": "BroadlinkA1_Update($id);" }
			],';
        }
        else
        {
            $form = '"actions":
			[
				{ "type": "Label", "label": "Update data" },
				{ "type": "Button", "label": "Update", "onClick": "BroadlinkA1_Update($id);" }
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
                    "caption": "Broadlink A1 created."
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