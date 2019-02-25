<?
declare(strict_types=1);

require_once __DIR__ . '/../libs/ConstHelper.php';
require_once __DIR__ . '/../libs/BufferHelper.php';
require_once __DIR__ . '/../libs/DebugHelper.php';



class BroadlinkConfigurator extends IPSModule
{
	use BufferHelper,
		DebugHelper;

	public function Create()
	{
		//Never delete this line!
		parent::Create();

		// 1. Verfügbarer Broadlink IO wird verbunden oder neu erzeugt, wenn nicht vorhanden.
		$this->ConnectParent("{E58707E8-8E2C-26D4-A7A9-2D6D6D93AB04}");
		$this->RegisterPropertyString("io_name", "");
		$this->RegisterPropertyString("io_host", "");
		$this->RegisterPropertyString("io_mac", "");
		$this->RegisterPropertyString("io_model", "");
		$this->RegisterPropertyString("broadlink_devices", "[]");
		$this->RegisterPropertyInteger("ImportCategoryID", 0);
		$this->RegisterPropertyBoolean("BroadlinkScript", false);

		//we will wait until the kernel is ready
		$this->RegisterMessage(0, IPS_KERNELMESSAGE);
	}

	/**
	 * Interne Funktion des SDK.
	 */
	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();

		if (IPS_GetKernelRunlevel() !== KR_READY) {
			return;
		}

		$ParentID = $this->GetParent();

		// Wenn I/O verbunden ist
		if ($this->HasActiveParent($ParentID)) {
			$this->SendDebug("Broadlink:", "Parent active", 0);
		}

