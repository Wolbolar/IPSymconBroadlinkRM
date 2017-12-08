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
            $this->SetStatus(102);
		}	
		
		public function SendCommand(string $command)
		{
            $deviceident = IPS_GetObject($this->InstanceID)["ObjectIdent"];
            $commandhex = $this->GetCommand($command);
			$payload = array("name" => $deviceident, "command" => $commandhex);
			$this->SendDebug("Send Data:",json_encode($payload),0);
									
			//an Splitter schicken
            $result = $this->SendDataToParent(json_encode(Array("DataID" => "{EFC61574-A0BC-2FBB-065A-8C6B42FC2646}", "Buffer" => $payload))); // Interface GUI
            $this->SendDebug("Send Data Result:",$result,0);
			return $result;
		}

		public function EnableWFVariable()
		{
            $this->EnableAction("WFCommands");
		}

        protected function GetCommand($command)
        {
            $valuesjson = GetValue($this->GetIDForIdent("Commands"));
            $values = json_decode($valuesjson, true);
            $keyexists = array_key_exists($command, $values);
            if($keyexists)
            {
                $commandhex = $values[$command];
            }
            else
            {
                $commandhex = false;
            }
            return $commandhex;
        }

        protected function GetCommandName($Ident, $Value)
		{
            $deviceident = IPS_GetObject($this->InstanceID)["ObjectIdent"];
            if($Ident == "WFCommands")
			{
                $profilename = "Broadlink.".$deviceident.".Command";
			}
			else
			{
                $profilename = "Broadlink.".$deviceident.".Command1";
			}
            $associations = IPS_GetVariableProfile($profilename)["Associations"];
            var_dump($associations);
            $command_name = false;
            foreach($associations as $key => $association)
            {
                if($association["Value"] == $Value)
                {
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
			$objectid = $data->Buffer->objectid;
			$values = $data->Buffer->values;
			$valuesjson = json_encode($values);
			if (($this->InstanceID) == $objectid)
			{
				//Parse and write values to our variables
				//$this->WriteValues($valuesjson);
			}
		}
		
		public function RequestAction($Ident, $Value)
		{	
			switch($Ident) {
				case "WFCommands":
                    $varid = $this->GetIDForIdent("WFCommands");
					SetValue($varid, $Value);
                    $command = $this->GetCommandName($Ident, $Value);
					$this->SendCommand($command);
					
					break;
				case "WFCommands1":
					SetValue($this->GetIDForIdent("WFCommands1"), $Value);
                    $command = $this->GetCommandName($Ident, $Value);
					$this->SendCommand($command);
					
					break;
				default:
					throw new Exception("Invalid ident");
			}
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
            $commands = $this->GetAvailableCommands();

        	$form = '"elements":
            [
            { "type": "Label", "label": "available commands" },
            ';
        	foreach ($commands as $key => $command)
			{
				$form .= '{ "type": "Label", "label": "'.$key.'" },';
			}

            return $form;
        }

        protected function FormActions()
        {
        	$form = '"actions":
			[
				
			],';

            return  $form;
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

	
	}

?>
