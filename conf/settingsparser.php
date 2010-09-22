<?php
/**
 * @author Li Dong <lenin.lee@gmail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php
 **/
require_once 'dumpers/mssqldumper.php';
require_once 'dumpers/mysqldumper.php';
require_once 'dumpers/oracledumper.php';

/**
 * Settings Parser
 **/
class SettingsParser
{
    var $arrRawCfg;                 // array, an array holding all settings

    function __construct()
    {
        $this->arrRawCfg = array();
    }

    /**
     * Parse the given config file
     *
     * @param string File path
     * @return boolean Success or not, if not, the error message can be fetched using the method GetMsg()
     **/
    function ParseFile($strFilePath)
    {
        try {
            $this->arrRawCfg = parse_ini_file($strFilePath, true);
            $this->Sanitize();
            $this->ParseRawCfg();
            $this->Validate();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Sanitize settings array, and convert every value into trimmed string
     **/
    function Sanitize()
    {
        if (is_array($this->arrRawCfg)) {
            self::SanitizeArray($this->arrRawCfg);
        }
    }

    /**
     * Sanitize an array, convert every value into trimmed lower-case strings
     *
     * @param array Pass by reference
     **/
    public static function SanitizeArray(&$arr)
    {
        if (is_array($arr)) {
            foreach ($arr as $key=>$val) {
                if (is_array($val)) {
                    self::SanitizeArray($arr[$key]);
                } else {
                    $arr[$key] = strtolower(trim($val.''));
                }
            }
            $arr = array_change_key_case($arr);
        }
    }

    /**
     * Parse raw settings array
     **/
    private function ParseRawCfg()
    {
        $this->ParseExclusiveSettings();
        $this->ParseInclusiveSettings();
        $this->ParseFakeSettings();
        $this->ParseCondSettings();
    }

    /**
     * Parse section EXCLUSIVE
     **/
    private function ParseExclusiveSettings()
    {
        $rawTbl = $this->get('exclusive', 'tables');
        $rawCol = $this->get('exclusive', 'columns');

        if (is_string($rawTbl)) {
            $this->set('exclusive', 'tables', explode(',', $rawTbl));
        } else {
            $this->set('exclusive', 'tables', array());
        }
        if (is_string($rawCol)) {
            $this->set('exclusive', 'columns', explode(',', $rawCol));
        } else {
            $this->set('exclusive', 'columns', array());
        }
    }

    /**
     * Parse section INCLUSIVE
     **/
    private function ParseInclusiveSettings()
    {
        $rawTbl = $this->get('inclusive', 'tables');

        if (is_string($rawTbl)) {
            $this->set('inclusive', 'tables', explode(',', $rawTbl));
        } else {
            $this->set('inclusive', 'tables', array());
        }
    }

    /**
     * Parse section FAKE of settings
     **/
    private function ParseFakeSettings()
    {
        $rawFake = $this->get('fake');
        if (is_array($rawFake) && count($rawFake)>0) {
            $xrr = array();
            foreach ($rawFake as $key=>$val) {
                if (is_string($key) && is_scalar($val) && strstr($key.'', '.')) {
                    $arr = explode('.', $key);
                    $xrr[trim($arr[0])][trim($arr[1])] = $val;
                }
            }
            $this->set('fake', $xrr);
        }
    }

    /**
     * Parse section COND of settings
     **/
    private function ParseCondSettings()
    {
        $rawCond = $this->get('cond');
        if (is_array($rawCond) && count($rawCond)>0) {
            $xrr = array();
            foreach ($rawCond as $key=>$val) {
                if (is_string($key) && is_scalar($val) && !strstr($key.'', '.')) {
                    $xrr[$key] = $val;
                }
            }
            $this->set('cond', $xrr);
        }
    }

    /**
     * Check configurations
     **/
    function Validate()
    {
        if (!is_array($this->arrRawCfg) || count($this->arrRawCfg)<=0) {
            throw new Exception('Nothing found from '.$strFilePath);
        }

        // DB section should not be missed
        $arrDB = $this->get('db');
        if (!is_array($arrDB) || count($arrDB) == 0) {
            throw new Exception('DB section is empty');
        }
        if (!isset($arrDB['name']) || strlen($arrDB['name']) == 0) {
            throw new Exception('DB name not found');
        }
        if (!isset($arrDB['type']) || strlen($arrDB['type']) == 0) {
            throw new Exception('DB type not found');
        }
        if (!isset($arrDB['host']) || strlen($arrDB['host']) == 0) {
            throw new Exception('DB host not found');
        }
        if (!isset($arrDB['login']) || strlen($arrDB['login']) == 0) {
            throw new Exception('DB login name not found');
        }
        if (!isset($arrDB['password']) || strlen($arrDB['password']) == 0) {
            throw new Exception('DB password not found');
        }
    }

    /**
     * Fetch value from option
     *
     * @param string Settings section name
     * @param string Settings option name, if none given, the whole section will be returned in an array
     * @param mixed Option value, if none found, return null
     **/
    function Get($strSec, $strOpt=false)
    {
        $strSec = strtolower($strSec.'');
        $strOpt = false === $strOpt ? false : strtolower($strOpt.'');

        if (isset($this->arrRawCfg[$strSec])) {
            if (false !== $strOpt) {
                if (false !== $strOpt && isset($this->arrRawCfg[$strSec][$strOpt]))
                    return $this->arrRawCfg[$strSec][$strOpt];
                else
                    return false;
            }
            return $this->arrRawCfg[$strSec];
        }

        return null;
    }

    /**
     * Set value to option
     *
     * @param string Settings section name
     * @param string Settings option name, if none given, the value will be set to the section
     * @param mixed Value of the option
     **/
    function Set()
    {
        $args = func_get_args();
        if (count($args) == 2 || count($args) == 3) {
            $strSec = strtolower(trim($args[0].''));
            $strOpt = count($args) == 2 ? false : strtolower(trim($args[1].''));
            $mixVal = count($args) == 3 ? $args[2] : $args[1];

            if (false === $strOpt) {
                $this->arrRawCfg[$strSec] = $mixVal;
            } else {
                $this->arrRawCfg[$strSec][$strOpt] = $mixVal;
            }

            $this->Sanitize();
        }
    }
}
?>
