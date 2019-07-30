<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Fonzo\Broadlink\Broadlink;

class BroadlinkDevice extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{E58707E8-8E2C-26D4-A7A9-2D6D6D93AB04}'); // Broadlink I/O
        $this->RegisterPropertyString('name', '');
        $this->RegisterPropertyString('mac', '');
        $this->RegisterPropertyString('ident', '');
        $this->RegisterPropertyString('devicetype', '');
        $this->RegisterPropertyString('model', '');
        $this->RegisterAttributeString('commands', '[]');
        $this->RegisterPropertyString('BroadlinkCommands', '[]');
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
        $commands = $this->GetAvailableCommands();
        $this->CreateWFVariables($commands);
    }

    public function SendCommand(string $command)
    {
        $ident = $this->ReadPropertyString('ident');
        $commandhex = $this->GetCommand($command);
        $payload = ['name' => $ident, 'command' => $command, 'command_code' => $commandhex];
        $this->SendDebug('Send Data:', json_encode($payload), 0);

        //an Splitter schicken
        $result = $this->SendDataToParent(json_encode(['DataID' => '{EFC61574-A0BC-2FBB-065A-8C6B42FC2646}', 'Buffer' => $payload])); // Interface GUI
        $this->SendDebug('Send Data Result:', $result, 0);
        return $result;
    }

    protected function CreateWFVariable($commands)
    {
        $deviceident = $this->ReadPropertyString('ident');
        // count commands
        $values = $commands;
        $valuescount = count($values);
        if($valuescount >0)
        {
            // 32 Limit
            if ($valuescount > 32 && $valuescount <= 64) {
                $profilename = 'Broadlink.' . $deviceident . '.Command1';
                $profilecounter = 31;
                $this->CreateBroadlinkWebFrontVariable('WFCommands1', 'Command 1', $values, $profilename, $profilecounter);
            } elseif ($valuescount > 64 && $valuescount <= 96) {
                $profilename = 'Broadlink.' . $deviceident . '.Command2';
                $profilecounter = 63;
                $this->CreateBroadlinkWebFrontVariable('WFCommands2', 'Command 2', $values, $profilename, $profilecounter);
            } elseif ($valuescount > 96 && $valuescount <= 128) {
                $profilename = 'Broadlink.' . $deviceident . '.Command3';
                $profilecounter = 95;
                $this->CreateBroadlinkWebFrontVariable('WFCommands3', 'Command 3', $values, $profilename, $profilecounter);
            } elseif ($valuescount > 128 && $valuescount <= 160) {
                $profilename = 'Broadlink.' . $deviceident . '.Command4';
                $profilecounter = 127;
                $this->CreateBroadlinkWebFrontVariable('WFCommands4', 'Command 4', $values, $profilename, $profilecounter);
            } elseif ($valuescount > 160 && $valuescount <= 192) {
                $profilename = 'Broadlink.' . $deviceident . '.Command5';
                $profilecounter = 159;
                $this->CreateBroadlinkWebFrontVariable('WFCommands5', 'Command 5', $values, $profilename, $profilecounter);
            } elseif ($valuescount > 192 && $valuescount <= 224) {
                $profilename = 'Broadlink.' . $deviceident . '.Command6';
                $profilecounter = 191;
                $this->CreateBroadlinkWebFrontVariable('WFCommands6', 'Command 6', $values, $profilename, $profilecounter);
            } else {
                $profilename = 'Broadlink.' . $deviceident . '.Command';
                $profilecounter = 0;
                $this->CreateBroadlinkWebFrontVariable('WFCommands', 'Command', $values, $profilename, $profilecounter);
            }
        }
    }

    protected function CreateBroadlinkWebFrontVariable($ident, $name, $values, $profilename, $profilecounter)
    {
        $wfcommandid = $this->CreateVariableByIdent($this->InstanceID, $ident, $name, 1);
        $commandass = [];
        $profilelimit = $profilecounter + 31;
        $profilekey = 0;
        $i = 0;
        foreach ($values as $key => $value) {
            if ($i >= $profilecounter && $i <= $profilelimit) {
                $commandass[$profilekey] = [$i, $key, '', -1];
                $profilekey = $profilekey + 1;
            }
            $i++;
            if ($i == $profilelimit) {
                break;
            }
        }
        $this->RegisterProfileAssociation($profilename, 'Execute', '', '', 0, $profilekey, 0, 0, 1, $commandass);
        IPS_SetVariableCustomProfile($wfcommandid, $profilename);
        $this->EnableAction($ident);
        return $wfcommandid;
    }

    protected function CreateWebFrontVariable($ident, $name, $values, $profilename, $profilecounter)
    {
        $wfcommandid = $this->CreateVariableByIdent($this->InstanceID, $ident, $name, 1);
        $commandass = [];
        $profilelimit = $profilecounter + 31;
        $profilekey = 0;
        $i = 0;
        foreach ($values as $key => $value) {
            if ($i >= $profilecounter && $i <= $profilelimit) {
                $commandass[$profilekey] = [$i, $key, '', -1];
                $profilekey = $profilekey + 1;
            }
            $i++;
            if ($i == $profilelimit) {
                break;
            }
        }
        $this->SetValue($ident, $profilecounter);
        $this->RegisterProfileAssociation($profilename, 'Execute', '', '', 0, $profilekey, 0, 0, 1, $commandass);
        IPS_SetVariableCustomProfile($wfcommandid, $profilename);
        $this->EnableAction($ident);
        return $wfcommandid;
    }

    protected function GetCommand($command)
    {
        $values = $this->GetAvailableCommands();
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
        $deviceident = $this->ReadPropertyString('ident');
        if ($Ident == 'WFCommands') {
            $profilename = 'Broadlink.' . $deviceident . '.Command';
        } elseif ($Ident == 'WFCommands1') {
            $profilename = 'Broadlink.' . $deviceident . '.Command1';
        } elseif ($Ident == 'WFCommands2') {
            $profilename = 'Broadlink.' . $deviceident . '.Command2';
        } elseif ($Ident == 'WFCommands3') {
            $profilename = 'Broadlink.' . $deviceident . '.Command3';
        } elseif ($Ident == 'WFCommands4') {
            $profilename = 'Broadlink.' . $deviceident . '.Command4';
        } elseif ($Ident == 'WFCommands5') {
            $profilename = 'Broadlink.' . $deviceident . '.Command5';
        } else {
            $profilename = 'Broadlink.' . $deviceident . '.Command6';
        }
        $associations = IPS_GetVariableProfile($profilename)['Associations'];
        $this->SendDebug('Profile Associations:', json_encode($associations), 0);
        $command_name = false;
        foreach ($associations as $key => $association) {
            if ($association['Value'] == $Value) {
                $command_name = $association['Name'];
            }
        }
        return $command_name;
    }

    public function GetAvailableCommands()
    {
        $id = @$this->GetIDForIdent('Commands');
        $ips_version = $this->GetIPSVersion();
        $commands = [];
        if($id > 0 && $ips_version < 6)
        {
            $this->SendDebug('Broadlink Get Commands:', 'Read commands from variable with object id ' . $id, 0);
            $commands = json_decode(GetValue($id), true);
        }
        if($ips_version == 6 || $ips_version == 7) // > 5.1
        {
            // $this->SendDebug("Broadlink Get Commands:", "Read commands from attribute, IP-Symcon > 5.1 detected", 0);
            // $commands = json_decode($this->ReadAttributeString("commands"), true);
            $current_valuesjson = IPS_GetProperty($this->InstanceID, 'BroadlinkCommands');
            $current_values = json_decode($current_valuesjson, true);
            $commands = [];
            foreach($current_values as $value)
            {
                $commands[$value['key']] = $value['code'];
            }
        }
        return $commands;
    }

    protected function SaveCommands($commands)
    {
        // Attribute
        /*
        $this->WriteAttributeString("commands", json_encode($commands));
        $this->SetupVariables();
         */

        // convert commands to list
        $list_values = [];
        foreach($commands as $key => $command)
        {
            $list_values[] = ['key' => $key, 'code' => $command];
        }
        $list_values_json = json_encode($list_values);
        IPS_SetProperty($this->InstanceID, 'BroadlinkCommands', $list_values_json);
        IPS_ApplyChanges($this->InstanceID);
    }

    public function ImportCode(string $command_name, string $commandhex)
    {
        if ($command_name == '') {
            $this->SendDebug('Broadlink Learn:', 'Empty command name', 0);
            $result = 'Empty command name';
            return $result;
        }
        $values = $this->GetAvailableCommands();
        $values[$command_name] = $commandhex;
        $this->SaveCommands($values);
        return $values;
    }

    public function ImportCodesText(string $codes)
    {
        if($codes == '')
        {
            $this->SendDebug('Broadlink Import:', 'no import codes selected', 0);
            return 'no import codes selected';
        }
        else{
            $this->SendDebug('Broadlink Import:', 'codes: ' . $codes, 0);
            $values = $this->GetAvailableCommands();
            // add aray to old array, existing values are not overwritten
            $values = $values + json_decode($codes, true);
            $this->SaveCommands($values);
            return $values;
        }
    }

    public function ImportCodesVariable(int $variableid)
    {
        if($variableid == 0)
        {
            $this->SendDebug('Broadlink Import:', 'no variable selected', 0);
            return 'no variable selected';
        }
        else{
            $codes = GetValue($variableid);
            $this->SendDebug('Broadlink Import:', 'codes: ' . $codes, 0);
            $values = $this->GetAvailableCommands();
            // add aray to old array, existing values are not overwritten
            $values = $values + json_decode($codes, true);
            $this->SaveCommands($values);
            return $values;
        }
    }

    public function LearnDeviceCode(string $command_name)
    {
        $result = $this->LearnDevice($command_name);
        return $result;
    }

    public function LearnCommandKey(int $list_number)
    {
        $command_name = $this->GetListCommand($list_number);
        if($command_name != false)
        {
            $this->SendDebug('Broadlink Learn:', 'learn for command name ' . $command_name, 0);
            $result = $this->LearnDevice($command_name);
            return $result;
        }
        return 'could not find command';
    }

    public function SendCommandKey(int $list_number)
    {
        $command_name = $this->GetListCommand($list_number);
        if($command_name != false)
        {
            $this->SendDebug('Broadlink Send:', 'send for command name ' . $command_name, 0);
            $this->SendCommand($command_name);
        }
    }

    protected function GetListCommand($list_number)
    {
        $commands = $this->GetAvailableCommands();
        $i = 1;
        foreach ($commands as $key => $command) {
            if($list_number == $i)
            {
                return $key;
            }
            $i++;
        }
        return false;
    }

    protected function GetParent()
    {
        $instance = IPS_GetInstance($this->InstanceID);
        return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;
    }

    protected function LearnDevice($command_name)
    {

        if ($command_name == '') {
            $this->SendDebug('Broadlink Learn:', 'Empty command name', 0);
            $result = 'Empty command name';
            return $result;
        }

        $my_parent = $this->GetParent();
        $devicetype = IPS_GetProperty($my_parent, 'devicetype');
        $name = IPS_GetProperty($my_parent, 'name');
        $mac = IPS_GetProperty($my_parent, 'mac');
        $host = IPS_GetProperty($my_parent, 'host');
        $model = IPS_GetProperty($my_parent, 'model');

        $json = [];
        $info = ['devtype' => $devicetype, 'name' => $name, 'mac' => $mac, 'host' => $host, 'model' => $model];
        $json['code'] = -1;
        $devtype = Broadlink::getdevtype($info['devtype']);
        if ($devtype == 2) {

            $rm = Broadlink::CreateDevice($info['host'], $info['mac'], 80, $info['devtype']);

            $rm->Auth();
            $rm->Enter_learning();

            sleep(10);

            $json['hex'] = $rm->Check_data();

            $json['code'] = 1;

            $json['hex_number'] = '';

            foreach ($json['hex'] as $value) {
                $json['hex_number'] .= sprintf('%02x', $value);
            }

            if (strlen($command_name) > 0 && count($json['hex']) > 0) {
                $values = $this->GetAvailableCommands();
                $values[$command_name] = $json['hex_number'];
                $this->SaveCommands($values);
            }
        }
        $result = json_encode($json, JSON_NUMERIC_CHECK);
        $this->SendDebug('Broadlink Learn:', $result, 0);
        $this->LogMessage($result, KL_MESSAGE);
        return $result;
    }

    protected function SetCommands($commands)
    {
        $this->SaveCommands($commands);
    }

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString);
        $objectident = $data->Buffer->ident;
        $this->SendDebug('Receive Data:', 'Send to Device Ident: ' . $objectident, 0);
        $deviceident = $this->ReadPropertyString('ident');
        $command = json_encode($data->Buffer->command);
        $this->SendDebug('Receive Data:', $command, 0);
        if ($deviceident == $objectident) {
            $this->SendDebug('Receive Data:', 'Data for Device (Ident: ' . $deviceident . ')', 0);
            $this->SetCommands($command);
        }
    }

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'WFCommands':
                if ($this->GetIDForIdent('WFCommands'))
                    $this->SetValue('WFCommands', $Value);
                $command = $this->GetCommandName($Ident, $Value);
                $this->SendCommand($command);

                break;
            case 'WFCommands1':
                if ($this->GetIDForIdent('WFCommands1'))
                    $this->SetValue('WFCommands1', $Value);
                $command = $this->GetCommandName($Ident, $Value);
                $this->SendCommand($command);

                break;
            case 'WFCommands2':
                if ($this->GetIDForIdent('WFCommands2'))
                    $this->SetValue('WFCommands2', $Value);
                $command = $this->GetCommandName($Ident, $Value);
                $this->SendCommand($command);

                break;
            case 'WFCommands3':
                if ($this->GetIDForIdent('WFCommands3'))
                    $this->SetValue('WFCommands3', $Value);
                $command = $this->GetCommandName($Ident, $Value);
                $this->SendCommand($command);

                break;
            case 'WFCommands4':
                SetValue($this->GetIDForIdent('WFCommands4'), $Value);
                $command = $this->GetCommandName($Ident, $Value);
                $this->SendCommand($command);

                break;
            case 'WFCommands5':
                if ($this->GetIDForIdent('WFCommands5'))
                    $this->SetValue('WFCommands5', $Value);
                $command = $this->GetCommandName($Ident, $Value);
                $this->SendCommand($command);

                break;
            case 'WFCommands6':
                if ($this->GetIDForIdent('WFCommands6'))
                    $this->SetValue('WFCommands6', $Value);
                $command = $this->GetCommandName($Ident, $Value);
                $this->SendCommand($command);

                break;
            default:
                $this->SendDebug('Profile:', 'Invalid ident', 0);
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
                $this->SendDebug('Profile:', 'Variable profile type does not match for profile ' . $Name, 0);
            }
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

    protected function CreateMAC()
    {
        $mac = implode(':', str_split(substr(md5(strval(mt_rand())), 0, 12), 2));
        return $mac;
    }

    protected function SetIdentifier()
    {
        $mac = $this->ReadPropertyString('mac');
        $this->SendDebug('Bordlink Device:', 'mac: ' . $mac, 0);
        if($mac == '')
        {
            $name = IPS_GetName($this->InstanceID);
            IPS_SetProperty($this->InstanceID, 'name', $name);
            $mac = $this->CreateMAC();
            IPS_SetProperty($this->InstanceID, 'mac', $mac);
            $this->SendDebug('Bordlink Device:', 'mac created: ' . $mac, 0);
            $ident = str_replace(':', '_', $mac);
            IPS_SetProperty($this->InstanceID, 'ident', $ident);
            $this->SendDebug('Bordlink Device:', 'ident created: ' . $ident, 0);
            IPS_ApplyChanges($this->InstanceID);
        }
    }

    protected function CheckOldInstance()
    {
        $id = @$this->GetIDForIdent('Commands');
        $ips_version = $this->GetIPSVersion();
        $mac = $this->ReadPropertyString('mac');
        if($id > 0 && $ips_version >= 6)
        {
            $this->SendDebug('Bordlink Device:', 'IP-Symcon > 5.1 detected', 0);
            $this->SendDebug('Bordlink Device:', 'converting commands from variable to attribute', 0);
            $commands = GetValue($id);
            $this->SaveCommands($commands);
            $this->UnregisterVariable('Commands');
            if($mac == '')
            {
                $this->SetIdentifier();
            }
            $old_instance = false;
        }
        elseif($ips_version >= 6)
        {
            $this->SendDebug('Bordlink Device:', 'IP-Symcon > 5.1 detected', 0);
            if($mac == '')
            {
                $this->SetIdentifier();
            }
            $old_instance = false;
        }
        else
        {
            $old_instance = true;
        }
        return $old_instance;
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
        $this->CheckOldInstance();
        $commands = $this->GetAvailableCommands();
        $model = $this->ReadPropertyString('model');
        $number = count($commands);
        $form = [];
        if ($model == '') {
            $form = array_merge_recursive(
                $form,
                [
                    [
                        'type'    => 'Label',
                        'caption' => 'This device should be created by the broadlink configurator, please open the Broadlink configurator and create the device there.'
                    ]
                ]
            );
        }
        else
        {
            $form = array_merge_recursive(
                $form,
                [
                    [
                        'type'    => 'Label',
                        'caption' => 'Broadlink ' . $model . ' device'
                    ],
                    [
                        'type'     => 'List',
                        'name'     => 'BroadlinkCommands',
                        'caption'  => 'available commands',
                        'rowCount' => $number,
                        'add'      => true,
                        'delete'   => true,
                        'sort'     => [
                            'column'    => 'key',
                            'direction' => 'ascending'
                        ],
                        'columns' => [
                            [
                                'name'    => 'key',
                                'caption' => 'key label',
                                'width'   => '250px',
                                'visible' => true,
                                'add'     => $model . ' key label',
                                'edit'    => [
                                    'type' => 'ValidationTextBox'
                                ],
                                'save' => true
                            ],
                            [
                                'name'    => 'code',
                                'caption' => $model . ' code',
                                'width'   => 'auto',
                                'visible' => true,
                                'add'     => '0',
                                'edit'    => [
                                    'type' => 'ValidationTextBox'
                                ],
                                'save' => true
                            ]
                        ],
                        'values' => $this->CommandListValues($commands)
                    ]
                ]
            );
        }
        return $form;
    }

    private function CommandListValues($commands)
    {
        $form = [];
        $number = count($commands);
        if ($number == 0) {
            $this->SendDebug('Bordlink Form:', 'empty no commands available', 0);
        } else {
            foreach ($commands as $key => $command) {
                $form = array_merge_recursive(
                    $form,
                    [
                        [
                            'key'  => $key,
                            'code' => $command
                        ]
                    ]
                );
            }
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
        $model = $this->ReadPropertyString('model');
        $form = [];
        if ($model == '') {
            $this->SendDebug('Form Actions:', 'empty', 0);
        }
        else
        {
            $form = array_merge_recursive(
                $form,
                [
                    [
                        'type'    => 'ExpansionPanel',
                        'caption' => 'Import code',
                        'items'   => [
                            [
                                'type'    => 'Label',
                                'caption' => 'Insert import code:'
                            ],
                            [
                                'type'    => 'Label',
                                'caption' => 'Format Code JSON: {"Power On":"xxxxx","Power Off":"xxxxx"}'
                            ],
                            [
                                'name'    => 'importtextfield',
                                'type'    => 'ValidationTextBox',
                                'caption' => 'Import Code'
                            ],
                            [
                                'type'    => 'Button',
                                'caption' => 'Import',
                                'onClick' => 'BroadlinkDevice_ImportCodesText($id, $importtextfield);'
                            ],
                            [
                                'type'    => 'Label',
                                'caption' => 'or select a variable with commands:'
                            ],
                            [
                                'name'    => 'importvariable',
                                'type'    => 'SelectVariable',
                                'caption' => 'Import Variable'
                            ],
                            [
                                'type'    => 'Button',
                                'caption' => 'Import',
                                'onClick' => 'BroadlinkDevice_ImportCodesVariable($id, $importvariable);'
                            ]
                        ]
                    ],
                    [
                        'type'    => 'ExpansionPanel',
                        'caption' => 'Learn key',
                        'items'   => [
                            [
                                'type'    => 'Select',
                                'name'    => 'learnkey',
                                'caption' => 'key',
                                'options' => $this->GetSendListCommands()
                            ],
                            [
                                'type'    => 'Button',
                                'caption' => 'Learn',
                                'onClick' => 'BroadlinkDevice_LearnCommandKey($id, $learnkey);'
                            ]
                        ]
                    ],
                    [
                        'type'    => 'ExpansionPanel',
                        'caption' => 'Send key',
                        'items'   => [
                            [
                                'type'    => 'Select',
                                'name'    => 'sendkey',
                                'caption' => 'key',
                                'options' => $this->GetSendListCommands()
                            ],
                            [
                                'type'    => 'Button',
                                'caption' => 'Send',
                                'onClick' => 'BroadlinkDevice_SendCommandKey($id, $sendkey);'
                            ]
                        ]
                    ]
                ]
            );
        }
        return $form;
    }

    protected function GetSendListCommands()
    {
        $form = [
            [
                'caption' => 'Please Select',
                'value'   => -1
            ]
        ];
        $commands = $this->GetAvailableCommands();
        $i = 1;
        foreach ($commands as $key => $command) {
            $form = array_merge_recursive(
                $form,
                [
                    [
                        'caption' => $key,
                        'value'   => $i
                    ]
                ]
            );
            $i++;
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
                'caption' => 'Broadlink device created'
            ],
            [
                'code'    => 104,
                'icon'    => 'inactive',
                'caption' => 'Broadlink Device is inactive'
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
            ]
        ];

        return $form;
    }

    protected function CreateVariableByIdent($id, $ident, $name, $type, $profile = '')
    {
        $vid = @IPS_GetObjectIDByIdent($ident, $id);
        if ($vid === false) {
            $vid = IPS_CreateVariable($type);
            IPS_SetParent($vid, $id);
            IPS_SetName($vid, $name);
            IPS_SetIdent($vid, $ident);
            if ($profile != '')
                IPS_SetVariableCustomProfile($vid, $profile);
        }
        return $vid;
    }

    protected function CreateWFVariables($commands)
    {
        $deviceident = $this->ReadPropertyString('ident');
        if (empty($commands)) {
            $this->SendDebug('Command:', 'empty', 0);
        } else {
            // count commands
            $values = $commands;
            $valuescount = count($commands);
            // 32 Limit
            if ($valuescount > 32 && $valuescount <= 64) {
                $profilename = 'Broadlink.' . $deviceident . '.Command';
                $profilecounter = 0;
                $this->CreateWebFrontVariable('WFCommands', 'Command', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command1';
                $profilecounter = 31;
                $this->CreateWebFrontVariable('WFCommands1', 'Command 1', $values, $profilename, $profilecounter);
            } elseif ($valuescount > 64 && $valuescount <= 96) {
                $profilename = 'Broadlink.' . $deviceident . '.Command';
                $profilecounter = 0;
                $this->CreateWebFrontVariable('WFCommands', 'Command', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command1';
                $profilecounter = 31;
                $this->CreateWebFrontVariable('WFCommands1', 'Command 1', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command2';
                $profilecounter = 63;
                $this->CreateWebFrontVariable('WFCommands2', 'Command 2', $values, $profilename, $profilecounter);
            } elseif ($valuescount > 96 && $valuescount <= 128) {
                $profilename = 'Broadlink.' . $deviceident . '.Command';
                $profilecounter = 0;
                $this->CreateWebFrontVariable('WFCommands', 'Command', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command1';
                $profilecounter = 31;
                $this->CreateWebFrontVariable('WFCommands1', 'Command 1', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command2';
                $profilecounter = 63;
                $this->CreateWebFrontVariable('WFCommands2', 'Command 2', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command3';
                $profilecounter = 95;
                $this->CreateWebFrontVariable('WFCommands3', 'Command 3', $values, $profilename, $profilecounter);
            } elseif ($valuescount > 128 && $valuescount <= 160) {
                $profilename = 'Broadlink.' . $deviceident . '.Command';
                $profilecounter = 0;
                $this->CreateWebFrontVariable('WFCommands', 'Command', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command1';
                $profilecounter = 31;
                $this->CreateWebFrontVariable('WFCommands1', 'Command 1', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command2';
                $profilecounter = 63;
                $this->CreateWebFrontVariable('WFCommands2', 'Command 2', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command3';
                $profilecounter = 95;
                $this->CreateWebFrontVariable('WFCommands3', 'Command 3', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command4';
                $profilecounter = 127;
                $this->CreateWebFrontVariable('WFCommands4', 'Command 4', $values, $profilename, $profilecounter);
            } elseif ($valuescount > 160 && $valuescount <= 192) {
                $profilename = 'Broadlink.' . $deviceident . '.Command';
                $profilecounter = 0;
                $this->CreateWebFrontVariable('WFCommands', 'Command', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command1';
                $profilecounter = 31;
                $this->CreateWebFrontVariable('WFCommands1', 'Command 1', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command2';
                $profilecounter = 63;
                $this->CreateWebFrontVariable('WFCommands2', 'Command 2', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command3';
                $profilecounter = 95;
                $this->CreateWebFrontVariable('WFCommands3', 'Command 3', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command4';
                $profilecounter = 127;
                $this->CreateWebFrontVariable('WFCommands4', 'Command 4', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command5';
                $profilecounter = 159;
                $this->CreateWebFrontVariable('WFCommands5', 'Command 5', $values, $profilename, $profilecounter);
            } elseif ($valuescount > 192 && $valuescount <= 224) {
                $profilename = 'Broadlink.' . $deviceident . '.Command';
                $profilecounter = 0;
                $this->CreateWebFrontVariable('WFCommands', 'Command', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command1';
                $profilecounter = 31;
                $this->CreateWebFrontVariable('WFCommands1', 'Command 1', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command2';
                $profilecounter = 63;
                $this->CreateWebFrontVariable('WFCommands2', 'Command 2', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command3';
                $profilecounter = 95;
                $this->CreateWebFrontVariable('WFCommands3', 'Command 3', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command4';
                $profilecounter = 127;
                $this->CreateWebFrontVariable('WFCommands4', 'Command 4', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command5';
                $profilecounter = 159;
                $this->CreateWebFrontVariable('WFCommands5', 'Command 5', $values, $profilename, $profilecounter);

                $profilename = 'Broadlink.' . $deviceident . '.Command6';
                $profilecounter = 191;
                $this->CreateWebFrontVariable('WFCommands6', 'Command 6', $values, $profilename, $profilecounter);
            } else {
                $profilename = 'Broadlink.' . $deviceident . '.Command';
                $profilecounter = 0;
                $this->CreateWebFrontVariable('WFCommands', 'Command', $values, $profilename, $profilecounter);
            }

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
