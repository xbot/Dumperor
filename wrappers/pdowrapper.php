<?php
/**
 * @author Li Dong <lenin.lee@gmail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php
 **/
require_once 'dbtoolkitwrapper.php';

/**
 * PDO Wrapper
 **/
class PDOWrapper extends DBToolkitWrapper
{
    /**
     * Connect to the database
     **/
    function Connect()
    {
        $this->Sanitize();
        if (!$this->ValidateDBInfo()) {
            throw new Exception('Failed validating db information: '.$this->strMsg);
        }

        $strDSN = $this->GetDSNStr();
        //die($strDSN);

        try {
            $this->conn = new PDO($strDSN, $this->strLogin, $this->strPass);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
        } catch ( Exception $e ) {
            throw $e;
        }
    }

    /**
     * Query method
     *
     * @param string
     * @return mixed
     **/
    function Query($strSQL)
    {
        $rs = $this->conn->query($strSQL);
        if (false === $rs) {
            throw new Exception($this->conn->errorCode().': '.$this->conn->errorInfo());
        }

        return $rs;
    }

    /**
     * Return one row of the results a time
     *
     * @param object Result set
     * @return array Key=>Value pairs, or false if no result exists
     **/
    function Read($objRs, $ff=false)
    {
        if ($ff) {
            return true;
        }
        if (is_object($objRs)) {
            return $objRs->fetch(PDO::FETCH_ASSOC);
        }

        return false;
    }

    /**
     * Sanitize some properties
     **/
    private function Sanitize()
    {
        $this->strHost = trim($this->strHost.'');
        $this->strLogin = trim($this->strLogin.'');
        $this->strPass = trim($this->strPass.'');
        $this->strDB = trim($this->strDB.'');
        $this->strType = strtolower(trim($this->strType.''));
    }

    /**
     * Validate necessary information to connect to database
     *
     * @return boolean
     **/
    private function ValidateDBInfo()
    {
        if (strlen($this->strLogin) == 0) {
            $this->strMsg = 'Login name missing !';
            return false;
        }
        if (strlen($this->strPass) == 0) {
            $this->strMsg = 'Password missing !';
            return false;
        }
        if (strlen($this->strDB) == 0) {
            $this->strMsg = 'DB name missing !';
            return false;
        }
        if (strlen($this->strHost) == 0) {
            $this->strMsg = 'Host name missing !';
            return false;
        }
        if (strlen($this->strType) == 0) {
            $this->strMsg = 'Type missing !';
            return false;
        }
        return true;
    }

    /**
     * Generate a DSN string
     *
     * @return string
     **/
    private function GetDSNStr()
    {
        $strDSN = '';

        switch ($this->strType) {
            case 'dblib':
            case 'mssql':
                $strDSN = $this->strType.':host='.$this->strHost.':1433;dbname='.$this->strDB;
                break;
            case 'sqlsrv':
                $strDSN = 'sqlsrv:server='.$this->strHost.';Database='.$this->strDB;
                break;
            case 'mysql':
                $strDSN = 'mysql:host='.$this->strHost.';dbname='.$this->strDB;
                break;
            case 'oracle':
            case 'oci':
                $strDSN = 'oci:dbname=//'.$this->strHost.':1521/'.$this->strDB;
                break;
            default:
                // code...
                break;
        }

        return $strDSN;
    }
}
?>
