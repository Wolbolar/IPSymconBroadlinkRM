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
		$this->RegisterPropertyString("model", "");
		$this->RegisterPropertyString("devicetype", "");
		$this->RegisterAttributeInteger("CategoryID", 0);
		$this->RegisterPropertyInteger("a1interval", 0);
		$this->RegisterPropertyInteger("temperatureinterval", 0);
		$this->RegisterAttributeBoolean("a1device", false);
		$this->RegisterAttributeString("devices", "[]");
		$this->RegisterTimer('A1Update', 0, 'Broadlink_A1Timer(' . $this->InstanceID . ');');
		$this->RegisterTimer('TemperatureUpdate', 0, 'Broadlink_TemperatureTimer(' . $this->InstanceID . ');');

		//we will wait until the kernel is ready
		$this->RegisterMessage(0, IPS_KERNELMESSAGE);
	}

	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();

		if (IPS_GetKernelRunlevel() !== KR_READY) {
			return;
		}


		$devicetype = $this->ReadPropertyString("devicetype");
		if ($devicetype == "0x2712" || $devicetype == "0x272a" || $devicetype == "0x2787" || $devicetype == "0x279d") {
			$this->RegisterVariableFloat("Temperature", "Temperatur", "~Temperature");
		}


		$this->SetA1Interval();
		$this->SetTemperatureInterval();
		$this->Discover();
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

	public function TemperatureTimer()
	{
		$this->Discover();
	}

	protected function SetA1Interval()
	{
		$a1device = $this->ReadAttributeBoolean("a1device");
		$a1interval = $this->ReadPropertyInteger("a1interval");
		$interval = $a1interval * 60 * 1000;
		if ($a1device)
		{
			$this->SetTimerInterval("A1Update", $interval);
		} else {
			$this->SetTimerInterval("A1Update", 0);
		}
	}

	protected function SetTemperatureInterval()
	{
		$model = $this->ReadPropertyString("model");
		$this->SendDebug("Broadlink IO:", "broadlink io model ".$model, 0);
		$temperatureinterval = $this->ReadPropertyInteger("temperatureinterval");
		$interval = $temperatureinterval * 60 * 1000;
		if ($model == "RM2" || $model == "RM2 Pro Plus" || $model == "RM2 Pro Plus2" || $model == "RM2 Pro Plus3")
		{
			$this->SendDebug("Broadlink IO:", "found model ".$model." , set interval for temperature update to ".$temperatureinterval." minutes", 0);
			$this->SetTimerInterval("TemperatureUpdate", $interval);
		} else {
			$this->SetTimerInterval("TemperatureUpdate", 0);
		}

	}

	public function Set_IO_Name()
	{
		$name = IPS_GetName($this->InstanceID);
		if ($name == "BroadlinkGateway") {
			$model = $this->ReadPropertyString("model");
			$host = $this->ReadPropertyString("host");
			IPS_SetName($this->InstanceID, $model . " I/O (" . $host . ")");
		}
	}

	protected function SetCategoryID($CategoryID)
	{
		$this->WriteAttributeInteger("CategoryID", $CategoryID);
	}

	protected function GetDevices()
	{
		$devices = $this->ReadAttributeString("devices");
		return $devices;
	}

	protected function GetModel()
	{
		$model = $this->ReadPropertyString("model");
		return $model;
	}

	protected function GetHost()
	{
		$host = $this->ReadPropertyString("host");
		return $host;
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

	protected function SendToDevice($deviceident, $command)
	{
		$data = array("ident" => $deviceident, "command" => $command);
		// send to device / children
		$this->SendDataToChildren(json_encode(Array("DataID" => "{A05B41B1-7478-8E54-296E-17F406FD3876}", "Buffer" => $data)));
	}

	public function ForwardData($JSONString)
	{

		// Empfangene Daten von der Device Instanz
		$data = json_decode($JSONString);
		$datasend = $data->Buffer;
		$datasend = json_encode($datasend);
		$this->SendDebug("Broadlink Forward Data:", $datasend, 0);

		// Hier würde man den Buffer im Normalfall verarbeiten
		// z.B. CRC prüfen, in Einzelteile zerlegen
		$payload = json_decode($datasend);
		$name = $payload->name;
		$command = $payload->command;
		if (isset($payload->command_code)) {
			$command_code = $payload->command_code;
		}
		if ($command == "UpdateA1") {
			$this->Discover();
			$result = true;
		} elseif ($command == "GetModel") {
			$model = $this->GetModel();
			$result = $model;
		} elseif ($command == "GetHubIP") {
			$host = $this->GetHost();
			$result = $host;
		} elseif ($command == "GetDevices") {
			$devices = $this->GetDevices();
			$result = $devices;
		} elseif ($command == "SetCategory") {
			$CategoryID = $payload->categoryid;
			$this->SetCategoryID($CategoryID);
			$result = true;
		} else {
			$this->SendDebug("Broadlink Device:", $name, 0);
			$this->SendDebug("Broadlink Command:", $command, 0);
			$this->SendDebug("Broadlink Code:", $command_code, 0);
			$result = $this->SendCommand($command_code);
			//$this->SendDebug("Send Command Result:",$result,0);
		}
		return $result;
	}

	protected function SendCommand($command)
	{
		$this->SendDebug("Broadlink Send:", $command, 0);
		$json = array();
		$info = array("devtype" => $this->ReadPropertyString("devicetype"), "name" => json_decode($this->ReadPropertyString("name")), "mac" => $this->ReadPropertyString("mac"), "host" => $this->ReadPropertyString("host"), "model" => $this->ReadPropertyString("model"));
		$json['code'] = -1;
		$devtype = Broadlink::getdevtype($info['devtype']);

		if ($devtype == 2) {

			$rm = Broadlink::CreateDevice($info['host'], $info['mac'], 80, $info['devtype']);

			$auth = $rm->Auth();
			$id = $auth["id"];
			$this->SendDebug("Broadlink ID:", $id, 0);
			$key = $auth["key"];
			$this->SendDebug("Broadlink Key:", $key, 0);
			$auth_response = $auth["response"];
			$this->SendDebug("Broadlink Response:", $auth_response, 0);
			$payload = $auth["payload"];
			$this->SendDebug("Broadlink Payload:", $payload, 0);

			$data = $rm->Send_data($command);
			$response = $data["response"];
			$packet = $data["packet"];
			$this->SendDebug("Broadlink Response:", $response, 0);
			$this->SendDebug("Broadlink Packet:", $packet, 0);
			$json['code'] = 1;

		}
		$result = json_encode($json, JSON_NUMERIC_CHECK);
		return $result;
	}

	public function Discover()
	{
		$result = array();

		$devices = Broadlink::Discover();
		$this->SendDebug("Discover Response:", $devices, 0);
		foreach ($devices as $device) {

			$obj = array();

			$obj['devtype'] = $device->devtype();
			$this->SendDebug("devtype:", $obj['devtype'], 0);
			$obj['name'] = $device->name();
			$this->SendDebug("name:", $obj['name'], 0);
			$obj['mac'] = $device->mac();
			$this->SendDebug("mac:", $obj['mac'], 0);
			$obj['host'] = $device->host();
			$this->SendDebug("host:", $obj['host'], 0);
			$obj['model'] = $device->model();
			$this->SendDebug("model:", $obj['model'], 0);

			if ($obj['model'] == "RM2" || $obj['model'] == "RM2 Pro Plus" || $obj['model'] == "RM2 Pro Plus2" || $obj['model'] == "RM2 Pro Plus3") {
				$authresponse = $device->Auth();
				$payload = $authresponse["payload"];
				$this->SendDebug("Auth Payload:", $payload, 0);
				$id = $authresponse["id"];
				$this->SendDebug("Auth ID:", $id, 0);
				$key = $authresponse["key"];
				$this->SendDebug("Auth Key:", $key, 0);
				$encrytresponse = $authresponse["response"];
				$this->SendDebug("Auth Response:", $encrytresponse, 0);
				$temperature = $device->Check_temperature();
				$obj['temperature'] = $temperature;
				$this->UpdateGatewayData($obj);
			}
			if ($obj['model'] == "RM Mini") {

				// $device->Auth();
				$this->UpdateGatewayData($obj);
			} else if ($obj['model'] == "A1") {
				$authresponse = $device->Auth();
				$payload = $authresponse["payload"];
				$this->SendDebug("A1 Auth Payload:", $payload, 0);
				$id = $authresponse["id"];
				$this->SendDebug("A1 Auth ID:", $id, 0);
				$key = $authresponse["key"];
				$this->SendDebug("A1 Auth Key:", $key, 0);
				$encrytresponse = $authresponse["response"];
				$this->SendDebug("A1 Auth Response:", $encrytresponse, 0);
				$data = $device->Check_sensors();
				$this->SendDebug("Broadlink Discover A1:", $data, 0);
				$obj = array_merge($obj, $data);

				$this->UpdateA1($obj);
				$this->WriteAttributeBoolean("a1device", true);
				$this->SendDebug("Broadlink Discover:", "A1 Device found", 0);
			}
			array_push($result, $obj);
		}
		return $result;
	}

	protected function UpdateGatewayData($device)
	{
		$type = $device["devtype"];
		$host = $device["host"];
		$mac = $device["mac"];
		$model = $device["model"];
		$name = $device["name"];
		if (isset($device["temperature"])) {
			$temperature = floatval($device["temperature"]);
		}
		$this->SendDebug("Broadlink Discover:", "Name " . $name, 0);
		$this->SendDebug("Broadlink Discover:", "Host " . $host, 0);
		$this->SendDebug("Broadlink Discover:", "Mac " . $mac, 0);
		$this->SendDebug("Broadlink Discover:", "Model " . $model, 0);
		$this->SendDebug("Broadlink Discover:", "Device type " . $type, 0);

		if ($model == "RM2" || $model == "RM2 Pro Plus" || $model == "RM2 Pro Plus2" || $model == "RM2 Pro Plus3" ) {
			$this->SendDebug("Broadlink Discover:", "Temperature " . $temperature, 0);
			$io_mac = $this->ReadPropertyString("mac");
			if($io_mac == $mac)
			{
				$this->SetValue('Temperature', $temperature);
			}
		}
	}

	protected function UpdateA1($device)
	{
		$mac = $device["mac"];
		$deviceident = str_replace(":", "_", $mac);
		$data = array("ident" => $deviceident, "device" => $device);
		// send to device / children
		$this->SendDataToChildren(json_encode(Array("DataID" => "{D6AB7ABE-1A40-F949-C5B3-64AEAAB179D8}", "Buffer" => $data)));
	}



	################## DUMMYS / WOARKAROUNDS - protected


	protected function SendJSON($data)
	{
		// Weiterleitung zu allen Gerät-/Device-Instanzen
		$this->SendDataToChildren(json_encode(Array("DataID" => "{A05B41B1-7478-8E54-296E-17F406FD3876}", "Buffer" => $data))); //  I/O RX GUI
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
		return json_encode([
			'elements' => $this->FormHead(),
			'actions' => $this->FormActions(),
			'status' => $this->FormStatus()
		]);
	}

	/**
	 * return form configurations on configuration step
	 * @return array
	 */
	protected function FormHead()
	{
		$mac = $this->ReadPropertyString("mac");
		if ($mac == "") {
			$form = [
				[
					'type' => 'Label',
					'caption' => 'Broadlink'
				],
				[
					'type' => 'Label',
					'caption' => 'Discover Device'
				],
				[
					'type' => 'Button',
					'caption' => 'Discover',
					'onClick' => 'Broadlink_Discover($id);'
				]
			];
		} else {
			$form = [
				[
					'type' => 'Label',
					'caption' => 'Broadlink'
				],
				[
					'type' => 'List',
					'name' => 'BroadlinkInformation',
					'caption' => 'Broadlink information',
					'rowCount' => 2,
					'add' => false,
					'delete' => false,
					'sort' => [
						'column' => 'host',
						'direction' => 'ascending'
					],
					'columns' => [
						[
							'name' => 'name',
							'caption' => 'Name',
							'width' => '370px',
							'visible' => true
						],
						[
							'name' => 'host',
							'caption' => 'IP address',
							'width' => '150px',
						],
						[
							'name' => 'mac',
							'caption' => 'MAC address',
							'width' => '150px',
						],
						[
							'name' => 'model',
							'caption' => 'Model',
							'width' => 'auto',
						],
						[
							'name' => 'devicetype',
							'caption' => 'Device type',
							'width' => '150px',
						]
					],
					'values' => [
						[
							'name' => $this->ReadPropertyString("name"),
							'host' => $this->ReadPropertyString("host"),
							'mac' => $this->ReadPropertyString("mac"),
							'model' => $this->ReadPropertyString("model"),
							'devicetype' => $this->ReadPropertyString("devicetype")
						]]
				]
			];
		}
		$model = $this->ReadPropertyString("model");
		if ($model == "RM2" || $model == "RM2 Pro Plus" || $model == "RM2 Pro Plus2" || $model == "RM2 Pro Plus3" ) {
			$form = array_merge_recursive(
				$form,
				[
					[
						'type' => 'Label',
						'caption' => 'Update inteval temperature sensor in minutes'
					],
					[
						'name' => 'temperatureinterval',
						'type' => 'IntervalBox',
						'caption' => 'minutes'
					]
				]
			);
		}
		return $form;
	}

	/**
	 * return form actions by token
	 * @return array
	 */
	protected function FormActions()
	{
		$form = [
			[
				'type' => 'Label',
				'caption' => 'Update Temperature'
			],
			[
				'type' => 'Button',
				'caption' => 'Update',
				'onClick' => 'Broadlink_Discover($id);'
			],
			[
				'type' => 'Label',
				'caption' => 'Rename IO'
			],
			[
				'type' => 'Button',
				'caption' => 'Rename',
				'onClick' => 'Broadlink_Set_IO_Name($id);'
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
				'caption' => 'Broadlink created.'
			],
			[
				'code' => 104,
				'icon' => 'inactive',
				'caption' => 'Interface closed.'
			],
			[
				'code' => 201,
				'icon' => 'inactive',
				'caption' => 'Please follow the instructions.'
			],
			[
				'code' => 202,
				'icon' => 'error',
				'caption' => 'special errorcode.'
			],
			[
				'code' => 203,
				'icon' => 'error',
				'caption' => 'No active Broadlink I/O.'
			],
			[
				'code' => 211,
				'icon' => 'error',
				'caption' => 'choose category for Broadlink devices.'
			]
		];

		return $form;
	}

	################## SEMAPHOREN Helper  - private

	private function lock($ident)
	{
		for ($i = 0; $i < 3000; $i++) {
			if (IPS_SemaphoreEnter("Broadlink_" . (string)$this->InstanceID . (string)$ident, 1)) {
				return true;
			} else {
				IPS_Sleep(mt_rand(1, 5));
			}
		}
		return false;
	}

	private function unlock($ident)
	{
		IPS_SemaphoreLeave("Broadlink_" . (string)$this->InstanceID . (string)$ident);
	}

	//Profile
	protected function RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Vartype)
	{

		if (!IPS_VariableProfileExists($Name)) {
			IPS_CreateVariableProfile($Name, $Vartype); // 0 boolean, 1 int, 2 float, 3 string,
		} else {
			$profile = IPS_GetVariableProfile($Name);
			if ($profile['ProfileType'] != $Vartype)
				throw new Exception("Variable profile type does not match for profile " . $Name);
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
		/*
		else {
			//undefiened offset
			$MinValue = $Associations[0][0];
			$MaxValue = $Associations[sizeof($Associations)-1][0];
		}
		*/
		$this->RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Vartype);

		//boolean IPS_SetVariableProfileAssociation ( string $ProfilName, float $Wert, string $Name, string $Icon, integer $Farbe )
		foreach ($Associations as $Association) {
			IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
		}

	}

	/**
	 * Ergänzt SendDebug um Möglichkeit Objekte und Array auszugeben.
	 *
	 * @access protected
	 * @param string $Message Nachricht für Data.
	 * @param mixed $Data Daten für die Ausgabe.
	 * @return int $Format Ausgabeformat für Strings.
	 */
	protected function SendDebug($Message, $Data, $Format)
	{

		if (is_object($Data)) {
			foreach ($Data as $Key => $DebugData) {

				$this->SendDebug($Message . ":" . $Key, $DebugData, 0);
			}
		} else if (is_array($Data)) {
			foreach ($Data as $Key => $DebugData) {
				$this->SendDebug($Message . ":" . $Key, $DebugData, 0);
			}
		} else if (is_bool($Data)) {
			parent::SendDebug($Message, ($Data ? 'true' : 'false'), 0);
		} else {
			parent::SendDebug($Message, (string)$Data, $Format);
		}
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

	private function GetIPSVersion()
	{
		$ipsversion = floatval(IPS_GetKernelVersion());
		if ($ipsversion < 4.1) // 4.0
		{
			$ipsversion = 0;
		} elseif ($ipsversion >= 4.1 && $ipsversion < 4.2) // 4.1
		{
			$ipsversion = 1;
		} elseif ($ipsversion >= 4.2 && $ipsversion < 4.3) // 4.2
		{
			$ipsversion = 2;
		} elseif ($ipsversion >= 4.3 && $ipsversion < 4.4) // 4.3
		{
			$ipsversion = 3;
		} elseif ($ipsversion >= 4.4 && $ipsversion < 5) // 4.4
		{
			$ipsversion = 4;
		} elseif ($ipsversion >= 5.0 && $ipsversion < 5.1) // 5.0
		{
			$ipsversion = 5;
		} elseif ($ipsversion >= 5.1 && $ipsversion < 5.2) // 5.1
		{
			$ipsversion = 6;
		} else   // > 5.1
		{
			$ipsversion = 7;
		}

		return $ipsversion;
	}
}

?>