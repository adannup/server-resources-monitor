<?php

class Monitor{

    private $cmd = "wmic cpu get loadpercentage /all";
    public $result = array();

    public function __construct(){
        date_default_timezone_set('America/Mexico_City');
    }

    public function getServerLoad() {
        @exec($this->cmd, $output);

        if ($output)
        {
            //Cpu load is stored in the key 1 of the array $output
            return $output[1];
        }
    }

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


    public function getSystemMemoryInfo( $output_key = '' ) {
        $keys = array( 'MemTotal', 'MemFree', 'MemAvailable', 'SwapTotal', 'SwapFree' );

        try {
            
            $wmi_found = false;
            if ( $wmi_query = $this->wmiWBemLocatorQuery( 
                "SELECT FreePhysicalMemory,FreeVirtualMemory,TotalVirtualMemorySize,TotalVisibleMemorySize FROM Win32_OperatingSystem" ) ) {
                foreach ( $wmi_query as $r ) {
                    $this->result['CPU usage:']                         = $this->getServerLoad().'%';
                    $this->result['Memoria fisica disponible:']         = round($r->FreePhysicalMemory / 1024);
                    $this->result['Memoria fisica en uso:']             = round($r->TotalVisibleMemorySize / 1024) - round($r->FreePhysicalMemory / 1024);
                    $this->result['Cantidad total de memoria fisica:']  = round($r->TotalVisibleMemorySize / 1024);
                    $this->result['Memoria virtual disponible:']        = round($r->FreeVirtualMemory / 1024);
                    $this->result['Memoria virtual en uso:']            = round($r->TotalVirtualMemorySize / 1024) - round($r->FreeVirtualMemory / 1024);
                    $this->result['Memoria virtual tamaño maximo:']     = round($r->TotalVirtualMemorySize / 1024);
                }
            
            // TODO a backup implementation using the $_SERVER array
            }
        } catch ( Exception $e ) {
            echo $e->getMessage();
        }
        return empty( $output_key ) || ! isset( $this->result[$output_key] ) ? $this->result : $this->result[$output_key];
    }
}


class View extends Monitor{

    public $results;
    public $graphics = array();

    public function __construct(){
        self::getSystemMemoryInfo();
    }

    public function ejecute(){
        $this->store();
        $this->to_view();
        $this->graphics();
        $this->set_level();
    }

    //Funcion para guardar los datos en un archivo txt
    private function log($store){ 
        $ddf = fopen('info.log','a'); 
        fwrite($ddf,"[".date("r")."] $store\r\n"); 
        fclose($ddf); 
    } 

    public function store(){
        foreach ($this->result as $key => $value) {
            if($key !='CPU usage:'){
                $this->log($key.' '.$value.'MB');
            }else{
                $this->log($key.' '.$value);
            }
        }
    }

    public function to_view(){
        foreach ($this->result as $key => $value) {
            if($key !='CPU usage:'){
                $this->results[$key] = $value.' MB';
            }else{
                $this->results[$key] = $value;
            }
        }
    }

    public function graphics(){

        $this->graphics['Cantidad total de memoria fisica:']['value']     =   300;
        $this->graphics['Memoria fisica en uso:']['value']                =   ($this->result['Memoria fisica en uso:'] * 300 )/ $this->result['Cantidad total de memoria fisica:'];
        
        $this->graphics['Memoria virtual tamaño maximo:']['value']        =   300;
        $this->graphics['Memoria virtual en uso:']['value']              =   ($this->result['Memoria virtual en uso:'] * 300 )/ $this->result['Memoria virtual tamaño maximo:'];
    }

    public function set_level(){
        if($this->graphics['Memoria fisica en uso:']['value'] <= 100){
            $this->graphics['Memoria fisica en uso:']['level']  =   'LOW';
        }elseif($this->graphics['Memoria fisica en uso:']['value'] > 100 || $this->graphics['Memoria fisica en uso:']['value'] <= 200){
            $this->graphics['Memoria fisica en uso:']['level']  =   'MEDIUM';
        }elseif($this->graphics['Memoria fisica en uso:']['value'] > 200){
            $this->graphics['Memoria fisica en uso:']['level']  =   'HIGH';
        }

        if($this->graphics['Memoria virtual en uso:']['value'] <= 100){
            $this->graphics['Memoria virtual en uso:']['level']  =   'LOW';
        }elseif($this->graphics['Memoria virtual en uso:']['value'] > 100 || $this->graphics['Memoria virtual en uso:']['value'] <= 200){
            $this->graphics['Memoria virtual en uso:']['level']  =   'MEDIUM';
        }elseif($this->graphics['Memoria virtual en uso:']['value'] > 200){
            $this->graphics['Memoria virtual en uso:']['level']  =   'HIGH';
        }
    }

}
$monitor = new View();
$monitor->ejecute();