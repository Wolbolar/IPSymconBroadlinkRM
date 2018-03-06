<?

class BroadlinkDevice extends IPSModule
{

	public function Create()
	{
		//Never delete this line!
		parent::Create();
		$this->ConnectParent("{E58707E8-8E2C-26D4-A7A9-2D6D6D93AB04}"); // Broadlink I/O
		$this->RegisterPropertyString("Name", "");
	}

	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();

		$this->ValidateConfiguration();
	}

	private function ValidateConfiguration()
	{
		$this->SetupVariables();
		$this->SetStatus(102);
	}

	protected function SetupVariables()
	{
		$commandsid = $this->CreateVariableByIdent($this->InstanceID, "Commands", "Commands", 3);
		IPS_SetHidden($commandsid, true);
		$command = GetValue(IPS_GetObjectIDByIdent("Commands", $this->InstanceID));
		$deviceident = @IPS_GetObject($this->InstanceID)["ObjectIdent"];
		$this->CreateWFVariables($deviceident, $command);
	}

	public function SendCommand(string $command)
	{
		$deviceident = IPS_GetObject($this->InstanceID)["ObjectIdent"];
		$commandhex = $this->GetCommand($command);
		$payload = array("name" => $deviceident, "command" => $command, "command_code" => $commandhex);
		$this->SendDebug("Send Data:", json_encode($payload), 0);

		//an Splitter schicken
		$result = $this->SendDataToParent(json_encode(Array("DataID" => "{EFC61574-A0BC-2FBB-065A-8C6B42FC2646}", "Buffer" => $payload))); // Interface GUI
		$this->SendDebug("Send Data Result:", $result, 0);
		return $result;
	}

	protected function CreateWFVariable($deviceident, $command)
	{
		// count commands
		$values = json_decode($command, true);
		$valuescount = count($values);
		// 32 Limit
		if ($valuescount > 32 && $valuescount <= 64) {
			$profilename = "Broadlink." . $deviceident . ".Command1";
			$profilecounter = 31;
			$this->CreateBroadlinkWebFrontVariable("WFCommands1", "Command 1", $values, $profilename, $profilecounter);
		} elseif ($valuescount > 64 && $valuescount <= 96) {
			$profilename = "Broadlink." . $deviceident . ".Command2";
			$profilecounter = 63;
			$this->CreateBroadlinkWebFrontVariable("WFCommands2", "Command 2", $values, $profilename, $profilecounter);
		} elseif ($valuescount > 96 && $valuescount <= 128) {
			$profilename = "Broadlink." . $deviceident . ".Command3";
			$profilecounter = 95;
			$this->CreateBroadlinkWebFrontVariable("WFCommands3", "Command 3", $values, $profilename, $profilecounter);
		} elseif ($valuescount > 128 && $valuescount <= 160) {
			$profilename = "Broadlink." . $deviceident . ".Command4";
			$profilecounter = 127;
			$this->CreateBroadlinkWebFrontVariable("WFCommands4", "Command 4", $values, $profilename, $profilecounter);
		} elseif ($valuescount > 160 && $valuescount <= 192) {
			$profilename = "Broadlink." . $deviceident . ".Command5";
			$profilecounter = 159;
			$this->CreateBroadlinkWebFrontVariable("WFCommands5", "Command 5", $values, $profilename, $profilecounter);
		} elseif ($valuescount > 192 && $valuescount <= 224) {
			$profilename = "Broadlink." . $deviceident . ".Command6";
			$profilecounter = 191;
			$this->CreateBroadlinkWebFrontVariable("WFCommands6", "Command 6", $values, $profilename, $profilecounter);
		} else {
			$profilename = "Broadlink." . $deviceident . ".Command";
			$profilecounter = 0;
			$this->CreateBroadlinkWebFrontVariable("WFCommands", "Command", $values, $profilename, $profilecounter);
		}
	}

	protected function CreateBroadlinkWebFrontVariable($ident, $name, $values, $profilename, $profilecounter)
	{
		$wfcommandid = $this->CreateVariableByIdent($this->InstanceID, $ident, $name, 1);
		$commandass = Array();
		$profilelimit = $profilecounter + 31;
		$profilekey = 0;
		$i = 0;
		foreach ($values as $key => $value) {
			if ($i >= $profilecounter && $i <= $profilelimit) {
				$commandass[$profilekey] = Array($i, $key, "", -1);
				$profilekey = $profilekey + 1;
			}
			$i++;
			if ($i == $profilelimit) {
				break;
			}
		}
		$this->RegisterProfileAssociation($profilename, "Execute", "", "", 0, $profilekey, 0, 0, 1, $commandass);
		IPS_SetVariableCustomProfile($wfcommandid, $profilename);
		$this->EnableAction($ident);
		return $wfcommandid;
	}

	protected function GetCommand($command)
	{
		$valuesjson = GetValue($this->GetIDForIdent("Commands"));
		$values = json_decode($valuesjson, true);
		$keyexists = array_key_exists($command, $values);
		if ($keyexists) {
			$commandhex = $values[$command];
		} else {
			$commandhex = false;
		}
		return $commandhex;
	}

	protected function GetCommandName($Ident, $Value)
	{
		$deviceident = IPS_GetObject($this->InstanceID)["ObjectIdent"];
		if ($Ident == "WFCommands") {
			$profilename = "Broadlink." . $deviceident . ".Command";
		} elseif ($Ident == "WFCommands1") {
			$profilename = "Broadlink." . $deviceident . ".Command1";
		} elseif ($Ident == "WFCommands2") {
			$profilename = "Broadlink." . $deviceident . ".Command2";
		} elseif ($Ident == "WFCommands3") {
			$profilename = "Broadlink." . $deviceident . ".Command3";
		} elseif ($Ident == "WFCommands4") {
			$profilename = "Broadlink." . $deviceident . ".Command4";
		} elseif ($Ident == "WFCommands5") {
			$profilename = "Broadlink." . $deviceident . ".Command5";
		} else {
			$profilename = "Broadlink." . $deviceident . ".Command6";
		}
		$associations = IPS_GetVariableProfile($profilename)["Associations"];
		$this->SendDebug("Profile Associations:", json_encode($associations), 0);
		$command_name = false;
		foreach ($associations as $key => $association) {
			if ($association["Value"] == $Value) {
				$command_name = $association["Name"];
			}
		}
		return $command_name;
	}

	public function GetAvailableCommands()
	{
		$commands = json_decode(GetValue($this->GetIDForIdent("Commands")), true);
		return $commands;
	}

	public function ReceiveData($JSONString)
	{
		$data = json_decode($JSONString);
		$objectident = $data->Buffer->ident;
		$this->SendDebug("Receive Data:", "Send to Device Ident: " . $objectident, 0);
		$deviceident = IPS_GetObject($this->InstanceID)["ObjectIdent"];
		$command = json_encode($data->Buffer->command);
		$this->SendDebug("Receive Data:", $command, 0);
		if ($deviceident == $objectident) {
			$this->SendDebug("Receive Data:", "Data for Device (Ident: " . $deviceident . ")", 0);
			//Parse and write values to our variables
			$this->SetValue('Commands', $command);
			$this->CreateWFVariable($deviceident, $command);
		}
	}

	public function RequestAction($Ident, $Value)
	{
		switch ($Ident) {
			case "WFCommands":
				if ($this->GetIDForIdent("WFCommands"))
					$this->SetValue('WFCommands', $Value);
				$command = $this->GetCommandName($Ident, $Value);
				$this->SendCommand($command);

				break;
			case "WFCommands1":
				if ($this->GetIDForIdent("WFCommands1"))
					$this->SetValue('WFCommands1', $Value);
				$command = $this->GetCommandName($Ident, $Value);
				$this->SendCommand($command);

				break;
			case "WFCommands2":
				if ($this->GetIDForIdent("WFCommands2"))
					$this->SetValue('WFCommands2', $Value);
				$command = $this->GetCommandName($Ident, $Value);
				$this->SendCommand($command);

				break;
			case "WFCommands3":
				if ($this->GetIDForIdent("WFCommands3"))
					$this->SetValue('WFCommands3', $Value);
				$command = $this->GetCommandName($Ident, $Value);
				$this->SendCommand($command);

				break;
			case "WFCommands4":
				SetValue($this->GetIDForIdent("WFCommands4"), $Value);
				$command = $this->GetCommandName($Ident, $Value);
				$this->SendCommand($command);

				break;
			case "WFCommands5":
				if ($this->GetIDForIdent("WFCommands5"))
					$this->SetValue('WFCommands5', $Value);
				$command = $this->GetCommandName($Ident, $Value);
				$this->SendCommand($command);

				break;
			case "WFCommands6":
				if ($this->GetIDForIdent("WFCommands6"))
					$this->SetValue('WFCommands6', $Value);
				$command = $this->GetCommandName($Ident, $Value);
				$this->SendCommand($command);

				break;
			default:
				$this->SendDebug("Profile:", "Invalid ident", 0);
		}
	}


	//Profile
	protected function RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Vartype)
	{

		if (!IPS_VariableProfileExists($Name)) {
			IPS_CreateVariableProfile($Name, $Vartype); // 0 boolean, 1 int, 2 float, 3 string,
		} else {
			$profile = IPS_GetVariableProfile($Name);
			if ($profile['ProfileType'] != $Vartype) {
				$this->SendDebug("Profile:", "Variable profile type does not match for profile " . $Name, 0);
			}
		}

		IPS_SetVariableProfileIcon($Name, $Icon);
		IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
		IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
		IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize); // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite
	}

	protected function RegisterProfileAssociation($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Vartype, $Associations)
	{
		if (sizeof($Associations) === 0) {
			$MinValue = 0;
			$MaxValue = 0;
		}
		$this->RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Vartype);

		//boolean IPS_SetVariableProfileAssociation ( string $ProfilName, float $Wert, string $Name, string $Icon, integer $Farbe )
		foreach ($Associations as $Association) {
			IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
		}

	}

	//Configuration Form
	public function GetConfigurationForm()
	{
		$formhead = $this->FormHead();
		$formselection = $this->FormSelection();
		$formstatus = $this->FormStatus();
		$formactions = $this->FormActions();
		$formelementsend = '{ "type": "Label", "label": "__________________________________________________________________________________________________" }';

		return '{ ' . $formhead . $formselection . $formelementsend . '],' . $formactions . $formstatus . ' }';
	}


	protected function FormSelection()
	{
		$form = '';
		return $form;
	}

	protected function FormHead()
	{
		$commands = $this->GetAvailableCommands();

		$form = '"elements":
            [
            { "type": "Label", "label": "available commands" },
            ';
		foreach ($commands as $key => $command) {
			$form .= '{ "type": "Label", "label": "' . $key . '" },';
		}

		return $form;
	}

	protected function FormActions()
	{
		$form = '"actions":
			[
				
			],';

		return $form;
	}

	protected function FormStatus()
	{
		$form = '"status":
            [
                {
                    "code": 101,
                    "icon": "inactive",
                    "caption": "creating instance"
                },
				{
                    "code": 102,
                    "icon": "active",
                    "caption": "configuration valid"
                },
                {
                    "code": 104,
                    "icon": "inactive",
                    "caption": "Broadlink Device is inactive"
                }
            ]';
		return $form;
	}

	protected function CreateVariableByIdent($id, $ident, $name, $type, $profile = "")
	{
		$vid = @IPS_GetObjectIDByIdent($ident, $id);
		if ($vid === false) {
			$vid = IPS_CreateVariable($type);
			IPS_SetParent($vid, $id);
			IPS_SetName($vid, $name);
			IPS_SetIdent($vid, $ident);
			if ($profile != "")
				IPS_SetVariableCustomProfile($vid, $profile);
		}
		return $vid;
	}

	protected function CreateWFVariables($deviceident, $command)
	{
		if ($command == "") {
			$this->SendDebug("Command:", "empty", 0);
		} else {
			// count commands
			$values = json_decode($command, true);
			$valuescount = count($values);
			// 32 Limit
			if ($valuescount > 32 && $valuescount <= 64) {
				$profilename = "Broadlink." . $deviceident . ".Command";
				$profilecounter = 0;
				$this->CreateWebFrontVariable("WFCommands", "Command", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command1";
				$profilecounter = 31;
				$this->CreateWebFrontVariable("WFCommands1", "Command 1", $values, $profilename, $profilecounter);
			} elseif ($valuescount > 64 && $valuescount <= 96) {
				$profilename = "Broadlink." . $deviceident . ".Command";
				$profilecounter = 0;
				$this->CreateWebFrontVariable("WFCommands", "Command", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command1";
				$profilecounter = 31;
				$this->CreateWebFrontVariable("WFCommands1", "Command 1", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command2";
				$profilecounter = 63;
				$this->CreateWebFrontVariable("WFCommands2", "Command 2", $values, $profilename, $profilecounter);
			} elseif ($valuescount > 96 && $valuescount <= 128) {
				$profilename = "Broadlink." . $deviceident . ".Command";
				$profilecounter = 0;
				$this->CreateWebFrontVariable("WFCommands", "Command", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command1";
				$profilecounter = 31;
				$this->CreateWebFrontVariable("WFCommands1", "Command 1", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command2";
				$profilecounter = 63;
				$this->CreateWebFrontVariable("WFCommands2", "Command 2", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command3";
				$profilecounter = 95;
				$this->CreateWebFrontVariable("WFCommands3", "Command 3", $values, $profilename, $profilecounter);
			} elseif ($valuescount > 128 && $valuescount <= 160) {
				$profilename = "Broadlink." . $deviceident . ".Command";
				$profilecounter = 0;
				$this->CreateWebFrontVariable("WFCommands", "Command", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command1";
				$profilecounter = 31;
				$this->CreateWebFrontVariable("WFCommands1", "Command 1", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command2";
				$profilecounter = 63;
				$this->CreateWebFrontVariable("WFCommands2", "Command 2", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command3";
				$profilecounter = 95;
				$this->CreateWebFrontVariable("WFCommands3", "Command 3", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command4";
				$profilecounter = 127;
				$this->CreateWebFrontVariable("WFCommands4", "Command 4", $values, $profilename, $profilecounter);
			} elseif ($valuescount > 160 && $valuescount <= 192) {
				$profilename = "Broadlink." . $deviceident . ".Command";
				$profilecounter = 0;
				$this->CreateWebFrontVariable("WFCommands", "Command", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command1";
				$profilecounter = 31;
				$this->CreateWebFrontVariable("WFCommands1", "Command 1", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command2";
				$profilecounter = 63;
				$this->CreateWebFrontVariable("WFCommands2", "Command 2", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command3";
				$profilecounter = 95;
				$this->CreateWebFrontVariable("WFCommands3", "Command 3", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command4";
				$profilecounter = 127;
				$this->CreateWebFrontVariable("WFCommands4", "Command 4", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command5";
				$profilecounter = 159;
				$this->CreateWebFrontVariable("WFCommands5", "Command 5", $values, $profilename, $profilecounter);
			} elseif ($valuescount > 192 && $valuescount <= 224) {
				$profilename = "Broadlink." . $deviceident . ".Command";
				$profilecounter = 0;
				$this->CreateWebFrontVariable("WFCommands", "Command", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command1";
				$profilecounter = 31;
				$this->CreateWebFrontVariable("WFCommands1", "Command 1", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command2";
				$profilecounter = 63;
				$this->CreateWebFrontVariable("WFCommands2", "Command 2", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command3";
				$profilecounter = 95;
				$this->CreateWebFrontVariable("WFCommands3", "Command 3", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command4";
				$profilecounter = 127;
				$this->CreateWebFrontVariable("WFCommands4", "Command 4", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command5";
				$profilecounter = 159;
				$this->CreateWebFrontVariable("WFCommands5", "Command 5", $values, $profilename, $profilecounter);

				$profilename = "Broadlink." . $deviceident . ".Command6";
				$profilecounter = 191;
				$this->CreateWebFrontVariable("WFCommands6", "Command 6", $values, $profilename, $profilecounter);
			} else {
				$profilename = "Broadlink." . $deviceident . ".Command";
				$profilecounter = 0;
				$this->CreateWebFrontVariable("WFCommands", "Command", $values, $profilename, $profilecounter);
			}

		}
	}

	protected function CreateWebFrontVariable($ident, $name, $values, $profilename, $profilecounter)
	{
		$wfcommandid = $this->CreateVariableByIdent($this->InstanceID, $ident, $name, 1);
		$commandass = Array();
		$profilelimit = $profilecounter + 31;
		$profilekey = 0;
		$i = 0;
		foreach ($values as $key => $value) {
			if ($i >= $profilecounter && $i <= $profilelimit) {
				$commandass[$profilekey] = Array($i, $key, "", -1);
				$profilekey = $profilekey + 1;
			}
			$i++;
			if ($i == $profilelimit) {
				break;
			}
		}
		$this->RegisterProfileAssociation($profilename, "Execute", "", "", 0, $profilekey, 0, 0, 1, $commandass);
		IPS_SetVariableCustomProfile($wfcommandid, $profilename);
		$this->EnableAction($ident);
		return $wfcommandid;
	}

	//Add this Polyfill for IP-Symcon 4.4 and older
	protected function SetValue($Ident, $Value)
	{

		if (IPS_GetKernelVersion() >= 5) {
			parent::SetValue($Ident, $Value);
		} else {
			SetValue($this->GetIDForIdent($Ident), $Value);
		}
	}
}

?>
