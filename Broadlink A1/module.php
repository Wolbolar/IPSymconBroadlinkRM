<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Fonzo\Broadlink\Broadlink;

class BroadlinkA1 extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
        $this->ConnectParent('{E58707E8-8E2C-26D4-A7A9-2D6D6D93AB04}'); // Broadlink I/O
        $this->RegisterPropertyInteger('a1interval', 0);
        $this->RegisterPropertyString('name', '');
        $this->RegisterPropertyString('host', '');
        $this->RegisterPropertyString('mac', '');
        $this->RegisterPropertyString('model', '');
        $this->RegisterPropertyString('devicetype', '');
        $this->RegisterVariableFloat('Temperature', 'Temperatur', '~Temperature');
        $this->RegisterVariableFloat('Humidity', 'Feuchtigkeit', '~Humidity.F');
        $lightass = [
            [0, $this->Translate('dark'), 'Light', -1],
            [1, $this->Translate('dimmed'), 'Light', -1],
            [2, $this->Translate('normal'), 'Light', -1],
            [3, $this->Translate('light'), 'Light', -1],
            [4, $this->Translate('unknown'), 'Light', -1]
        ];
        $airqualityass = [
            [0, $this->Translate('excellent'), 'Factory', -1],
            [1, $this->Translate('good'), 'Factory', -1],
            [2, $this->Translate('normal'), 'Factory', -1],
            [3, $this->Translate('bad'), 'Factory', -1],
            [4, $this->Translate('unknown'), 'Factory', -1]
        ];
        $noiseass = [
            [0, $this->Translate('quiet'), 'Speaker', -1],
            [1, $this->Translate('normal'), 'Speaker', -1],
            [2, $this->Translate('noisy'), 'Speaker', -1],
            [3, $this->Translate('unknown'), 'Speaker', -1]
        ];
        $this->RegisterProfileAssociation('Broadlink.A1.Light', 'Light', '', '', 0, 4, 0, 0, 1, $lightass);
        $this->RegisterProfileAssociation('Broadlink.A1.Airquality', 'Factory', '', '', 0, 4, 0, 0, 1, $airqualityass);
        $this->RegisterProfileAssociation('Broadlink.A1.Noise', 'Speaker', '', '', 0, 3, 0, 0, 1, $noiseass);
        $this->RegisterVariableInteger('Light', 'Licht', 'Broadlink.A1.Light');
        $this->RegisterVariableInteger('Air_quality', 'Luftqualität', 'Broadlink.A1.Airquality');
        $this->RegisterVariableInteger('Noise', 'Lautstärke', 'Broadlink.A1.Noise');
        $this->RegisterTimer('A1Update', 0, 'BroadlinkA1_TimerUpdateData(' . $this->InstanceID . ');');

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
        if($this->HasActiveParent())
        {
            $this->SetStatus(102);
            $this->SetA1Interval();
        }
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

    /**
     * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
     * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:.
     */
    public function TimerUpdateData()
    {
        $this->Update();
    }

    protected function SetA1Interval()
    {
        $a1interval = $this->ReadPropertyInteger('a1interval');
        $interval = $a1interval * 60 * 1000;
        $this->SetTimerInterval('A1Update', $interval);
    }

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString);
        $objectident = $data->Buffer->ident;
        $this->SendDebug('Receive Data:', 'Send to A1 Ident: ' . $objectident, 0);
        $mac = $this->ReadPropertyString('mac');
        $a1ident =  str_replace(':', '_', $mac);
        $devicejson = json_encode($data->Buffer->device);
        $this->SendDebug('Receive Data:', $devicejson, 0);
        if ($a1ident == $objectident) {
            $this->SendDebug('Receive Data:', 'Data for A1 (Ident: ' . $a1ident . ')', 0);
            //Parse and write values to our variables
            $this->UpdateA1($devicejson);
        }
    }

    protected function UpdateA1($devicejson)
    {
        $device = json_decode($devicejson, true);
        $type = $device['devtype'];
        $host = $device['host'];
        $mac = $device['mac'];
        $model = $device['model'];
        $name = $device['name'];
        if (isset($device['temperature'])) {
            $temperature = floatval($device['temperature']);
            if ($this->GetIDForIdent('Temperature'))
                $this->SetValue('Temperature', $temperature);
            $this->SendDebug('Broadlink A1:', 'Temperature ' . $temperature, 0);
        } else {
            $this->SendDebug('Broadlink A1:', 'could not find temperature', 0);
        }
        if (isset($device['humidity'])) {
            $humidity = floatval($device['humidity']);
            if ($this->GetIDForIdent('Humidity'))
                $this->SetValue('Humidity', $humidity);
            $this->SendDebug('Broadlink A1:', 'Humidity ' . $humidity, 0);
        } else {
            $this->SendDebug('Broadlink A1:', 'could not find humidity', 0);
        }
        if (isset($device['light'])) {
            $light = intval($device['light']);
            if ($this->GetIDForIdent('Light'))
                $this->SetValue('Light', $light);
            $this->SendDebug('Broadlink A1:', 'Light ' . $light, 0);
        } else {
            $this->SendDebug('Broadlink A1:', 'could not find light', 0);
        }
        if (isset($device['air_quality'])) {
            $air_quality = intval($device['air_quality']);
            if ($this->GetIDForIdent('Air_quality'))
                $this->SetValue('Air_quality', $air_quality);
            $this->SendDebug('Broadlink A1:', 'Air quality ' . $air_quality, 0);

        } else {
            $this->SendDebug('Broadlink A1:', 'could not find air quality', 0);
        }
        if (isset($device['noise'])) {
            $noise = intval($device['noise']);
            if ($this->GetIDForIdent('Noise'))
                $this->SetValue('Noise', $noise);
            $this->SendDebug('Broadlink A1:', 'Noise ' . $noise, 0);
        } else {
            $this->SendDebug('Broadlink A1:', 'could not find noise', 0);
        }
        IPS_SetProperty($this->InstanceID, 'name', json_encode($name));
        $this->SendDebug('Broadlink A1:', 'Name ' . json_encode($name), 0);
        IPS_SetProperty($this->InstanceID, 'host', $host);
        $this->SendDebug('Broadlink A1:', 'Host ' . $host, 0);
        IPS_SetProperty($this->InstanceID, 'mac', $mac);
        $this->SendDebug('Broadlink A1:', 'Mac ' . $mac, 0);
        IPS_SetProperty($this->InstanceID, 'model', $model);
        $this->SendDebug('Broadlink A1:', 'Model ' . $model, 0);
        IPS_SetProperty($this->InstanceID, 'devicetype', $type);
        $this->SendDebug('Broadlink A1:', 'Device type ' . $type, 0);
        IPS_ApplyChanges($this->InstanceID); //Neue Konfiguration übernehmen
    }

    public function Update()
    {
        $mac = $this->ReadPropertyString('mac');
        $deviceident =  str_replace(':', '_', $mac);
        $payload = ['name' => $deviceident, 'command' => 'UpdateA1'];
        $this->SendDebug('Send Data:', json_encode($payload), 0);

        //an Splitter schicken
        $result = $this->SendDataToParent(json_encode(['DataID' => '{EFC61574-A0BC-2FBB-065A-8C6B42FC2646}', 'Buffer' => $payload])); // Interface GUI
        $this->SendDebug('Send Data Result:', $result, 0);
        return $result;
    }

    //################# DUMMYS / WOARKAROUNDS - protected

    protected function GetParent()
    {
        $instance = IPS_GetInstance($this->InstanceID);
        return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;
    }

    /***********************************************************
     * Configuration Form
     ***********************************************************/

    /**
     * build configuration form.
     *
     * @return string
     */
    public function GetConfigurationForm()
    {
        // return current form
        return json_encode([
            'elements' => $this->FormHead(),
            'actions'  => $this->FormActions(),
            'status'   => $this->FormStatus()
        ]);
    }

    /**
     * return form configurations on configuration step.
     *
     * @return array
     */
    protected function FormHead()
    {
        $mac = $this->ReadPropertyString('mac');
        if($mac == '')
        {
            $form = [
                [
                    'type'    => 'Label',
                    'caption' => 'This device is created by the Broadlink configurator, please go to the Broadlink configurator and press Discover'
                ]
            ];
        }
        else
        {
            $form = [
                [
                    'type'    => 'Label',
                    'caption' => 'Broadlink'
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Broadlink A1 update interval'
                ],
                [
                    'name'    => 'a1interval',
                    'type'    => 'IntervalBox',
                    'caption' => 'minutes'
                ],
                [
                    'type'     => 'List',
                    'name'     => 'BroadlinkInformation',
                    'caption'  => 'Broadlink information',
                    'rowCount' => 2,
                    'add'      => false,
                    'delete'   => false,
                    'sort'     => [
                        'column'    => 'host',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'name'    => 'name',
                            'caption' => 'Name',
                            'width'   => '370px',
                            'visible' => true
                        ],
                        [
                            'name'    => 'host',
                            'caption' => 'IP address',
                            'width'   => '150px',
                        ],
                        [
                            'name'    => 'mac',
                            'caption' => 'MAC address',
                            'width'   => '150px',
                        ],
                        [
                            'name'    => 'model',
                            'caption' => 'Model',
                            'width'   => 'auto',
                        ],
                        [
                            'name'    => 'devicetype',
                            'caption' => 'Device type',
                            'width'   => '150px',
                        ]
                    ],
                    'values' => [
                        [
                            'name'       => $this->ReadPropertyString('name'),
                            'host'       => $this->ReadPropertyString('host'),
                            'mac'        => $this->ReadPropertyString('mac'),
                            'model'      => $this->ReadPropertyString('model'),
                            'devicetype' => $this->ReadPropertyString('devicetype')
                        ]]
                ]
            ];
        }
        return $form;
    }

    /**
     * return form actions by token.
     *
     * @return array
     */
    protected function FormActions()
    {
        $mac = $this->ReadPropertyString('mac');
        if ($mac == '') {
            $form = [
                [
                    'type'    => 'Label',
                    'caption' => 'Discover Device'
                ],
                [
                    'type'    => 'Button',
                    'caption' => 'Discover',
                    'onClick' => 'BroadlinkA1_Update($id);'
                ]
            ];
        } else {
            $form = [
                [
                    'type'    => 'Label',
                    'caption' => 'Update data'
                ],
                [
                    'type'    => 'Button',
                    'caption' => 'Update',
                    'onClick' => 'BroadlinkA1_Update($id);'
                ]
            ];
        }
        return $form;
    }

    /**
     * return from status.
     *
     * @return array
     */
    protected function FormStatus()
    {
        $form = [
            [
                'code'    => 101,
                'icon'    => 'inactive',
                'caption' => 'Creating instance.'
            ],
            [
                'code'    => 102,
                'icon'    => 'active',
                'caption' => 'Broadlink A1 created.'
            ],
            [
                'code'    => 104,
                'icon'    => 'inactive',
                'caption' => 'Interface closed.'
            ],
            [
                'code'    => 201,
                'icon'    => 'inactive',
                'caption' => 'Please follow the instructions.'
            ],
            [
                'code'    => 202,
                'icon'    => 'error',
                'caption' => 'special errorcode.'
            ],
            [
                'code'    => 203,
                'icon'    => 'error',
                'caption' => 'No active Broadlink I/O.'
            ],
            [
                'code'    => 211,
                'icon'    => 'error',
                'caption' => 'choose category for Broadlink devices.'
            ]
        ];

        return $form;
    }

    //Profile
    protected function RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Vartype)
    {

        if (!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, $Vartype); // 0 boolean, 1 int, 2 float, 3 string,
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if ($profile['ProfileType'] != $Vartype)
                $this->SendDebug('Profile:', 'Variable profile type does not match for profile ' . $Name, 0);
        }

        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize); // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite
    }

    protected function RegisterProfileAssociation($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Vartype, $Associations)
    {
        if (count($Associations) === 0) {
            $MinValue = 0;
            $MaxValue = 0;
        }
        $this->RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Vartype);

        //boolean IPS_SetVariableProfileAssociation ( string $ProfilName, float $Wert, string $Name, string $Icon, integer $Farbe )
        foreach ($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
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
        }
        elseif ($ipsversion >= 5.0 && $ipsversion < 5.1) // 5.0
        {
            $ipsversion = 5;
        }
        elseif ($ipsversion >= 5.1 && $ipsversion < 5.2) // 5.1
        {
            $ipsversion = 6;
        }else   // > 5.1
        {
            $ipsversion = 7;
        }

        return $ipsversion;
    }
}
