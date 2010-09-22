<?php
/**
 * @author Li Dong <lenin.lee@gmail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php
 **/

/**
 * Generic Database Toolkit Wrapper Class
 * Provide a convenient way to make various database toolkits work together with Dumperor
 **/
abstract class DBToolkitWrapper
{
    var $conn;                  // object, database connection
    var $strName;               // Wrapper name
    var $strHost;               // Database host name or IP
    var $strLogin;              // Database login name
    var $strPass;               // Database password
    var $strDB;                 // Database name
    var $strType;               // Database type, e.g. mssql, mysql, oracle
    var $strMsg;                // Error message

    function __construct($strLogin, $strPass, $strHost, $strDB, $strType)
    {
        $this->strName = __CLASS__;
        $this->strHost = $strHost;
        $this->strLogin = $strLogin;
        $this->strPass = $strPass;
        $this->strDB = $strDB;
        $this->strType = $strType;
        $this->strMsg = '';
        $this->conn = null;
    }

    /**
     * Connect to the database
     **/
    abstract function Connect();

    /**
     * Disconnect to the database
     **/
    function Close()
    {
        $this->conn = null;
    }

    /**
     * Close result set and free db resources
     *
     * @param object Result set
     **/
    function Free(&$objRs)
    {
        $objRs = null;
    }

    /**
     * Query method
     *
     * @param string
     * @return mixed If the query success, return an object to be read by the read() method, or false if fails
     **/
    abstract function Query($strSQL);

    /**
     * Return one row of the results a time
     *
     * @param object Result set
     * @return array Key=>Value pairs, or false if no result exists
     **/
    abstract function Read($objRs);

    /**
     * Get strHost.
     *
     * @return strHost.
     */
    function GetHost()
    {
        return $this->strHost;
    }
    
    /**
     * Set strHost.
     *
     * @param strHost the value to set.
     */
    function SetHost($strHost)
    {
        $this->strHost = $strHost;
    }
    
    /**
     * Get strLogin.
     *
     * @return strLogin.
     */
    function GetLogin()
    {
        return $this->strLogin;
    }
    
    /**
     * Set strLogin.
     *
     * @param strLogin the value to set.
     */
    function SetLogin($strLogin)
    {
        $this->strLogin = $strLogin;
    }
    
    /**
     * Get strPass.
     *
     * @return strPass.
     */
    function GetPass()
    {
        return $this->strPass;
    }
    
    /**
     * Set strPass.
     *
     * @param strPass the value to set.
     */
    function SetPass($strPass)
    {
        $this->strPass = $strPass;
    }
    
    /**
     * Get strDB.
     *
     * @return strDB.
     */
    function GetDB()
    {
        return $this->strDB;
    }
    
    /**
     * Set strDB.
     *
     * @param strDB the value to set.
     */
    function SetDB($strDB)
    {
        $this->strDB = $strDB;
    }
    
    /**
     * Get strType.
     *
     * @return strType.
     */
    function GetType()
    {
        return $this->strType;
    }
    
    /**
     * Set strType.
     *
     * @param strType the value to set.
     */
    function SetType($strType)
    {
        $this->strType = $strType;
    }
    
    /**
     * Get strName.
     *
     * @return strName.
     */
    function GetName()
    {
        return $this->strName;
    }
    
    /**
     * Set strName.
     *
     * @param strName the value to set.
     */
    function SetName($strName)
    {
        $this->strName = $strName;
    }

    /**
     * Get strMsg
     **/
    function GetMsg()
    {
        return $this->strMsg;
    }

    /**
     * Set strMsg
     **/
    function SetMsg($strMsg)
    {
        $this->strMsg = $strMsg;
    }
}
?>
