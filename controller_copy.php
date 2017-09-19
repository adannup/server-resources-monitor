<?php

class Monitor{

    private $cmd = "wmic cpu get loadpercentage /all";
    public $cpu_load;
    public $result = array();
    public $grafica = array();

    public function __construct(){
        date_default_timezone_set('America/Mexico_City');
    }

    public function ejecute(){
        //Only for windows version
        if (stristr(PHP_OS, "win"))
        {
            $this->getServerLoad();
            $this->getSystemMemoryInfo();
            $this->store();
            $this->graficas();
        }
    }

    public function getServerLoad() {
        
        @exec($this->cmd, $output);

        if ($output)
        {
            //Cpu load store in the key 1 of the array $output
            return $this->cpu_load  = $output[1];
        }
    }

    //----------------------------
    //Funcion para obtener el uso de memoria RAM y virtual
    public function wmiWBemLocatorQuery( $query ) {
        if ( class_exists( '\\COM' ) ) {
            try {
                $WbemLocator = new \COM( "WbemScripting.SWbemLocator" );
                $WbemServices = $WbemLocator->ConnectServer( '127.0.0.1', 'root\CIMV2' );
                $WbemServices->Security_->ImpersonationLevel = 3;
                // use wbemtest tool to query all classes for namespace root\cimv2
                return $WbemServices->ExecQuery( $query );
            } catch ( \com_exception $e ) {
                echo $e->getMessage();
            }
        } elseif ( ! extension_loaded( 'com_dotnet' ) )
            trigger_error( 'It seems that the COM is not enabled in your php.ini', E_USER_WARNING );
        else {
            $err = error_get_last();
            trigger_error( $err['message'], E_USER_WARNING );
        }

        return false;
    }

    // _dir_in_allowed_path this is your function to detect if a file is withing the allowed path (see the open_basedir PHP directive)
    public function getSystemMemoryInfo( $output_key = '' ) {
        $keys = array( 'MemTotal', 'MemFree', 'MemAvailable', 'SwapTotal', 'SwapFree' );

        try {
            
            $wmi_found = false;
            if ( $wmi_query = $this->wmiWBemLocatorQuery( 
                "SELECT FreePhysicalMemory,FreeVirtualMemory,TotalVirtualMemorySize,TotalVisibleMemorySize FROM Win32_OperatingSystem" ) ) {
                foreach ( $wmi_query as $r ) {
                    $this->result['CPU usage: ']['load']                         = $this->cpu_load.'%';
                    $this->result['Memoria fisica disponible: ']['load']         = round($r->FreePhysicalMemory / 1024).' MB';
                    $this->result['Memoria fisica en uso: ']['load']             = round($r->TotalVisibleMemorySize / 1024) - round($r->FreePhysicalMemory / 1024).' MB';
                    $this->result['Memoria virtual disponible: ']['load']        = round($r->FreeVirtualMemory / 1024).' MB';
                    $this->result['Memoria virtual en uso: ']['load']            = round($r->TotalVirtualMemorySize / 1024) - round($r->FreeVirtualMemory / 1024).' MB';
                    $this->result['Memoria virtual tamaño maximo: ']['load']     = round($r->TotalVirtualMemorySize / 1024).' MB';
                    $this->result['Cantidad total de memoria fisica: ']['load']  = round($r->TotalVisibleMemorySize / 1024).' MB';
                }
            
            // TODO a backup implementation using the $_SERVER array
            }
        } catch ( Exception $e ) {
            echo $e->getMessage();
        }
        return empty( $output_key ) || ! isset( $this->result[$output_key] ) ? $this->result : $this->result[$output_key];
    }


    //Funcion para guardar los datos en un archivo txt
    public function save($store){ 
        $ddf = fopen('info.log','a'); 
        fwrite($ddf,"[".date("r")."] $store\r\n"); 
        fclose($ddf); 
    } 

    public function store(){
        foreach ($this->result as $key => $value) {
            $this->save($key.$value['load']);
        }
    }

    public function graficas(){
        
        $this->grafica['Cantidad total de memoria fisica: ']['percent']  =   300;
        $this->grafica['Memoria fisica en uso: ']['percent']             =   (filter_var($this->result['Memoria fisica en uso: ']['load'], FILTER_SANITIZE_NUMBER_INT) * 300 )/ filter_var($this->result['Cantidad total de memoria fisica: ']['load'], FILTER_SANITIZE_NUMBER_INT);
        
        $this->grafica['Memoria virtual tamaño maximo: ']['percent']     =   300;
        $this->grafica['Memoria virtual en uso: ']['percent']   =   (filter_var($this->result['Memoria virtual en uso: ']['load'], FILTER_SANITIZE_NUMBER_INT) * 300 )/ filter_var($this->result['Memoria virtual tamaño maximo: ']['load'], FILTER_SANITIZE_NUMBER_INT);
   
    }
}

$monitor = new Monitor();
$monitor->ejecute();