		//Import Kategorie
		$ImportCategoryID = $this->ReadPropertyInteger('ImportCategoryID');
		if ($ImportCategoryID === 0) {
			// Status Error Kategorie zum Import auswählen
			$this->SetStatus(211);
		} elseif ($ImportCategoryID != 0) {
			// Status Error Kategorie zum Import auswählen
			$this->SetStatus(102);
		}
	}


	protected function GetParent()
	{
		$instance = IPS_GetInstance($this->InstanceID);
		return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;
	}

	protected function HasActiveParent($ParentID)
	{
		if ($ParentID > 0) {
			$parent = IPS_GetInstance($ParentID);
			if ($parent['InstanceStatus'] == 102) {
				$this->SetStatus(102);
				return true;
			}
		}
		$this->SetStatus(203);
		return false;
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
	{

		switch ($Message) {
			case IM_CHANGESTATUS:
				if ($Data[0] === IS_ACTIVE) {
					$this->ApplyChanges();
				}
				break;

			case IPS_KERNELMESSAGE:
				if ($Data[0] === KR_READY) {
					$this->ApplyChanges();
				}
				break;

			default:
				break;
		}
	}

	public function CreateDevice($devicename, $type)
	{
		$devices_json = $this->ReadPropertyString("broadlink_devices");
		$devices = json_decode($devices_json, true);
		if($type != 0)
		{
			if($type == 1)
			{
				$devtype = "IR Device";
				$model = "IR";
			}
			else {
				$devtype = "RF Device";
				$model = "RF";
			}
			$devices[] = ["devtype" => $devtype, "name" => $devicename, "mac" => $this->CreateMAC(), "host" => "", "model" => $model];
		}

		$devices_json = json_encode($devices);
		IPS_SetProperty($this->InstanceID, "broadlink_devices", $devices_json);
		IPS_ApplyChanges($this->InstanceID);
	}

	public function RemoveDevices()
	{
		$devices_json = $this->ReadPropertyString("broadlink_devices");
		$devices = json_decode($devices_json, true);
		$BroadlinkInstanceIDList = IPS_GetInstanceListByModuleID('{B5A1F2D9-0530-6130-5933-9D0E916E8F8A}'); // Broadlink Devices
		foreach ($devices as $key => $device)
		{
			if($device["model"] == "IR" || $device["model"] == "RF")
			{
				foreach ($BroadlinkInstanceIDList as $BroadlinkInstanceID) {
					if ($device["mac"] == IPS_GetProperty($BroadlinkInstanceID, 'mac')) {
						$broadlink_device_name = IPS_GetName($BroadlinkInstanceID);
						$this->SendDebug('Broadlink Config', 'device found: '.utf8_decode($broadlink_device_name).' ('.$BroadlinkInstanceID.')' , 0);
					}
					else
					{
						// delete
						$this->SendDebug('Broadlink Config', 'delete device : '.$device["mac"] , 0);
						unset($devices[$key]);
					}
				}
			}
		}
		$this->SendDebug('Broadlink Config', 'devices : '.json_encode($devices) , 0);
		$devices_json = json_encode($devices);
		IPS_SetProperty($this->InstanceID, "broadlink_devices", $devices_json);
		IPS_ApplyChanges($this->InstanceID);
	}

	protected function CreateMAC()
	{
		$mac = implode(':', str_split(substr(md5(strval(mt_rand())), 0, 12), 2));
		return $mac;
	}

	// Geräte Skripte und Links anlegen
	public function SetupBroadlink()
	{
		$HubCategoryID = $this->CreateBroadlinkCategory();
		//Skripte installieren
		$BroadlinkScript = $this->ReadPropertyBoolean('BroadlinkScript');
		if ($BroadlinkScript == true) {
			$this->SendDebug("Broadlink Hub Configurator", "Setup Scripts", 0);
			$this->SetBroadlinkInstanceScripts($HubCategoryID);
		}
	}

	protected function CreateBroadlinkCategory()
	{
		$io_model = $this->ReadPropertyString("io_model");
		$io_host = $this->ReadPropertyString("io_host");
		$hubipident = str_replace('.', '_', $io_host); // Replaces all . with underline.
		$CategoryID = $this->ReadPropertyInteger('ImportCategoryID');
		//Prüfen ob Kategorie schon existiert
		$HubCategoryID = @IPS_GetObjectIDByIdent("CatBroadlinkHub_" . $hubipident, $CategoryID);
		if ($HubCategoryID === false) {
			$HubCategoryID = IPS_CreateCategory();
			IPS_SetName($HubCategoryID,  $io_model . " (" . $io_host . ")");
			IPS_SetIdent($HubCategoryID, "CatBroadlinkHub_" . $hubipident); // Ident muss eindeutig sein
			IPS_SetInfo($HubCategoryID, $io_host);
			IPS_SetParent($HubCategoryID, $CategoryID);
		}
		$this->SendDebug("Broadlink Skript Category", strval($HubCategoryID), 0);
		return $HubCategoryID;
	}

	protected function GetCurrentBroadlinkDevices()
	{
		$BroadlinkInstanceIDList = IPS_GetInstanceListByModuleID('{B5A1F2D9-0530-6130-5933-9D0E916E8F8A}'); // Broadlink Devices
		$BroadlinkInstanceList = [];
		foreach($BroadlinkInstanceIDList as $key => $BroadlinkInstanceID)
		{
			$mac = IPS_GetProperty($BroadlinkInstanceID, "mac");
			$ident = IPS_GetProperty($BroadlinkInstanceID, "ident");
			$name = IPS_GetName($BroadlinkInstanceID);
			$BroadlinkInstanceList[$ident] = ["objid" => $BroadlinkInstanceID, "ident" => $ident, "mac" => $mac, "name" => $name];
		}
		return $BroadlinkInstanceList;
	}

	protected function SetBroadlinkInstanceScripts($HubCategoryID)
	{
		$devices = $this->GetCurrentBroadlinkDevices(); // Broadlink Devices
		if(!empty($devices)) {
			foreach ($devices as $device) {
				//Prüfen ob Kategorie schon existiert
				$MainCatID = @IPS_GetObjectIDByIdent("Broadlink_Device_Cat" . $device["ident"], $HubCategoryID);
				if ($MainCatID === false) {
					$MainCatID = IPS_CreateCategory();
					IPS_SetName($MainCatID, utf8_decode($device["name"]));
					IPS_SetInfo($MainCatID, $device["mac"]);
					IPS_SetIdent($MainCatID, "Broadlink_Device_Cat" . $device["ident"]);
					IPS_SetParent($MainCatID, $HubCategoryID);
				}
				$objid = $device["objid"];
				$name = $device["name"];
				$commands = BroadlinkDevice_GetAvailableCommands($objid);
				foreach ($commands as $key => $command) {
					//Prüfen ob Script schon existiert
					$Scriptname = $key;
					$command_ident = $this->CreateIdent("Broadlink_Device_" . $this->CreateIdent($name) . "_Command_" . $this->CreateIdent($Scriptname));
					$ScriptID = @IPS_GetObjectIDByIdent($command_ident, $MainCatID);
					if ($ScriptID === false) {
						$ScriptID = IPS_CreateScript(0);
						IPS_SetName($ScriptID, $Scriptname);
						IPS_SetParent($ScriptID, $MainCatID);
						IPS_SetIdent($ScriptID, $command_ident);
						$content = "<? BroadlinkDevice_SendCommand(" . $objid . ", \"" . $key . "\");?>";
						IPS_SetScriptContent($ScriptID, $content);
					}
				}
			}
		}
	}


	protected function CreateIdent($str)
	{
		$search = array("ä", "ö", "ü", "ß", "Ä", "Ö",
			"Ü", "&", "é", "á", "ó",
			" :)", " :D", " :-)", " :P",
			" :O", " ;D", " ;)", " ^^",
			" :|", " :-/", ":)", ":D",
			":-)", ":P", ":O", ";D", ";)",
			"^^", ":|", ":-/", "(", ")", "[", "]",
			"<", ">", "!", "\"", "§", "$", "%", "&",
			"/", "(", ")", "=", "?", "`", "´", "*", "'",
			"-", ":", ";", "²", "³", "{", "}",
			"\\", "~", "#", "+", ".", ",",
			"=", ":", "=)");
		$replace = array("ae", "oe", "ue", "ss", "Ae", "Oe",
			"Ue", "und", "e", "a", "o", "", "",
			"", "", "", "", "", "", "", "", "",
			"", "", "", "", "", "", "", "", "",
			"", "", "", "", "", "", "", "", "",
			"", "", "", "", "", "", "", "", "",
			"", "", "", "", "", "", "", "", "",
			"", "", "", "", "", "", "", "", "", "");

		$str = str_replace($search, $replace, $str);
		$str = str_replace(' ', '_', $str); // Replaces all spaces with underline.
		$how = '_';
		//$str = strtolower(preg_replace("/[^a-zA-Z0-9]+/", trim($how), $str));
		$str = preg_replace("/[^a-zA-Z0-9]+/", trim($how), $str);
		return $str;
	}


	//Profile
	protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
	{

		if (!IPS_VariableProfileExists($Name)) {
			IPS_CreateVariableProfile($Name, 1);
		} else {
			$profile = IPS_GetVariableProfile($Name);
			if ($profile['ProfileType'] != 1)
			{
				$this->SendDebug("Harmony Hub", "Variable profile type does not match for profile " . $Name, 0);
			}
		}

		IPS_SetVariableProfileIcon($Name, $Icon);
		IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
		IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
		IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize); // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite

	}

	protected function RegisterProfileIntegerAss($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Associations)
	{
		if (sizeof($Associations) === 0) {
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
		$this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits);

		//boolean IPS_SetVariableProfileAssociation ( string $ProfilName, float $Wert, string $Name, string $Icon, int $Farbe )
		foreach ($Associations as $Association) {
			IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
		}

	}

	public function RefreshListConfiguration()
	{
		$this->Get_ListConfiguration();
	}

	/**
	 * Liefert alle Geräte.
	 *
	 * @return array configlist all devices
	 */
	private function Get_ListConfiguration()
	{
		$config_list = [];
		$BroadlinkInstanceIDList = IPS_GetInstanceListByModuleID('{B5A1F2D9-0530-6130-5933-9D0E916E8F8A}'); // Broadlink Devices
		$BroadlinkA1IDList = IPS_GetInstanceListByModuleID('{1A1402D6-B4BD-F179-444E-9E351075D937}'); // Broadlink A1 Sensors
		$io_model = $this->ReadPropertyString("io_model");
		// $io_name = $this->ReadPropertyString("io_name");
		$io_host = $this->ReadPropertyString("io_host");
		$io_mac = $this->ReadPropertyString("io_mac");
		$MyParent = $this->GetParent();
		$devices_json = $this->ReadPropertyString("broadlink_devices");
		$this->SendDebug('Broadlink discovered devices', $devices_json, 0);
		$devices = json_decode($devices_json, true);
		if(!empty($devices))
		{
			foreach ($devices as $device) {
				$instanceID = 0;
				$devtype = $device["devtype"];
				$name = $device["name"];
				$mac = $device["mac"];
				$host = $device["host"];
				$model = $device["model"];
				$device_id = 0;
				foreach ($BroadlinkInstanceIDList as $BroadlinkInstanceID) {
					if (IPS_GetInstance($BroadlinkInstanceID)['ConnectionID'] == $MyParent && $mac == IPS_GetProperty($BroadlinkInstanceID, 'mac')) {
						$broadlink_device_name = IPS_GetName($BroadlinkInstanceID);
						$this->SendDebug('Broadlink Config', 'device found: '.utf8_decode($broadlink_device_name).' ('.$BroadlinkInstanceID.')' , 0);
						$instanceID = $BroadlinkInstanceID;
					}
				}
				foreach ($BroadlinkA1IDList as $BroadlinkA1ID) {
					if ($mac == IPS_GetProperty($BroadlinkA1ID, 'mac')) {
						$broadlink_device_name = IPS_GetName($BroadlinkA1ID);
						$this->SendDebug('Broadlink Config', 'device found: '.utf8_decode($broadlink_device_name).' ('.$BroadlinkA1ID.')' , 0);
						$instanceID = $BroadlinkA1ID;
					}
				}
				if($model == "A1")
				{
					$config_list[] = [
						"instanceID" => $instanceID,
						"id" => $device_id,
						"name" => "Broadlink ".$model. " Sensor (".$host.")",
						"broadlinkname" => $name,
						"devicetype" => $devtype,
						"mac" => $mac,
						"deviceid" => $device_id,
						"model" => $model,
						"host" => $host,
						"location" => $this->SetLocation($io_model, $io_host),
						"create" => [
							"moduleID" => "{1A1402D6-B4BD-F179-444E-9E351075D937}",
							"configuration" =>  [
								"a1interval" => 0,
								"name" => $name,
								"host" => $host,
								"mac" => $mac,
								"model" => $model,
								"devicetype" => $devtype
							]
						]
					];
				}
				elseif($model == "IR" || $model == "RF")
				{
					$config_list[] = [
						"instanceID" => $instanceID,
						"id" => $device_id,
						"name" => $name,
						"broadlinkname" => $name,
						"devicetype" => $devtype,
						"mac" => $mac,
						"deviceid" => $device_id,
						"model" => $model,
						"host" => $host,
						"location" => $this->SetLocation($io_model, $io_host),
						"create" => [
							"moduleID" => "{B5A1F2D9-0530-6130-5933-9D0E916E8F8A}",
							"configuration" =>  [
								"name" => $name,
								"mac" => $mac,
								"ident" => str_replace(":", "_", $mac),
								"model" => $model,
								"devicetype" => $devtype
							]
						]
					];
				}
			}
		}
		return $config_list;
	}

	private function SetLocation($model, $hubip)
	{
		$category = $this->ReadPropertyInteger("ImportCategoryID");
		$tree_position[] = IPS_GetName($category);
		$parent = IPS_GetObject($category)['ParentID'];
		$tree_position[] = IPS_GetName($parent);
		do {
			$parent = IPS_GetObject($parent)['ParentID'];
			$tree_position[] = IPS_GetName($parent);
		} while ($parent > 0);
		// delete last key
		end($tree_position);
		$lastkey = key($tree_position);
		unset($tree_position[$lastkey]);
		// reverse array
		$tree_position = array_reverse($tree_position);
		array_push($tree_position, $this->Translate('Broadlink devices'));
		array_push($tree_position, $model . " (" . $hubip . ")");
		$this->SendDebug('Broadlink Location', json_encode($tree_position) , 0);
		return $tree_position;
	}

	/***********************************************************
	 * Configuration Form
	 ***********************************************************/

	/**
	 * build configuration form
	 * @return string
	 */
	public function GetConfigurationForm()
	{
		// return current form
		$Form =  json_encode([
			'elements' => $this->FormHead(),
			'actions' => $this->FormActions(),
			'status' => $this->FormStatus()
		]);
		$this->SendDebug('FORM', $Form, 0);
		$this->SendDebug('FORM', json_last_error_msg(), 0);
		return $Form;
	}

	/**
	 * return form configurations on configuration step
	 * @return array
	 */
	protected function FormHead()
	{
		$form = [
			[
				'type' => 'Label',
				'label' => 'category for Broadlink devices'
			],
			[
				'name' => 'ImportCategoryID',
				'type' => 'SelectCategory',
				'caption' => 'category Broadlink'
			],
			[
				'type' => 'Label',
				'label' => 'create scripts for remote control (alternative or addition for remote control via webfront):'
			],
			[
				'name' => 'BroadlinkScript',
				'type' => 'CheckBox',
				'caption' => 'Broadlink script'
			],
			[
				'name' => 'BroadlinkConfiguration',
				'type' => 'Configurator',
				'rowCount' => 20,
				'add' => false,
				'delete' => true,
				'sort' => [
					'column' => 'name',
					'direction' => 'ascending'
				],
				'columns' => [
					[
						'label' => 'ID',
						'name' => 'id',
						'width' => '200px',
						'visible' => false
					],
					[
						'label' => 'device name',
						'name' => 'name',
						'width' => 'auto'
					],
					[
						'label' => 'broadlink name',
						'name' => 'broadlinkname',
						'width' => '250px'
					],
					[
						'label' => 'IP adress',
						'name' => 'host',
						'width' => '250px'
					],
					[
						'label' => 'mac',
						'name' => 'mac',
						'width' => '250px'
					],
					[
						'label' => 'model',
						'name' => 'model',
						'width' => '200px'
					],
					[
						'label' => 'device type',
						'name' => 'devicetype',
						'width' => '200px'
					]
				],
				'values' => $this->Get_ListConfiguration()
			]
		];
		return $form;
	}

	/**
	 * return form actions by token
	 * @return array
	 */
	protected function FormActions()
	{
		$MyParent = IPS_GetInstance($this->InstanceID)['ConnectionID'];
		$form = [
			[
				'type' => 'ExpansionPanel',
				'caption' => 'Create new Broadlink device instance:',
				'items' => [
					[
						'name' => 'devicename',
						'type' => 'ValidationTextBox',
						'caption' => 'device name'
					],
					[
						'type' => 'Select',
						'name' => 'type',
						'caption' => 'type',
						'options' => [
							[
								'label' => 'Please select type',
								'value' => 0
							],
							[
								'label' => 'IR',
								'value' => 1
							],
							[
								'label' => 'RF',
								'value' => 2
							]
						]

					],
					[
						'type' => 'Button',
						'caption' => 'Create',
						'onClick' => 'BroadlinkConfig_CreateDevice($id, $devicename, $type);'
					],
					[
						'type' => 'Label',
						'caption' => 'Remove devices from the list that have not been created as an instance'
					],
					[
						'type' => 'Button',
						'caption' => 'Remove Devices',
						'onClick' => 'BroadlinkConfig_RemoveDevices($id);'
					]
				]
			],
			[
				'type' => 'Label',
				'caption' => 'Discover Device'
			],
			[
				'type' => 'Button',
				'caption' => 'Discover',
				'onClick' => 'Broadlink_Discover($id);'
			],
			[
				'type' => 'Label',
				'label' => 'create scripts for remote control (alternative or addition for remote control via webfront):'
			],
			[
				'type' => 'Button',
				'label' => 'Setup Broadlink',
				'onClick' => 'BroadlinkConfig_SetupBroadlink($id);'
			]
		];
		return $form;
	}

	/**
	 * return from status
	 * @return array
	 */
	protected function FormStatus()
	{
		$form = [
			[
				'code' => 101,
				'icon' => 'inactive',
				'caption' => 'Creating instance.'
			],
			[
				'code' => 102,
				'icon' => 'active',
				'caption' => 'Broadlink configurator created.'
			],
			[
				'code' => 104,
				'icon' => 'inactive',
				'caption' => 'interface closed.'
			],
			[
				'code' => 201,
				'icon' => 'inactive',
				'caption' => 'Please follow the instructions.'
			],
			[
				'code' => 202,
				'icon' => 'error',
				'caption' => 'no category selected.'
			],
			[
				'code' => 211,
				'icon' => 'error',
				'caption' => 'choose category for Broadlink devices.'
			]
		];

		return $form;
	}


	/** Eine Anfrage an den IO und liefert die Antwort.
	 * @param string $Method
	 * @return string
	 */
	private function SendData(string $Method)
	{
		$Data['DataID'] = '{EFC61574-A0BC-2FBB-065A-8C6B42FC2646}';
		$Data['Buffer'] = ['name' => 'Broadlink Configurator', 'Command' => $Method, "command_code" => ""];
		$this->SendDebug('Method:', $Method, 0);
		$result = @$this->SendDataToParent(json_encode($Data));
		$this->SendDebug('Send data result:', $result, 0);
		return $result;
	}


}
