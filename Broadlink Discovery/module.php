<?
declare(strict_types=1);

require_once __DIR__ . '/../libs/ConstHelper.php';
require_once __DIR__ . '/../libs/BufferHelper.php';
require_once __DIR__ . '/../libs/DebugHelper.php';

require_once(__DIR__ . "/../bootstrap.php");

use Fonzo\Broadlink\Broadlink;

class BroadlinkDiscovery extends IPSModule
{
	use BufferHelper,
		DebugHelper;

	public function Create()
	{
		//Never delete this line!
		parent::Create();
		$this->RegisterAttributeString("devices", "[]");

		//we will wait until the kernel is ready
		$this->RegisterMessage(0, IPS_KERNELMESSAGE);
		$this->RegisterMessage(0, IPS_KERNELSTARTED);
		$this->RegisterTimer('Discovery', 0, 'BroadlinkDiscovery_Discover($_IPS[\'TARGET\']);');
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

		$this->WriteAttributeString("devices", json_encode($this->DiscoverDevices()));
		$this->SetTimerInterval('Discovery', 300000);

		// Status Error Kategorie zum Import auswählen
		$this->SetStatus(102);
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
			case IPS_KERNELSTARTED:
				$this->WriteAttributeString("devices", json_encode($this->DiscoverDevices()));
				break;

			default:
				break;
		}
	}


	/**
	 * Liefert alle Geräte.
	 *
	 * @return array configlist all devices
	 */
	private function Get_ListConfiguration()
	{
		$config_list = [];
		$ConfiguratorIDList = IPS_GetInstanceListByModuleID('{EE791548-91D0-1E0C-7EEB-FD602A3101A0}'); // Broadlink Configurator
		$devices = $this->DiscoverDevices();
		$this->SendDebug('Broadlink discovered devices', json_encode($devices), 0);
		if (!empty($devices)) {
			foreach ($devices as $device) {
				$instanceID = 0;
				$devicetype = $device["devtype"];
				$name = $device["name"];
				$mac = $device["mac"];
				$host = $device["host"];
				$model = $device["model"];
				$device_id = 0;
				foreach ($ConfiguratorIDList as $ConfiguratorID) {
					if ($mac == IPS_GetProperty($ConfiguratorID, 'io_mac')) {
						$configurator_name = IPS_GetName($ConfiguratorID);
						$this->SendDebug('Broadlink Config', 'device found: ' . utf8_decode($configurator_name) . ' (' . $ConfiguratorID . ')', 0);
						$instanceID = $ConfiguratorID;
					}
				}
				if ($model == "RM2" || $model == "RM2 Pro Plus" || $model == "RM2 Pro Plus2" || $model == "RM2 Pro Plus3" || $model == "RM Mini") {
					$config_list[] = [
						"instanceID" => $instanceID,
						"id" => $device_id,
						"name" => $model. " ". $host,
						"broadlinkname" => $name,
						"devicetype" => $devicetype,
						"mac" => $mac,
						"deviceid" => $device_id,
						"model" => $model,
						"host" => $host,
						"create" => [
							[
								'moduleID' => '{EE791548-91D0-1E0C-7EEB-FD602A3101A0}',
								'configuration' => [
									'io_name' => $name,
									'io_host' => $host,
									'io_mac' => $mac,
									'io_model' => $model,
									'broadlink_devices' => json_encode($devices)
								]
							],
							[
								'moduleID' => '{E58707E8-8E2C-26D4-A7A9-2D6D6D93AB04}',
								'configuration' => [
									'name' => $name,
									'host' => $host,
									'mac' => $mac,
									'model' => $model,
									'devicetype' => $devicetype
								]
							]

						]
					];
				}
			}
		}
		return $config_list;
	}

	private function DiscoverDevices(): array
	{
		$result = array();

		$devices = Broadlink::Discover();
		$this->SendDebug("Discover Response:", json_encode($devices), 0);
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
			array_push($result, $obj);
		}
		return $result;
	}

	public function GetDevices()
	{
		$devices = $this->ReadPropertyString("devices");
		return $devices;
	}

	public function Discover()
	{
		$this->LogMessage($this->Translate('Background Discovery of Broadlink Devices'), KL_NOTIFY);
		$result = $this->DiscoverDevices();
		$this->WriteAttributeString("devices", json_encode($result));
		return $result;
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
		$Form = json_encode([
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
		];
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
				'name' => 'BroadlinkDiscovery',
				'type' => 'Configurator',
				'rowCount' => 20,
				'add' => false,
				'delete' => true,
				'sort' => [
					'column' => 'host',
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
			]
		];

		return $form;
	}
}
